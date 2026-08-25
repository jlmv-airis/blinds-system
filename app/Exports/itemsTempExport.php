<?php

namespace App\Exports;

use App\Models\DTempOrder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class itemsTempExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return DTempOrder::select('*')->where('is_active','=','2')->get();
    }
    public function headings(): array {
        return ['Item ID','Articulo','Ancho','Alto','Cantidad','Mecanismo','Mando'];
    }
}
