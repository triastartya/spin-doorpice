<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['absensi_buka' => 'datetime', 'absensi_tutup' => 'datetime'];
    }

    public function prizes()
    {
        return $this->hasMany(Prize::class);
    }

    public function coupons()
    {
        return $this->hasMany(Coupon::class);
    }

    public function spins()
    {
        return $this->hasMany(Spin::class);
    }

    public function winners()
    {
        return $this->hasMany(Winner::class);
    }

    public function prizesTotalStock()
    {
        return (int) $this->prizes()->sum('stock');
    }

    public function prizesRemainingStock()
    {
        return (int) ($this->prizesTotalStock() - $this->winners()->whereIn('status', ['menang', 'diserahkan'])->count());
    }
}
