<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppChat extends Model
{
    protected $fillable = ['user_id', 'chat_id', 'status', 'last_message_at', 'message_count'];

    protected $casts = ['last_message_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isStale(int $ttlMinutes): bool
    {
        if (!$this->last_message_at) {
            return true;
        }

        return $this->last_message_at->diffInMinutes(now()) >= $ttlMinutes;
    }
}