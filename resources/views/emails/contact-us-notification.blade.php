{{-- resources/views/emails/contact-us-notification.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Contact Form Submission</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #14181C;
            background-color: #F5F8F5;
            margin: 0;
            padding: 20px;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(10, 25, 48, 0.08), 0 2px 4px -1px rgba(10, 25, 48, 0.05);
        }
        .header {
            background: linear-gradient(135deg, #0a1930 0%, #0e2340 55%, #0A7C6E 100%);
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            color: white;
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .header p {
            color: rgba(255, 255, 255, 0.85);
            font-size: 16px;
        }
        .content {
            padding: 30px;
        }
        .badge-container {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-inquiry {
            background-color: #E1F3F0;
            color: #075E53;
        }
        .badge-source {
            background-color: #EEF2F7;
            color: #0e2340;
        }
        .badge-status {
            background-color: #FBEFDA;
            color: #8a5a17;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }
        .info-item {
            background-color: #F5F8F5;
            padding: 15px;
            border-radius: 12px;
            border-left: 4px solid #0A7C6E;
        }
        .info-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #5B6660;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: #14181C;
            word-break: break-word;
        }
        .info-value a {
            color: #0A7C6E;
            text-decoration: none;
        }
        .info-value a:hover {
            text-decoration: underline;
        }
        .message-section {
            background-color: #F5F8F5;
            padding: 20px;
            border-radius: 12px;
            margin: 25px 0;
            border: 1px solid #E4E9E4;
        }
        .message-section h3 {
            color: #0e2340;
            margin-bottom: 15px;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .message-content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #E4E9E4;
            white-space: pre-line;
            font-size: 15px;
            line-height: 1.7;
        }
        .action-button {
            text-align: center;
            margin: 30px 0;
            color: #F5F8F5;
        }
        .button {
            display: inline-block;
            background-color: #0A7C6E;
            color: #F5F8F5;
            padding: 14px 32px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            transition: background-color 0.3s;
            box-shadow: 0 2px 4px rgba(10, 124, 110, 0.3);
        }
        .button:hover {
            background-color: #075E53;
        }
        .footer {
            background-color: #F5F8F5;
            padding: 25px 30px;
            text-align: center;
            border-top: 1px solid #E4E9E4;
        }
        .footer p {
            color: #5B6660;
            font-size: 14px;
            margin: 5px 0;
        }
        .footer .app-name {
            font-weight: 600;
            color: #075E53;
        }
        hr {
            border: none;
            border-top: 1px solid #E4E9E4;
            margin: 20px 0;
        }
        @media (max-width: 600px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            .content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>📬 New Contact Submission</h1>
            <p>You have received a new inquiry from your website</p>
        </div>

        <div class="content">
            <div class="badge-container">
                <span class="badge badge-inquiry">{{ $inquiryType }}</span>
                <span class="badge badge-source">{{ $source }}</span>
                <span class="badge badge-status">Pending</span>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">👤 Name</div>
                    <div class="info-value">{{ $name }}</div>
                </div>

                <div class="info-item">
                    <div class="info-label">📧 Email</div>
                    <div class="info-value">
                        <a href="mailto:{{ $email }}">{{ $email }}</a>
                    </div>
                </div>

                @if($phone)
                <div class="info-item">
                    <div class="info-label">📞 Phone</div>
                    <div class="info-value">
                        <a href="tel:{{ $phone }}">{{ $phone }}</a>
                    </div>
                </div>
                @endif

                @if($company)
                <div class="info-item">
                    <div class="info-label">🏢 Company</div>
                    <div class="info-value">{{ $company }}</div>
                </div>
                @endif

                <div class="info-item">
                    <div class="info-label">📝 Subject</div>
                    <div class="info-value">{{ $subject }}</div>
                </div>

                <div class="info-item">
                    <div class="info-label">⏰ Submitted</div>
                    <div class="info-value">{{ $submittedAt }}</div>
                </div>
            </div>

            <div class="message-section">
                <h3>💬 Message</h3>
                <div class="message-content">
                    {{ $contactMessage }}
                </div>
            </div>

            <hr>

            <div style="background-color: #f0fdf9; padding: 15px; border-radius: 8px; margin-top: 20px;">
                <p style="color: #075E53; font-weight: 600; margin-bottom: 5px;">📋 Quick Actions:</p>
                <ul style="color: #14181C; margin-left: 20px;">
                    <li>Reply to: <a href="mailto:{{ $email }}" style="color: #0A7C6E;">{{ $email }}</a></li>
                    @if($phone)
                    <li>Call: <a href="tel:{{ $phone }}" style="color: #0A7C6E;">{{ $phone }}</a></li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="footer">
<!-- App download badges -->
<table role="presentation" align="center" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto; padding:20px 0 10px 0;">
<tr>
<td style="padding:0 5px;"><a href="https://apps.apple.com/pk/app/staffoo/id6798964069" target="_blank"><img src="https://apis.staffoo.com.au/uploads/app-store-badge.png" alt="Download on the App Store" width="120" height="41" style="display:block; border:0; width:120px; height:auto;"></a></td>
<td style="padding:0 5px;"><a href="https://play.google.com/store/apps/details?id=com.staffoo" target="_blank"><img src="https://apis.staffoo.com.au/uploads/google-play-badge.png" alt="Get it on Google Play" width="120" height="41" style="display:block; border:0; width:120px; height:auto;"></a></td>
</tr>
</table>
            <p class="app-name">{{ $appName }}</p>
            <p>This is an automated notification from your contact form.</p>
            <p style="font-size: 12px;">&copy; {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>