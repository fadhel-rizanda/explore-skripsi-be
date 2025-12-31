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
            <!-- Container -->
            <table width="600" cellpadding="0" cellspacing="0" role="presentation"
                   style="background-color:#ffffff;">
                <tr>
                    <td style="padding:30px;">
                        <h2 style="margin-top:0;">
                            Hello {{ $userName }},
                        </h2>

                        <p>
                            This is a quick reminder that you currently have
                            <strong>{{ $unreadCount }}</strong> unread notifications.
                        </p>

                        <p>
                            Please review them to stay up to date with important updates.
                        </p>

                        <!-- Button -->
                        <table cellpadding="0" cellspacing="0" align="center" role="presentation"
                               style="margin:30px auto;">
                            <tr>
                                <td align="center"
                                    style="background-color:#2563eb;">
                                    <a href="{{ $notificationUrl }}"
                                       style="display:inline-block;
                                       padding:12px 24px;
                                       color:#ffffff;
                                       text-decoration:none;
                                       font-weight:bold;">
                                        View Notifications
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <p>
                            Thank you for using our service.
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
