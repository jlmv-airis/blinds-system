<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class dashboar4Export implements FromArray, WithMultipleSheets
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
            new d4InvoicesExport($this->sheets['invoices']),
            new d4CreditNotesExport($this->sheets['credit_notes']),
            new d4ReturnsExport($this->sheets['returns']),
        ];

        return $sheets;
    }
}
