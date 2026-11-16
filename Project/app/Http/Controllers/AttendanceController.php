<?php

namespace App\Http\Controllers;

use App\Exports\AttendanceExport;
use App\Models\Coupon;
use App\Models\Event;
use App\Support\ActiveEvent;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceController extends Controller
{
    public function index(Request $request, Event $event)
    {
        ActiveEvent::authorize($event);

        $coupons = $this->filteredCoupons($request, $event)->orderBy('code')->paginate(50)->withQueryString();

        $departments = $event->coupons()->join('employees', 'employees.id', '=', 'coupons.employee_id')->orderBy('employees.department')->distinct()->pluck('employees.department');

        return view('admin.attendance', compact('event', 'coupons', 'departments'));
    }

    public function print(Request $request, Event $event)
    {
        ActiveEvent::authorize($event);

        $coupons = $this->filteredCoupons($request, $event)->with('employee')->orderBy('code')->get();

        return response()->view('admin.attendance-print', compact('event', 'coupons'))->header('Content-Type', 'text/html');
    }

    public function printOne(Event $event, Coupon $coupon)
    {
        ActiveEvent::authorize($event);
        abort_unless($coupon->event_id === $event->id, 404);
        $coupon->load('employee');

        return response()->view('admin.attendance-print-one', compact('event', 'coupon'))->header('Content-Type', 'text/html');
    }

    public function destroyDevice(Event $event, Coupon $coupon)
    {
        ActiveEvent::authorize($event);
        abort_unless($coupon->event_id === $event->id, 404);
        $coupon->update(['device_token' => null, 'device_agent' => null, 'device_ip' => null]);

        return back()->with('success', 'Data perangkat dihapus. Absensi dan kupon tetap tersimpan.');
    }

    public function export(Event $event)
    {
        ActiveEvent::authorize($event);

        return Excel::download(new AttendanceExport($event), 'absensi-'.$event->id.'.xlsx');
    }

    private function filteredCoupons(Request $request, Event $event)
    {
        $query = $event->coupons()->with('employee');

        if ($q = trim((string) $request->get('q'))) {
            $query->where(function ($w) use ($q) {
                $w->where('coupons.code', 'like', "%{$q}%")
                    ->orWhereHas('employee', fn ($e) => $e->where('nik', 'like', "%{$q}%")->orWhere('name', 'like', "%{$q}%")->orWhere('department', 'like', "%{$q}%"));
            });
        }

        if ($department = $request->get('department')) {
            $query->whereHas('employee', fn ($e) => $e->where('department', $department));
        }

        if (in_array($request->get('gps'), ['valid', 'invalid'], true)) {
            $query->where('gps_valid', $request->get('gps') === 'valid');
        }

        if ($request->get('gps') === 'manual') {
            $query->where('attendance_method', 'manual');
        }

        return $query;
    }
}
