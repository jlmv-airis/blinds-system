<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class d2ReturnsExport implements FromArray, WithHeadings, WithTitle
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
            'Factura',
            'Fecha de elaboración',
            'Cliente ID',
            'Cliente Nombre ',
            'Clave',
            'Pdc',
            'Unidad',
            'Cantidad',
            'P/U',
            'Costo',
            'Total Venta',
            'Total Costo',
            'Utilidad',
            '%',
            'Vendedor ID',
            'Vendedor',
        ];
    }

    public function title(): string
    {
        return 'Devoluciones';
    }
}
