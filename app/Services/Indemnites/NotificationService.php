<?php

namespace App\Services\Indemnites;

use App\Models\Admin\User;
use App\Models\admin\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    public function listForUser(User $user, int $limit = 10): Collection
    {
        return Notification::query()
            ->actif()
            ->forUser($user)
            ->with(['users' => function ($query) use ($user) {
                $query->where('users.id', $user->id);
            }])
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function unreadCountForUser(User $user): int
    {
        return Notification::query()
            ->actif()
            ->unreadFor($user)
            ->count();
    }

    public function markAsRead(User $user, int $notificationId): void
    {
        $notification = Notification::query()
            ->actif()
            ->forUser($user)
            ->findOrFail($notificationId);

        $notification->users()->updateExistingPivot($user->id, [
            'est_lu' => true,
            'lu_at'  => Carbon::now(),
        ]);
    }

    public function markAllAsRead(User $user): void
    {
        DB::table('notification_user')
            ->where('user_id', $user->id)
            ->where('est_lu', false)
            ->update([
                'est_lu'     => true,
                'lu_at'      => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
    }

    public function createAndDispatch(array $data, iterable $userIds): Notification
    {
        /* $notification = Notification::create($data);

        $notification->users()->attach(
            collect($userIds)->mapWithKeys(fn ($id) => [$id => ['est_lu' => false]])->all()
        );

        return $notification; */

        return DB::transaction(function () use ($data, $userIds) {
        $notification = Notification::create($data);

        $notification->users()->attach(
            collect($userIds)->mapWithKeys(fn ($id) => [$id => ['est_lu' => false]])->all()
        );

        return $notification;
    });

    }
}
