<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class Dashboard4ClientsExport implements FromArray, WithHeadings, WithTitle
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
            'Cliente ID',
            'Cliente',
            'Subtotal',
            'Importe',
            'Costo',
            'Utilidad',
            'Porcentaje',
            'Agente',
        ];
    }

    public function title(): string
    {
        return 'Clientes';
    }
}
