<?php

namespace App\Exports\inventory;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class lotsDataExport implements FromArray, WithHeadings, WithTitle
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return  $this->data;
    }

    public function headings(): array
    {
        return [
            'Clave',
            'Producto',
            'Lote',
            'Unidad',
            'Existencia',
        ];
    }

    public function title(): string
    {
        return 'Lotes';
    }
}
