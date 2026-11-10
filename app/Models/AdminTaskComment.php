<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminTaskComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_task_id',
        'commented_by',
        'comment',
    ];

    public function task()
    {
        return $this->belongsTo(AdminTask::class, 'admin_task_id');
    }

    public function commentedBy()
    {
        return $this->belongsTo(User::class, 'commented_by');
    }
}
