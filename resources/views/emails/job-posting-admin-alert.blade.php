<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Job Posted - Admin Alert</title>
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
            background: #1a237e;
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
        .footer {
            background: #f5f5f5;
            padding: 20px;
            text-align: center;
            color: #777;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="https://apis.staffoo.com.au/uploads/staffologo.png" alt="Staffoo Logo" style="max-width: 180px; height: auto; display: inline-block;">
        </div>

        <div class="content">
            <div class="greeting">📋 Admin Alert — New Job Posted</div>

            <div class="message">
                <p>A new job has just been posted on the platform.</p>
            </div>

            <div class="job-details">
                <h3 style="margin-top: 0; color: #1a237e;">Job Information</h3>
                <p><span class="label">Client:</span> {{ $client_name }}</p>
                <p><span class="label">Site:</span> {{ $site_name }}</p>
                <p><span class="label">Shifts Posted:</span> {{ $shift_count }}</p>
                <p><span class="label">Job ID(s):</span> #{{ $job_ids }}</p>
                <p><span class="label">Posted On:</span> {{ $posted_at }}</p>
            </div>

            <div class="message">
                <p>Best regards,<br>
                <strong>Staffoo System</strong></p>
            </div>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} Staffoo. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
