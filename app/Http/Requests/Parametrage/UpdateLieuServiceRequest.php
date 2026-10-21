<?php

namespace App\Http\Requests\Parametrage;

use App\Models\Parametrage\LieuService;
use Illuminate\Validation\Validator;

class UpdateLieuServiceRequest extends StoreLieuServiceRequest
{
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $lieu = $this->route('lieuService');
            if (! $lieu instanceof LieuService) {
                return;
            }
            $hierarchyChanged = (int) $lieu->ia_id !== $this->integer('ia_id')
                || (int) $lieu->ief_id !== $this->integer('ief_id');
            if ($hierarchyChanged && $lieu->enseignants()->withTrashed()->exists()) {
                $validator->errors()->add('ief_id', 'Impossible de changer le rattachement d’un établissement lié à des enseignants. Réaffectez-les d’abord.');
            }
        }];
    }
}
