<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
</head>

<body style="margin:0;padding:0;background-color:#f9fafb;font-family:Arial, sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
    <tr>
        <td align="center" style="padding:20px;">
            <table width="600" cellpadding="0" cellspacing="0" role="presentation"
                   style="background-color:#ffffff;border-radius:8px;overflow:hidden;">
                <tr>
                    <td style="padding:30px;">
                        <h2 style="margin-top:0;margin-bottom:16px;color:#111827;font-size:24px;">
                            Reset Your Password
                        </h2>

                        <p style="margin:0 0 20px;color:#374151;font-size:16px;line-height:1.5;">
                            We received a request to reset your password. Please use the reset token below to proceed.
                        </p>

                        <!-- Reset Token Box -->
                        <div style="
                            margin:20px 0;
                            padding:20px;
                            background-color:#f3f4f6;
                            border:1px dashed #d1d5db;
                            border-radius:4px;
                            text-align:center;
                            font-size:24px;
                            font-weight:bold;
                            letter-spacing:3px;
                            color:#111827;
                        ">
                            {{ $token }}
                        </div>

                        <p style="margin:20px 0 0;color:#374151;font-size:16px;line-height:1.5;">
                            This token will expire in <strong>{{ $expire }} minutes</strong>. If you didn't request a password reset, you can safely ignore this email.
                        </p>

                        <hr style="border:none;border-top:1px solid #e5e7eb;margin:30px 0;">

                        <p style="margin:0;font-size:12px;color:#6b7280;text-align:center;">
                            © {{ date('Y') }} Pawsitive. All rights reserved.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
