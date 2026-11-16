<?php

namespace App\Exports;

use App\Models\Event;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class WinnersExport implements FromQuery, WithEvents, WithHeadings, WithMapping
{
    private int $number = 0;

    private array $photos = [];

    public function __construct(public Event $event) {}

    public function query()
    {
        return $this->event->winners()->with(['coupon.employee', 'prize', 'takenBy'])->orderByDesc('id');
    }

    public function headings(): array
    {
        return ['No', 'Kode Kupon', 'NIK', 'Nama', 'Departemen', 'Hadiah', 'Status', 'Diserahkan Pada', 'Operator', 'Foto Bukti', 'Alasan Pembatalan'];
    }

    public function map($winner): array
    {
        $this->number++;
        if ($winner->photo_path) {
            $this->photos[$this->number + 1] = $winner->photo_path;
        }

        return [
            $this->number,
            $winner->coupon->code,
            $winner->coupon->employee->nik,
            $winner->coupon->employee->name,
            $winner->coupon->employee->department,
            $winner->prize->name,
            $winner->status,
            $winner->taken_at?->format('Y-m-d H:i:s'),
            $winner->takenBy?->name,
            null,
            $winner->cancel_reason,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->getDelegate()->getDelegate();
                $sheet->getColumnDimension('J')->setWidth(20);

                foreach ($this->photos as $row => $path) {
                    if (! is_file(public_path($path))) {
                        continue;
                    }
                    $sheet->getRowDimension($row)->setRowHeight(80);
                    $drawing = new Drawing;
                    $drawing->setPath(public_path($path));
                    $drawing->setHeight(90);
                    $drawing->setCoordinates('J'.$row);
                    $drawing->setWorksheet($sheet);
                }
            },
        ];
    }
}
