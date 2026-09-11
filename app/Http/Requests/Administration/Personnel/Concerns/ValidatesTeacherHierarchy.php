<?php

namespace App\Http\Requests\Administration\Personnel\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;

trait ValidatesTeacherHierarchy
{
    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach (['ia_id', 'ief_id', 'lieu_service_id'] as $field) {
                if ($validator->errors()->has($field)) {
                    return;
                }
            }
            // Une modification sans changement d’affectation conserve le rattachement existant.
            if (! $this->filled('ia_id') || ! $this->filled('ief_id')) {
                return;
            }
            if (! DB::table('iefs')->where('id', $this->input('ief_id'))
                ->where('ia_id', $this->input('ia_id'))->whereNull('deleted_at')->exists()) {
                $validator->errors()->add('ief_id', 'L’IEF sélectionnée n’appartient pas à cette IA.');
            }
            if ($this->filled('lieu_service_id') && ! DB::table('lieu_de_services')
                ->where('id', $this->input('lieu_service_id'))
                ->where('ia_id', $this->input('ia_id'))
                ->where('ief_id', $this->input('ief_id'))
                ->whereNull('deleted_at')->exists()) {
                $validator->errors()->add('lieu_service_id', 'L’établissement sélectionné n’appartient pas à cette IEF et à cette IA.');
            }
        }];
    }
}
