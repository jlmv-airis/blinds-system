<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class dashboar2Export implements FromArray, WithMultipleSheets
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
            new d2InvoicesExport($this->sheets['invoices']),
            new d2CreditNotesExport($this->sheets['credit_notes']),
            new d2ReturnsExport($this->sheets['returns']),
        ];

        return $sheets;
    }
}
