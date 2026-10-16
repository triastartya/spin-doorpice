<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Event;
use App\Models\Prize;
use App\Services\EmployeeImportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\EmployeesTemplateExport;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');

        $employees = Employee::when($q, fn ($query) => $query->where(fn ($w) => $w->where('nik', 'like', "%$q%")->orWhere('name', 'like', "%$q%")->orWhere('department', 'like', "%$q%")))
            ->orderBy('nik')
            ->paginate(25)
            ->withQueryString();

        return view('admin.employees', compact('employees'));
    }

    public function store(Request $request)
    {
        Employee::create($this->validated($request));

        return back()->with('success', 'Karyawan ditambahkan.');
    }

    public function update(Request $request, Employee $employee)
    {
        $employee->update($this->validated($request, $employee->id));

        return back()->with('success', 'Karyawan diperbarui.');
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();

        return back()->with('success', 'Karyawan dihapus.');
    }

    public function importForm()
    {
        return view('admin.employees-import');
    }

    public function import(Request $request, EmployeeImportService $service)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx']]);

        $report = $service->handle($request->file('file')->getPathname());

        return back()->with('report', $report);
    }

    public function template()
    {
        return Excel::download(new EmployeesTemplateExport, 'template-karyawan.xlsx');
    }

    protected function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'nik' => ['required', 'max:64', 'unique:employees,nik'.($ignoreId ? ",$ignoreId" : '')],
            'name' => ['required'],
            'department' => ['nullable', 'max:255'],
            'email' => ['nullable', 'email'],
            'is_active' => ['boolean'],
        ]);
    }
}
