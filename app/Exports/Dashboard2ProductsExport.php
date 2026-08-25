<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class Dashboard2ProductsExport implements  FromArray, WithMultipleSheets
{
    protected $sheets;

    public function __construct(array $sheets)
    {
        $this->sheets = $sheets;
    }

    public function array(): array
    {
        return $this->sheets;
    }

    public function sheets(): array
    {
        $sheets = [
            new d2ProductsExport($this->sheets['products']),
            new d2ProductsPerClientExport($this->sheets['products_per_clients']),
        ];

        return $sheets;
    }
}
