<?php

namespace App\Http\Controllers;

use App\Enums\ChannelEnum;
use App\Http\Requests\ActivationCodeRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RefreshTokenRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\RefreshToken;
use App\Models\User;
use App\Notifications\ActivationCodeNotification;
use App\Notifications\ResetPasswordNotification;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends BaseController
{
    use ResponseAPI;

    public function register(RegisterRequest $request)
    {
        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => Hash::make($request->input('password')),
            ]);

            $user->assignRole($request->input('role'));
            $this->sendActivationCode($user);

            // Generate JWT token
            $token = auth('api')->login($user);
            $refreshToken = RefreshToken::createToken($user->id, request()->ip(), request()->userAgent());

            $data = [
                'user' => [
                    'id' => $user->id,
                    'username' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles,
                    'avatar' => $user->avatar,
                    'channels' => [
                        [
                            'name' => ChannelEnum::NOTIFICATION->channel($user->id),
                            'event' => ChannelEnum::NOTIFICATION->event(),
                        ],
                    ],
                ],
                'access_token' => $token,
                'refresh_token' => $refreshToken,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
                'refresh_expires_in' => config('jwt.refresh_ttl') * 60,
            ];

            DB::commit();

            return $this->sendSuccess('User registered successfully', $data, 201);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error('Registration error: ', [
                'input' => $request->all(),
                'message' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString(),
            ]);

            return $this->sendError('Registration failed', 500);
        }
    }

    public function login(LoginRequest $request)
    {
        try {
            $credentials = $request->only('email', 'password');
            $credentials['is_active'] = true;

            if (! $token = auth('api')->attempt($credentials)) {
                return $this->sendError('Wrong credentials or account inactive', 401);
            }

            $user = auth('api')->user();
            $user->load([
                'roles:id,name',
                'roles.permissions:id,name',
                'communities',
                'chatRooms',
                'attachment',
            ]);

            $refreshToken = RefreshToken::createToken($user->id, request()->ip(), request()->userAgent());

            $data = $this->getCompleteData($user, $token, $refreshToken);

            return $this->sendSuccess('Login successful', $data, 200);
        } catch (\Exception $ex) {
            Log::error('Login error: ', [
                'input' => $request->all(),
                'message' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString(),
            ]);

            return $this->sendError('Login failed', 500);
        }
    }

    public function refresh(RefreshTokenRequest $request)
    {
        try {
            DB::beginTransaction();

            $refreshToken = $request->input('refresh_token');

            $tokenModel = RefreshToken::findByToken($refreshToken);

            if (! $tokenModel) {
                DB::commit();

                return $this->sendError('Invalid or expired refresh token', 401);
            }

            if ($tokenModel->used_at) {
                RefreshToken::where('user_id', $tokenModel->user_id)->delete();
                DB::commit();

                return $this->sendError('Refresh token has already been used. For security, all sessions have been logged out.', 401);
            }

            $user = $tokenModel->user;

            if (! $user || ! $user->is_active) {
                DB::commit();

                return $this->sendError('Invalid credentials', 401);
            }

            $tokenModel->update(['used_at' => now()]);

            $newAccessToken = auth('api')->login($user);
            $newRefreshToken = RefreshToken::createToken($user->id, request()->ip(), request()->userAgent());

            DB::commit();

            $data = [
                'access_token' => $newAccessToken,
                'refresh_token' => $newRefreshToken,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
                'refresh_expires_in' => config('jwt.refresh_ttl') * 60,
            ];

            return $this->sendSuccess('Token refreshed successfully', $data, 200);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error('Token refresh error: ', [
                'message' => $ex->getMessage(),
            ]);

            return $this->sendError('Token refresh failed', 401);
        }
    }

    public function logout()
    {
        try {
            DB::beginTransaction();
            $user = auth('api')->user();

            RefreshToken::where('user_id', $user->id)->delete();

            $user->token_version = ($user->token_version ?? 0) + 1;
            $user->save();
            DB::commit();

            return $this->sendSuccess('Successfully logged out from all devices', null, 200);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error('Logout all devices error: ', [
                'user_id' => auth('api')->id(),
                'message' => $ex->getMessage(),
            ]);

            return $this->sendError('Failed to logout from all devices', 500);
        }
    }

    #[\Deprecated]
    public function redirectToProvider(string $provider)
    {
        try {
            $allowedProviders = ['google'];
            if (! in_array($provider, $allowedProviders)) {
                return $this->sendError('Invalid provider', 400);
            }

            $url = Socialite::driver($provider)->stateless()->redirect()->getTargetUrl();

            return response()->json([
                'error' => false,
                'status' => 'success',
                'message' => 'Redirect to ' . ucfirst($provider),
                'data' => ['url' => $url],
            ]);
        } catch (\Exception $ex) {
            Log::error('OAuth redirect error: ', [
                'provider' => $provider,
                'message' => $ex->getMessage(),
            ]);

            return $this->sendError('OAuth redirect failed', 500);
        }
    }

    #[\Deprecated]
    public function handleProviderCallback(string $provider, Request $request)
    {
        DB::beginTransaction();

        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();

            $user = User::where('email', $socialUser->getEmail())->first();

            if (! $user) {
                $user = User::create([
                    'name' => $socialUser->getName(),
                    'email' => $socialUser->getEmail(),
                    'password' => Hash::make(Str::random(32)),
                    'email_verified_at' => now(),
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'avatar' => $socialUser->getAvatar(),
                ]);
            } else {
                $user->update([
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'avatar' => $socialUser->getAvatar(),
                ]);
            }

            $token = auth('api')->login($user);

            $data = [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                ],
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
                'refresh_expires_in' => config('jwt.refresh_ttl') * 60,
            ];

            DB::commit();

            return $this->sendSuccess('Login via ' . ucfirst($provider) . ' successful', $data, 200);

        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error('OAuth callback error: ', [
                'provider' => $provider,
                'message' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString(),
            ]);

            return $this->sendError('OAuth authentication failed', 500);
        }
    }

    public function loginWithProvider(Request $request)
    {
        DB::beginTransaction();

        try {
            $validated = $request->validate([
                'provider' => 'required|in:google',
                'access_token' => 'required|string',
                'role' => 'required|in:adopter,provider',
            ]);

            // Validasi token dengan Google
            $socialUser = Socialite::driver($validated['provider'])
                ->userFromToken($validated['access_token']);

            $user = User::where('email', $socialUser->getEmail())->first();

            if (! $user) {
                $user = User::create([
                    'name' => $socialUser->getName(),
                    'email' => $socialUser->getEmail(),
                    'password' => Hash::make(Str::random(32)),
                    'email_verified_at' => now(),
                    'provider' => $validated['provider'],
                    'provider_id' => $socialUser->getId(),
                    'avatar' => $socialUser->getAvatar(),
                ]);
                $user->assignRole($validated['role']);
            } else {
                if (! $user->is_active) {
                    DB::rollBack();

                    return $this->sendError('Your account has been deactivated. Please contact support.', 403);
                }

                $user->update([
                    'provider' => $validated['provider'],
                    'provider_id' => $socialUser->getId(),
                    'avatar' => $socialUser->getAvatar(),
                ]);
                if (! $user->hasRole($validated['role'])) {
                    $user->assignRole($validated['role']);
                }
            }

            $token = auth('api')->login($user);
            $refreshToken = RefreshToken::createToken($user->id, request()->ip(), request()->userAgent());
            $user->load([
                'roles:id,name',
                'roles.permissions:id,name',
                'communities',
                'chatRooms',
            ]);

            $data = $this->getCompleteData($user, $token, $refreshToken);

            DB::commit();

            return $this->sendSuccess('Login successful', $data, 200);

        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error('Provider login error: ', [
                'message' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString(),
            ]);

            return $this->sendError('Authentication failed', 500);
        }
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        try {
            $user = User::active()->where('email', $request->email)->first();
            if ($user) {
                $token = Str::upper(Str::random(8));
                DB::table(config('auth.passwords.users.table'))->updateOrInsert(
                    ['email' => $user->email],
                    [
                        'token' => Hash::make($token),
                        'created_at' => now(),
                    ]
                );
                $user->notify(new ResetPasswordNotification($token));
            }

            return $this->sendSuccess('Password reset link sent to your email', null, 200);
        } catch (\Exception $ex) {
            Log::error('Forgot password error: ', [
                'email' => $request->email,
                'message' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString(),
            ]);

            return $this->sendError('Failed to send reset link', 500);
        }
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        DB::beginTransaction();

        try {
            $tokenRecord = DB::table(config('auth.passwords.users.table'))->where('email', $request->email)->first();
            if (! $tokenRecord || ! Hash::check($request->token, $tokenRecord->token)) {
                return $this->sendError('Invalid or expired token', 400);
            }
            $tokenAge = now()->diffinMinutes($tokenRecord->created_at);

            $expireTime = config('auth.passwords.users.expire');
            if ($tokenAge > $expireTime) {
                DB::table(config('auth.passwords.users.table'))
                    ->where('email', $request->email)
                    ->delete();

                return $this->sendError('Token has expired', 400);
            }

            $user = User::where('email', $request->email)->first();
            if (! $user) {
                return $this->sendError('User not found', 404);
            }
            if (! $user->is_active) {
                return $this->sendError(
                    'Your account has been deactivated. Please contact support.',
                    403
                );
            }
            $user->password = Hash::make($request->password);
            $user->token_version = ($user->token_version ?? 0) + 1;
            $user->save();

            DB::table(config('auth.passwords.users.table'))
                ->where('email', $request->email)
                ->delete();

            DB::commit();

            return $this->sendSuccess('Password has been reset successfully', null, 200);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error('Reset password error: ', [
                'email' => $request->email,
                'message' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString(),
            ]);

            return $this->sendError('Failed to reset password', 500);
        }
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        try {
            $user = auth('api')->user();

            if (! $user->is_active) {
                return $this->sendError('Your account has been deactivated.', 403);
            }

            if (! Hash::check($request->current_password, $user->password)) {
                return $this->sendError('Current password is incorrect', 400);
            }

            $user->password = Hash::make($request->new_password);
            // logout from all devices
            RefreshToken::where('user_id', $user->id)->delete();
            $user->token_version = ($user->token_version ?? 0) + 1;
            $user->save();

            return $this->sendSuccess('Password changed successfully', null, 200);
        } catch (\Exception $ex) {
            Log::error('Change password error: ', [
                'user_id' => auth('api')->id(),
                'message' => $ex->getMessage(),
            ]);

            return $this->sendError('Failed to change password', 500);
        }
    }

    public function resendActivationCode()
    {
        try {
            $user = auth('api')->user();

            if (! $user->is_active) {
                return $this->sendError('Your account has been deactivated.', 403);
            }

            $this->sendActivationCode($user);

            return $this->sendSuccess('Activation code resent successfully', null, 200);
        } catch (\Exception $ex) {
            Log::error('Resend activation code error: ', [
                'user_id' => auth('api')->id(),
                'message' => $ex->getMessage(),
            ]);

            return $this->sendError('Failed to resend activation code', 500);
        }
    }

    private function sendActivationCode(User $user)
    {
        $token = Str::upper(Str::random(8));

        DB::table('tr_user_activation')->updateOrInsert(
            ['user_id' => $user->id],
            [
                'activation_token' => Hash::make($token),
                'created_at' => now(),
                'expires_at' => now()->addMinutes(config('auth.activation.expire')),
            ]
        );

        $user->notify(new ActivationCodeNotification($token));
    }

    public function validateActivationCode(ActivationCodeRequest $request)
    {
        try {
            $user = auth('api')->user();

            if (! $user->is_active) {
                return $this->sendError('Your account has been deactivated.', 403);
            }

            $activationRecord = DB::table('tr_user_activation')->where('user_id', $user->id)->first();
            if (! $activationRecord || ! Hash::check($request->token, $activationRecord->activation_token)) {
                return $this->sendError('Invalid or expired activation token', 400);
            }

            if (now()->greaterThan($activationRecord->expires_at)) {
                DB::table('tr_user_activation')->where('user_id', $user->id)->delete();

                return $this->sendError('Activation token has expired', 400);
            }

            $user->email_verified_at = now();
            $user->save();

            DB::table('tr_user_activation')->where('user_id', $user->id)->delete();

            return $this->sendSuccess('Account activated successfully', null, 200);
        } catch (\Exception $ex) {
            Log::error('Verify activation code error: ', [
                'user_id' => auth('api')->id(),
                'message' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString(),
            ]);

            return $this->sendError('Failed to verify activation code', 500);
        }
    }

    private function getCompleteData(
        User|\Illuminate\Contracts\Auth\Authenticatable|null $user,
        string $token,
        string $refreshToken
    ): array {
        $channels = [
            [
                'name' => ChannelEnum::NOTIFICATION->channel($user->id),
                'event' => ChannelEnum::NOTIFICATION->event(),
            ],
        ];

        foreach ($user->adoptionsByRole()->where('is_active', true)->pluck('id') as $id) {
            $channels[] = [
                'name' => ChannelEnum::ADOPTION->channel($id),
                'event' => ChannelEnum::ADOPTION->event(),
            ];
        }

        foreach ($user->communities->where('is_active', true)->pluck('id') as $id) {
            $channels[] = [
                'name' => ChannelEnum::COMMUNITY->channel($id),
                'event' => ChannelEnum::COMMUNITY->event(),
            ];
        }

        $activeChatIds = DB::table('tr_chat_room as target')
            ->join('tr_chat_room as counts', 'target.chat_id', '=', 'counts.chat_id')
            ->where('target.user_id', $user->id)
            ->where('target.is_active', true)
            ->where('counts.is_active', true)
            ->select('target.chat_id')
            ->groupBy('target.chat_id')
            ->havingRaw('COUNT(counts.user_id) >= 2')
            ->pluck('chat_id');
        $channels = [
            [
                'name' => ChannelEnum::NOTIFICATION->channel($user->id),
                'event' => ChannelEnum::NOTIFICATION->event(),
            ],
        ];
        foreach ($activeChatIds as $id) {
            $channels[] = [
                'name' => ChannelEnum::CHAT->channel($id),
                'event' => ChannelEnum::CHAT->event(),
            ];
        }

        return [
            'user' => [
                'id' => $user->id,
                'username' => $user->username ?? $user->name,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles,
                'avatar' => $user->attachment?->public_url ?? $user->avatar,
                'channels' => $channels,
                'email_verified_at' => $user->email_verified_at,
                'address_street' => $user->address?->street,
            ],
            'access_token' => $token,
            'refresh_token' => $refreshToken,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'refresh_expires_in' => config('jwt.refresh_ttl') * 60,
        ];
    }
}
