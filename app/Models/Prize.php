<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prize extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset($this->image_path) : null;
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function winners()
    {
        return $this->hasMany(Winner::class);
    }

    public function remainingStock()
    {
        return $this->stock - $this->winners()->whereIn('status', ['menang', 'diserahkan'])->count();
    }
}
