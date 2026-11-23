{{-- resources/views/emails/contact-auto-reply.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You for Contacting Us</title>
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
        .badge-status {
            background-color: rgba(10, 124, 110, 0.22);
            color: #6CE0CD;
        }

        .content {
            padding: 30px 28px 8px;
        }

        .intro-text {
            font-size: 14.5px;
            color: #14181C;
            margin-bottom: 28px;
        }
        .intro-text strong {
            color: #075E53;
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
            font-style: italic;
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
            font-style: normal;
        }

        /* Next steps */
        .section-heading {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #5B6660;
            font-weight: 700;
            margin-bottom: 14px;
        }
        .steps-list {
            list-style: none;
            margin-bottom: 30px;
        }
        .steps-list li {
            margin-bottom: 14px;
            padding-left: 26px;
            position: relative;
            font-size: 14px;
            color: #14181C;
        }
        .steps-list li:last-child {
            margin-bottom: 0;
        }
        .steps-list li:before {
            content: "✓";
            color: #0A7C6E;
            font-weight: bold;
            position: absolute;
            left: 0;
            top: 1px;
        }

        /* Support line */
        .support-box {
            background-color: #F5F8F5;
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 30px;
        }
        .support-box a {
            color: #075E53;
            text-decoration: none;
            font-weight: 600;
        }
        .support-box p {
            font-size: 13.5px;
            color: #5B6660;
            margin-top: 4px;
        }

        .signature {
            margin-bottom: 28px;
            font-size: 14px;
            color: #14181C;
        }
        .signature .team-name {
            font-weight: 700;
            color: #075E53;
            margin-top: 4px;
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
            <h1>Thank You, {{ $name }}!</h1>
            <p>Received {{ $submittedAt }}</p>
            <div class="badge-row">
                <span class="badge badge-inquiry">{{ $inquiryType }}</span>
                <span class="badge badge-status">Received</span>
            </div>
        </div>

        <div class="content">
            <p class="intro-text">
                Thank you for reaching out to <strong>STAFFOO</strong>. We have successfully received your inquiry and our team will review it promptly.
            </p>

            <div class="detail-list">
                <div class="detail-row">
                    <span class="detail-label">Subject</span>
                    <span class="detail-value">{{ $subject }}</span>
                </div>
            </div>

            @if($contactMessage)
            <div class="message-quote">
                <span class="message-quote-label">Your Message</span>
                "{{ $contactMessage }}"
            </div>
            @endif

            <div class="section-heading">What happens next</div>
            <ul class="steps-list">
                <li><strong>Response:</strong> You'll hear back personally from one of our specialists</li>
                <li><strong>Resolution:</strong> We'll work with you until your question or concern is addressed</li>
            </ul>

            <div class="support-box">
                <div class="section-heading" style="margin-bottom: 8px;">Need immediate assistance?</div>
                <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>
                @if($supportPhone && $supportPhone != 'Not available')
                &nbsp;&bull;&nbsp;
                <a href="tel:{{ $supportPhone }}">{{ $supportPhone }}</a>
                @endif
                <p>{{ $supportHours }}</p>
            </div>

            <div class="signature">
                <p>Best regards,</p>
                <p class="team-name">STAFFOO</p>
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
            <p>This is an automated response. Please do not reply to this email.</p>
            <p>&copy; {{ $year }} STAFFOO. All rights reserved.</p>
            <p style="font-size: 11px; margin-top: 10px;">{{ $appUrl }}</p>
        </div>
    </div>
</body>
</html>