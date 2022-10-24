<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Question;

class Positive extends Model
{
    use HasFactory;

    protected $fillable = [
        'desc',
        'point',
        'question_id',
        'user_id'
    ];

    // Relación uno a muchos inversa

    public function question() {
        return $this->belongsTo(Question::class);
    }
}
