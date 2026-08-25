<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

use function Ramsey\Uuid\v1;

class dashboarInvoice6Export implements FromArray, WithHeadings
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
            'Serie',
            'Folio',
            'Doc',
            'Cliente',
            'Nombre Cliente',
            'Descuento',
            'Importe',
            'Status',
            'Fecha Documento',
            'ID Vendedor',
            'Nombre Vendedor',
        ];
    }
}
