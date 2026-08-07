<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PolitiqueArchivageAccuse extends Model { protected $table = 'politiques_archivage_accuses'; protected $fillable = ['duree_conservation_annees', 'acces_admin_seul', 'modifie_par']; protected $casts = ['acces_admin_seul' => 'boolean']; }
