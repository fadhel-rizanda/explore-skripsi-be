<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Traits\ResponseAPI;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Request;
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

            // Generate JWT token
            $token = auth('api')->login($user);

            $data = [
                'user' => [
                    'id' => $user->id,
                    'username' => $user->name,
                    'email' => $user->email,
                ],
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60
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

            if (!$token = auth('api')->attempt($credentials)) {
                return $this->sendError('Wrong credentials', 401);
            }

            $user = auth('api')->user();
            $user->load(['roles:id,name', 'roles.permissions:id,name']);

            $data = [
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username ?? $user->name,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles,
                    'avatar' => $user->avatar,
                ],
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60
            ];

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

    public function refresh()
    {
        try {
            $newToken = auth('api')->refresh();
            $data = [
                'access_token' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
            ];
            return $this->sendSuccess('Token refreshed successfully', $data, 200);
        } catch (\Exception $ex) {
            Log::error('Token refresh error: ', [
                'message' => $ex->getMessage(),
            ]);
            return $this->sendError('Token refresh failed', 401);
        }
    }

    public function logout()
    {
        try {
            $user = auth('api')->user();

            Log::info($user->all());
            // logout from all devices
            $user->token_version = ($user->token_version ?? 0) + 1;
            $user->save();
            return $this->sendSuccess('Successfully logged out from all devices', null, 200);
        } catch (\Exception $ex) {
            Log::error('Logout all devices error: ', [
                'user_id' => auth('api')->id(),
                'message' => $ex->getMessage(),
            ]);
            return $this->sendError('Failed to logout from all devices', 500);
        }
    }


    public function redirectToProvider(string $provider)
    {
        try {
            // Validasi provider
            $allowedProviders = ['google', 'github', 'facebook']; // sekarang baru google doang
            if (!in_array($provider, $allowedProviders)) {
                return $this->sendError('Invalid provider', 400);
            }

            $url = Socialite::driver($provider)->stateless()->redirect()->getTargetUrl();

            return response()->json([
                'error' => false,
                'status' => 'success',
                'message' => 'Redirect to ' . ucfirst($provider),
                'data' => ['url' => $url]
            ]);
        } catch (\Exception $ex) {
            Log::error('OAuth redirect error: ', [
                'provider' => $provider,
                'message' => $ex->getMessage(),
            ]);
            return $this->sendError('OAuth redirect failed', 500);
        }
    }

    public function handleProviderCallback(string $provider, Request $request)
    {
        DB::beginTransaction();
        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();

            $user = User::where('email', $socialUser->getEmail())->first();

            if (!$user) {
                $user = User::create([
                    'name' => $socialUser->getName(),
                    'email' => $socialUser->getEmail(),
                    'password' => Hash::make(uniqid()), // Random password
                    'email_verified_at' => now(),
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'avatar' => $socialUser->getAvatar(),
                ]);
            } else {
                // Update provider info jika user sudah ada
                $user->update([
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'avatar' => $socialUser->getAvatar(),
                ]);
            }

            // Generate JWT token
            $token = auth('api')->login($user);

            $data = [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                ],
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60
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

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        try {
            $user = User::where('email', $request->email)->first();
            if ($user) {
                $token = Str::random(64);
                DB::table('password_reset_tokens')->updateOrInsert(
                    ['email' => $user->email],
                    [
                        'token' => Hash::make($token),
                        'created_at' => now()
                    ]
                );
                $user->notify(new ResetPasswordNotification($token, $user->email));
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
            $tokenRecord = DB::table('password_reset_tokens')->where('email', $request->email)->first();
            if (!$tokenRecord || !Hash::check($request->token, $tokenRecord->token)) {
                return $this->sendError('Invalid or expired token', 400);
            }
            $tokenAge = now()->diffinMinutes($tokenRecord->created_at);

            $expireTime = config('auth.passwords.users.expire');
            if ($tokenAge > $expireTime) {
                DB::table('password_reset_tokens')
                    ->where('email', $request->email)
                    ->delete();
                return $this->sendError('Token has expired', 400);
            }

            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return $this->sendError('User not found', 404);
            }
            $user->password = Hash::make($request->password);
            $user->save();

            DB::table('password_reset_tokens')
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

            if (!Hash::check($request->current_password, $user->password)) {
                return $this->sendError('Current password is incorrect', 400);
            }

            $user->password = Hash::make($request->new_password);
            // logout from all devices
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
}
