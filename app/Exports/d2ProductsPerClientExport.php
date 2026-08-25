<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class d2ProductsPerClientExport implements  FromArray, WithHeadings, WithTitle
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
            'ID Cliente',
            'Cliente',
            'SKU',
            'Artículo',
            'Unidad',
            'Cantidad',
            'Subtotal',
            'IVA',
            'Total',
            'Costo',
            'Utilidad',
            '%',
        ];
    }

    public function title(): string
    {
        return 'Productos por cliente';
    }
}
