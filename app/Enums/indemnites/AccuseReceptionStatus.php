<?php

declare(strict_types=1);

namespace App\Enums\indemnites;

enum AccuseReceptionStatus: string
{
    case EN_ATTENTE = 'en_attente';
    case RECU = 'recu';
    case VALIDE = 'valide';
    case REJETE = 'rejete';
    case ARCHIVE = 'archive';

    public function label(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'En attente',
            self::RECU => 'Reçu',
            self::VALIDE => 'Validé',
            self::REJETE => 'Rejeté',
            self::ARCHIVE => 'Archivé',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'warning',
            self::RECU => 'info',
            self::VALIDE => 'success',
            self::REJETE => 'danger',
            self::ARCHIVE => 'secondary',
        };
    }

    public function isPending(): bool
    {
        return $this === self::EN_ATTENTE;
    }

    public function isReceived(): bool
    {
        return $this === self::RECU;
    }

    public function isValidated(): bool
    {
        return $this === self::VALIDE;
    }

    public function isRejected(): bool
    {
        return $this === self::REJETE;
    }

    public function isArchived(): bool
    {
        return $this === self::ARCHIVE;
    }
}
