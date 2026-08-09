<?php

namespace App\Models\indemnites;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    use HasFactory;

    protected $table = 'documents';

    protected $fillable = [
        'reference',
        'libelle',
    ];

    public function accuseReceptions(): HasMany
    {
        return $this->hasMany(Accuse_reception::class);
    }
}
