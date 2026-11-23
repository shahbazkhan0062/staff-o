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

        /* Header */
        .header {
            background-color: #0a1930;
            padding: 28px 30px;
        }
        .header-top {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        .header-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background-color: #0A7C6E;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }
        .header-top h1 {
            color: #ffffff;
            font-size: 19px;
            font-weight: 600;
        }
        .header-top p {
            color: rgba(255, 255, 255, 0.55);
            font-size: 13px;
            margin-top: 2px;
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
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .badge-inquiry {
            background-color: rgba(10, 124, 110, 0.18);
            color: #6CE0CD;
        }
        .badge-source {
            background-color: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.75);
        }
        .badge-status {
            background-color: rgba(226, 163, 61, 0.2);
            color: #E2A33D;
        }

        .content {
            padding: 28px 30px 10px;
        }

        /* Sender card */
        .sender-card {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px;
            background-color: #F5F8F5;
            border-radius: 12px;
            margin-bottom: 22px;
        }
        .sender-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background-color: #0A7C6E;
            color: #ffffff;
            font-size: 18px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .sender-name {
            font-size: 17px;
            font-weight: 700;
            color: #14181C;
        }
        .sender-contact {
            font-size: 14px;
            color: #5B6660;
            margin-top: 2px;
        }
        .sender-contact a {
            color: #075E53;
            text-decoration: none;
        }
        .sender-contact span {
            margin-right: 10px;
        }

        /* Detail list */
        .detail-list {
            margin-bottom: 22px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            padding: 11px 0;
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
            padding: 16px 18px;
            margin-bottom: 24px;
            font-size: 14.5px;
            line-height: 1.7;
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
        }

        /* Primary CTA */
        .action-button {
            text-align: center;
            margin-bottom: 26px;
        }
        .button {
            display: inline-block;
            background-color: #0A7C6E;
            color: #ffffff;
            padding: 13px 34px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            box-shadow: 0 2px 4px rgba(10, 124, 110, 0.3);
        }
        .button:hover {
            background-color: #075E53;
        }

        /* Quick action buttons */
        .quick-actions-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }
        .quick-action-btn {
            flex: 1;
            text-align: center;
            padding: 11px 10px;
            border-radius: 8px;
            background-color: #F5F8F5;
            border: 1px solid #E4E9E4;
            color: #075E53;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
        }

        .footer {
            background-color: #F5F8F5;
            padding: 25px 30px;
            text-align: center;
            border-top: 1px solid #E4E9E4;
            margin-top: 10px;
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
                padding: 22px 20px 5px;
            }
            .header {
                padding: 22px 20px;
            }
            .detail-row {
                flex-direction: column;
                align-items: flex-start;
            }
            .detail-value {
                text-align: left;
                margin-top: 2px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="header-top">
                <div class="header-icon">📬</div>
                <div>
                    <h1>New Contact Submission</h1>
                    <p>Received {{ $submittedAt }}</p>
                </div>
            </div>
            <div class="badge-row">
                <span class="badge badge-inquiry">{{ $inquiryType }}</span>
                <span class="badge badge-source">{{ $source }}</span>
                <span class="badge badge-status">Pending</span>
            </div>
        </div>

        <div class="content">
            <div class="sender-card">
                <div class="sender-avatar">{{ strtoupper(substr($name, 0, 1)) }}</div>
                <div>
                    <div class="sender-name">{{ $name }}</div>
                    <div class="sender-contact">
                        <span><a href="mailto:{{ $email }}">{{ $email }}</a></span>
                        @if($phone)
                        <span><a href="tel:{{ $phone }}">{{ $phone }}</a></span>
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

            <div class="action-button">
                <a href="{{ $appUrl }}/admin/contact-us/{{ $contactId }}" class="button" target="_blank">View &amp; Respond →</a>
            </div>

            <div class="quick-actions-row">
                <a href="mailto:{{ $email }}" class="quick-action-btn">✉ Reply by Email</a>
                @if($phone)
                <a href="tel:{{ $phone }}" class="quick-action-btn">📞 Call {{ $name }}</a>
                @endif
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