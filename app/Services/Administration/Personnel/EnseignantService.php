<?php

namespace App\Services\Administration\Personnel;

use App\Models\Personnel\Enseignant;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class EnseignantService
{
    public function create(
        array $data,
        ?int $userId = null
    ): Enseignant {

        return DB::transaction(function () use ($data, $userId) {

            $compteBancaire =
                $data['compte_bancaire'] ?? null;

            $syndicat =
                $data['syndicat'] ?? null;

            $mutuelle =
                $data['mutuelle'] ?? null;

            $data = Arr::except($data, [
                'compte_bancaire',
                'syndicat',
                'mutuelle',
            ]);

            if (!empty($data['date_recrutement'])) {
                $data['annee_recrutement'] =
                    (int) date(
                        'Y',
                        strtotime($data['date_recrutement'])
                    );
            }

            $data['created_by'] = $userId;

            $enseignant = Enseignant::create($data);

            if (!empty($compteBancaire)) {

                $compteBancaire['est_principal'] =
                    $compteBancaire['est_principal'] ?? true;

                $compteBancaire['est_actif'] = true;

                $enseignant
                    ->comptesBancaires()
                    ->create($compteBancaire);
            }

            if (!empty($syndicat['syndicat_id'])) {

                $enseignant
                    ->syndicats()
                    ->attach(
                        $syndicat['syndicat_id'],
                        [
                            'taux_personnalise' =>
                                $syndicat['taux_personnalise'] ?? null,

                            'date_adhesion' =>
                                $syndicat['date_adhesion'] ?? null,

                            'date_resiliation' =>
                                $syndicat['date_resiliation'] ?? null,

                            'numero_affiliation' =>
                                $syndicat['numero_affiliation'] ?? null,

                            'est_actif' => true,
                        ]
                    );
            }

            if (!empty($mutuelle['mutuelle_id'])) {

                $enseignant
                    ->mutuelles()
                    ->attach(
                        $mutuelle['mutuelle_id'],
                        [
                            'numero_affiliation' =>
                                $mutuelle['numero_affiliation'] ?? null,

                            'date_adhesion' =>
                                $mutuelle['date_adhesion'] ?? null,

                            'date_resiliation' =>
                                $mutuelle['date_resiliation'] ?? null,

                            'est_actif' => true,
                        ]
                    );
            }

            return $enseignant->load([
                'ia',
                'ief',
                'corps',
                'grade',
                'categorie',
                'discipline',
                'diplome',
                'lieuService',
                'comptesBancaires.institutFinancier',
                'syndicats',
                'mutuelles',
            ]);
        });
    }

    public function find(int $id): Enseignant
    {
        return Enseignant::with([
            'ia',
            'ief',
            'corps',
            'grade',
            'categorie',
            'discipline',
            'diplome',
            'lieuService',
            'comptesBancaires.institutFinancier',
            'syndicats',
            'mutuelles',
        ])->findOrFail($id);
    }

    public function update(Enseignant $enseignant, array $data, ?int $userId = null): Enseignant
    {
        $compteBancaire = $data['compte_bancaire'] ?? null;
        $data = Arr::except($data, ['compte_bancaire', 'syndicat', 'mutuelle']);
        if (!empty($data['date_recrutement'])) {
            $data['annee_recrutement'] = (int) date('Y', strtotime($data['date_recrutement']));
        }
        $data['updated_by'] = $userId;
        $enseignant->update($data);

        if (! empty($compteBancaire) && collect($compteBancaire)->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty()) {
            $compte = $enseignant->comptesBancaires()->where('est_principal', true)->first()
                ?? $enseignant->comptesBancaires()->first();
            $values = array_merge($compteBancaire, ['est_principal' => true, 'est_actif' => true]);
            $compte ? $compte->update($values) : $enseignant->comptesBancaires()->create($values);
        }

        return $this->find($enseignant->id);
    }

    public function delete(Enseignant $enseignant): void
    {
        $enseignant->delete();
    }

    public function paginate(int $perPage = 20)
    {
        return Enseignant::query()
            ->with([
                'ia',
                'ief',
                'corps',
                'grade',
                'categorie',
                'discipline',
                'diplome',
                'lieuService',
                'comptesBancaires.institutFinancier',
                'syndicats',
                'mutuelles',
            ])
            ->latest('id')
            ->paginate($perPage);
    }
}
