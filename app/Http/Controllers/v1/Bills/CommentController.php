<?php

namespace App\Http\Controllers\v1\Bills;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\BillComment;
use App\Models\BillCommentReaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class CommentController extends Controller
{
    /**
     * Safely broadcast an event, catching any broadcasting errors.
     * This prevents queue failures when broadcasting is not configured.
     */
    private function safeBroadcast($event)
    {
        try {
            // Only broadcast if broadcasting is configured (not 'null')
            if (config('broadcasting.default') !== 'null') {
                event($event);
            }
        } catch (\Exception $e) {
            // Silently fail if broadcasting is not configured
            // This prevents queue failures when Pusher/WebSocket is not set up
            \Log::warning('Broadcasting failed: ' . get_class($event) . ' - ' . $e->getMessage());
        }
    }
    /**
     * Get comments for a bill.
     */
    public function getComments($billId, Request $request)
    {
        $sortBy = $request->get('sort', 'top'); // 'top' or 'newest'
        
        $cacheKey = "bill_comments_{$billId}_{$sortBy}";
        
        $comments = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($billId, $sortBy) {
            $query = BillComment::with(['user:id,first_name,last_name,dp', 'replies.user:id,first_name,last_name,dp'])
                ->where('bill_id', $billId)
                ->whereNull('parent_id'); // Only top-level comments

            if ($sortBy === 'newest') {
                $query->orderBy('created_at', 'desc');
            } else {
                // Sort by likes (top comments)
                $query->orderBy('likes_count', 'desc')
                      ->orderBy('created_at', 'desc');
            }

            return $query->get()->map(function ($comment) {
                return $this->formatComment($comment);
            });
        });

        // Check for authentication token even though route is public
        // This allows us to include user_reaction for authenticated users
        $userId = null;
        
        // Check if bearer token is present
        $token = $request->bearerToken();
        if ($token) {
            try {
                // Manually validate Sanctum token
                $personalAccessToken = PersonalAccessToken::findToken($token);
                if ($personalAccessToken) {
                    // Check if token is not expired (if it has expiration)
                    $isValid = !$personalAccessToken->expires_at || $personalAccessToken->expires_at->isFuture();
                    if ($isValid) {
                        $user = $personalAccessToken->tokenable;
                        $userId = $user ? $user->id : null;
                    }
                }
            } catch (\Exception $e) {
                // Token invalid or expired, continue without user
                $userId = null;
            }
        }
        
        // Fallback: Try default Auth (for session-based auth or if Sanctum guard is already set)
        if (!$userId) {
            $userId = Auth::guard('sanctum')->id() ?? Auth::id();
        }

        if ($userId) {
            // Add user reactions to comments
            $comments = $comments->map(function ($comment) use ($userId) {
                $comment['user_reaction'] = $this->getUserReaction($comment['id'], $userId);
                if (isset($comment['replies'])) {
                    $comment['replies'] = collect($comment['replies'])->map(function ($reply) use ($userId) {
                        $reply['user_reaction'] = $this->getUserReaction($reply['id'], $userId);
                        return $reply;
                    })->toArray();
                }
                return $comment;
            });
        }

        return response()->json([
            'success' => true,
            'data' => $comments,
        ], 200);
    }

    /**
     * Create a new comment.
     */
    public function createComment(Request $request)
    {
        $request->validate([
            'bill_id' => 'required|exists:bills,id',
            'comment' => 'required|string|max:5000',
            'parent_id' => 'nullable|exists:bill_comments,id',
        ]);

        $user = Auth::user();
        
        $comment = BillComment::create([
            'bill_id' => $request->bill_id,
            'user_id' => $user->id,
            'parent_id' => $request->parent_id,
            'comment' => $request->comment,
        ]);

        // Clear cache
        Cache::forget("bill_comments_{$request->bill_id}_top");
        Cache::forget("bill_comments_{$request->bill_id}_newest");

        // Clear cache
        Cache::forget("bill_comments_{$request->bill_id}_top");
        Cache::forget("bill_comments_{$request->bill_id}_newest");

        // Broadcast event for real-time update
        $this->safeBroadcast(new \App\Events\CommentCreated($comment));

        $formattedComment = $this->formatComment($comment->load('user:id,first_name,last_name,dp'));
        // Add user reaction (will be null for new comment)
        $formattedComment['user_reaction'] = null;

        return response()->json([
            'success' => true,
            'message' => 'Comment created successfully',
            'data' => $formattedComment,
        ], 201);
    }

    /**
     * Update a comment.
     */
    public function updateComment($commentId, Request $request)
    {
        $request->validate([
            'comment' => 'required|string|max:5000',
        ]);

        $user = Auth::user();
        $comment = BillComment::findOrFail($commentId);

        // Check if user owns the comment
        if ($comment->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $comment->comment = $request->comment;
        $comment->save();

        // Clear cache
        Cache::forget("bill_comments_{$comment->bill_id}_top");
        Cache::forget("bill_comments_{$comment->bill_id}_newest");

        // Broadcast event
        $this->safeBroadcast(new \App\Events\CommentUpdated($comment));

        return response()->json([
            'success' => true,
            'message' => 'Comment updated successfully',
            'data' => $this->formatComment($comment->load('user:id,first_name,last_name,dp')),
        ], 200);
    }

    /**
     * Delete a comment.
     */
    public function deleteComment($commentId)
    {
        $user = Auth::user();
        $comment = BillComment::findOrFail($commentId);

        // Check if user owns the comment
        if ($comment->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $billId = $comment->bill_id;
        $comment->delete();

        // Clear cache
        Cache::forget("bill_comments_{$billId}_top");
        Cache::forget("bill_comments_{$billId}_newest");

        // Broadcast event
        $this->safeBroadcast(new \App\Events\CommentDeleted($commentId, $billId));

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted successfully',
        ], 200);
    }

    /**
     * React to a comment (like/dislike).
     */
    public function reactToComment($commentId, Request $request)
    {
        $request->validate([
            'reaction' => 'required|in:like,dislike',
        ]);

        $user = Auth::user();
        $comment = BillComment::findOrFail($commentId);

        DB::transaction(function () use ($comment, $user, $request) {
            $existingReaction = BillCommentReaction::where('comment_id', $comment->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existingReaction) {
                // If same reaction, remove it
                if ($existingReaction->reaction === $request->reaction) {
                    $this->decrementReactionCount($comment, $request->reaction);
                    $existingReaction->delete();
                    return;
                } else {
                    // Change reaction
                    $this->decrementReactionCount($comment, $existingReaction->reaction);
                    $existingReaction->reaction = $request->reaction;
                    $existingReaction->save();
                    $this->incrementReactionCount($comment, $request->reaction);
                    return;
                }
            }

            // Create new reaction
            BillCommentReaction::create([
                'comment_id' => $comment->id,
                'user_id' => $user->id,
                'reaction' => $request->reaction,
            ]);

            $this->incrementReactionCount($comment, $request->reaction);
        });

        $comment->refresh();

        // Clear cache
        Cache::forget("bill_comments_{$comment->bill_id}_top");
        Cache::forget("bill_comments_{$comment->bill_id}_newest");

        // Broadcast event
        $this->safeBroadcast(new \App\Events\CommentReactionUpdated($comment));

        return response()->json([
            'success' => true,
            'message' => 'Reaction updated successfully',
            'data' => [
                'likes_count' => $comment->likes_count,
                'dislikes_count' => $comment->dislikes_count,
                'user_reaction' => $this->getUserReaction($comment->id, $user->id),
            ],
        ], 200);
    }

    /**
     * Format comment for API response.
     */
    private function formatComment($comment)
    {
        $user = $comment->user;
        $replies = $comment->replies ? $comment->replies->map(function ($reply) {
            return $this->formatComment($reply);
        })->toArray() : [];

        return [
            'id' => $comment->id,
            'user_id' => $comment->user_id,
            'user_name' => $user ? ($user->first_name . ' ' . ($user->last_name ?? '')) : 'Anonymous',
            'user_avatar' => $user?->dp,
            'comment' => $comment->comment,
            'likes' => $comment->likes_count,
            'dislikes' => $comment->dislikes_count,
            'timestamp' => $comment->created_at->toISOString(),
            'replies' => $replies,
        ];
    }

    /**
     * Get user reaction for a comment.
     */
    private function getUserReaction($commentId, $userId)
    {
        $reaction = BillCommentReaction::where('comment_id', $commentId)
            ->where('user_id', $userId)
            ->first();

        return $reaction ? $reaction->reaction : null;
    }

    /**
     * Increment reaction count.
     */
    private function incrementReactionCount($comment, $reaction)
    {
        if ($reaction === 'like') {
            $comment->increment('likes_count');
        } else {
            $comment->increment('dislikes_count');
        }
    }

    /**
     * Decrement reaction count.
     */
    private function decrementReactionCount($comment, $reaction)
    {
        if ($reaction === 'like') {
            $comment->decrement('likes_count');
        } else {
            $comment->decrement('dislikes_count');
        }
    }
}

