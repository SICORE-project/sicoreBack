<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Fixed monthly deductions owned by the existing Rubriques de paie CRUD. */
class PayrollRubricAmounts
{
    public const CODES = [
        'impr' => ['IMPR', 'I061'],
        'trimf' => ['TRIMF', 'I090'],
        'ipm' => ['IPM', 'I080'],
        'checkoff' => ['CHECKOFF_UES', 'I790'],
    ];

    public function resolve(string $engagement): array
    {
        if (! in_array($engagement, ['contractuel', 'vacataire'], true)) {
            throw ValidationException::withMessages(['type_engagement' => 'Sélectionnez PC ou VAC avant de configurer la paie.']);
        }
        $keys = $engagement === 'contractuel' ? array_keys(self::CODES) : ['impr', 'trimf'];
        $codes = collect($keys)->flatMap(fn ($key) => self::CODES[$key])->all();
        $rubrics = DB::table('rubrique_paies')->whereIn('code', $codes)->get()->keyBy('code');
        $amounts = ['impr' => 0.0, 'trimf' => 0.0, 'ipm' => 0.0, 'checkoff' => 0.0];
        $sources = [];
        $errors = [];
        foreach ($keys as $key) {
            $aliases = self::CODES[$key];
            $matches = collect($aliases)->map(fn ($code) => $rubrics->get($code))->filter();
            if ($matches->isEmpty()) {
                $errors[] = 'Rubrique '.$aliases[0].' manquante (code '.$aliases[0].' ou '.$aliases[1].').';
                continue;
            }
            foreach ($matches as $rubric) {
                $problems = [];
                if (! $rubric->est_actif) { $problems[] = 'rubrique inactive'; }
                if ($rubric->type !== 'retenue') { $problems[] = 'type attendu : Retenue'; }
                if ($rubric->periodicite !== 'mensuelle') { $problems[] = 'périodicité attendue : Mensuelle'; }
                if ($rubric->montant === null || $rubric->montant === '') {
                    $problems[] = 'champ Montant vide (saisissez le montant prévu, ou 0 explicitement si aucune retenue ne s’applique)';
                } elseif (! is_numeric($rubric->montant) || (float) $rubric->montant < 0) {
                    $problems[] = 'Montant invalide : un nombre positif ou zéro est attendu';
                }
                if ($problems !== []) {
                    $errors[] = $rubric->code.' : '.implode(' ; ', $problems).'.';
                }
            }
            if ($matches->map(fn ($rubric) => $rubric->montant === null ? null : number_format((float) $rubric->montant, 2, '.', ''))->unique()->count() > 1) {
                $errors[] = 'Montants contradictoires entre '.$aliases[0].' et '.$aliases[1].' : conservez une seule configuration.';
            }
            $rubric = $matches->first();
            $amounts[$key] = round((float) $rubric->montant, 2);
            $sources[$key] = [
                'id' => $rubric->id, 'code' => $rubric->code, 'label' => $rubric->libelle,
                'amount' => $amounts[$key], 'updated_at' => $rubric->updated_at,
            ];
        }
        if ($errors !== []) {
            throw ValidationException::withMessages(['rubriques_paie' => 'À compléter dans Paramétrage → Rubriques de paie : '.implode(' ', $errors)]);
        }
        return [...$amounts, 'deduction_rubrics' => $sources];
    }
}
