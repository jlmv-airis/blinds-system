<?php

namespace App\Http\Controllers;

use App\Models\CLocalInventory;
use App\Models\DLocalInventoryLot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CLocalInventoryController extends Controller
{
    public function index(Request $request)
    {
        $items = CLocalInventory::select('id','companie_id','sku','product','unit','stock','is_active','updated_at')
            ->orderBy('sku')
            ->get();

        $lots = DLocalInventoryLot::select('local_inventory_id','lot','stock','status')->get();

        $result = [];
        foreach ($items as $item) {
            $itemLots = [];
            foreach ($lots as $lot) {
                if ($lot->local_inventory_id == $item->id) {
                    $itemLots[] = [
                        'lot'   => $lot->lot,
                        'stock' => (float)$lot->stock,
                        'status'=> $lot->status,
                    ];
                }
            }
            $result[] = [
                'id'          => $item->id,
                'companie_id' => (int)$item->companie_id,
                'sku'         => $item->sku,
                'product'     => $item->product,
                'unit'        => $item->unit,
                'stock'       => (float)$item->stock,
                'is_active'   => (int)$item->is_active,
                'updated_at'  => $item->updated_at,
                'lots'        => $itemLots,
            ];
        }

        return response()->json([
            'success' => true,
            'inventory' => $result,
        ], 200);
    }

    public function store(Request $request)
    {
        $sku = strtoupper(trim($request->sku));
        if (!$sku) {
            return response()->json(['success' => false, 'message' => 'SKU requerido'], 200);
        }

        DB::beginTransaction();
        try {
            $item = CLocalInventory::updateOrCreate(
                ['companie_id' => (int)$request->companie_id, 'sku' => $sku],
                [
                    'product' => $request->product,
                    'unit'    => $request->unit,
                    'stock'   => (float)$request->stock,
                    'is_active' => 1,
                ]
            );

            DLocalInventoryLot::where('local_inventory_id', $item->id)->delete();
            foreach ($this->parseLots($request->lots_text ?? '') as $lot) {
                DLocalInventoryLot::create([
                    'local_inventory_id' => $item->id,
                    'lot'    => $lot['lot'],
                    'stock'  => $lot['stock'],
                    'status' => 'A',
                ]);
            }

            DB::commit();
            return response()->json(['success' => true], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $th->getMessage()], 200);
        }
    }

    public function destroy(Request $request)
    {
        // politica: no se elimina informacion, se desactiva
        CLocalInventory::where('id', $request->id)->update(['is_active' => 0]);
        return response()->json(['success' => true], 200);
    }

    public function importCsv(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['success' => false, 'message' => 'Archivo no recibido'], 200);
        }

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return response()->json(['success' => false, 'message' => 'No se pudo abrir el archivo'], 200);
        }

        $companieId = (int)($request->companie_id ?? 1);
        if ($companieId <= 0) { $companieId = 1; }

        $inserted = 0; $updated = 0; $line = 0; $errors = [];

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle, 10000, ',')) !== false) {
                $line++;
                if ($line === 1) {
                    // encabezado: detectar si es datos o cabecera
                    if (strtolower(trim($row[0])) === 'sku') { continue; }
                }
                $row = array_map(function($v){ return trim((string)$v); }, $row);
                if (count($row) < 4 || $row[0] === '') { continue; }

                $sku     = strtoupper($row[0]);
                $product = $row[1] !== '' ? $row[1] : null;
                $unit    = $row[2] !== '' ? $row[2] : null;
                $stock   = is_numeric($row[3]) ? (float)$row[3] : 0;

                $existing = CLocalInventory::where('companie_id', $companieId)->where('sku', $sku)->first();
                if ($existing) {
                    $existing->update([
                        'product' => $product ?? $existing->product,
                        'unit'    => $unit ?? $existing->unit,
                        'stock'   => $stock,
                        'is_active' => 1,
                    ]);
                    $itemId = $existing->id;
                    $updated++;
                } else {
                    $new = CLocalInventory::create([
                        'companie_id' => $companieId,
                        'sku' => $sku,
                        'product' => $product,
                        'unit' => $unit,
                        'stock' => $stock,
                        'is_active' => 1,
                    ]);
                    $itemId = $new->id;
                    $inserted++;
                }

                // columna 5: lote | columna 6: cantidad lote
                if (isset($row[4]) && $row[4] !== '') {
                    $lotStock = isset($row[5]) && is_numeric($row[5]) ? (float)$row[5] : 0;
                    $lotRow = DLocalInventoryLot::where('local_inventory_id', $itemId)->where('lot', $row[4])->first();
                    if ($lotRow) {
                        $lotRow->update(['stock' => $lotStock]);
                    } else {
                        DLocalInventoryLot::create([
                            'local_inventory_id' => $itemId,
                            'lot' => $row[4],
                            'stock' => $lotStock,
                            'status' => 'A',
                        ]);
                    }
                }
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            fclose($handle);
            return response()->json(['success' => false, 'error' => $th->getMessage()], 200);
        }
        fclose($handle);

        return response()->json([
            'success' => true,
            'inserted' => $inserted,
            'updated' => $updated,
        ], 200);
    }

    private function parseLots($text)
    {
        $out = [];
        $lines = preg_split('/\r\n|\r|\n/', trim((string)$text));
        foreach ($lines as $ln) {
            $ln = trim($ln);
            if ($ln === '') { continue; }
            if (strpos($ln, ':') !== false) {
                [$lot, $qty] = explode(':', $ln, 2);
                $out[] = ['lot' => trim($lot), 'stock' => is_numeric(trim($qty)) ? (float)trim($qty) : 0];
            } elseif (strpos($ln, ',') !== false) {
                [$lot, $qty] = explode(',', $ln, 2);
                $out[] = ['lot' => trim($lot), 'stock' => is_numeric(trim($qty)) ? (float)trim($qty) : 0];
            } else {
                $out[] = ['lot' => $ln, 'stock' => 0];
            }
        }
        return $out;
    }
}
