<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #111827; margin:0; padding:0; background:#f4f4f7; }
    .wrapper { max-width: 640px; margin: 0 auto; padding: 24px; }
    .logo-band { background:#FFFFFF; padding:18px 22px; text-align:center; border:1px solid #E5E7EB; border-bottom:none; border-radius:6px 6px 0 0; }
    .logo-band img { height:40px; }
    .header { background:#0A7C6E; padding:16px 22px; }
    .header h1 { color:#fff; font-size:17px; margin:0; }
    .body { border:1px solid #E5E7EB; border-top:none; padding:22px; border-radius:0 0 6px 6px; }
    .intro { font-size:14px; line-height:1.7; margin-bottom: 18px; }
    .meta { margin-bottom:18px; line-height:1.8; font-size:13px; color:#374151; background:#F9FAFB; border-left:4px solid #0A7C6E; border-radius:4px; padding:12px 16px; }
    .meta .row { padding:2px 0; }
    .meta .label { color:#6B7280; }
    .meta .value { color:#111827; font-weight:bold; }
    .footer-note { font-size:13px; color:#6B7280; line-height:1.6; margin-top: 6px; }
    .footer { text-align:center; color:#9CA3AF; font-size:11px; margin-top:18px; }
</style>
</head>
<body>
<div class="wrapper">
    <div class="logo-band">
        <img src="https://apis.staffoo.com.au/uploads/staffologo.png" alt="Staffoo Logo">
    </div>
    <div class="header">
        <h1>{{ $title }}</h1>
    </div>
    <div class="body">
        <p class="intro">Hi {{ $recipientName ?: 'there' }},</p>

        <p class="intro">{{ $message }}</p>

        @if(!empty($meta))
            <div class="meta">
                @foreach ($meta as $row)
                    <div class="row"><span class="label">{{ $row['label'] }}:</span> <span class="value">{{ $row['value'] }}</span></div>
                @endforeach
            </div>
        @endif

        <p class="footer-note">
            Please open the Staffoo app or portal for full details.
        </p>
    </div>
    <div class="footer">
<!-- App download badges -->
<table role="presentation" align="center" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto; padding:20px 0 10px 0;">
<tr>
<td style="padding:0 5px;"><a href="https://apps.apple.com/pk/app/staffoo/id6798964069" target="_blank"><img src="https://apis.staffoo.com.au/uploads/app-store-badge.png" alt="Download on the App Store" width="120" height="41" style="display:block; border:0; width:120px; height:auto;"></a></td>
<td style="padding:0 5px;"><a href="https://play.google.com/store/apps/details?id=com.staffoo" target="_blank"><img src="https://apis.staffoo.com.au/uploads/google-play-badge.png" alt="Get it on Google Play" width="120" height="41" style="display:block; border:0; width:120px; height:auto;"></a></td>
</tr>
</table>
        This is an automated notification from STAFFOO. Please do not reply to this email.<br>
        &copy; {{ date('Y') }} Staffoo Pty Ltd. All rights reserved.
    </div>
</div>
</body>
</html>
