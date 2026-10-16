<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        return [
            'name' => 'Acara '.$this->faker->words(2, true),
            'status' => 'aktif',
            'absensi_buka' => now()->subHour(),
            'absensi_tutup' => now()->addHour(),
            'latitude' => -6.2,
            'longitude' => 106.8166666,
            'radius_meter' => 500,
            'display_token' => Str::random(24),
        ];
    }
}
