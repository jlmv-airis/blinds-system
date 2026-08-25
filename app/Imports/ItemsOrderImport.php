<?php

namespace App\Imports;
use Maatwebsite\Excel\Concerns\ToModel;

class ItemsOrderImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $rows)
    {
        //
        $dataExcel = array();
        foreach($rows as $row) {
            array_push($dataExcel, [
                "item_id" => $row[0],
                "article" => $row[1],
                "width" => $row[2],
                "height" => $row[3],
                "quantity" => $row[4],
                "mechanism" => $row[5],
                "side" => $row[6],
            ]);
        }
        array_splice($dataExcel,0,1);
        return $dataExcel;
    }
}
