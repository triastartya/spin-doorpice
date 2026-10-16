<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Employee;
use App\Models\Event;
use App\Models\Prize;
use App\Models\User;
use App\Models\Winner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbsensiTest extends TestCase
{
    use RefreshDatabase;

    public function test_nik_tidak_terdaftar_ditolak(): void
    {
        Event::factory()->create();

        $this->postJson('/absensi', ['nik' => 'X1', 'latitude' => -6.2, 'longitude' => 106.8166666])
            ->assertStatus(422);
    }

    public function test_absensi_di_luar_radius_ditolak(): void
    {
        $event = Event::factory()->create(['radius_meter' => 100]);
        Employee::factory()->create(['nik' => 'EMP001']);

        $this->postJson('/absensi', ['nik' => 'EMP001', 'latitude' => -6.3, 'longitude' => 106.9])
            ->assertStatus(422)
            ->assertJsonValidationErrors('gps');

        $this->assertSame(0, $event->coupons()->count());
    }

    public function test_absensi_di_luar_jadwal_ditolak(): void
    {
        Event::factory()->create(['absensi_buka' => now()->addDay(), 'absensi_tutup' => now()->addDays(2)]);
        Employee::factory()->create(['nik' => 'EMP001']);

        $this->postJson('/absensi', ['nik' => 'EMP001', 'latitude' => -6.2, 'longitude' => 106.8166666])
            ->assertStatus(422)
            ->assertJsonValidationErrors('gps');
    }

    public function test_absensi_sukses_membuat_kupon_dan_tidak_bisa_duplikat(): void
    {
        Event::factory()->create();
        Employee::factory()->create(['nik' => 'EMP001']);

        $this->postJson('/absensi', ['nik' => 'EMP001', 'latitude' => -6.2, 'longitude' => 106.8166666])
            ->assertOk()
            ->assertJsonStructure(['code']);

        $this->assertSame(1, Coupon::count());

        $this->postJson('/absensi', ['nik' => 'EMP001', 'latitude' => -6.2, 'longitude' => 106.8166666])
            ->assertOk()
            ->assertJsonPath('already_attended', true)
            ->assertJsonPath('code', Coupon::first()->code);

        $this->assertSame(1, Coupon::count());
    }

    public function test_kode_kupon_berurutan_format_ast(): void
    {
        Event::factory()->create();
        Employee::factory()->create(['nik' => 'EMP001']);

        $this->postJson('/absensi', ['nik' => 'EMP001', 'latitude' => -6.2, 'longitude' => 106.8166666])
            ->assertOk()
            ->assertJsonPath('code', 'AST0001');
    }
}
