<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Cancellation Notice</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: #ffffff;
            padding: 20px 30px;
            text-align: center;
        }
        .header img {
            max-height: 60px;
            width: auto;
        }
        .content {
            padding: 30px;
        }
        .greeting {
            font-size: 24px;
            color: #1a237e;
            margin-bottom: 20px;
        }
        .message {
            color: #333;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .job-details {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .job-details p {
            margin: 8px 0;
        }
        .label {
            font-weight: bold;
            color: #555;
        }
        .alert-box {
            background: #fff3e0;
            border-left: 4px solid #ff6f00;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .footer {
            background: #f5f5f5;
            padding: 20px;
            text-align: center;
            color: #777;
            font-size: 14px;
        }
        .footer a {
            color: #1a237e;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header with Logo -->
        <div class="header">
            <img src="https://apis.staffoo.com.au/uploads/staffologo.png" alt="Staffoo Logo" style="max-width: 180px; height: auto; display: inline-block;">
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">
                Dear {{ $recipient_name }},
            </div>

            <div class="message">
                <p>I hope this message finds you well.</p>
                <p>Please be advised that the job you {{ $role_label === 'resource partner' ? 'accepted' : 'were assigned' }} for the time slot <strong>{{ $start_time }} to {{ $end_time }}</strong> on {{ $date }} has been cancelled at the client's request. We apologise for any inconvenience this may cause.</p>
            </div>

            <!-- Job Details -->
            <div class="job-details">
                <h3 style="margin-top: 0; color: #1a237e;">Job Details</h3>
                <p><span class="label">Job ID:</span> #{{ $job_id }}</p>
                <p><span class="label">Site:</span> {{ $site_name }}</p>
                <p><span class="label">Date:</span> {{ $date }}</p>
                <p><span class="label">Time:</span> {{ $start_time }} – {{ $end_time }}</p>
            </div>

            <div class="alert-box">
                <p>Thank you for {{ $role_label === 'resource partner' ? 'accepting the assignment' : 'being ready for this shift' }}. We truly appreciate your support as a {{ $role_label }} and look forward to working together. We will be in touch to arrange more jobs for you in the near future.</p>
            </div>

            <div class="message">
                <p>Thank you for your continued cooperation and support.</p>
                <p>Best regards,<br>
                <strong>Staffoo Team</strong></p>
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
            <p>&copy; {{ date('Y') }} Staffoo. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
