<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class pdpExport implements FromArray, WithHeadings
{
    private $data;
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array {
        return ['Cliente ID','Cliente','Al corriente','1 - 30','31- 60','61 - 90','91 o más','Total'];
    }
}
