<?php

namespace App\Exports;

use App\Models\Event;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AttendanceExport implements FromQuery, WithHeadings, WithMapping
{
    private int $number = 0;
    public function __construct(public Event $event)
    {
    }

    public function query()
    {
        return $this->event->coupons()->with('employee')->orderBy('code');
    }

    public function headings(): array
    {
        return ['No', 'Kode Kupon', 'NIK', 'Nama', 'Departemen', 'Latitude', 'Longitude', 'Akurasi (m)', 'GPS Valid', 'Waktu Absen'];
    }

    public function map($coupon): array
    {
        return [
            ++$this->number,
            $coupon->code,
            $coupon->employee->nik,
            $coupon->employee->name,
            $coupon->employee->department,
            $coupon->latitude,
            $coupon->longitude,
            $coupon->accuracy,
            $coupon->gps_valid ? 'ya' : 'tidak',
            $coupon->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
