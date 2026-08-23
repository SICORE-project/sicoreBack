<?php

namespace App\Listeners;

use App\Events\ConvocationCreated;
use App\Models\Admin\Role;
use App\Models\indemnite\Convocations;
use App\Services\Indemnites\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifierAdminsNouvelleConvocation implements ShouldQueue
{
    public function __construct(protected NotificationService $notificationService) {}

    public function handle(ConvocationCreated $event): void
    {
        $convocation = $event->convocation;

        $adminRoleId = Role::where('slug', 'admin')->value('id');
        if (! $adminRoleId) return;

        $adminIds = $this->notificationService->resolveTargetUserIds(['role_id' => $adminRoleId]);
        if ($adminIds->isEmpty()) return;

        $this->notificationService->createAndDispatch([
            'titre'      => 'Nouvelle convocation créée',
            'message'    => "Une convocation « {$convocation->objet} » a été créée.",
            'type'       => 'info',
            'url'        => "/indemnites/convocations/{$convocation->id}",
            'created_by' => null,
            'sujet_type' => Convocations::class,
            'sujet_id'   => $convocation->id,
        ], $adminIds);
    }
}
