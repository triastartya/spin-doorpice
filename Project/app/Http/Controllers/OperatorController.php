<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Employee;
use App\Models\Event;
use App\Models\Prize;
use App\Models\Spin;
use App\Models\Winner;
use App\Services\SpinService;
use App\Support\ActiveEvent;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

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
            ActiveEvent::authorize($event);
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

    public function manualAttendance(Request $request)
    {
        $event = ActiveEvent::get();
        abort_unless($event, 404, 'Tidak ada acara aktif.');
        $employees = collect();
        $q = trim((string) $request->get('q'));

        if ($q !== '') {
            $employees = Employee::where('is_active', true)
                ->where(fn ($query) => $query->where('nik', 'like', "%{$q}%")->orWhere('name', 'like', "%{$q}%")->orWhere('department', 'like', "%{$q}%"))
                ->with(['coupons' => fn ($query) => $query->where('event_id', $event->id)])
                ->limit(30)->get();
        }

        return view('operator.manual-attendance', compact('event', 'employees', 'q'));
    }

    public function storeManualAttendance(Request $request)
    {
        $data = $request->validate(['employee_id' => ['required', 'exists:employees,id']]);
        $event = ActiveEvent::get();
        abort_unless($event, 404, 'Tidak ada acara aktif.');

        if (($event->absensi_buka && now()->lt($event->absensi_buka)) || ($event->absensi_tutup && now()->gt($event->absensi_tutup))) {
            return back()->withErrors(['employee_id' => 'Absensi di luar jadwal acara.'])->withInput();
        }

        $employee = Employee::whereKey($data['employee_id'])->where('is_active', true)->firstOrFail();

        try {
            [$coupon, $exists] = DB::transaction(function () use ($event, $employee) {
                $event = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();

                if ($coupon = $event->coupons()->where('employee_id', $employee->id)->first()) {
                    return [$coupon, true];
                }

                $next = (int) $event->coupons()->max('sequence') + 1;

                return [Coupon::create([
                    'event_id' => $event->id,
                    'employee_id' => $employee->id,
                    'code' => 'AST'.str_pad((string) $next, 4, '0', STR_PAD_LEFT),
                    'sequence' => $next,
                    'gps_valid' => false,
                    'attendance_method' => 'manual',
                ]), false];
            }, 3);
        } catch (QueryException $e) {
            report($e);

            return back()->withErrors(['employee_id' => 'Server sedang sibuk memproses absensi. Mohon coba lagi.'])->withInput();
        }

        if ($exists) {
            return back()->withErrors(['employee_id' => 'Peserta ini sudah memiliki kupon ('.$coupon->code.') pada acara ini.'])->withInput();
        }

        return redirect(url('admin/events/'.$event->id.'/attendance'))->with('success', 'Absensi manual berhasil. Kupon '.$coupon->code.' untuk '.$employee->name.'.');
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
            'photo_data' => ['required_without:photo', 'nullable', 'string', 'regex:/^data:image\/(jpeg|png|webp);base64,[A-Za-z0-9+\/=]+$/'],
        ], ['photo_data.required_without' => 'Foto bukti pengambilan wajib diambil.']);
        $code = strtoupper(trim($data['code']));
        $winner = Winner::whereHas('coupon', fn ($query) => $query->where('code', $code))->with(['coupon.employee', 'prize'])->first();

        if (! $winner) {
            return back()->withErrors(['code' => 'Kupon tidak ditemukan.']);
        }

        if ($winner->status === 'diserahkan') {
            return back()->with('success', 'Hadiah sudah pernah diserahkan kepada '.$winner->coupon->employee->name.'.');
        }

        abort_if($winner->status !== 'menang', 422, 'Kemenangan sudah dibatalkan.');

        if ($request->hasFile('photo')) {
            $name = $request->file('photo')->hashName();
            File::ensureDirectoryExists(public_path('uploads/handover'));
            $request->file('photo')->move(public_path('uploads/handover'), $name);
            $winner->update(['photo_path' => 'uploads/handover/'.$name]);
        } elseif (preg_match('/^data:image\/(jpeg|png|webp);base64,(.+)$/', $request->input('photo_data'), $matches)) {
            $photo = base64_decode($matches[2], true);
            if ($photo === false || @getimagesizefromstring($photo) === false) {
                return back()->withErrors(['photo_data' => 'Foto bukti tidak valid. Silakan ambil ulang.']);
            }
            File::ensureDirectoryExists(public_path('uploads/handover'));
            $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
            $name = bin2hex(random_bytes(16)).'.'.$extension;
            if (file_put_contents(public_path('uploads/handover/'.$name), $photo) === false) {
                return back()->withErrors(['photo_data' => 'Foto gagal disimpan. Silakan coba lagi.']);
            }
            $winner->update(['photo_path' => 'uploads/handover/'.$name]);
        }

        $service->handover($winner, $request->user()->id);

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
                    : 10;
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
                'prize_name' => $spin->prize->name, 'prize_image' => $spin->prize->image_url, 'spin_duration' => $spin->prize->category === 'utama' ? $spin->prize->spin_duration : null, 'last_digit_duration' => $spin->prize->category === 'utama' ? $spin->prize->last_digit_duration : null, 'winner_count' => $spin->winner_count, 'unserved_count' => $spin->unserved_count,
                'codes' => $service->wheelSnapshot($event, $spin),
                'winners' => $spin->status === 'siap' ? [] : $spin->winners->map(fn ($winner) => ['code' => $winner->coupon->code, 'name' => $winner->coupon->employee->name, 'nik' => $winner->coupon->employee->nik, 'department' => $winner->coupon->employee->department]),
            ] : null,
        ]);
    }
}
