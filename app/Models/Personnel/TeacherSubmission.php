<?php
namespace App\Models\Personnel;
use Illuminate\Database\Eloquent\Model;
class TeacherSubmission extends Model
{
    protected $guarded = [];
    protected $casts = ['payload' => 'array', 'approved_at' => 'datetime'];
}
