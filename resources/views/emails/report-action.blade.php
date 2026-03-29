<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Moderation Update</title>
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
                            Moderation Action Notice
                        </h2>

                        <p style="margin:0 0 20px;color:#374151;font-size:16px;line-height:1.5;">
                            A moderation action has been taken related to your account or content on Pawsitive.
                        </p>

                        <!-- Action Details Box -->
                        <div style="
                            margin:20px 0;
                            padding:15px;
                            background-color:#f3f4f6;
                            border:1px dashed #d1d5db;
                            border-radius:4px;
                            color:#111827;
                            font-size:14px;
                        ">
                            <p style="margin:0 0 8px;"><strong>Action:</strong> {{ $action }}</p>
                            <p style="margin:0 0 8px;"><strong>Entity Type:</strong> {{ $entityType }}</p>
                            <p style="margin:0;"><strong>Entity:</strong> {{ $entityName }}</p>
                        </div>

                        @if(!empty($notes))
                            <div style="
                                margin:20px 0;
                                padding:15px;
                                background-color:#f9fafb;
                                border-left:4px solid #d1d5db;
                                border-radius:4px;
                                color:#374151;
                                font-size:14px;
                                line-height:1.5;
                            ">
                                <strong>Moderator Notes:</strong> {{ $notes }}
                            </div>
                        @endif

                        <p style="margin:20px 0 0;color:#374151;font-size:16px;line-height:1.5;">
                            If you believe this action was taken in error or have questions, please contact our support team. Thank you for helping us keep the community safe and respectful.
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
