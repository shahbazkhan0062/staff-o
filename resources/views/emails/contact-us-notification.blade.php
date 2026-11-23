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

        /* Logo band */
        .logo-band {
            background-color: #ffffff;
            text-align: center;
            padding: 22px 20px 18px;
            border-bottom: 1px solid #E4E9E4;
        }
        .logo-band img {
            max-width: 150px;
            height: auto;
            display: inline-block;
        }

        /* Header */
        .header {
            background-color: #0a1930;
            padding: 26px 28px;
        }
        .header h1 {
            color: #ffffff;
            font-size: 18px;
            font-weight: 600;
            line-height: 1.3;
        }
        .header p {
            color: rgba(255, 255, 255, 0.5);
            font-size: 12.5px;
            margin-top: 4px;
            margin-bottom: 18px;
            line-height: 1.3;
        }
        .badge-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11.5px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            line-height: 1.4;
        }
        .badge-inquiry {
            background-color: rgba(10, 124, 110, 0.22);
            color: #6CE0CD;
        }
        .badge-source {
            background-color: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.7);
        }
        .badge-status {
            background-color: rgba(226, 163, 61, 0.22);
            color: #F0B860;
        }

        .content {
            padding: 30px 28px 8px;
        }

        /* Sender card */
        .sender-card {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 18px;
            background-color: #F5F8F5;
            border-radius: 12px;
            margin-bottom: 28px;
        }
        .sender-avatar {
            width: 46px;
            height: 46px;
            min-width: 46px;
            border-radius: 50%;
            background-color: #0A7C6E;
            display: table;
        }
        .sender-avatar span {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
            color: #ffffff;
            font-size: 17px;
            font-weight: 700;
            line-height: 1;
        }
        .sender-name {
            font-size: 16px;
            font-weight: 700;
            color: #14181C;
            line-height: 1.3;
        }
        .sender-contact {
            font-size: 13.5px;
            color: #5B6660;
            margin-top: 3px;
            line-height: 1.5;
        }
        .sender-contact a {
            color: #075E53;
            text-decoration: none;
        }
        .sender-contact .sep {
            color: #c7d0cc;
            margin: 0 6px;
        }

        /* Detail list */
        .detail-list {
            margin-bottom: 28px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 13px 2px;
            border-bottom: 1px solid #E4E9E4;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-size: 13px;
            color: #5B6660;
            flex-shrink: 0;
            margin-right: 16px;
        }
        .detail-value {
            font-size: 14px;
            font-weight: 600;
            color: #14181C;
            text-align: right;
            word-break: break-word;
        }

        /* Message */
        .message-quote {
            position: relative;
            background-color: #F5F8F5;
            border-left: 3px solid #0A7C6E;
            border-radius: 0 10px 10px 0;
            padding: 18px 20px;
            margin-bottom: 30px;
            font-size: 14.5px;
            line-height: 1.65;
            color: #2A3330;
            white-space: pre-line;
        }
        .message-quote-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0A7C6E;
            font-weight: 700;
            margin-bottom: 8px;
            display: block;
            line-height: 1;
        }

        /* Quick action buttons */
        .quick-actions-row {
            display: flex;
            gap: 10px;
            margin-bottom: 28px;
        }
        .quick-action-btn {
            flex: 1;
            display: block;
            text-align: center;
            padding: 12px 8px;
            border-radius: 8px;
            background-color: #E1F3F0;
            color: #075E53 !important;
            font-size: 13px;
            font-weight: 600;
            line-height: 1.2 !important;
            text-decoration: none;
            white-space: nowrap;
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

        @media (max-width: 600px) {
            .content {
                padding: 24px 18px 4px;
            }
            .header {
                padding: 22px 18px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="logo-band">
            <img src="https://apis.staffoo.com.au/uploads/staffologo.png" alt="Staffoo">
        </div>

        <div class="header">
            <h1>New Contact Submission</h1>
            <p>Received {{ $submittedAt }}</p>
            <div class="badge-row">
                <span class="badge badge-inquiry">{{ $inquiryType }}</span>
                <span class="badge badge-source">{{ $source }}</span>
                <span class="badge badge-status">Pending</span>
            </div>
        </div>

        <div class="content">
            <div class="sender-card">
                <div class="sender-avatar"><span>{{ strtoupper(substr($name, 0, 1)) }}</span></div>
                <div>
                    <div class="sender-name">{{ $name }}</div>
                    <div class="sender-contact">
                        <a href="mailto:{{ $email }}">{{ $email }}</a>
                        @if($phone)
                        <span class="sep">&bull;</span>
                        <a href="tel:{{ $phone }}">{{ $phone }}</a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="detail-list">
                @if($company)
                <div class="detail-row">
                    <span class="detail-label">Company</span>
                    <span class="detail-value">{{ $company }}</span>
                </div>
                @endif
                <div class="detail-row">
                    <span class="detail-label">Subject</span>
                    <span class="detail-value">{{ $subject }}</span>
                </div>
            </div>

            @if($contactMessage)
            <div class="message-quote">
                <span class="message-quote-label">Message</span>
                "{{ $contactMessage }}"
            </div>
            @endif

            <div class="quick-actions-row">
                <a href="mailto:{{ $email }}" class="quick-action-btn">Reply by Email</a>
                @if($phone)
                <a href="tel:{{ $phone }}" class="quick-action-btn">Call {{ $name }}</a>
                @endif
            </div>
        </div>

        <div class="footer">
<!-- App download badges -->
<table role="presentation" align="center" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto; padding:0 0 10px 0;">
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