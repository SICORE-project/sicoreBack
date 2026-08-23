<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Requests\Indemnites\Notification\StoreNotificationRequest;
use App\Http\Resources\Indemnites\NotificationDetailResource;
use App\Http\Resources\Indemnites\NotificationResource;
use App\Models\admin\User;
use App\Services\Indemnites\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;
    public function __construct(NotificationService $notificationService)
        {
            $this->notificationService = $notificationService;
        }
    public function index(Request $request)
        {
            return NotificationResource::collection(
                $this->notificationService->listForUser($request->user())
            );
        }
    public function unreadCount(Request $request)
        {
            return response()->json([
                'count' => $this->notificationService->unreadCountForUser($request->user()),
            ]);
        }
    public function markAsRead(Request $request, int $id)
        {
            $this->notificationService->markAsRead($request->user(), $id);
    return response()->json(['message' => 'Notification marquée comme lue.']);
        }
    public function markAllAsRead(Request $request)
        {
            $this->notificationService->markAllAsRead($request->user());
    return response()->json(['message' => 'Toutes les notifications ont été marquées comme lues.']);
        }
    /**
         * Bonus : créer + diffuser une notification à tous les utilisateurs.
         */
    public function store(StoreNotificationRequest $request)
    {
        $userIds = $request->validated('user_ids')
            ?? $this->notificationService->resolveTargetUserIds($request->validated('filters', []));

        if (collect($userIds)->isEmpty()) {
            throw ValidationException::withMessages([
                'user_ids' => 'Aucun utilisateur ne correspond aux critères fournis.',
            ]);
        }

        $notification = $this->notificationService->createAndDispatch(
            [
                ...$request->only(['titre', 'message', 'type', 'url']),
                'created_by' => $request->user()->id,
            ],
            $userIds
        );

        return response()->json(['data' => $notification], 201);
    }
}
