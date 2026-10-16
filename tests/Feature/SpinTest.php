<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Employee;
use App\Models\Event;
use App\Models\Prize;
use App\Models\User;
use App\Models\Winner;
use App\Services\SpinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SpinTest extends TestCase
{
    use RefreshDatabase;

    protected function setupEventWithCoupons(int $couponCount, array $prizeAttrs = []): Event
    {
        $event = Event::factory()->create(['status' => 'spin']);
        $prize = Prize::create(array_merge(['event_id' => $event->id, 'name' => 'Hadiah', 'category' => 'utama', 'stock' => 1], $prizeAttrs));
        Employee::factory()->count($couponCount)->create()->each(fn ($employee) => Coupon::create([
            'event_id' => $event->id,
            'employee_id' => $employee->id,
            'code' => Coupon::generateCode($event->id),
            'gps_valid' => true,
        ]));

        return $event->refresh();
    }

    public function test_spin_utama_mengurangi_stok(): void
    {
        $event = $this->setupEventWithCoupons(5, ['stock' => 2, 'category' => 'utama']);
        $operator = User::factory()->create(['role' => 'operator']);

        $this->actingAs($operator)->post('/operator/spin', ['event_id' => $event->id, 'prize_id' => $event->prizes->first()->id]);

        $spin = $event->spins()->latest('id')->first();
        $this->assertSame('siap', $spin->status);
        $this->assertSame(0, Winner::count());
        $this->post('/display/'.$event->display_token.'/trigger/'.$spin->id);
        $this->assertSame('berjalan', $spin->refresh()->status);
        $spin->update(['status' => 'selesai']);
        $prize = $event->prizes->first()->refresh();
        $this->assertSame(1, Winner::count());
        $this->assertSame(1, $prize->remainingStock());

        $this->post('/operator/spin', ['event_id' => $event->id, 'prize_id' => $prize->id]);
        $spin2 = $event->spins()->latest('id')->first();
        $this->post('/display/'.$event->display_token.'/trigger/'.$spin2->id);
        $spin2->update(['status' => 'selesai']);
        $this->assertSame(2, Winner::count());
        $this->assertSame(0, $prize->refresh()->remainingStock());

        $this->post('/operator/spin', ['event_id' => $event->id, 'prize_id' => $prize->id]);
        $this->assertSame(2, Winner::count());
    }

    public function test_spin_massal_ambil_min_stok_eligible_dan_hitung_unserved(): void
    {
        $event = $this->setupEventWithCoupons(4, ['stock' => 10, 'category' => 'massal']);
        $operator = User::factory()->create(['role' => 'operator']);

        $this->actingAs($operator)->post('/operator/spin', ['event_id' => $event->id, 'prize_id' => $event->prizes->first()->id]);
        $spin = $event->spins()->first();
        $this->post('/display/'.$event->display_token.'/trigger/'.$spin->id);

        $spin = $spin->refresh();
        $this->assertSame('berjalan', $spin->status);
        $this->assertSame(4, $spin->winner_count);
        $this->assertSame(6, $spin->unserved_count);
        $this->assertSame(4, Winner::count());
    }

    public function test_peserta_yang_sudah_menang_tidak_eligible_lagi(): void
    {
        $event = $this->setupEventWithCoupons(3, ['stock' => 3, 'category' => 'utama']);
        $operator = User::factory()->create(['role' => 'operator']);

        $this->actingAs($operator)->post('/operator/spin', ['event_id' => $event->id, 'prize_id' => $event->prizes->first()->id]);
        $spin = $event->spins()->latest('id')->first();
        $this->post('/display/'.$event->display_token.'/trigger/'.$spin->id);
        $winnerCouponId = Winner::first()->coupon_id;
        $event->spins()->latest('id')->first()->update(['status' => 'selesai']);

        $this->post('/operator/spin', ['event_id' => $event->id, 'prize_id' => $event->prizes->first()->id]);
        $spin2 = $event->spins()->latest('id')->first();
        $this->post('/display/'.$event->display_token.'/trigger/'.$spin2->id);

        $this->assertNotSame($winnerCouponId, Winner::latest('id')->first()->coupon_id);
    }

    public function test_karyawan_tidak_aktif_tidak_eligible(): void
    {
        $event = Event::factory()->create(['status' => 'spin']);
        Prize::create(['event_id' => $event->id, 'name' => 'P', 'category' => 'utama', 'stock' => 1]);
        $active = Employee::factory()->create(['is_active' => true]);
        $inactive = Employee::factory()->create(['is_active' => false]);

        foreach ([$active, $inactive] as $employee) {
            Coupon::create(['event_id' => $event->id, 'employee_id' => $employee->id, 'code' => Coupon::generateCode($event->id)]);
        }

        $service = app(SpinService::class);
        $this->assertSame(1, $service->eligibleCoupons($event)->count());
    }

    public function test_pembatalan_mengembalikan_stok_tapi_pemenang_tetap_gugur(): void
    {
        $event = $this->setupEventWithCoupons(2, ['stock' => 1, 'category' => 'utama']);
        $operator = User::factory()->create(['role' => 'operator']);

        $this->actingAs($operator)->post('/operator/spin', ['event_id' => $event->id, 'prize_id' => $event->prizes->first()->id]);
        $spin = $event->spins()->latest('id')->first();
        $this->post('/display/'.$event->display_token.'/trigger/'.$spin->id);

        $winner = Winner::first();
        app(SpinService::class)->cancel($winner, $operator->id, 'tidak hadir');
        $event->spins()->latest('id')->first()->update(['status' => 'selesai']);

        $this->assertSame('dibatalkan', $winner->refresh()->status);
        $this->assertSame(1, $event->prizes->first()->refresh()->remainingStock());

        $this->post('/operator/spin', ['event_id' => $event->id, 'prize_id' => $event->prizes->first()->id]);
        $spin2 = $event->spins()->latest('id')->first();
        $this->post('/display/'.$event->display_token.'/trigger/'.$spin2->id);
        $this->assertSame(2, Winner::count());
        $this->assertNotSame($winner->coupon_id, Winner::latest('id')->first()->coupon_id);
    }

    public function test_penyerahan_idempotent(): void
    {
        $event = $this->setupEventWithCoupons(1);
        $operator = User::factory()->create(['role' => 'operator']);

        $this->actingAs($operator)->post('/operator/spin', ['event_id' => $event->id, 'prize_id' => $event->prizes->first()->id]);
        $spin = $event->spins()->latest('id')->first();
        $this->post('/display/'.$event->display_token.'/trigger/'.$spin->id);

        $code = Winner::first()->coupon->code;

        $this->post('/operator/kupon/handover', ['code' => $code]);
        $winner = Winner::first()->refresh();
        $this->assertSame('diserahkan', $winner->status);
        $this->assertNotNull($winner->taken_at);

        $this->post('/operator/kupon/handover', ['code' => $code]);
        $winner = Winner::first()->refresh();
        $this->assertSame('diserahkan', $winner->status);
        $this->assertSame($operator->id, $winner->taken_by);
        $this->assertSame(now()->timestamp, $winner->taken_at->timestamp);
    }

    public function test_spin_ditolak_bila_acara_bukan_mode_spin(): void
    {
        $event = $this->setupEventWithCoupons(2);
        $event->update(['status' => 'aktif']);
        $operator = User::factory()->create(['role' => 'operator']);

        $this->expectException(ValidationException::class);
        app(SpinService::class)->spin($event, $event->prizes->first(), $operator->id);
    }
}
