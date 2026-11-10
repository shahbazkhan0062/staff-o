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
        .comment-box { background-color: #f1f5f9; border-radius: 6px; padding: 16px; margin: 16px 0; font-size: 14.5px; color: #1e293b; white-space: pre-wrap; }
        .footer { text-align: center; padding: 16px; font-size: 12px; color: #999999; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header">
                <h1>New Comment On Your Task</h1>
            </div>
            <div class="body">
                <p><strong>{{ $comment->commentedBy->name ?? 'An admin' }}</strong> commented on a task:</p>

                <div class="task-details">
                    <table>
                        <tr>
                            <td class="label">Task</td>
                            <td class="value">{{ $task->title }}</td>
                        </tr>
                        <tr>
                            <td class="label">Status</td>
                            <td class="value">{{ ucfirst($task->status) }}</td>
                        </tr>
                    </table>
                </div>

                <div class="comment-box">{{ $comment->comment }}</div>

                <p>Please log in to the admin panel to reply.</p>
            </div>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Staffoo. All rights reserved.<br>
            This is an automated notification — please do not reply to this email.
        </div>
    </div>
</body>
</html>
