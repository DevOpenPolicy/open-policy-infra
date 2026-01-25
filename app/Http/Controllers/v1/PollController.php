<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PollController extends Controller
{
    /**
     * Create a new poll with options.
     */
    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'options' => 'required|array|min:2',
            'options.*.option_text' => 'required|string',
            'options.*.color' => 'nullable|string',
            'options.*.order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $poll = Poll::create([
                'user_id' => auth()->id(),
                'title' => $request->title,
            ]);

            foreach ($request->options as $index => $optionData) {
                PollOption::create([
                    'poll_id' => $poll->id,
                    'option_text' => $optionData['option_text'],
                    'color' => $optionData['color'] ?? null,
                    'order' => $optionData['order'] ?? $index,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Poll created successfully',
                'poll' => $poll->load('options'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create poll', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all polls with options.
     */
    public function index()
    {
        $polls = Poll::with('options')->latest()->get();
        return response()->json($polls);
    }

    /**
     * Get polls created by a specific user.
     */
    public function getByUser($userId)
    {
        $polls = Poll::where('user_id', $userId)->with('options')->latest()->get();
        return response()->json($polls);
    }

    /**
     * Vote on a poll option.
     */
    public function vote(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'poll_id' => 'required|exists:polls,id',
            'poll_option_id' => 'required|exists:poll_options,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = auth()->user();
        $pollId = $request->poll_id;
        $optionId = $request->poll_option_id;

        // Check if correct option for poll
        $option = PollOption::where('id', $optionId)->where('poll_id', $pollId)->first();
        if (!$option) {
            return response()->json(['message' => 'Invalid option for this poll'], 422);
        }

        // Check if user already voted using the unique constraint logic
        // We can just try-catch or check existence
        $existingVote = PollVote::where('user_id', $user->id)->where('poll_id', $pollId)->first();
        if ($existingVote) {
             return response()->json(['message' => 'You have already voted on this poll'], 403);
        }

        try {
            DB::beginTransaction();

            PollVote::create([
                'poll_id' => $pollId,
                'poll_option_id' => $optionId,
                'user_id' => $user->id,
            ]);

            // Increment vote count
            $option->increment('vote_count');

            DB::commit();

            return response()->json(['message' => 'Vote recorded successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to record vote', 'error' => $e->getMessage()], 500);
        }
    }
    /**
     * Update a poll and its options.
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'poll_id' => 'required|exists:polls,id',
            'title' => 'required|string',
            'options' => 'required|array|min:2',
            'options.*.id' => 'nullable|exists:poll_options,id',
            'options.*.option_text' => 'required|string',
            'options.*.color' => 'nullable|string',
            'options.*.order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $poll = Poll::where('id', $request->poll_id)->where('user_id', auth()->id())->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found or unauthorized'], 404);
        }

        try {
            DB::beginTransaction();
            $poll->update(['title' => $request->title]);

            // Handling Options:
            // 1. Identify existing option IDs from request
            // 2. Delete options not in the request (unless we want to keep them but that might be messy with order)
            // Strategy: Update existing, Create new.
            // If strict sync is needed, we should delete others. Let's assume sync behavior.
            
            $requestOptionIds = collect($request->options)->pluck('id')->filter()->toArray();
            
            // Delete options missing from request
            PollOption::where('poll_id', $poll->id)->whereNotIn('id', $requestOptionIds)->delete();

            foreach ($request->options as $index => $optionData) {
                if (isset($optionData['id'])) {
                    PollOption::where('id', $optionData['id'])->where('poll_id', $poll->id)->update([
                        'option_text' => $optionData['option_text'],
                        'color' => $optionData['color'] ?? null,
                        'order' => $optionData['order'] ?? $index,
                    ]);
                } else {
                    PollOption::create([
                        'poll_id' => $poll->id,
                        'option_text' => $optionData['option_text'],
                        'color' => $optionData['color'] ?? null,
                        'order' => $optionData['order'] ?? $index,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Poll updated successfully',
                'poll' => $poll->load('options'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update poll', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a poll.
     */
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'poll_id' => 'required|exists:polls,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $poll = Poll::where('id', $request->poll_id)->where('user_id', auth()->id())->first();

        if (!$poll) {
             return response()->json(['message' => 'Poll not found or unauthorized'], 404);
        }

        try {
            $poll->delete(); // This should cascade delete options and votes if FKs are set correctly
            return response()->json(['message' => 'Poll deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete poll', 'error' => $e->getMessage()], 500);
        }
    }
}
