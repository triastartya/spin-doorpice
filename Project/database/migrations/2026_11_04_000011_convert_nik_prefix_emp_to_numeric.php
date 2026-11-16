<?php

use App\Models\Employee;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        Employee::query()->orderBy('id')->chunk(500, function ($employees) {
            foreach ($employees as $employee) {
                $new = preg_replace('/^EMP/i', '221', $employee->nik);

                if ($new === $employee->nik) {
                    continue;
                }

                if (Employee::where('nik', $new)->where('id', '!=', $employee->id)->exists()) {
                    $new .= $employee->id;
                    Log::warning('NIK duplikat saat konversi numerik, diberi suffix id', ['id' => $employee->id, 'nik' => $employee->nik, 'baru' => $new]);
                }

                $employee->update(['nik' => $new]);
            }
        });
    }

    public function down(): void
    {
        Employee::query()->orderBy('id')->chunk(500, function ($employees) {
            foreach ($employees as $employee) {
                if (preg_match('/^221\d+$/', $employee->nik)) {
                    $employee->update(['nik' => 'EMP'.substr($employee->nik, 3)]);
                }
            }
        });
    }
};
