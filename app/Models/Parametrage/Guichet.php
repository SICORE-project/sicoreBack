<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Guichet extends Model
{
    use HasFactory;

    protected $table = 'guichets';

    protected $fillable = [
        'institut_financier_id',
        'code',
        'libelle',
    ];

    public function institutFinancier(): BelongsTo
    {
        return $this->belongsTo(InstitutFinancier::class);
    }
}
