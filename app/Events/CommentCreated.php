<?php

namespace App\Events;

use App\Models\BillComment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $comment;

    /**
     * Create a new event instance.
     */
    public function __construct(BillComment $comment)
    {
        $this->comment = $comment->load('user:id,first_name,last_name,dp');
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('bill.' . $this->comment->bill_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'comment.created';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        $user = $this->comment->user;
        
        return [
            'id' => $this->comment->id,
            'bill_id' => $this->comment->bill_id,
            'user_id' => $this->comment->user_id,
            'user_name' => $user ? ($user->first_name . ' ' . ($user->last_name ?? '')) : 'Anonymous',
            'user_avatar' => $user?->dp,
            'comment' => $this->comment->comment,
            'parent_id' => $this->comment->parent_id,
            'likes' => $this->comment->likes_count,
            'dislikes' => $this->comment->dislikes_count,
            'timestamp' => $this->comment->created_at->toISOString(),
        ];
    }
}

