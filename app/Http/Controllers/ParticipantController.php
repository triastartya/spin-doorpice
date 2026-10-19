<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Employee;
use App\Models\Event;
use App\Support\Geo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ParticipantController extends Controller
{
    public function index()
    {
        return view('participant.absensi', ['event' => Event::whereIn('status', ['aktif', 'spin'])->latest('id')->first()]);
    }

    public function checkNik(Request $request)
    {
        $nik = $request->validate(['nik' => ['required', 'string']])['nik'];

        $employee = Employee::where('nik', $nik)->where('is_active', true)->first();

        if (! $employee) {
            throw ValidationException::withMessages(['nik' => 'NIK tidak terdaftar atau tidak aktif.']);
        }

        $event = Event::whereIn('status', ['aktif', 'spin'])->latest('id')->first();
        $coupon = $event?->coupons()->where('employee_id', $employee->id)->first();

        if ($coupon) {
            session(['participant' => $employee->id]);
        }

        return response()->json([
            'name' => $employee->name,
            'department' => $employee->department,
            'has_coupon' => (bool) $coupon,
            'code' => $coupon?->code,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nik' => ['required', 'string'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'accuracy' => ['nullable', 'numeric'],
        ]);

        $employee = Employee::where('nik', $data['nik'])->where('is_active', true)->first();

        if (! $employee) {
            throw ValidationException::withMessages(['nik' => 'NIK tidak terdaftar atau tidak aktif.']);
        }

        $event = Event::whereIn('status', ['aktif', 'spin'])->latest('id')->first();

        if (! $event) {
            throw ValidationException::withMessages(['event' => 'Tidak ada acara yang aktif.']);
        }

        if (($event->absensi_buka && now()->lt($event->absensi_buka)) || ($event->absensi_tutup && now()->gt($event->absensi_tutup))) {
            Log::info('absensi ditolak jadwal', ['nik' => $data['nik'], 'event_id' => $event->id, 'lat' => $data['latitude'], 'lng' => $data['longitude']]);

            throw ValidationException::withMessages(['gps' => 'Absensi di luar jadwal acara. Jadwal: '.($event->absensi_buka?->format('d M Y H:i') ?? 'tidak dibatasi').' – '.($event->absensi_tutup?->format('d M Y H:i') ?? 'tidak dibatasi').'. Radius lokasi: '.$event->radius_meter.' meter.']);
        }

        if ($coupon = $event->coupons()->where('employee_id', $employee->id)->first()) {
            session(['participant' => $employee->id]);

            return response()->json([
                'code' => $coupon->code,
                'event' => $event->name,
                'name' => $employee->name,
                'employee_id' => $employee->id,
                'already_attended' => true,
            ]);
        }

        $distance = $event->latitude !== null
            ? Geo::haversineMeters((float) $event->latitude, (float) $event->longitude, (float) $data['latitude'], (float) $data['longitude'])
            : null;

        $valid = $distance === null || $distance <= $event->radius_meter;

        if (! $valid) {
            Log::info('absensi ditolak radius', ['nik' => $data['nik'], 'event_id' => $event->id, 'lat' => $data['latitude'], 'lng' => $data['longitude'], 'accuracy' => $data['accuracy'] ?? null, 'distance' => round($distance)]);

            throw ValidationException::withMessages(['gps' => 'Posisi Anda di luar radius acara ('.round($distance).' m dari pusat, maksimal '.$event->radius_meter.' m). Jadwal absensi: '.($event->absensi_buka?->format('d M Y H:i') ?? 'tidak dibatasi').' – '.($event->absensi_tutup?->format('d M Y H:i') ?? 'tidak dibatasi').'.']);
        }

        $next = (int) Coupon::where('event_id', $event->id)->max('sequence') + 1;

        $coupon = Coupon::create([
            'event_id' => $event->id,
            'employee_id' => $employee->id,
            'code' => 'AST'.str_pad((string) $next, 4, '0', STR_PAD_LEFT),
            'sequence' => $next,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'accuracy' => $data['accuracy'] ?? null,
            'gps_valid' => true,
        ]);

        session(['participant' => $employee->id]);

        return response()->json([
            'code' => $coupon->code,
            'event' => $event->name,
            'name' => $employee->name,
            'employee_id' => $employee->id,
        ]);
    }

    public function dashboard()
    {
        $employeeId = session('participant');

        if (! $employeeId) {
            return redirect('/absensi');
        }

        $employee = Employee::findOrFail($employeeId);

        $event = Event::whereIn('status', ['aktif', 'spin'])->latest('id')->first();

        $coupon = $event?->coupons()->where('employee_id', $employee->id)->with('winner.prize')->first();

        return view('participant.dashboard', compact('employee', 'event', 'coupon'));
    }

    public function winners(Request $request)
    {
        $employeeId = session('participant');

        if (! $employeeId) {
            return redirect('/absensi');
        }

        $employee = Employee::findOrFail($employeeId);

        $event = Event::whereIn('status', ['aktif', 'spin'])->latest('id')->first();

        if (! $event) {
            return view('participant.winners', ['employee' => $employee, 'event' => null, 'winners' => collect(), 'prizes' => collect(), 'q' => null, 'prizeId' => null]);
        }

        $query = $event->winners()->with(['coupon.employee', 'prize']);

        if ($q = trim((string) $request->get('q'))) {
            $query->where(function ($w) use ($q) {
                $w->whereHas('coupon', fn ($c) => $c->where('code', 'like', "%{$q}%"))
                    ->orWhereHas('coupon.employee', fn ($e) => $e->where('name', 'like', "%{$q}%"));
            });
        }

        if ($prizeId = $request->get('prize_id')) {
            $query->where('prize_id', $prizeId);
        }

        $winners = $query->orderByDesc('id')->paginate(30)->withQueryString();
        $prizes = $event->prizes()->orderBy('name')->get(['id', 'name']);

        return view('participant.winners', compact('employee', 'event', 'winners', 'prizes', 'q', 'prizeId'));
    }

    public function logout(Request $request)
    {
        $request->session()->forget('participant');

        return redirect('/absensi');
    }
}
