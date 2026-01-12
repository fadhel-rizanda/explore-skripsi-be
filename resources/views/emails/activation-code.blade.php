<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Activate Your Account</title>
</head>

<body style="margin:0;padding:0;background-color:#f9fafb;font-family:Arial, sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
    <tr>
        <td align="center" style="padding:20px;">
            <!-- Container -->
            <table width="600" cellpadding="0" cellspacing="0" role="presentation"
                   style="background-color:#ffffff;">
                <tr>
                    <td style="padding:30px;">
                        <h2 style="margin-top:0;">
                            Activate Your Account
                        </h2>

                        <p>
                            Thank you for registering. To complete your registration,
                            please activate your account using the activation code below.
                        </p>

                        <!-- Token Box -->
                        <div style="
                            margin:15px 0 25px;
                            padding:15px;
                            background-color:#f3f4f6;
                            border:1px dashed #d1d5db;
                            text-align:center;
                            font-size:16px;
                            font-weight:bold;
                            letter-spacing:2px;
                            color:#111827;
                        ">
                            {{ $token }}
                        </div>

                        <p>
                            This activation code will expire in {{ $expire }} minutes.
                        </p>

                        <p>
                            If you did not create an account, you can safely ignore this email.
                        </p>

                        <hr style="border:none;border-top:1px solid #e5e7eb;">

                        <p style="font-size:12px;color:#6b7280;">
                            © {{ date('Y') }} Your App. All rights reserved.
                        </p>
                    </td>
                </tr>
            </table>
            <!-- End Container -->
        </td>
    </tr>
</table>
</body>
</html>
