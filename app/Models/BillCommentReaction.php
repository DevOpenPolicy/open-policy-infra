<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillCommentReaction extends Model
{
    protected $fillable = [
        'comment_id',
        'user_id',
        'reaction',
    ];

    /**
     * Get the comment that owns the reaction.
     */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(BillComment::class, 'comment_id');
    }

    /**
     * Get the user that made the reaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

