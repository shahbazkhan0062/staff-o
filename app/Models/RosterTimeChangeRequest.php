<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RosterTimeChangeRequest extends Model
{
    protected $table = 'roster_time_change_requests';

    protected $fillable = [
        'job_roster_id',
        'requested_by',
        'case_no',
        'old_start',
        'old_end',
        'new_start',
        'new_end',
        'extra_hours',
        'status',
        'responded_at',
    ];

    public function jobRoster()
    {
        return $this->belongsTo(JobRoster::class, 'job_roster_id', 'id');
    }
}
