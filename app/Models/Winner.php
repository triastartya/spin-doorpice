<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Winner extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['taken_at' => 'datetime'];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function prize()
    {
        return $this->belongsTo(Prize::class);
    }

    public function spin()
    {
        return $this->belongsTo(Spin::class);
    }
}
