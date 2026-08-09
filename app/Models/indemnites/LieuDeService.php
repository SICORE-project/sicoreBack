<?php

declare(strict_types=1);

namespace App\Models\indemnites;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LieuDeService extends Model
{
    use HasFactory;

    protected $table = 'lieu_de_services';

    protected $fillable = [
        'code',
        'libelle',
    ];

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class);
    }
}
