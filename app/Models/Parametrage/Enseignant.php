<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Admin\User;
use App\Services\Administration\OrganizationalScope;

class Enseignant extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'enseignants';

    protected $fillable = [
        'matricule',
        'nom',
        'prenom',
        'date_naissance',
        'lieu_naissance',
        'cni',
        'genre',
        'telephone',
        'email',
        'adresse',
        'photo',
        'situation_familiale_id',
        'nombre_enfants',
        'nombre_femmes',
        'nombre_parts_fiscales',
        'conjoint_travaille',
        'salaire_brut',
        'corps_id',
        'grade_id',
        'echelon_id',
        'diplome_id',
        'discipline_id',
        'specialite_id',
        'categorie_id',
        'lieu_service_id',
        'lieu_paiement_id',
        'ief_id',
        'ia_id',
        'nationalite_id',
        'statut_enseignant_id',   // FK déjà en base (statuts_enseignant), oubliée du fillable
        'categorie_personnel',    // vacataire / contractuel / fonctionnaire — distinct de statuts_enseignant.categorie
        'statut',
        'date_statut',
        'date_recrutement',
        'date_prise_service',
        'date_fin_contrat',
        'est_actif',
        'numero_compte_bancaire',
        'titulaire_compte',
        'iban',
        'bic',
        'code_banque',
        'code_guichet',
        'cle_rib',
        'annee_recrutement',
        'generation',
        'observations',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
        'conjoint_travaille' => 'boolean',
        'date_naissance' => 'date',
        'date_statut' => 'date',
        'date_recrutement' => 'date',
        'date_prise_service' => 'date',
        'date_fin_contrat' => 'date',
        'salaire_brut' => 'decimal:2',
        'nombre_enfants' => 'integer',
        'nombre_femmes' => 'integer',
        'nombre_parts_fiscales' => 'integer',
        'annee_recrutement' => 'integer',
    ];

    // === RELATIONS ===

    // Admin
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function user()
    {
        return $this->hasOne(User::class);
    }

    // Paramétrage
    public function situationFamiliale()
    {
        return $this->belongsTo(SituationFamiliale::class);
    }

    public function corps()
    {
        return $this->belongsTo(Corps::class);
    }

  

    public function diplome()
    {
        return $this->belongsTo(Diplome::class);
    }

    public function discipline()
    {
        return $this->belongsTo(Discipline::class);
    }

    public function specialite()
    {
        return $this->belongsTo(Specialite::class);
    }

    public function categorie()
    {
        return $this->belongsTo(Categorie::class);
    }

    public function lieuService()
    {
        return $this->belongsTo(LieuService::class);
    }

    public function lieuPaiement()
    {
        return $this->belongsTo(LieuPaiement::class);
    }

    public function ief()
    {
        return $this->belongsTo(Ief::class);
    }

    public function ia()
    {
        return $this->belongsTo(Ia::class);
    }

   
    public function statutEnseignant()
    {
        return $this->belongsTo(StatutEnseignant::class, 'statut_enseignant_id');
    }
    

    // === RELATIONS MANY TO MANY ===
    public function syndicats()
    {
        return $this->belongsToMany(Syndicat::class, 'enseignant_syndicat')
                    ->withPivot('taux_personnalise', 'date_adhesion', 'date_resiliation', 'numero_affiliation', 'est_actif')
                    ->withTimestamps();
    }

    public function institutsFinanciers()
    {
        return $this->belongsToMany(InstitutFinancier::class, 'enseignant_institut_financier')
                    ->withPivot('iban', 'bic', 'titulaire_compte', 'est_principal', 'est_actif')
                    ->withTimestamps();
    }

    public function centresFormation()
    {
        return $this->belongsToMany(CentreFormation::class, 'enseignant_etablissement')
                    ->withPivot('date_affectation', 'date_fin', 'est_actif')
                    ->withTimestamps();
    }

    // === RELATIONS PERSONNALISÉES ===
    public function reclassements()
    {
        return $this->hasMany(Reclassement::class);
    }

    public function affectations()
    {
        return $this->hasMany(Affectation::class);
    }

    public function cessationsPaiement()
    {
        return $this->hasMany(CessationPaiement::class);
    }

    public function transferts()
    {
        return $this->hasMany(TransfertEnseignant::class);
    }

    public function heuresSupplementaires()
    {
        return $this->hasMany(HeureSupplementaire::class);
    }

   

    public function miseAJourMcVe()
    {
        return $this->hasMany(MiseAJourMcVe::class);
    }

    public function miseAJourPcVac()
    {
        return $this->hasMany(MiseAJourPcVac::class);
    }

  

    public function validations()
    {
        return $this->hasMany(Validation::class);
    }

    // === SCOPES ===
    public function scopeActif($query)
    {
        return $query->where('est_actif', true);
    }

    public function scopeVisibleTo($query, User $user)
    {
        return app(OrganizationalScope::class)->apply($query, $user);
    }

    public function scopeByIef($query, $iefId)
    {
        return $query->where('ief_id', $iefId);
    }

    public function scopeByIa($query, $iaId)
    {
        return $query->where('ia_id', $iaId);
    }

    public function scopeByLieuService($query, $lieuServiceId)
    {
        return $query->where('lieu_service_id', $lieuServiceId);
    }

    public function scopeByStatut($query, $statut)
    {
        return $query->where('statut', $statut);
    }

    public function scopeByCorps($query, $corpsId)
    {
        return $query->where('corps_id', $corpsId);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('matricule', 'LIKE', "%{$search}%")
              ->orWhere('nom', 'LIKE', "%{$search}%")
              ->orWhere('prenom', 'LIKE', "%{$search}%")
              ->orWhere('cni', 'LIKE', "%{$search}%")
              ->orWhere('email', 'LIKE', "%{$search}%");
        });
    }

    // === ACCESSORS ===
    public function getNomCompletAttribute()
    {
        return $this->prenom . ' ' . $this->nom;
    }

    public function getNomCompletInverseAttribute()
    {
        return $this->nom . ' ' . $this->prenom;
    }

    public function getAgeAttribute()
    {
        if ($this->date_naissance) {
            return $this->date_naissance->age;
        }
        return null;
    }

    public function getStatutLibelleAttribute()
    {
        $statuts = [
            'en_activite' => 'En activité',
            'retraite' => 'Retraité',
            'suspension_provisoire' => 'Suspendu provisoirement',
            'abandon' => 'Abandon',
            'decede' => 'Décédé',
            'integre' => 'Intégré',
            'radie' => 'Radié',
            'cessation_paiement' => 'Cessation de paiement',
        ];
        return $statuts[$this->statut] ?? $this->statut;
    }

    // === METHODS ===
    public function isActif()
    {
        return $this->est_actif && $this->statut === 'en_activite';
    }

    public function getSalaireBrutFormattedAttribute()
    {
        return number_format($this->salaire_brut, 0, ',', ' ') . ' FCFA';
    }
}
