<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LieuPaiement extends Model
{
    use HasFactory;

    protected $table = 'lieux_paiement';

    protected $fillable = [
        'code',
        'libelle',
        'adresse',
        'telephone',
        'email',
        'institut_financier_id',
        'est_actif',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
    ];

    // === RELATIONS ===
    public function institutFinancier()
    {
        return $this->belongsTo(InstitutFinancier::class);
    }

    public function enseignants()
    {
        return $this->hasMany(Enseignant::class);
    }

    // === SCOPES ===
    public function scopeActif($query)
    {
        return $query->where('est_actif', true);
    }
}
