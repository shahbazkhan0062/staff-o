<?php

namespace App\Jobs;

use App\Events\MessageEvent;
use App\Models\User;
use App\Services\AgoraService;
use App\Services\AppChatService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendAppChatAutoReplyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $senderId,
        private int $adminId,
        private string $body,
    ) {
    }

    public function handle(AppChatService $appChat, AgoraService $agora): void
    {
        $sender = User::find($this->senderId);

        if (!$sender) {
            return;
        }

        try {
            $reply = $appChat->reply($sender, $this->body);
        } catch (\Throwable $e) {
            Log::error('[AppChat] auto-reply failed', [
                'user_id' => $this->senderId,
                'error'   => $e->getMessage(),
            ]);
            $reply = "Sorry, something went wrong on our side. Please try again in a moment.";
        }

        if ($reply === '') {
            return; // agent ran a tool but had nothing to say — no empty bubble
        }

        // Reuse the same path MessageController uses for a normal message,
        // so channel_name etc. stay consistent — this is just the admin
        // "replying" to the sender.
        $message = $agora->saveMessage($this->adminId, $this->senderId, $reply);

        broadcast(new MessageEvent($message->load('sender')));
    }
}