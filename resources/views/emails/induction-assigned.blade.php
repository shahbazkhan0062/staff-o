<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Induction Assigned</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f7fa;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            padding: 30px 0 20px 0;
            border-bottom: 2px solid #e8edf3;
        }
        .logo {
            max-width: 180px;
            height: auto;
        }
        .content {
            padding: 30px 20px;
            color: #333333;
        }
        .greeting {
            font-size: 24px;
            font-weight: 600;
            color: #0a7c6e;
            margin-bottom: 20px;
        }
        .message {
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        .induction-box {
            background-color: #f8f9fa;
            border-left: 4px solid #0a7c6e;
            padding: 20px;
            margin: 25px 0;
            border-radius: 6px;
        }
        .induction-box h3 {
            margin-top: 0;
            color: #1a237e;
            font-size: 16px;
        }
        .induction-box ul {
            margin: 8px 0 0 0;
            padding-left: 20px;
        }
        .induction-box li {
            font-size: 15px;
            line-height: 1.8;
        }
        .cta-section {
            text-align: center;
            margin: 30px 0;
        }
        .btn {
            display: inline-block;
            background-color: #0a7c6e;
            color: #ffffff !important;
            padding: 14px 35px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
        }
        .footer {
            text-align: center;
            padding: 20px;
            border-top: 2px solid #e8edf3;
            color: #6c757d;
            font-size: 14px;
        }
        .footer a {
            color: #0a7c6e;
            text-decoration: none;
        }
        .social-links {
            margin-top: 15px;
        }
        .social-links a {
            margin: 0 10px;
            color: #6c757d;
            text-decoration: none;
        }
        .support {
            margin-top: 15px;
            font-size: 13px;
        }
        @media only screen and (max-width: 480px) {
            .container { padding: 15px; }
            .greeting { font-size: 20px; }
            .content { padding: 20px 15px; }
            .btn { padding: 12px 25px; font-size: 14px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header with Logo -->
        <div class="header" style="text-align: center; padding: 30px 0 20px 0; border-bottom: 2px solid #e8edf3;">
            <img src="https://apis.staffoo.com.au/uploads/staffologo.png" alt="Staffoo Logo" style="max-width: 180px; height: auto; display: inline-block;">
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">
                New Induction{{ count($inductionTitles) > 1 ? 's' : '' }} Assigned, {{ $userName }} 📋
            </div>

            <div class="message">
                <p>{{ count($inductionTitles) > 1 ? 'The following inductions have' : 'The following induction has' }} been assigned to you. Please log in to the app or portal to complete {{ count($inductionTitles) > 1 ? 'them' : 'it' }}.</p>
            </div>

            <!-- Induction Details -->
            <div class="induction-box">
                <h3>📋 Assigned to You</h3>
                <ul>
                    @foreach ($inductionTitles as $title)
                        <li>{{ $title }}</li>
                    @endforeach
                </ul>
            </div>

            <div class="message" style="margin-top: 25px;">
                <p style="font-style: italic; color: #555;"> A score of 80% or higher is required to pass. If you don't pass on your first attempt, you'll be able to try again. </p><p style="font-style: italic; color: #555;"> Read the complete documents attached with the induction, and complete all the MCQs and true/false questions. </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <!-- App download badges -->
            <table role="presentation" align="center" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto; padding:20px 0 10px 0;">
                <tr>
                    <td style="padding:0 5px;"><a href="https://apps.apple.com/pk/app/staffoo/id6798964069" target="_blank"><img src="https://apis.staffoo.com.au/uploads/app-store-badge.png" alt="Download on the App Store" width="120" height="41" style="display:block; border:0; width:120px; height:auto;"></a></td>
                    <td style="padding:0 5px;"><a href="https://play.google.com/store/apps/details?id=com.staffoo" target="_blank"><img src="https://apis.staffoo.com.au/uploads/google-play-badge.png" alt="Get it on Google Play" width="120" height="41" style="display:block; border:0; width:120px; height:auto;"></a></td>
                </tr>
            </table>
            <p style="margin: 0;">
                &copy; {{ date('Y') }} Staffoo. All rights reserved.
            </p>
            <div class="social-links">
                <a href="https://staffoo.com.au">Website</a> |
                <a href="mailto:admin@staffoo.com.au">Support</a> |
                <a href="https://staffoo.com.au/privacy-policy">Privacy Policy</a>
            </div>
            <div class="support">
                <p style="margin: 5px 0;">
                    Need help? Contact us at
                    <a href="mailto:admin@staffoo.com.au">admin@staffoo.com.au</a>
                </p>
                <p style="margin: 5px 0; font-size: 12px; color: #999;">
                    This is an automated message, please do not reply directly to this email.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
