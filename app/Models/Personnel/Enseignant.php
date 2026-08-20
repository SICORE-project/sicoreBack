<?php

namespace App\Models\Personnel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Admin\User;

use App\Models\Parametrage\{
    CorpsEnseignant,
    Grade,
    Diplome,
    Discipline,
    Specialite,
    Categorie,
    LieuService,
    LieuPaiement,
    Ief,
    Ia,
    SituationFamiliale,
    Syndicat,
    Mutuelle,
    InstitutFinancier,
    CentreFormation,
    StatutEnseignant
};

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

        'statut_enseignant_id',

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

    // =====================================================
    // ADMINISTRATION
    // =====================================================

    public function createdBy()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function updatedBy()
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }

    public function user()
    {
        return $this->hasOne(User::class);
    }

    // =====================================================
    // PARAMÉTRAGE
    // =====================================================

    public function situationFamiliale()
    {
        return $this->belongsTo(
            SituationFamiliale::class,
            'situation_familiale_id'
        );
    }

    public function corps()
    {
        return $this->belongsTo(
            CorpsEnseignant::class,
            'corps_id'
        );
    }

    public function grade()
    {
        return $this->belongsTo(
            Grade::class,
            'grade_id'
        );
    }

    public function diplome()
    {
        return $this->belongsTo(
            Diplome::class,
            'diplome_id'
        );
    }

    public function discipline()
    {
        return $this->belongsTo(
            Discipline::class,
            'discipline_id'
        );
    }

    public function specialite()
    {
        return $this->belongsTo(
            Specialite::class,
            'specialite_id'
        );
    }

    public function categorie()
    {
        return $this->belongsTo(
            Categorie::class,
            'categorie_id'
        );
    }

    public function lieuService()
    {
        return $this->belongsTo(
            LieuService::class,
            'lieu_service_id'
        );
    }

    public function lieuPaiement()
    {
        return $this->belongsTo(
            LieuPaiement::class,
            'lieu_paiement_id'
        );
    }

    public function ief()
    {
        return $this->belongsTo(
            Ief::class,
            'ief_id'
        );
    }

    public function ia()
    {
        return $this->belongsTo(
            Ia::class,
            'ia_id'
        );
    }

    public function statutEnseignant()
    {
        return $this->belongsTo(
            StatutEnseignant::class,
            'statut_enseignant_id'
        );
    }

    // =====================================================
    // COMPTE BANCAIRE
    // =====================================================

    public function comptesBancaires()
    {
        return $this->hasMany(
            CompteBancaireEnseignant::class,
            'enseignant_id'
        );
    }

    // =====================================================
    // SYNDICATS
    // =====================================================

    public function syndicats()
    {
        return $this->belongsToMany(
            Syndicat::class,
            'enseignant_syndicat'
        )
        ->withPivot([
            'taux_personnalise',
            'date_adhesion',
            'date_resiliation',
            'numero_affiliation',
            'est_actif',
        ])
        ->withTimestamps();
    }

    // =====================================================
    // MUTUELLES
    // =====================================================

    public function mutuelles()
    {
        return $this->belongsToMany(
            Mutuelle::class,
            'enseignant_mutuelle'
        )
        ->withPivot([
            'numero_affiliation',
            'date_adhesion',
            'date_resiliation',
            'est_actif',
        ])
        ->withTimestamps();
    }

    // =====================================================
    // ANCIENNE RELATION INSTITUT FINANCIER
    // =====================================================

    public function institutsFinanciers()
    {
        return $this->belongsToMany(
            InstitutFinancier::class,
            'enseignant_institut_financier'
        )
        ->withPivot([
            'iban',
            'bic',
            'titulaire_compte',
            'est_principal',
            'est_actif',
        ])
        ->withTimestamps();
    }

    // =====================================================
    // CENTRES DE FORMATION
    // =====================================================

    public function centresFormation()
    {
        return $this->belongsToMany(
            CentreFormation::class,
            'enseignant_etablissement'
        )
        ->withPivot([
            'date_affectation',
            'date_fin',
            'est_actif',
        ])
        ->withTimestamps();
    }

    // =====================================================
    // RELATIONS PERSONNEL
    // =====================================================

    public function reclassements()
    {
        return $this->hasMany(
            Reclassement::class,
            'enseignant_id'
        );
    }

    public function affectations()
    {
        return $this->hasMany(
            Affectation::class,
            'enseignant_id'
        );
    }

    public function cessationsPaiement()
    {
        return $this->hasMany(
            CessationPaiement::class,
            'enseignant_id'
        );
    }

    public function transferts()
    {
        return $this->hasMany(
            TransfertEnseignant::class,
            'enseignant_id'
        );
    }

    public function miseAJourMcVe()
    {
        return $this->hasMany(
            MiseAJourMcVe::class,
            'enseignant_id'
        );
    }

    public function miseAJourPcVac()
    {
        return $this->hasMany(
            MiseAJourPcVac::class,
            'enseignant_id'
        );
    }

    // =====================================================
    // SCOPES
    // =====================================================

    public function scopeActif($query)
    {
        return $query->where('est_actif', true);
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
        return $query->where(
            'lieu_service_id',
            $lieuServiceId
        );
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
        return $query->where(function ($q) use ($search) {
            $q->where(
                'matricule',
                'LIKE',
                "%{$search}%"
            )
            ->orWhere(
                'nom',
                'LIKE',
                "%{$search}%"
            )
            ->orWhere(
                'prenom',
                'LIKE',
                "%{$search}%"
            )
            ->orWhere(
                'cni',
                'LIKE',
                "%{$search}%"
            )
            ->orWhere(
                'email',
                'LIKE',
                "%{$search}%"
            );
        });
    }

    // =====================================================
    // ACCESSORS
    // =====================================================

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
        return $this->date_naissance
            ? $this->date_naissance->age
            : null;
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

    // =====================================================
    // MÉTHODES
    // =====================================================

    public function isActif()
    {
        return $this->est_actif
            && $this->statut === 'en_activite';
    }

    public function getSalaireBrutFormattedAttribute()
    {
        if ($this->salaire_brut === null) {
            return null;
        }

        return number_format(
            $this->salaire_brut,
            0,
            ',',
            ' '
        ) . ' FCFA';
    }
}