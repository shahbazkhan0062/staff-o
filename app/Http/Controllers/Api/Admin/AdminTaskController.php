<?php

namespace App\Http\Controllers\Api\Admin;

use App\Events\DynamicUserNotification;
use App\Http\Controllers\Controller;
use App\Mail\AdminTaskAssignedMail;
use App\Mail\AdminTaskCompletedMail;
use App\Models\AdminTask;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Admin-to-admin task assignment.
 *
 * One admin assigns a task to another admin (assigned_to). The task starts
 * "pending" and an email goes to the assignee. When the assignee marks it
 * "completed", an email goes back to whoever assigned it. Pending tasks for
 * the logged-in admin are also surfaced on the admin dashboard via
 * pendingDashboard(), and they get a daily reminder email for anything still
 * pending (see App\Console\Commands\SendAdminTaskReminders).
 */
class AdminTaskController extends Controller
{
    /**
     * List tasks for the logged-in admin.
     * ?box=assigned_to_me (default) | assigned_by_me | all
     * ?status=pending | completed
     */
    public function index(Request $request)
    {
        $admin = $request->user();

        if (!$admin || $admin->user_type !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $box = $request->query('box', 'assigned_to_me');

        $query = AdminTask::with(['assignedBy:id,name,email', 'assignedTo:id,name,email'])
            ->orderByDesc('created_at');

        if ($box === 'assigned_by_me') {
            $query->where('assigned_by', $admin->id);
        } elseif ($box === 'all') {
            $query->where(function ($q) use ($admin) {
                $q->where('assigned_by', $admin->id)->orWhere('assigned_to', $admin->id);
            });
        } else {
            $query->where('assigned_to', $admin->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        return response()->json([
            'success' => true,
            'data'    => $query->get(),
        ]);
    }

    /**
     * View a single task (title, description, both admins, status, dates).
     */
    public function show(Request $request, $id)
    {
        $admin = $request->user();

        if (!$admin || $admin->user_type !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $task = AdminTask::with(['assignedBy:id,name,email', 'assignedTo:id,name,email'])->findOrFail($id);

        if ((int) $admin->id !== (int) $task->assigned_to && (int) $admin->id !== (int) $task->assigned_by) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json(['success' => true, 'data' => $task]);
    }

    /**
     * Pending tasks assigned to the logged-in admin, for the dashboard widget.
     */
    public function pendingDashboard(Request $request)
    {
        $admin = $request->user();

        if (!$admin || $admin->user_type !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $tasks = AdminTask::with(['assignedBy:id,name,email'])
            ->where('assigned_to', $admin->id)
            ->pending()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $count = AdminTask::where('assigned_to', $admin->id)->pending()->count();

        return response()->json([
            'success' => true,
            'data'    => [
                'pending_count' => $count,
                'tasks'         => $tasks,
            ],
        ]);
    }

    /**
     * Admin A assigns a task to Admin B. Sends Admin B an email.
     */
    public function store(Request $request)
    {
        $admin = $request->user();

        if (!$admin || $admin->user_type !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'required|integer|exists:users,id',
        ]);

        $assignee = User::where('id', $data['assigned_to'])->where('user_type', 'admin')->first();

        if (!$assignee) {
            return response()->json(['success' => false, 'message' => 'The selected user is not an admin.'], 422);
        }

        if ((int) $assignee->id === (int) $admin->id) {
            return response()->json(['success' => false, 'message' => 'You cannot assign a task to yourself.'], 422);
        }

        $task = AdminTask::create([
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'assigned_by' => $admin->id,
            'assigned_to' => $assignee->id,
            'status'      => 'pending',
        ]);

        $task->load(['assignedBy:id,name,email', 'assignedTo:id,name,email']);

        try {
            Mail::to($assignee->email)->send(new AdminTaskAssignedMail($task));
        } catch (\Exception $e) {
            Log::error('AdminTaskAssignedMail failed', ['task_id' => $task->id, 'error' => $e->getMessage()]);
        }

        $this->notifyAdmin(
            $assignee->id,
            'New Task Assigned',
            $admin->name . ' assigned you a task: ' . $task->title,
            ['admin_task_id' => $task->id],
        );

        return response()->json([
            'success' => true,
            'message' => 'Task assigned to ' . $assignee->name . '.',
            'data'    => $task,
        ], 201);
    }

    /**
     * Edit a task's title/description/assignee. Only the admin who created
     * the task can edit it, and only while it's still pending.
     */
    public function update(Request $request, $id)
    {
        $admin = $request->user();

        if (!$admin || $admin->user_type !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $task = AdminTask::findOrFail($id);

        if ((int) $admin->id !== (int) $task->assigned_by) {
            return response()->json(['success' => false, 'message' => 'Only the admin who assigned this task can edit it.'], 403);
        }

        if ($task->status === 'completed') {
            return response()->json(['success' => false, 'message' => 'A completed task cannot be edited.'], 422);
        }

        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'required|integer|exists:users,id',
        ]);

        $assignee = User::where('id', $data['assigned_to'])->where('user_type', 'admin')->first();

        if (!$assignee) {
            return response()->json(['success' => false, 'message' => 'The selected user is not an admin.'], 422);
        }

        if ((int) $assignee->id === (int) $admin->id) {
            return response()->json(['success' => false, 'message' => 'You cannot assign a task to yourself.'], 422);
        }

        $reassigned = (int) $task->assigned_to !== (int) $assignee->id;

        $task->title       = $data['title'];
        $task->description = $data['description'] ?? null;
        $task->assigned_to = $assignee->id;
        $task->save();

        $task->load(['assignedBy:id,name,email', 'assignedTo:id,name,email']);

        // Reassigning to a different admin is treated like a fresh assignment.
        if ($reassigned) {
            try {
                Mail::to($assignee->email)->send(new AdminTaskAssignedMail($task));
            } catch (\Exception $e) {
                Log::error('AdminTaskAssignedMail failed', ['task_id' => $task->id, 'error' => $e->getMessage()]);
            }

            $this->notifyAdmin(
                $assignee->id,
                'Task Assigned To You',
                $admin->name . ' assigned you a task: ' . $task->title,
                ['admin_task_id' => $task->id],
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Task updated.',
            'data'    => $task,
        ]);
    }

    /**
     * Delete a task. Only the admin who created it can delete it.
     */
    public function destroy(Request $request, $id)
    {
        $admin = $request->user();

        if (!$admin || $admin->user_type !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $task = AdminTask::findOrFail($id);

        if ((int) $admin->id !== (int) $task->assigned_by) {
            return response()->json(['success' => false, 'message' => 'Only the admin who assigned this task can delete it.'], 403);
        }

        $task->delete();

        return response()->json(['success' => true, 'message' => 'Task deleted.']);
    }

    /**
     * Mark a task completed (or back to pending). Completing it emails
     * whoever assigned it.
     */
    public function updateStatus(Request $request, $id)
    {
        $admin = $request->user();

        if (!$admin || $admin->user_type !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'status' => 'required|in:pending,completed',
        ]);

        $task = AdminTask::with(['assignedBy:id,name,email', 'assignedTo:id,name,email'])->findOrFail($id);

        // Only the two admins involved in the task may change its status.
        if ((int) $admin->id !== (int) $task->assigned_to && (int) $admin->id !== (int) $task->assigned_by) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $wasPending = $task->isPending();

        if ($data['status'] === 'completed') {
            $task->markCompleted();

            if ($wasPending) {
                try {
                    Mail::to($task->assignedBy->email)->send(new AdminTaskCompletedMail($task));
                } catch (\Exception $e) {
                    Log::error('AdminTaskCompletedMail failed', ['task_id' => $task->id, 'error' => $e->getMessage()]);
                }

                $this->notifyAdmin(
                    (int) $task->assigned_by,
                    'Task Completed',
                    ($task->assignedTo->name ?? 'The assignee') . ' completed the task: ' . $task->title,
                    ['admin_task_id' => $task->id],
                );
            }
        } else {
            $task->status = 'pending';
            $task->completed_at = null;
            $task->save();
        }

        return response()->json([
            'success' => true,
            'message' => $data['status'] === 'completed' ? 'Task marked as completed.' : 'Task reopened.',
            'data'    => $task,
        ]);
    }

    /**
     * Push a real-time notification (Pusher, via the existing "notifications.{userId}"
     * private channel) and save it so it shows up in the header bell's notification
     * list, same as every other in-app notification — see
     * App\Http\Controllers\Api\NotificationController and
     * App\Console\Commands\CheckDocumentExpiry for the same pattern.
     */
    private function notifyAdmin(int $userId, string $title, string $message, array $data = []): void
    {
        try {
            broadcast(new DynamicUserNotification($userId, $message, $title, $data, 'info'));

            Notification::create([
                'receiver_id' => null,
                'guard_id'    => $userId,
                'title'       => $title,
                'message'     => $message,
                'type'        => 'info',
                'data'        => $data,
                'read_at'     => null,
            ]);
        } catch (\Exception $e) {
            Log::error('Admin task notification failed', ['user_id' => $userId, 'error' => $e->getMessage()]);
        }
    }
}
