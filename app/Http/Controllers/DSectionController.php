<?php

namespace App\Http\Controllers;

use App\Models\DSection;
use Illuminate\Http\Request;
use App\classes\WebService;
use Illuminate\Support\Facades\DB;

class DSectionController extends Controller
{
    protected $webService;
    public function __construct(WebService $webService)
    {
        $this->webService = $webService;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\DSection  $dSection
     * @return \Illuminate\Http\Response
     */
    public function show(DSection $dSection)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\DSection  $dSection
     * @return \Illuminate\Http\Response
     */
    public function edit(DSection $dSection)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\DSection  $dSection
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, DSection $dSection, $id, $status_id) {
        // try {


            switch ((INT)$status_id) {
                case 1:

                    $dSection::where('id',$id)
                    ->update(['projected'   =>  $request->data,]);
                    return response()->json([
                        'success'   =>  true,
                        'data'      => $request->data,
                        'id'        => $id,
                        'status_id' => $status_id,
                    ], 200);

                break;
                case 2:

                    // Revisamos el apartado
                    $company_id = $request->company_id;
                    $response = [];
                    if( (INT)$request->company_id === 2) { // INDIGOFF
                        $response = $this->webService->getInventoryItemRT(['sku' => $request->sku]);
                    }
                    if( (INT)$request->company_id === 4) { // INDIGOFF
                        $response = $this->webService->getInventoryItemINDF(['sku' => $request->sku]);
                    }
                    if( (INT)$request->company_id === 5) { // WRKS
                        $response = $this->webService->getInventoryItemWRKS(['sku' => $request->sku]);
                    }
                    $exist = 0;
                    if((INT)count($response->item) > 0) { $exist = (DOUBLE)$response->item[0]->EXIST; }
                    //
                    $DSection = DSection::select(DB::raw('SUM(d_sections.section) AS section, SUM(d_add_section_requests.quantity) AS add_quantity'))
                    ->join('e_sections','e_sections.id','d_sections.section_id')
                    ->leftJoin('d_add_section_requests', 'd_add_section_requests.detail_section_id', 'd_sections.id')
                    ->whereIn('e_sections.status_id',[1,2,3])
                    ->where('d_sections.sku',$request->sku)
                    ->where('d_sections.id','!=',$id)
                    ->groupBy('d_sections.sku')
                    ->first();

                    $available = (DOUBLE)$exist - ((DOUBLE)$DSection->section + (DOUBLE)$DSection->add_quantity + (DOUBLE)$request->data) ;
                    if( (DOUBLE)$available >= 0 ) {
                        $dSection::where('id',$id)
                        ->update(['section'   =>  $request->data,]);
                        return response()->json([
                            'success'   =>  true,
                            'data'      => $request->data,
                            'id'        => $id,
                            'sku'       => $request->sku,
                            'section'   => ((DOUBLE)$DSection->section + (DOUBLE)$DSection->add_quantity + (DOUBLE)$request->data),
                            'available' => $available,
                            'status_id' => $status_id,
                        ], 200);
                    } else {
                        return response()->json([
                            'success'   =>  false ,
                            'optError'  => 2 ,
                            'data'      => $request->data ,
                        ], 400);
                    }

                break;
            }
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success'    => false ,
        //         'optError'   => 1 ,
        //         'error'      => $th
        //     ], 400);
        // }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\DSection  $dSection
     * @return \Illuminate\Http\Response
     */
    public function destroy(DSection $dSection)
    {
        //
    }
}
