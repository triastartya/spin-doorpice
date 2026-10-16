<?php

namespace Database\Seeders;

use App\Models\Coupon;
use App\Models\Employee;
use App\Models\Event;
use App\Models\Prize;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DatabaseSeeder::class);

        $event = Event::create([
            'name' => 'Doorprize Gathering 2026',
            'status' => 'aktif',
            'absensi_buka' => now()->subHour(),
            'absensi_tutup' => now()->addHours(8),
            'latitude' => -6.2000000,
            'longitude' => 106.8166666,
            'radius_meter' => 500,
            'display_token' => Str::random(24),
        ]);

        Prize::create(['event_id' => $event->id, 'name' => 'Motor', 'category' => 'utama', 'stock' => 2]);
        Prize::create(['event_id' => $event->id, 'name' => 'Voucher Belanja', 'category' => 'massal', 'stock' => 100]);

        Employee::factory()->count(1100)->create();

        $absenCount = (int) $this->command?->ask('Jumlah karyawan sample yang absen', 500) ?: 500;

        Employee::inRandomOrder()->take(min($absenCount, Employee::count()))->get()->each(function ($employee) use ($event) {
            Coupon::create([
                'event_id' => $event->id,
                'employee_id' => $employee->id,
                'code' => Coupon::generateCode($event->id),
                'latitude' => $event->latitude + (mt_rand(-200, 200) / 100000),
                'longitude' => $event->longitude + (mt_rand(-200, 200) / 100000),
                'accuracy' => mt_rand(5, 30),
                'gps_valid' => true,
                'created_at' => now()->subMinutes(mt_rand(1, 480)),
            ]);
        });
    }
}
