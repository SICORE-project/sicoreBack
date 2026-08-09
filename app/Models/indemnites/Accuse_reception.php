<?php

namespace App\Models\indemnites;

use App\Enums\indemnites\AccuseReceptionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Accuse_reception extends Model
{
     use HasFactory;
    use SoftDeletes;

    protected $table = 'accuse_receptions';

    protected $fillable = [
        'document_id',
        'agent_deposant_id',
        'agent_receptionnaire_id',
        'status',
        'date_depot',
    ];

    protected function casts(): array
    {
        return [
            'status' => AccuseReceptionStatus::class,
            'date_depot' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function agentDeposant(): BelongsTo
    {
        return $this->belongsTo(
            Agent::class,
            'agent_deposant_id'
        );
    }

    public function agentReceptionnaire(): BelongsTo
    {
        return $this->belongsTo(
            Agent::class,
            'agent_receptionnaire_id'
        );
    }

    public function scopeNonArchives($query)
    {
        return $query->where(
            'status',
            '!=',
            AccuseReceptionStatus::ARCHIVE->value
        );
    }
}
