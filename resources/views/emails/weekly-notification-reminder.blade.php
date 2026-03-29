<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Weekly Notification Reminder</title>
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
                            Hello {{ $userName }},
                        </h2>

                        <p style="margin:0 0 20px;color:#374151;font-size:16px;line-height:1.5;">
                            This is a quick reminder that you currently have <strong>{{ $unreadCount }}</strong> unread notification{{ $unreadCount != 1 ? 's' : '' }}.
                        </p>

                        <!-- Notification Info Box -->
                        <div style="
                            margin:20px 0;
                            padding:15px;
                            background-color:#f3f4f6;
                            border:1px dashed #d1d5db;
                            border-radius:4px;
                            text-align:center;
                            color:#111827;
                        ">
                            <p style="margin:0;font-size:14px;color:#6b7280;">Unread Notifications</p>
                            <p style="margin:8px 0 0;font-size:32px;font-weight:bold;color:#111827;">
                                {{ $unreadCount }}
                            </p>
                        </div>

                        <p style="margin:20px 0;color:#374151;font-size:16px;line-height:1.5;">
                            Please review them to stay up to date with important updates.
                        </p>

                        <!-- Button -->
                        <table cellpadding="0" cellspacing="0" role="presentation" style="margin:20px auto;">
                            <tr>
                                <td align="center" style="background-color:#2563eb;border-radius:6px;">
                                    <a href="{{ $notificationUrl }}"
                                       style="display:inline-block;
                                              padding:12px 28px;
                                              color:#ffffff;
                                              text-decoration:none;
                                              font-weight:bold;
                                              font-size:16px;">
                                        View Notifications
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:20px 0 0;color:#374151;font-size:16px;line-height:1.5;">
                            Thank you for being part of Pawsitive.
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
