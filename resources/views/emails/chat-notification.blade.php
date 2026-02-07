<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Chat Notification</title>
</head>
<body style="margin:0;padding:0;background-color:#f9fafb;font-family:Arial, sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
    <tr>
        <td align="center" style="padding:20px;">
            <table width="600" cellpadding="0" cellspacing="0" role="presentation"
                   style="background-color:#ffffff;border-radius:8px;overflow:hidden;">
                <tr>
                    <td style="padding:30px;">
                        <h2 style="margin-top:0;color:#111827;">
                            Chat Notification
                        </h2>

                        <p style="color:#374151;font-size:16px;line-height:1.5;">
                            A chat has been <strong>{{ strtolower($action) }}</strong>.
                        </p>

                        <div style="
                            margin:15px 0 25px;
                            padding:15px;
                            background-color:#f3f4f6;
                            border:1px dashed #d1d5db;
                            color:#111827;
                        ">
                            <p style="margin:0 0 8px;"><strong>Chat ID:</strong> {{ $chatId }}</p>
                            <p style="margin:0 0 8px;"><strong>Chat Name:</strong> {{ $chatName ?? 'Private Chat' }}</p>
                            <p style="margin:0 0 8px;"><strong>Type:</strong> {{ ucfirst($chatType) }}</p>
                            <p style="margin:0 0 8px;"><strong>Members:</strong> {{ $membersCount }}</p>

                            @if($lastMessage)
                                <p style="margin:8px 0 0;">
                                    <strong>Last Message:</strong><br>
                                    “{{ $lastMessage }}”
                                    <br>
                                    <span style="font-size:12px;color:#6b7280;">
                                        — {{ $lastMessageBy }}
                                    </span>
                                </p>
                            @endif
                        </div>

                        @if(!empty($notes))
                            <div style="
                                margin:10px 0 25px;
                                padding:15px;
                                background-color:#f9fafb;
                                border-left:4px solid #e5e7eb;
                                color:#374151;
                                font-size:14px;
                                line-height:1.4;
                            ">
                                {{ $notes }}
                            </div>
                        @endif

                        <p style="color:#374151;font-size:16px;line-height:1.5;">
                            Please open your application to view the full conversation.
                        </p>

                        <hr style="border:none;border-top:1px solid #e5e7eb;margin:30px 0;">

                        <p style="font-size:12px;color:#6b7280;text-align:center;">
                            © {{ date('Y') }} Your App. All rights reserved.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
