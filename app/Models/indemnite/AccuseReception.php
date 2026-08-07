<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AccuseReception extends Model {
    protected $table = 'accuses_reception';
    protected $fillable = ['reference', 'modele_id', 'beneficiaire_id', 'convocation_id', 'session', 'source_type', 'source_id', 'objet', 'contenu', 'recu_at', 'statut', 'statut_dossier', 'type_signature', 'signataire_nom', 'signature_chemin', 'signe_at', 'archive_par', 'archive_at', 'conserver_jusqu_au'];
    protected $casts = ['recu_at' => 'datetime', 'signe_at' => 'datetime', 'archive_at' => 'datetime', 'conserver_jusqu_au' => 'date'];
    public function beneficiaire(): BelongsTo { return $this->belongsTo(utilisateurs::class, 'beneficiaire_id'); }
    public function modele(): BelongsTo { return $this->belongsTo(ModeleAccuseReception::class, 'modele_id'); }
    public function convocation(): BelongsTo { return $this->belongsTo(Convocations::class, 'convocation_id'); }
}
