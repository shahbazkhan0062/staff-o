<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, Helvetica, sans-serif; background-color: #f4f4f7; margin: 0; padding: 0; color: #333333; }
        .wrapper { max-width: 600px; margin: 0 auto; padding: 24px 16px; }
        .card { background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .header { background-color: #b45309; color: #ffffff; padding: 24px; text-align: center; }
        .header h1 { margin: 0; font-size: 20px; }
        .body { padding: 24px; }
        .body p { font-size: 15px; line-height: 1.6; margin: 0 0 16px; }
        table.tasks { width: 100%; border-collapse: collapse; margin: 16px 0; }
        table.tasks th { text-align: left; font-size: 12px; color: #777777; text-transform: uppercase; border-bottom: 2px solid #eeeeee; padding: 8px 6px; }
        table.tasks td { font-size: 14px; padding: 10px 6px; border-bottom: 1px solid #eeeeee; vertical-align: top; }
        .status-pill { display: inline-block; background-color: #fef3c7; color: #92400e; font-size: 11px; font-weight: bold; padding: 3px 8px; border-radius: 12px; text-transform: uppercase; }
        .footer { text-align: center; padding: 16px; font-size: 12px; color: #999999; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header">
                <h1>Pending Task Reminder</h1>
            </div>
            <div class="body">
                <p>Hi {{ $adminName }},</p>
                <p>You have <strong>{{ $tasks->count() }}</strong> task(s) still marked as pending on the Staffoo admin panel:</p>

                <table class="tasks">
                    <thead>
                        <tr>
                            <th>Task</th>
                            <th>Assigned By</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tasks as $task)
                        <tr>
                            <td>{{ $task->title }}</td>
                            <td>{{ $task->assignedBy->name ?? 'N/A' }}</td>
                            <td><span class="status-pill">Pending</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <p>Please log in to the admin panel to complete or update these tasks.</p>
            </div>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Staffoo. All rights reserved.<br>
            This is an automated daily reminder — please do not reply to this email.
        </div>
    </div>
</body>
</html>
