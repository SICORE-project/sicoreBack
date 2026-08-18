<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EtatPresence extends Model
{
    protected $table = 'etats_presences';

    protected $fillable = [
        'mois',
        'annee',
        'nombre_jour',
        'nombre_jours_travailles',
        'nombre_absences',
        'nombre_retards',
        'retenue_absences',
        'retenue_retards',
        'total_retenues',
        'statut',
        'date_validation',
        'valide_par',
        'date_transmission',
        'transmis_par',
        'observation',
        'enseignant_id',
        'ia_id',
        'ief_id',
    ];

    protected $casts = [
        'date_validation' => 'date',
        'date_transmission' => 'date',
        'retenue_absences' => 'decimal:2',
        'retenue_retards' => 'decimal:2',
        'total_retenues' => 'decimal:2',
    ];

    public function enseignant()
    {
        return $this->belongsTo(enseignants::class, 'enseignant_id');
    }

    public function ia()
    {
        return $this->belongsTo(ias::class, 'ia_id');
    }

    public function ief()
    {
        return $this->belongsTo(iefs::class, 'ief_id');
    }

    public function validePar()
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    public function transmisPar()
    {
        return $this->belongsTo(User::class, 'transmis_par');
    }

    public function absences()
    {
        return $this->hasMany(Absence::class, 'etat_presence_id');
    }

    public function retards()
    {
        return $this->hasMany(Retard::class, 'etat_presence_id');
    }

    public function recalculerRetenues()
    {
        $this->nombre_absences = $this->absences()->count();
        $this->nombre_retards = $this->retards()->count();
        $this->retenue_absences = $this->absences()->sum('retenue_montant');
        $this->retenue_retards = $this->retards()->sum('retenue_montant');
        $this->total_retenues = $this->retenue_absences + $this->retenue_retards;
        $this->save();
    }
}
