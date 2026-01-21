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
            <!-- Container -->
            <table width="600" cellpadding="0" cellspacing="0" role="presentation"
                   style="background-color:#ffffff;">
                <tr>
                    <td style="padding:30px;">
                        <h2 style="margin-top:0;">
                            Moderation Action Notice
                        </h2>

                        ```
                        <p>
                            This email is to inform you that a moderation action has been taken
                            related to your account or content on our platform.
                        </p>

                        <!-- Action Box -->
                        <div style="
                        margin:15px 0 25px;
                        padding:15px;
                        background-color:#f3f4f6;
                        border:1px dashed #d1d5db;
                        color:#111827;
                    ">
                            <p style="margin:0 0 8px;">
                                <strong>Action:</strong> {{ $action }}
                            </p>
                            <p style="margin:0 0 8px;">
                                <strong>Entity Type:</strong> {{ $entityType }}
                            </p>
                            <p style="margin:0;">
                                <strong>Entity:</strong> {{ $entityName }}
                            </p>
                        </div>

                        @if(!empty($notes))
                            <p>
                                <strong>Moderator Notes:</strong>
                            </p>
                            <div style="
                            margin:10px 0 25px;
                            padding:15px;
                            background-color:#f9fafb;
                            border-left:4px solid #e5e7eb;
                            color:#374151;
                            font-size:14px;
                        ">
                                {{ $notes }}
                            </div>
                        @endif

                        <p>
                            If you believe this action was taken in error, or if you have any questions,
                            please contact our support team for further clarification.
                        </p>

                        <p>
                            Thank you for helping us keep the community safe and respectful.
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
    ```

</table>
</body>
</html>
