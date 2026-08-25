<?php

namespace App\Listeners;

use App\Events\ConvocationCreated;
use App\Models\Admin\Role;
use App\Models\indemnite\Convocations;
use App\Services\Indemnites\NotificationService;

/**
 * Volontairement PAS ShouldQueue : QUEUE_CONNECTION=database dans cet
 * environnement, mais aucun worker (`php artisan queue:work`) ne tourne en
 * permanence — un listener en file d'attente y resterait indéfiniment
 * (constaté : 53 jobs jamais traités dans la table `jobs`). Exécution
 * synchrone, immédiate à la création de la convocation.
 */
class NotifierAdminsNouvelleConvocation
{
    public function __construct(protected NotificationService $notificationService) {}

    public function handle(ConvocationCreated $event): void
    {
        $convocation = $event->convocation;

       $adminRoleIds = Role::whereIn('slug', ['drh', 'admin','super_admin'])->pluck('id');
        if (! $adminRoleIds) return;

        $adminIds = $this->notificationService->resolveTargetUserIds([
            'role_id' => $adminRoleIds->all(),
        ]);
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
