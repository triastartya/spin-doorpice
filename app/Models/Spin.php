<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Spin extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function prize()
    {
        return $this->belongsTo(Prize::class);
    }

    public function winners()
    {
        return $this->hasMany(Winner::class);
    }
}
