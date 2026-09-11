<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Collection;

class ReportsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection(): Collection
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        return [
            'ID Nota',
            'Nama Customer',
            'Nomor Meja',
            'Sewa Billing (Rp)',
            'F&B (Rp)',
            'Grand Total (Rp)',
            'Metode Pembayaran',
            'Kasir Soft Close',
            'Waktu Selesai',
        ];
    }

    public function map($transaction): array
    {
        return [
            sprintf('#%04d', $transaction->id),
            $transaction->customer_name ?? '-',
            $transaction->poolTable->table_number ?? '-',
            $transaction->bill_price ?? 0,
            $transaction->fnb_price ?? 0,
            $transaction->grand_total ?? 0,
            strtoupper($transaction->payment_method ?? '-'),
            $transaction->closer->name ?? '-',
            $transaction->end_time ? $transaction->end_time->format('d/m/Y H:i') : '-',
        ];
    }
}
