<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class EmployeesTemplateExport implements FromArray
{
    public function array(): array
    {
        return [
            ['NIK', 'nama', 'departemen', 'email', 'status aktif'],
            ['EMP0001', 'Nama Karyawan', 'IT', 'karyawan@example.com', 'aktif'],
        ];
    }
}
