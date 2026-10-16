<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Prize;
use App\Models\Spin;
use App\Models\Winner;
use App\Services\SpinService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

class OperatorController extends Controller
{
    public function index()
    {
        $event = Event::whereIn('status', ['aktif', 'spin'])->with('prizes')->latest('id')->first();
        $spins = $event?->spins()->with(['prize', 'winners.coupon.employee'])->latest('id')->take(20)->get() ?? collect();
        $currentSpin = $event?->spins()->whereIn('status', ['siap', 'berjalan', 'pemenang'])->with('prize')->latest('id')->first();

        return view('operator.index', compact('event', 'spins', 'currentSpin'));
    }

    public function arm(Request $request, SpinService $service)
    {
        $data = $request->validate(['event_id' => ['required', 'exists:events,id'], 'prize_id' => ['required', 'exists:prizes,id']]);
        $event = Event::findOrFail($data['event_id']);
        if ($request->user()->role === 'operator') {
            \App\Support\ActiveEvent::authorize($event);
        }
        $service->spin($event, Prize::findOrFail($data['prize_id']), $request->user()->id);

        return back()->with('success', 'Hadiah disiapkan. Buka layar display lalu tekan SPIN atau Enter untuk memulai undian.');
    }

    public function disarm(string $spinId, SpinService $service)
    {
        $service->disarm(Spin::findOrFail($spinId));

        return back()->with('success', 'Persiapan spin dibatalkan.');
    }

    public function trigger(string $token, string $spinId, SpinService $service)
    {
        $event = Event::where('display_token', $token)->firstOrFail();
        $spin = Spin::whereKey($spinId)->where('event_id', $event->id)->firstOrFail();
        $spin = $service->trigger($spin);

        return response()->json(['ok' => true, 'status' => $spin->status]);
    }

    public function finish(string $spinId, SpinService $service)
    {
        $service->finish(Spin::findOrFail($spinId));

        return response()->json(['ok' => true]);
    }

    public function complete(string $token, string $spinId, SpinService $service)
    {
        $event = Event::where('display_token', $token)->firstOrFail();
        $spin = Spin::whereKey($spinId)->where('event_id', $event->id)->firstOrFail();
        $service->complete($spin);

        return response()->json(['ok' => true]);
    }

    public function couponPage(Request $request)
    {
        $winner = null;

        if ($code = strtoupper(trim($request->get('code', '')))) {
            $winner = Winner::whereHas('coupon', fn ($query) => $query->where('code', $code))
                ->with(['coupon.employee', 'prize', 'event'])
                ->first();
        }

        return view('operator.coupon', ['winner' => $winner, 'code' => $code]);
    }

    public function handover(Request $request, SpinService $service)
    {
        $data = $request->validate([
            'code' => ['required'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp'],
            'photo_data' => ['nullable', 'string'],
        ]);
        $code = strtoupper(trim($data['code']));
        $winner = Winner::whereHas('coupon', fn ($query) => $query->where('code', $code))->with(['coupon.employee', 'prize'])->first();

        if (! $winner) {
            return back()->withErrors(['code' => 'Kupon tidak ditemukan.']);
        }

        $service->handover($winner, $request->user()->id);

        if ($request->filled('photo_data') && preg_match('/^data:image\/(jpeg|png|webp);base64,(.+)$/', $request->input('photo_data'), $matches)) {
            File::ensureDirectoryExists(public_path('uploads/handover'));
            $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
            $name = bin2hex(random_bytes(16)).'.'.$extension;
            file_put_contents(public_path('uploads/handover/'.$name), base64_decode($matches[2], true));
            $winner->update(['photo_path' => 'uploads/handover/'.$name]);
        } elseif ($request->hasFile('photo')) {
            $name = $request->file('photo')->hashName();
            File::ensureDirectoryExists(public_path('uploads/handover'));
            $request->file('photo')->move(public_path('uploads/handover'), $name);
            $winner->update(['photo_path' => 'uploads/handover/'.$name]);
        }

        return back()->with('success', 'Hadiah diserahkan kepada '.$winner->coupon->employee->name.'.');
    }

    public function cancel(Request $request, string $winnerId, SpinService $service)
    {
        $reason = $request->validate(['reason' => ['required', 'string', 'min:3']])['reason'];
        $service->cancel(Winner::findOrFail($winnerId), $request->user()->id, $reason);

        return back()->with('success', 'Kemenangan dibatalkan.');
    }

    public function displayState(string $token, SpinService $service)
    {
        $event = Event::where('display_token', $token)->firstOrFail();
        $spin = $event->spins()->with(['prize', 'winners.coupon.employee'])->latest('id')->first();

        if ($spin) {
            $transitioned = false;
            if ($spin->status === 'berjalan') {
                $duration = $spin->prize->category === 'utama'
                    ? ($spin->prize->spin_duration * 3) + $spin->prize->last_digit_duration + 2
                    : max(3, $spin->winner_count);
                if ($spin->updated_at->lt(now()->subSeconds($duration))) {
                    $service->complete($spin->refresh());
                    $transitioned = true;
                }
            }
            if ($transitioned) {
                $spin = $spin->refresh()->load(['prize', 'winners.coupon.employee']);
            }
        }

        return response()->json([
            'event' => $event->only(['id', 'name', 'status']),
            'phase' => $spin?->status ?? 'idle',
            'spin' => $spin ? [
                'id' => $spin->id, 'mode' => $spin->mode, 'status' => $spin->status,
                'prize_name' => $spin->prize->name, 'prize_image' => $spin->prize->image_path ? '/'.$spin->prize->image_path : null, 'spin_duration' => $spin->prize->category === 'utama' ? $spin->prize->spin_duration : null, 'last_digit_duration' => $spin->prize->category === 'utama' ? $spin->prize->last_digit_duration : null, 'winner_count' => $spin->winner_count, 'unserved_count' => $spin->unserved_count,
                'codes' => $service->wheelSnapshot($event, $spin),
                'winners' => $spin->status === 'siap' ? [] : $spin->winners->map(fn ($winner) => ['code' => $winner->coupon->code, 'name' => $winner->coupon->employee->name, 'nik' => $winner->coupon->employee->nik, 'department' => $winner->coupon->employee->department]),
            ] : null,
        ]);
    }
}
