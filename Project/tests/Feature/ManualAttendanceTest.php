<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Employee;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_tidak_bisa_akses(): void
    {
        $this->get('/operator/absensi-manual')->assertRedirect('/login');
    }

    public function test_halaman_pencarian_menampilkan_karyawan(): void
    {
        Event::factory()->create();
        Employee::factory()->create(['nik' => 'EMP001', 'name' => 'Budi']);
        $user = User::factory()->create(['role' => 'operator']);

        $this->actingAs($user)->get('/operator/absensi-manual?q=Budi')
            ->assertOk()
            ->assertSee('Budi')
            ->assertSee('Absenkan');
    }

    public function test_absensi_manual_berhasil_tanpa_gps(): void
    {
        Event::factory()->create();
        $employee = Employee::factory()->create(['nik' => 'EMP001']);
        $user = User::factory()->create(['role' => 'operator']);

        $response = $this->actingAs($user)->post('/operator/manual', ['employee_id' => $employee->id]);

        $response->assertRedirect();
        $coupon = Coupon::first();
        $this->assertSame('AST0001', $coupon->code);
        $this->assertSame('manual', $coupon->attendance_method);
        $this->assertFalse($coupon->gps_valid);
        $this->assertNull($coupon->latitude);
        $this->assertNull($coupon->longitude);
    }

    public function test_absensi_manual_ditolak_jika_sudah_absen(): void
    {
        Event::factory()->create();
        $employee = Employee::factory()->create();
        $user = User::factory()->create(['role' => 'operator']);

        $this->actingAs($user)->post('/operator/manual', ['employee_id' => $employee->id])->assertRedirect();
        $this->actingAs($user)->post('/operator/manual', ['employee_id' => $employee->id])->assertSessionHasErrors();

        $this->assertSame(1, Coupon::count());
    }

    public function test_absensi_manual_ditolak_di_luar_jadwal(): void
    {
        Event::factory()->create(['absensi_buka' => now()->addDay(), 'absensi_tutup' => now()->addDays(2)]);
        $employee = Employee::factory()->create();
        $user = User::factory()->create(['role' => 'operator']);

        $this->actingAs($user)->post('/operator/manual', ['employee_id' => $employee->id])->assertSessionHasErrors();

        $this->assertSame(0, Coupon::count());
    }

    public function test_absensi_manual_ditolak_tanpa_acara_aktif(): void
    {
        Event::factory()->create(['status' => 'selesai']);
        $employee = Employee::factory()->create();
        $user = User::factory()->create(['role' => 'operator']);

        $this->actingAs($user)->post('/operator/manual', ['employee_id' => $employee->id])->assertNotFound();

        $this->assertSame(0, Coupon::count());
    }
}
