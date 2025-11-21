<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Service\v1\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Notification;

class NotificationController extends Controller
{
    private $notificationService;

    public function __construct()
    {
        $this->notificationService = new NotificationService();
    }

    /**
     * Send push notifications to recipients
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendPushNotifications(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'notification.id' => 'nullable|string',
            'notification.title' => 'required|string|max:255',
            'notification.message' => 'required|string',
            'recipients' => 'required|array|min:1',
            'recipients.*.id' => 'required',
            'recipients.*.pushToken' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $notificationData = $request->input('notification');
        $recipients = $request->input('recipients');

        // Create notification records in the database for each recipient
        $firstNotification = null;
        foreach ($recipients as $recipient) {
            if (isset($recipient['id']) && is_numeric($recipient['id'])) {
                $notification = Notification::create([
                    'user_id' => $recipient['id'],
                    'title' => $notificationData['title'],
                    'message' => $notificationData['message'],
                ]);
                
                // Store the first notification to use its ID if needed
                if ($firstNotification === null) {
                    $firstNotification = $notification;
                }
            }
        }

        // Use database notification ID if notification.id is not provided
        if (!isset($notificationData['id']) || empty($notificationData['id'])) {
            $notificationData['id'] = $firstNotification ? (string) $firstNotification->id : '';
        }

        $result = $this->notificationService->sendPushNotifications(
            $notificationData,
            $recipients
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Update user's push token
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePushToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pushToken' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $user->update([
            'push_token' => $request->input('pushToken'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Push token updated successfully',
        ], 200);
    }

    /**
     * Send notification to authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendToMe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'notification.id' => 'nullable|string',
            'notification.title' => 'required|string|max:255',
            'notification.message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $pushToken = $user->push_token ?? null;

        if (!$pushToken) {
            return response()->json([
                'success' => false,
                'message' => 'User does not have a push token registered',
            ], 400);
        }

        $notificationData = $request->input('notification');

        // Create notification record in the database
        $notification = Notification::create([
            'user_id' => $user->id,
            'title' => $notificationData['title'],
            'message' => $notificationData['message'],
        ]);

        // Use database notification ID if notification.id is not provided
        if (!isset($notificationData['id']) || empty($notificationData['id'])) {
            $notificationData['id'] = (string) $notification->id;
        }

        $result = $this->notificationService->sendPushNotifications(
            $notificationData,
            [
                [
                    'id' => $user->id,
                    'pushToken' => $pushToken,
                ]
            ]
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Send notifications to multiple users by their IDs
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendToUsers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'notification.id' => 'nullable|string',
            'notification.title' => 'required|string|max:255',
            'notification.message' => 'required|string',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $users = User::whereIn('id', $request->input('user_ids'))
            ->whereNotNull('push_token')
            ->get();

        if ($users->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No users with push tokens found',
            ], 400);
        }

        $notificationData = $request->input('notification');

        // Create notification records in the database for each user
        $firstNotification = null;
        foreach ($users as $user) {
            $notification = Notification::create([
                'user_id' => $user->id,
                'title' => $notificationData['title'],
                'message' => $notificationData['message'],
            ]);
            
            // Store the first notification to use its ID if needed
            if ($firstNotification === null) {
                $firstNotification = $notification;
            }
        }

        // Use database notification ID if notification.id is not provided
        if (!isset($notificationData['id']) || empty($notificationData['id'])) {
            $notificationData['id'] = $firstNotification ? (string) $firstNotification->id : '';
        }

        $recipients = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'pushToken' => $user->push_token,
            ];
        })->toArray();

        $result = $this->notificationService->sendPushNotifications(
            $notificationData,
            $recipients
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }
}

