<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillComment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'bill_id',
        'user_id',
        'parent_id',
        'comment',
        'likes_count',
        'dislikes_count',
    ];

    protected $casts = [
        'likes_count' => 'integer',
        'dislikes_count' => 'integer',
    ];

    /**
     * Get the bill that owns the comment.
     */
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    /**
     * Get the user that wrote the comment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent comment (if this is a reply).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(BillComment::class, 'parent_id');
    }

    /**
     * Get the replies to this comment.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(BillComment::class, 'parent_id')->orderBy('created_at', 'asc');
    }

    /**
     * Get the reactions for this comment.
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(BillCommentReaction::class, 'comment_id');
    }

    /**
     * Check if a user has reacted to this comment.
     */
    public function getUserReaction($userId): ?string
    {
        $reaction = $this->reactions()->where('user_id', $userId)->first();
        return $reaction ? $reaction->reaction : null;
    }
}

