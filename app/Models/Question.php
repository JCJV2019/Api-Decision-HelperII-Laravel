<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\User;
use App\Models\Positive;
use App\Models\Negative;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'question',
        'user_id'
    ];

    // Relación uno a muchos

    public function positives() {
        return $this->hasMany(Positive::class);
    }

    public function negatives() {
        return $this->hasMany(Negative::class);
    }

    // Relación uno a muchos inversa

    public function user() {
        return $this->belongsTo(User::class);
    }
}
