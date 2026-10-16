<?php

namespace App\Exports;

use App\Models\Event;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class WinnersExport implements FromQuery, WithHeadings, WithMapping
{
    private int $number = 0;
    public function __construct(public Event $event)
    {
    }

    public function query()
    {
        return $this->event->winners()->with(['coupon.employee', 'prize'])->orderByDesc('id');
    }

    public function headings(): array
    {
        return ['No', 'Kode Kupon', 'NIK', 'Nama', 'Departemen', 'Hadiah', 'Status', 'Diserahkan Pada', 'Alasan Pembatalan'];
    }

    public function map($winner): array
    {
        return [
            ++$this->number,
            $winner->coupon->code,
            $winner->coupon->employee->nik,
            $winner->coupon->employee->name,
            $winner->coupon->employee->department,
            $winner->prize->name,
            $winner->status,
            $winner->taken_at?->format('Y-m-d H:i:s'),
            $winner->cancel_reason,
        ];
    }
}
