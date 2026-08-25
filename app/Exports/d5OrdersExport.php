<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class d5OrdersExport implements FromArray, WithHeadings, WithTitle
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
            'Realizo',
            'Nombre del cliente',
            'Status',
            'Total',
            'Piezas',
            'Nombre del proyecto',
            'Creado',
            'Autorizado',
            'Material solicitado',
            'Material surtido',
            'Material validado',
            'Producción',
            'Empaque',
            'Finalizado',
            'Método de pago',
            'Opción de pago',
            'Entrega',
            'Dirección del cliente',
        ];
    }

    public function title(): string
    {
        return 'Orders';
    }
}
