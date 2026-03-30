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
                        <h2 style="margin-top:0;margin-bottom:16px;color:#111827;font-size:24px;">
                            Adoption Update
                        </h2>

                        <p style="margin:0 0 20px;color:#374151;font-size:16px;line-height:1.5;">
                            An adoption application has been <strong>{{ strtolower($action) }}</strong> for <strong>{{ $petName }}</strong>.
                        </p>

                        <!-- Adoption Details Box -->
                        <div style="
                            margin:20px 0;
                            padding:15px;
                            background-color:#f3f4f6;
                            border:1px dashed #d1d5db;
                            border-radius:4px;
                            color:#111827;
                            font-size:14px;
                        ">
                            <p style="margin:0 0 8px;"><strong>Adoption ID:</strong> {{ $adoptionId }}</p>
                            <p style="margin:0 0 8px;"><strong>Status:</strong> {{ $status }}</p>
                            <p style="margin:0 0 8px;"><strong>Pet:</strong> {{ $petName }} (ID: {{ $petId }})</p>
                            <p style="margin:0 0 8px;"><strong>Adopter:</strong> {{ $adopterName }} (ID: {{ $adopterId }})</p>
                            <p style="margin:0 0 8px;"><strong>Provider:</strong> {{ $providerName }} (ID: {{ $providerId }})</p>
                            <p style="margin:0;"><strong>Updated By:</strong> {{ $updatedBy }}</p>
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
                                <strong>Note:</strong> {{ $notes }}
                            </div>
                        @endif

                        <p style="margin:20px 0 0;color:#374151;font-size:16px;line-height:1.5;">
                            Please check your dashboard for more details.
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
