<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['gps_valid' => 'boolean'];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function winner()
    {
        return $this->hasOne(Winner::class);
    }

    public static function generateCode(int $eventId): string
    {
        $next = (int) static::where('event_id', $eventId)->max('sequence') + 1;
        $code = sprintf('AST%04d', $next);

        while (static::where('event_id', $eventId)->where('code', $code)->exists()) {
            $next++;
            $code = sprintf('AST%04d', $next);
        }

        return $code;
    }
}
