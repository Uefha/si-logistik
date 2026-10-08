<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LaporanExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly Collection $rows, private readonly array $headers)
    {
    }

    public function collection(): Collection
    {
        return $this->rows->map(fn (array $row) => array_map(
            fn ($value) => is_string($value) && preg_match('/^[=+@-]/u', $value) ? "'".$value : $value,
            $row,
        ));
    }

    public function headings(): array
    {
        return $this->headers;
    }
}
