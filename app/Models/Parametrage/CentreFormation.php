<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Admin\User;
use App\Models\Personnel\Enseignant;

class CentreFormation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'nom',
        'sigle',
        'adresse',
        'telephone',
        'email',
        'site_web',
        'ia_id',
        'ief_id',
        'region_id',
        'departement_id',
        'type_etablissement_id',
        'statut',
        'categorie',
        'capacite_accueil',
        'nombre_salles',
        'nombre_ateliers',
        'possede_internat',
        'capacite_internat',
        'possede_restaurant',
        'directeur_nom',
        'directeur_titre',
        'directeur_telephone',
        'directeur_email',
        'est_actif',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
        'possede_internat' => 'boolean',
        'possede_restaurant' => 'boolean',
        'capacite_accueil' => 'integer',
        'nombre_salles' => 'integer',
        'nombre_ateliers' => 'integer',
        'capacite_internat' => 'integer',
    ];

    // === RELATIONS ===
    public function ia()
    {
        return $this->belongsTo(Ia::class);
    }

    public function ief()
    {
        return $this->belongsTo(Ief::class);
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function departement()
    {
        return $this->belongsTo(Departement::class);
    }

    public function typeEtablissement()
    {
        return $this->belongsTo(TypeEtablissement::class);
    }

    public function enseignants()
    {
        return $this->belongsToMany(Enseignant::class, 'enseignant_etablissement')
                    ->withPivot('date_affectation', 'date_fin', 'est_actif')
                    ->withTimestamps();
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // === SCOPES ===
    public function scopeActif($query)
    {
        return $query->where('est_actif', true);
    }

    public function scopeByIa($query, $iaId)
    {
        return $query->where('ia_id', $iaId);
    }

    public function scopeByIef($query, $iefId)
    {
        return $query->where('ief_id', $iefId);
    }

    public function scopeByRegion($query, $regionId)
    {
        return $query->where('region_id', $regionId);
    }

    public function scopePublic($query)
    {
        return $query->where('statut', 'public');
    }

    // === ACCESSORS ===
    public function getNomCompletAttribute()
    {
        return $this->code . ' - ' . $this->nom;
    }
}