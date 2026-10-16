<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Event;
use App\Models\Prize;
use App\Models\Spin;
use App\Models\Winner;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SpinService
{
    public function eligibleCoupons(Event $event)
    {
        return $event->coupons()
            ->join('employees', 'employees.id', '=', 'coupons.employee_id')
            ->leftJoin('winners', 'winners.coupon_id', '=', 'coupons.id')
            ->where('employees.is_active', true)
            ->whereNull('winners.id')
            ->orderBy('coupons.code')
            ->get(['coupons.id', 'coupons.code']);
    }

    public function wheelSnapshot(Event $event, Spin $spin, ?int $cap = null): array
    {
        $codes = $event->coupons()
            ->join('employees', 'employees.id', '=', 'coupons.employee_id')
            ->leftJoin('winners', 'winners.coupon_id', '=', 'coupons.id')
            ->where('employees.is_active', true)
            ->where(fn ($q) => $q->whereNull('winners.id')->orWhere('winners.spin_id', $spin->id))
            ->orderBy('coupons.code')
            ->pluck('coupons.code');

        if ($cap === null || $codes->count() <= $cap) {
            return $codes->values()->all();
        }

        $winnerCodes = $spin->winners()->join('coupons', 'coupons.id', '=', 'winners.coupon_id')->pluck('coupons.code');

        return $winnerCodes->values()->merge($codes->reject(fn ($c) => $winnerCodes->contains($c))->take($cap - $winnerCodes->count())->values())->values()->all();
    }

    public function spin(Event $event, Prize $prize, int $userId): Spin
    {
        return DB::transaction(function () use ($event, $prize, $userId) {
            $event = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();

            if ($event->status !== 'spin') {
                throw ValidationException::withMessages(['event' => 'Acara tidak dalam mode spin.']);
            }

            Spin::whereIn('status', ['siap', 'berjalan', 'pemenang'])->where('updated_at', '<', now()->subMinutes(10))->update(['status' => 'selesai']);

            if ($event->spins()->whereIn('status', ['siap', 'berjalan', 'pemenang'])->exists()) {
                throw ValidationException::withMessages(['spin' => 'Masih ada spin yang belum selesai.']);
            }

            $prize = Prize::whereKey($prize->id)->lockForUpdate()->firstOrFail();
            $remaining = $prize->remainingStock();

            if ($remaining <= 0) {
                throw ValidationException::withMessages(['prize' => 'Stok hadiah habis.']);
            }

            $eligible = $this->eligibleCoupons($event);

            if ($eligible->isEmpty()) {
                throw ValidationException::withMessages(['prize' => 'Tidak ada peserta yang eligible.']);
            }

            $mode = $prize->category;
            $count = $mode === 'utama' ? 1 : min($remaining, $eligible->count());
            $picked = $eligible->shuffle()->take($count);
            $unserved = $mode === 'massal' ? max(0, $remaining - $eligible->count()) : 0;

            $spin = Spin::create([
                'event_id' => $event->id,
                'prize_id' => $prize->id,
                'mode' => $mode,
                'performed_by' => $userId,
                'winner_count' => 0,
                'unserved_count' => 0,
                'status' => 'siap',
            ]);

            return $spin->fresh();
        });
    }

    public function trigger(Spin $spin): Spin
    {
        return DB::transaction(function () use ($spin) {
            $spin = Spin::whereKey($spin->id)->lockForUpdate()->firstOrFail();

            if ($spin->status !== 'siap') {
                throw ValidationException::withMessages(['spin' => 'Spin ini tidak dapat dimulai.']);
            }

            $event = Event::whereKey($spin->event_id)->lockForUpdate()->firstOrFail();
            $prize = Prize::whereKey($spin->prize_id)->lockForUpdate()->firstOrFail();

            if ($event->status !== 'spin') {
                throw ValidationException::withMessages(['event' => 'Acara tidak dalam mode spin.']);
            }

            $remaining = $prize->remainingStock();
            $eligible = $this->eligibleCoupons($event);

            if ($remaining <= 0 || $eligible->isEmpty()) {
                $spin->update(['status' => 'selesai', 'winner_count' => 0, 'unserved_count' => $eligible->isEmpty() ? $remaining : $remaining]);

                throw ValidationException::withMessages(['spin' => 'Tidak ada peserta eligible atau stok hadiah habis.']);
            }

            $count = $spin->mode === 'utama' ? 1 : min($remaining, $eligible->count());
            $picked = $eligible->shuffle()->take($count);
            $unserved = $spin->mode === 'massal' ? max(0, $remaining - $eligible->count()) : 0;

            $now = now();
            $rows = $picked->map(fn ($c) => [
                'event_id' => $spin->event_id,
                'coupon_id' => $c->id,
                'prize_id' => $spin->prize_id,
                'spin_id' => $spin->id,
                'status' => 'menang',
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            DB::table('winners')->insert($rows);
            $spin->update(['status' => 'berjalan', 'winner_count' => $count, 'unserved_count' => $unserved]);

            return $spin->fresh();
        });
    }

    public function finish(Spin $spin): Spin
    {
        if ($spin->status === 'berjalan') {
            $spin->update(['status' => 'pemenang']);
        }

        return $spin;
    }

    public function complete(Spin $spin): Spin
    {
        if (in_array($spin->status, ['berjalan', 'pemenang'], true)) {
            $spin->update(['status' => 'selesai']);
        }

        return $spin;
    }

    public function disarm(Spin $spin): void
    {
        if ($spin->status === 'siap') {
            $spin->delete();
        }
    }

    public function handover(Winner $winner, int $userId): Winner
    {
        if ($winner->status === 'diserahkan') {
            return $winner;
        }

        if ($winner->status !== 'menang') {
            throw ValidationException::withMessages(['winner' => 'Kemenangan sudah dibatalkan.']);
        }

        $winner->update(['status' => 'diserahkan', 'taken_at' => now(), 'taken_by' => $userId]);

        return $winner->fresh();
    }

    public function cancel(Winner $winner, int $userId, string $reason): Winner
    {
        if ($winner->status === 'dibatalkan') {
            return $winner;
        }

        $winner->update(['status' => 'dibatalkan', 'cancel_reason' => $reason]);

        return $winner->fresh();
    }
}
