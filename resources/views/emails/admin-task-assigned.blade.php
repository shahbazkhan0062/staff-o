<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, Helvetica, sans-serif; background-color: #f4f4f7; margin: 0; padding: 0; color: #333333; }
        .wrapper { max-width: 600px; margin: 0 auto; padding: 24px 16px; }
        .card { background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .header { background-color: #0a7c6e; color: #ffffff; padding: 24px; text-align: center; }
        .header h1 { margin: 0; font-size: 20px; }
        .body { padding: 24px; }
        .body p { font-size: 15px; line-height: 1.6; margin: 0 0 16px; }
        .task-details { background-color: #f9f9fb; border-left: 4px solid #0a7c6e; padding: 16px; border-radius: 4px; margin: 16px 0; }
        .task-details table { width: 100%; border-collapse: collapse; }
        .task-details td { padding: 4px 0; font-size: 14px; vertical-align: top; }
        .task-details td.label { color: #777777; width: 30%; }
        .task-details td.value { font-weight: bold; color: #0a7c6e; }
        .status-pill { display: inline-block; background-color: #fef3c7; color: #92400e; font-size: 12px; font-weight: bold; padding: 4px 10px; border-radius: 12px; text-transform: uppercase; }
        .footer { text-align: center; padding: 16px; font-size: 12px; color: #999999; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header">
                <h1>New Task Assigned</h1>
            </div>
            <div class="body">
                <p>Hi {{ $task->assignedTo->name ?? 'there' }},</p>
                <p><strong>{{ $task->assignedBy->name ?? 'An admin' }}</strong> has assigned you a new task on the Staffoo admin panel.</p>

                <div class="task-details">
                    <table>
                        <tr>
                            <td class="label">Task</td>
                            <td class="value">{{ $task->title }}</td>
                        </tr>
                        @if($task->description)
                        <tr>
                            <td class="label">Details</td>
                            <td class="value" style="color:#333333; font-weight:normal;">{{ $task->description }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="label">Assigned By</td>
                            <td class="value">{{ $task->assignedBy->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Status</td>
                            <td class="value"><span class="status-pill">Pending</span></td>
                        </tr>
                    </table>
                </div>

                <p>Please log in to the admin panel to review and complete this task. You'll get a daily reminder email for as long as it stays pending.</p>
            </div>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Staffoo. All rights reserved.<br>
            This is an automated notification — please do not reply to this email.
        </div>
    </div>
</body>
</html>
