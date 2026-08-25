<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class guaranteeExcelExport implements FromArray, WithHeadings, WithTitle
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
            'ID garantía',
            'Item',
            'Cantidad',
            'Ancho',
            'Alto',
            'Artículo',
            'Lado del control',
            'Mecanismo',
            'Área',
            'Observaciones',
        ];

    }

    public function title(): string
    {
        return 'Clientes';
    }
}
