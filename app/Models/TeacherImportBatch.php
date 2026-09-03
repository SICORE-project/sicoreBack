<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherImportBatch extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'summary' => 'array',
            'completed_at' => 'datetime',
        ];
    }
}
