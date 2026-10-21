<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class iefs extends Model
{
    protected $guarded = [];

    public function ia(): BelongsTo
    {
        return $this->belongsTo(ias::class, 'ia_id');
    }
}
