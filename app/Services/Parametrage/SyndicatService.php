<?php

namespace App\Services\Parametrage;

use App\Models\Parametrage\Syndicat;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class SyndicatService
{
    public function __construct(private Syndicat $syndicat) {}

    public function getAllSyndicats(): Collection
    {
        return $this->syndicat
            ->newQuery()
            ->latest('created_at')
            ->latest('id')
            ->get();
    }

    public function getSyndicatById(int $id): Syndicat
    {
        return $this->syndicat->findOrFail($id);
    }

    public function createSyndicat(array $data): Syndicat
    {
        return $this->syndicat->create($data);
    }

    public function updateSyndicat(int $id, array $data): Syndicat
    {
        $syndicat = $this->getSyndicatById($id);
        $syndicat->update($data);

        return $syndicat;
    }

    public function setActiveStatus(int $id, bool $estActif): Syndicat
    {
        return DB::transaction(function () use ($id, $estActif) {
            $syndicat = $this->syndicat
                ->newQuery()
                ->lockForUpdate()
                ->findOrFail($id);

            if ($syndicat->est_actif === $estActif) {
                throw new ConflictHttpException(
                    $estActif
                        ? 'Le syndicat est déjà actif.'
                        : 'Le syndicat est déjà inactif.',
                );
            }

            $syndicat->update(['est_actif' => $estActif]);

            return $syndicat;
        });
    }

    public function deleteSyndicat(int $id): void
    {
        $syndicat = $this->getSyndicatById($id);
        $syndicat->delete();
    }
}
