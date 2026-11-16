<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Employee;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbsensiTest extends TestCase
{
    use RefreshDatabase;

    public function test_nik_numeric_diterima(): void
    {
        Event::factory()->create();
        Employee::factory()->create(['nik' => '221001']);

        $this->postJson('/absensi/check-nik', ['nik' => '221001'])
            ->assertOk()
            ->assertJsonPath('name', fn ($name) => is_string($name));
    }

    public function test_nik_tidak_terdaftar_ditolak(): void
    {
        Event::factory()->create();

        $this->postJson('/absensi', ['nik' => 'X1', 'latitude' => -6.2, 'longitude' => 106.8166666])
            ->assertStatus(422);
    }

    public function test_absensi_di_luar_radius_ditolak(): void
    {
        $event = Event::factory()->create(['radius_meter' => 100]);
        Employee::factory()->create(['nik' => '221001']);

        $this->postJson('/absensi', ['nik' => '221001', 'latitude' => -6.3, 'longitude' => 106.9])
            ->assertStatus(422)
            ->assertJsonValidationErrors('gps');

        $this->assertSame(0, $event->coupons()->count());
    }

    public function test_absensi_di_luar_jadwal_ditolak(): void
    {
        Event::factory()->create(['absensi_buka' => now()->addDay(), 'absensi_tutup' => now()->addDays(2)]);
        Employee::factory()->create(['nik' => '221001']);

        $this->postJson('/absensi', ['nik' => '221001', 'latitude' => -6.2, 'longitude' => 106.8166666])
            ->assertStatus(422)
            ->assertJsonValidationErrors('gps');
    }

    public function test_absensi_sukses_membuat_kupon_dan_tidak_bisa_duplikat(): void
    {
        Event::factory()->create();
        Employee::factory()->create(['nik' => '221001']);

        $this->postJson('/absensi', ['nik' => '221001', 'latitude' => -6.2, 'longitude' => 106.8166666])
            ->assertOk()
            ->assertJsonStructure(['code']);

        $this->assertSame(1, Coupon::count());

        $this->postJson('/absensi', ['nik' => '221001', 'latitude' => -6.2, 'longitude' => 106.8166666])
            ->assertOk()
            ->assertJsonPath('already_attended', true)
            ->assertJsonPath('code', Coupon::first()->code);

        $this->assertSame(1, Coupon::count());
    }

    public function test_kode_kupon_berurutan_format_ast(): void
    {
        Event::factory()->create();
        Employee::factory()->create(['nik' => '221001']);

        $this->postJson('/absensi', ['nik' => '221001', 'latitude' => -6.2, 'longitude' => 106.8166666])
            ->assertOk()
            ->assertJsonPath('code', 'AST0001');
    }

    public function test_satu_perangkat_tidak_bisa_absen_nik_berbeda(): void
    {
        Event::factory()->create();
        Employee::factory()->create(['nik' => '221001']);
        Employee::factory()->create(['nik' => '221002']);
        $device = str_repeat('a', 40);

        $this->withCredentials()->withCookie('absensi_device', $device)
            ->postJson('/absensi', ['nik' => '221001', 'latitude' => -6.2, 'longitude' => 106.8166666])
            ->assertOk();

        $coupon = Coupon::first();
        $this->assertSame($device, $coupon->device_token);
        $this->assertNotNull($coupon->device_agent);
        $this->assertNotNull($coupon->device_ip);

        $this->withCredentials()->withCookie('absensi_device', $device)
            ->postJson('/absensi/check-nik', ['nik' => '221001'])
            ->assertOk();

        $this->withCredentials()->withCookie('absensi_device', $device)
            ->postJson('/absensi/check-nik', ['nik' => '221002'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('nik');

        $this->withCredentials()->withCookie('absensi_device', $device)
            ->postJson('/absensi', ['nik' => '221002', 'latitude' => -6.2, 'longitude' => 106.8166666])
            ->assertJsonValidationErrors('nik');

        $this->assertSame(1, Coupon::count());

        $this->withCredentials()->withCookie('absensi_device', str_repeat('b', 40))
            ->postJson('/absensi', ['nik' => '221002', 'latitude' => -6.2, 'longitude' => 106.8166666])
            ->assertOk();

        $this->assertSame(2, Coupon::count());
    }

    public function test_halaman_absensi_menampilkan_nik_terikat_perangkat(): void
    {
        Event::factory()->create();
        Employee::factory()->create(['nik' => '221001']);
        $device = str_repeat('a', 40);

        $this->withCredentials()->withCookie('absensi_device', $device)
            ->postJson('/absensi', ['nik' => '221001', 'latitude' => -6.2, 'longitude' => 106.8166666])
            ->assertOk();

        $this->withCookie('absensi_device', $device)
            ->get('/absensi')
            ->assertOk()
            ->assertSee('221001')
            ->assertSee(Coupon::first()->code)
            ->assertSessionHas('participant', Coupon::first()->employee_id);
    }

    public function test_kupon_manual_diikat_saat_diambil_dari_halaman_publik(): void
    {
        $event = Event::factory()->create();
        $employee = Employee::factory()->create(['nik' => '221001']);
        $coupon = Coupon::create(['event_id' => $event->id, 'employee_id' => $employee->id, 'code' => 'AST0001', 'sequence' => 1, 'attendance_method' => 'manual', 'gps_valid' => false]);
        $device = str_repeat('c', 40);
        $this->withCredentials()->withCookie('absensi_device', $device)
            ->postJson('/absensi/check-nik', ['nik' => $employee->nik])->assertOk();
        $this->assertSame($device, $coupon->fresh()->device_token);
        $this->assertSame('manual', $coupon->fresh()->attendance_method);
    }

    public function test_hapus_device_melepas_ikatan_tanpa_menghapus_kupon(): void
    {
        $event = Event::factory()->create();
        $other = Event::factory()->create(['status' => 'draft']);
        $employee = Employee::factory()->create();
        $coupon = Coupon::create(['event_id' => $event->id, 'employee_id' => $employee->id, 'code' => 'AST0001', 'sequence' => 1, 'device_token' => str_repeat('a', 40), 'device_agent' => 'Browser Uji', 'device_ip' => '127.0.0.1']);
        $url = "/admin/events/{$event->id}/attendance/{$coupon->id}/device";
        $this->delete($url)->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['role' => 'operator_hadiah']))->delete($url)->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->delete("/admin/events/{$other->id}/attendance/{$coupon->id}/device")->assertNotFound();
        $this->actingAs(User::factory()->create(['role' => 'operator']))->delete($url)->assertRedirect();
        $coupon->refresh();
        $this->assertNull($coupon->device_token);
        $this->assertNull($coupon->device_agent);
        $this->assertNull($coupon->device_ip);
        $this->assertSame('AST0001', $coupon->code);
        $this->assertSame(1, Coupon::count());
    }

    public function test_info_perangkat_tampil_di_list_absensi_admin(): void
    {
        $event = Event::factory()->create();
        Employee::factory()->create(['nik' => '221001']);
        $admin = User::factory()->create(['role' => 'admin']);
        $device = str_repeat('a', 40);

        $this->withCredentials()->withCookie('absensi_device', $device)
            ->postJson('/absensi', ['nik' => '221001', 'latitude' => -6.2, 'longitude' => 106.8166666])
            ->assertOk();

        $this->actingAs($admin)
            ->get("/admin/events/{$event->id}/attendance")
            ->assertOk()
            ->assertSee($device);
    }
}
