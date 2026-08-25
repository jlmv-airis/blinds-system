<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class d5DetilOrdersExport implements FromArray, WithHeadings, WithTitle
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
            'No. Pedido',
            'Vendedor Asignado',
            'Nombre del cliente',
            'Status',
            'Total',
            'Piezas',
            'Autorizado',
            'Empaque',
            'Finalizado',
            'Entrega',
            'Tipo de tela',
            'M2',
            'Tipo de Persiana',
        ];
    }

    public function title(): string
    {
        return 'Detail Orders';
    }
}
