<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ModeleAccuseReception extends Model { protected $table = 'modeles_accuses_reception'; protected $fillable = ['nom', 'objet', 'contenu', 'actif', 'cree_par']; protected $casts = ['actif' => 'boolean']; }
