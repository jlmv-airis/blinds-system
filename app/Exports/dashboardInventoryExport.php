<?php

namespace App\Exports;

use App\Exports\inventory\inventoryDataExport;
use App\Exports\inventory\lotsDataExport;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class dashboardInventoryExport implements FromArray, WithMultipleSheets
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
            new inventoryDataExport($this->sheets['inventory']),
            new lotsDataExport($this->sheets['lotes']),
        ];

        return $sheets;
    }
}
