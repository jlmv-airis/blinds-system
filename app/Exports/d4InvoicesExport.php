<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class d4InvoicesExport implements FromArray, WithHeadings, WithTitle
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        $dataExtract = [];
        foreach ($this->data as $item) {
            $dataExtract[] = [
                'CVE_DOC' => $item->CVE_DOC,
                'FECHA_DOC' => $item->FECHA_DOC,
                'CVE_CLPV' => $item->CVE_CLPV,
                'CLIENT_NAME' => $item->CLIENT_NAME,
                'CVE_ART' => $item->CVE_ART,
                'DESCR' => $item->DESCR,
                'UNI_MED' => $item->UNI_MED,
                'CANT' => $item->CANT,
                'PREC' => $item->PREC,
                'TOTAL' => $item->TOTAL,
                'COST' => $item->COST,
                'UTILIDAD' => $item->UTILIDAD,
                'PERCENTAGE' => $item->PERCENTAGE,
                'CVE_VEND' => $item->CVE_VEND,
                'NOMBRE' => $item->NOMBRE,
                'CVE_PEDI' => $item->CVE_PEDI,
                // 'order_id' => $item->order_id,
            ];
        }
        return  $dataExtract;
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
            'Total Venta',
            'Costo',
            'Utilidad',
            '%',
            'Vendedor ID',
            'Vendedor',
            'Pedido',
        ];
    }

    public function title(): string
    {
        return 'Facturas';
    }
}
