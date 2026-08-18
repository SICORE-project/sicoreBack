<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class bultins extends Model
{
    protected $fillable = [
        'matricule', 'mois_validite', 'date_enregistrement',
        'numero_ordre', 'net_a_payer', 'enseignant_id', 'ia_id',
    ];

    protected function casts(): array
    {
        return [
            'mois_validite' => 'date',
            'date_enregistrement' => 'date',
            'net_a_payer' => 'decimal:2',
        ];
    }

    public function enseignant()
    {
        return $this->belongsTo(enseignants::class, 'enseignant_id');
    }

    public function ia()
    {
        return $this->belongsTo(ias::class, 'ia_id');
    }

    public function details()
    {
        return $this->hasMany(detail_bultins::class, 'bultin_id');
    }
}
