<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class dashboar7FileClientsExport implements  FromArray, WithHeadings, WithTitle
{
    private $data;
    public function __construct($data)
    {
        $this->data = $data;
    }

    public function array(): array
    {    $dataNew = [];
        foreach ($this->data as $key => $data) {
            $dataNew[] = [
                'client_id'         => $data['client_id'],
                'name_client'       => $data['name_client'],
                'total_month'       => (INT)$data['total_month'] === 0 ? '' : number_format($data['total_month'],2),
                'total_3_months'    => (INT)$data['total_3_months'] === 0 ? '' : number_format($data['total_3_months'],2),
                'total_year'        => (INT)$data['total_year'] === 0 ? '' : number_format($data['total_year'],2),
            ];
        }
        return  $dataNew;
    }

    public function headings(): array
    {
        $meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
        $fecha = Carbon::now();
        $mes = $meses[($fecha->format('n')) - 1];
        return [
            'ID',
            'Cliente',
            'Total '.$mes,
            'Total 3 Meses',
            'Total Año',
        ];
    }

    public function title(): string
    {
        return 'Ventas cliente';
    }
}
