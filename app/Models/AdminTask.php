<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'assigned_by',
        'assigned_to',
        'status',
        'completed_at',
        'last_reminder_sent_at',
        'assigned_by_last_viewed_at',
        'assigned_to_last_viewed_at',
    ];

    protected $casts = [
        'completed_at'                => 'datetime',
        'last_reminder_sent_at'       => 'datetime',
        'assigned_by_last_viewed_at'  => 'datetime',
        'assigned_to_last_viewed_at'  => 'datetime',
    ];

    /**
     * Marks this task as viewed "now" by whichever of the two admins
     * $userId is. Used both when a task is opened (show) and when a
     * comment is posted (posting your own comment counts as viewing it).
     */
    public function markViewedBy(int $userId): void
    {
        if ((int) $userId === (int) $this->assigned_by) {
            $this->assigned_by_last_viewed_at = now();
            $this->save();
        } elseif ((int) $userId === (int) $this->assigned_to) {
            $this->assigned_to_last_viewed_at = now();
            $this->save();
        }
    }

    /**
     * Whether $userId has an unseen comment on this task — i.e. the newest
     * comment was posted after they last viewed it. Relies on the
     * comments_max_created_at attribute that AdminTaskController@index
     * loads via withMax('comments', 'created_at').
     */
    public function hasNewCommentFor(int $userId): bool
    {
        $latestCommentAt = $this->comments_max_created_at ?? null;

        if (!$latestCommentAt) {
            return false;
        }

        $viewedAt = (int) $userId === (int) $this->assigned_by
            ? $this->assigned_by_last_viewed_at
            : $this->assigned_to_last_viewed_at;

        if (!$viewedAt) {
            return true;
        }

        return \Carbon\Carbon::parse($latestCommentAt)->gt($viewedAt);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function comments()
    {
        return $this->hasMany(AdminTaskComment::class, 'admin_task_id')->orderBy('created_at');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function markCompleted(): void
    {
        $this->status = 'completed';
        $this->completed_at = now();
        $this->save();
    }
}
