<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeImportService
{
    public function handle(string $path): array
    {
        $report = [];
        $seen = [];

        $import = new class($report, $seen) implements ToCollection {
            public array $report;
            public array $seen;

            public function __construct(array $report, array $seen)
            {
                $this->report = $report;
                $this->seen = $seen;
            }

            public function collection(Collection $rows): void
            {
                foreach ($rows->skip(1) as $index => $row) {
                    $nik = trim((string) ($row[0] ?? ''));
                    $name = trim((string) ($row[1] ?? ''));

                    if (! $nik || ! $name) {
                        $this->report[] = ['row' => $index + 2, 'nik' => $nik, 'status' => 'dilewati', 'reason' => 'NIK/nama kosong'];
                        continue;
                    }

                    if (in_array($nik, $this->seen, true) || Employee::where('nik', $nik)->exists()) {
                        $this->report[] = ['row' => $index + 2, 'nik' => $nik, 'status' => 'dilewati', 'reason' => 'NIK duplikat'];
                        continue;
                    }

                    $this->seen[] = $nik;
                    $active = strtolower(trim((string) ($row[4] ?? 'aktif')));

                    Employee::create([
                        'nik' => $nik,
                        'name' => $name,
                        'department' => $row[2] ?: null,
                        'email' => $row[3] ?: null,
                        'is_active' => in_array($active, ['aktif', 'active', 'ya', 'yes', '1', 'true'], true),
                    ]);

                    $this->report[] = ['row' => $index + 2, 'nik' => $nik, 'status' => 'berhasil', 'reason' => ''];
                }
            }
        };

        Excel::import($import, $path);

        return $import->report;
    }
}
