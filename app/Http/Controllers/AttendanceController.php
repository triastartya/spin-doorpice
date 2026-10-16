<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Support\ActiveEvent;
use App\Exports\AttendanceExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceController extends Controller
{
    public function index(Request $request, Event $event)
    {
        ActiveEvent::authorize($event);

        $query = $event->coupons()->with('employee');

        if ($q = trim((string) $request->get('q'))) {
            $query->whereHas('employee', fn ($e) => $e->where('nik', 'like', "%{$q}%")->orWhere('name', 'like', "%{$q}%")->orWhere('department', 'like', "%{$q}%"));
        }

        if ($department = $request->get('department')) {
            $query->whereHas('employee', fn ($e) => $e->where('department', $department));
        }

        if (in_array($request->get('gps'), ['valid', 'invalid'], true)) {
            $query->where('gps_valid', $request->get('gps') === 'valid');
        }

        $coupons = $query->orderBy('code')->paginate(50)->withQueryString();

        $departments = $event->coupons()->join('employees', 'employees.id', '=', 'coupons.employee_id')->orderBy('employees.department')->distinct()->pluck('employees.department');

        return view('admin.attendance', compact('event', 'coupons', 'departments'));
    }

    public function export(Event $event)
    {
        ActiveEvent::authorize($event);

        return Excel::download(new AttendanceExport($event), 'absensi-'.$event->id.'.xlsx');
    }
}
