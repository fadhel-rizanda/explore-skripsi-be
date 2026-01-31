<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Adoption Update</title>
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
                            Adoption Notification
                        </h2>

                        <p style="color:#374151;font-size:16px;line-height:1.5;">
                            An adoption application has been <strong>{{ strtolower($action) }}</strong> for the pet:
                            <strong>{{ $petName }}</strong>
                        </p>

                        <div style="
                            margin:15px 0 25px;
                            padding:15px;
                            background-color:#f3f4f6;
                            border:1px dashed #d1d5db;
                            color:#111827;
                        ">
                            <p style="margin:0 0 8px;"><strong>Adoption ID:</strong> {{ $action }}</p>
                            <p style="margin:0 0 8px;"><strong>Action:</strong> {{ $action }}</p>
                            <p style="margin:0 0 8px;"><strong>Status:</strong> {{ $status }}</p>
                            <p style="margin:0 0 8px;"><strong>Pet:</strong> {{ $petId }} - {{ $petName }}</p>
                            <p style="margin:0 0 8px;"><strong>Adopter:</strong> {{ $adopterId }} - {{ $adopterName }}</p>
                            <p style="margin:0 0 8px;"><strong>Provider:</strong> {{ $providerId }} - {{ $providerName }}</p>
                            <p style="margin:0;"><strong>Last Updated By:</strong> {{ $updatedBy }}</p>
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
                            Please follow up or check your dashboard for more details.
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
