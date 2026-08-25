<?php

namespace App\Http\Controllers;

require_once app_path() . "/fpdf/fpdf.php";
require_once app_path() . "/fpdf/PDF_Code128.php";

use App\Models\CStatusLead;
use App\Models\CStatusSection;
use App\Models\DAddSectionRequest;
use App\Models\DSection;
use App\Models\ESection;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\classes\FPDF;
use App\Models\CCompany;
use PDF_Code128;
use App\classes\WebService;

class ESectionController extends Controller
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
        try {
            // Guardamos apartado
            $ESection = new ESection;
            $ESection->user_id          = $request->user_id;
            $ESection->company_id       = $request->company_id;
            $ESection->project          = $request->project;
            $ESection->detail           = $request->detail;
            $ESection->save();
            // Recorremos registros
            $dataPoductInsert = [];
            foreach ($request->products  as $key => $product) {
                $dataPoductInsert[] = [
                    "section_id"    => $ESection->id,
                    "sku"           => $product['CVE_ART'],
                    "projected"       => $product['projected_add'],
                ];
            }
            DSection::insert($dataPoductInsert);
            $section = $this->getIndividualSection($ESection->id);
            return response()->json([
                'success'   => true,
                'products'  => $request->products,
                'section'   => $section,
            ], 200 );
        } catch (\Throwable $th) {
            return response()->json([
                'success' =>  false ,
                'error'   =>  $th,
            ], 403);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ESection  $eSection
     * @return \Illuminate\Http\Response
     */
    public function show(ESection $eSection, $id, $company_id)
    {
        // try {
            $response = [];
            if( (INT)$company_id === 2) { // INDIGOFF
                $response = $this->webService->getInventoryRT();
            }
            if( (INT)$company_id === 4) { // INDIGOFF
                $response = $this->webService->getInventoryINDF();
            }
            if( (INT)$company_id === 5) { // INDIGOFF
                $response = $this->webService->getInventoryWRKS();
            }
            $DSection = DSection::select('d_sections.sku',DB::raw('SUM( CASE WHEN e_sections.id = '.$id.' THEN 0 ELSE d_sections.section END ) AS section, SUM( CASE WHEN e_sections.id = '.$id.' THEN 0 ELSE d_sections.projected END ) AS projected, SUM( CASE WHEN e_sections.id = '.$id.' THEN 0 ELSE d_add_section_requests.quantity END ) AS add_quantity'))
            ->join('e_sections','e_sections.id','d_sections.section_id')
            ->leftJoin('d_add_section_requests', 'd_add_section_requests.detail_section_id', 'd_sections.id')
            ->where('d_sections.section_id',$id)
            ->groupBy('d_sections.sku')
            ->get()
            ->toArray();

            // var_dump($DSection);
            $inventory = $this->discountSection($response->items,$DSection);
            $sectionData = $this->getIndividualRequestSection($id,$company_id);
            $section = $this->setInventorySection($sectionData,$inventory);
            return response()->json([
                'success' =>  true ,
                'section' => $section ,
            ], 200);
        // } catch (\Throwable $th) {
            //     return response()->json([
            //         'success' => false ,
            //         'error'   => $th
            //     ], 200);
        // }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ESection  $eSection
     * @return \Illuminate\Http\Response
     */
    public function edit(ESection $eSection)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ESection  $eSection
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ESection $eSection, $opt)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ESection  $eSection
     * @return \Illuminate\Http\Response
     */
    public function destroy(ESection $eSection)
    {
        //
    }
    public function requestSection(Request $request)
    {
        //
        try {
            $dateNow = Carbon::now();
            ESection::where('id',$request->id)
            ->update([
                'status_id'     => 3,
                'quotation_id'  => $request->quotation_id,
                'request_date'  => $dateNow
            ]);
            $statusSection = CStatusSection::where('id',3)->first();
            return response()->json([
                'success'       => true,
                'id'            => $request->id,
                'quotation_id'  => $request->quotation_id,
                'statusSection' => $statusSection,
            ], 200 );
        } catch (\Throwable $th) {
            return response()->json([
                'success' =>  false ,
                'error'   =>  $th,
            ], 403);
        }
    }

    public function saveSetSectionRequestChanges(Request $request) {
        // try {
            $insertData = [];
            foreach ($request->details as $key => $product) {
                if($product['add_quantity'] != $product['original_add_quantity'] ) {
                    if(is_null($product['original_add_quantity'])) {
                        $insertData[] = [
                            'detail_section_id' => $product['id'],
                            'quantity'          => $product['add_quantity'],
                        ];
                    }
                    if(empty($product['add_quantity'])) {
                        DAddSectionRequest::where('detail_section_id',$product['id'])
                        ->delete();
                    } else {
                        DAddSectionRequest::where('detail_section_id',$product['id'])
                        ->update(['quantity'=>$product['add_quantity']]);
                    }
                }
            }
            if( (INT)COUNT($insertData) > 0 ) { DAddSectionRequest::insert($insertData); }

            $response = [];
            if( (INT)$request->company_id === 2) { // INDIGOFF
                $response = $this->webService->getInventoryRT();
            }
            if( (INT)$request->company_id === 4) { // INDIGOFF
                $response = $this->webService->getInventoryINDF();
            }
            if( (INT)$request->company_id === 5) { // INDIGOFF
                $response = $this->webService->getInventoryWRKS();
            }
            $DSection = DSection::select('d_sections.sku',DB::raw('SUM( CASE WHEN e_sections.id = '.$request->id.' THEN 0 ELSE d_sections.section END ) AS section, SUM( CASE WHEN e_sections.id = '.$request->id.' THEN 0 ELSE d_sections.projected END ) AS projected, SUM( CASE WHEN e_sections.id = '.$request->id.' THEN 0 ELSE d_add_section_requests.quantity END ) AS add_quantity'))
            ->join('e_sections','e_sections.id','d_sections.section_id')
            ->leftJoin('d_add_section_requests', 'd_add_section_requests.detail_section_id', 'd_sections.id')
            ->whereIn('e_sections.status_id',[1,2,3,4])
            ->groupBy('d_sections.sku')
            ->get()
            ->toArray();
            $inventory = $this->discountSection($response->items,$DSection);
            $sectionData = $this->getIndividualRequestSection($request->id,$request->company_id);
            $section = $this->setInventorySection($sectionData,$inventory);
            return response()->json([
                'success' =>  true ,
                'section' => $section ,
            ], 200);
    // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 200);
    // }
    }

    public function sendSection(Request $request) {
        //
        // try {
            $company_id = $request->company_id;
            $response = [];
            if( (INT)$company_id === 2) { // INDIGOFF
                $response = $this->webService->getInventoryRT();
            }
            if( (INT)$company_id === 4) { // INDIGOFF
                $response = $this->webService->getInventoryINDF();
            }
            if( (INT)$company_id === 5) { // INDIGOFF
                $response = $this->webService->getInventoryWRKS();
            }
            $DataSection = DSection::select('d_sections.id','d_sections.section_id','d_sections.sku','c_inventory_products.product',DB::raw('SUM( d_sections.section ) AS section, SUM( d_sections.projected ) AS projected, SUM( d_add_section_requests.quantity ) AS add_quantity'))
            ->leftJoin('c_inventory_products', function($join) use($company_id){
                $join->on('c_inventory_products.sku', '=', 'd_sections.sku')
                    ->where('c_inventory_products.company_id', '=', $company_id);
            })
            ->leftJoin('d_add_section_requests','d_add_section_requests.detail_section_id', 'd_sections.id')
            ->join('e_sections','e_sections.id','d_sections.section_id')
            ->whereIn('e_sections.status_id',[1,2,3,4])
            ->where('e_sections.company_id',$company_id)
            ->groupBy('d_sections.sku')
            ->get()
            ->toArray();
            $sections = $this->discountSection($response->items,$DataSection);
            //
            $DSection = DSection::select('id','sku','section','projected')
            ->where('d_sections.section_id',$request->id)
            ->get()
            ->toArray();
            //
            $sectionReview = $this->getSectionReview($request->id,$DSection,$sections);
            if($sectionReview['success']) {
                $dateNow = Carbon::now();
                ESection::where('id',$request->id)
                ->update([
                    'status_id'     => 2,
                    'section_date'  => $dateNow
                ]);
                // UPDATE SECTION
                $DSection = DSection::select('id','projected')
                ->where('d_sections.section_id',$request->id)
                ->get();

                foreach ($DSection as $dsec) {
                    DSection::where('id',$dsec['id'])
                    ->update(['section'  => $dsec['projected']]);
                }
                $statusSection = CStatusSection::where('id',2)->first();

                $details = DSection::select('id','sku','projected','section')
                ->where('d_sections.section_id',$request->id)
                ->get();
                return response()->json([
                    'success'       => true,
                    'id'            => $request->id,
                    'statusSection' => $statusSection,
                    'details'       => $details,
                ], 200 );
            } else {
                return response()->json([
                    'success'       =>  false ,
                    'dataFailed'    =>  $sectionReview['dataFailed'],
                ], 400);
            }

        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' =>  false ,
        //         'error'   =>  $th,
        //     ], 400);
        // }
    }

    public function sendSectionRequest(Request $request) {
        try {
            $date_now = Carbon::now();
            ESection::where('id',$request->id)
            ->update([
                'status_id'             => 4,
                'request_user_id'       => $request->user_id,
                'material_request_date' => $date_now,
                'production_date'       => $request->production_date,
            ]);
            $section = $this->getIndividualRequestSection($request->id,$request->company_id);
            return response()->json([
                'success'   =>  true ,
                'id'        => $request->id,
                'section' => $section ,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false ,
                'error'   => $th
            ], 400);
        }
    }

    public function getSectionsRequest(Request $request) {
        try {
            $sections = $this->getSectionPerStatus(3,$request->company_id);
            return response()->json([
                'success'   =>  true ,
                'sections' => $sections ,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false ,
                'error'   => $th
            ], 400);
        }
    }


    public function downloadSectionRequestDetail(Request $request) {
        $section = $this->getIndividualRequestSection($request->id,$request->company_id);
        return app(FPDF::class)->createSectionRequestDetail($section,$request->company_id); // 1 - Geenral , 2 - proveedor
    }


    // PRIVATE

    private function getIndividualSection($id)
    {
        $ESection = ESection::select('e_sections.id','e_sections.user_id','e_sections.project','c_erp_info_users.short_name as user_name','e_sections.status_id','c_status_sections.status','c_status_sections.color_status','e_sections.detail','e_sections.quotation_id','e_sections.created_at')
        ->join('c_erp_info_users','c_erp_info_users.user_id','e_sections.user_id')
        ->join('c_status_sections','c_status_sections.id','e_sections.status_id')
        ->where('e_sections.id',$id)
        ->first();
        return $ESection;
    }

    private function getIndividualRequestSection($id,$company_id)
    {
        $ESection = ESection::select('e_sections.id','e_sections.user_id','e_sections.project','e_sections.company_id','c_companies.company','c_erp_info_users.short_name as user_name','e_sections.status_id','c_status_sections.status','c_status_sections.color_status','e_sections.detail','e_sections.quotation_id','e_sections.request_date','e_sections.material_request_date','e_sections.created_at')
        ->join('c_erp_info_users','c_erp_info_users.user_id','e_sections.user_id')
        ->join('c_status_sections','c_status_sections.id','e_sections.status_id')
        ->join('c_companies','c_companies.id','e_sections.company_id')
        ->where('e_sections.id',$id)
        ->first()
        ->toArray();
        $DSection = DSection::select('d_sections.id','d_sections.sku','c_inventory_products.product','d_sections.section','d_sections.projected',DB::raw('CASE WHEN d_add_section_requests.quantity != "" OR d_add_section_requests.quantity IS NOT NULL THEN d_add_section_requests.quantity ELSE null END AS add_quantity,d_add_section_requests.quantity AS original_add_quantity') )
        ->leftJoin('c_inventory_products', function($join) use($company_id){
            $join->on('c_inventory_products.sku', '=', 'd_sections.sku')
            ->where('c_inventory_products.company_id', '=', $company_id);
        })
        ->leftJoin('d_add_section_requests', function($join){
            $join->on('d_add_section_requests.detail_section_id', '=', 'd_sections.id')
            ->where('d_add_section_requests.is_complete', '=','0');
        })
        ->where('d_sections.section_id',$id)
        ->get()
        ->toArray();
        $section = $this->setIndividualSection($ESection,$DSection);
        return $section;
    }

    private function getSectionPerStatus($status_id,$company_id)
    {
        $ESection = ESection::select('e_sections.id','e_sections.user_id','e_sections.project','c_erp_info_users.short_name as user_name','e_sections.status_id','c_status_sections.status','c_status_sections.color_status','e_sections.detail','e_sections.quotation_id','e_sections.request_date','e_sections.material_request_date','e_sections.created_at')
        ->join('c_erp_info_users','c_erp_info_users.user_id','e_sections.user_id')
        ->join('c_status_sections','c_status_sections.id','e_sections.status_id')
        ->where('e_sections.status_id',$status_id)
        ->get()
        ->toArray();
        $DSection = DSection::select('d_sections.id','d_sections.section_id','d_sections.sku','c_inventory_products.product','d_sections.section','d_sections.projected')
        ->leftJoin('c_inventory_products', function($join) use($company_id){
            $join->on('c_inventory_products.sku', '=', 'd_sections.sku')
                ->where('c_inventory_products.company_id', '=', $company_id);
        })
        ->join('e_sections','e_sections.id','d_sections.section_id')
        ->where('e_sections.status_id',$status_id)
        ->get()
        ->toArray();
        $sections = $this->setSections($ESection,$DSection);
        //
        return $sections;
    }

    private function setIndividualSection($ESection,$DSection) {
        $ESection['details'] = [];
        foreach ($DSection as $key2 => $dSection) {
            $ESection['details'][] = $dSection;
        }
        return $ESection;
    }

    private function setSections($ESection,$DSection) {
        foreach ($ESection as $key => $eSection) {
            $ESection[$key]['details'] = [];
            foreach ($DSection as $key2 => $dSection) {
                if( (INT)$eSection['id'] === (INT)$dSection['section_id'] ) {
                    $ESection[$key]['details'][] = $dSection ;
                }
            }
        }
        return $ESection;
    }

    private function setInventorySection($sectionData,$inventory) {
        foreach ($sectionData['details'] as $key => $section) {
            $sectionData['details'][$key]['available'] = 0;
            foreach ($inventory as  $item) {
                if( $section['sku'] ==  $item['sku'] ) {
                    $sectionData['details'][$key]['available'] = round($item['available'],2);
                    $sectionData['details'][$key]['exist'] = round($item['exist'],2);
                }
            }
        }
        return $sectionData;
    }

    private function discountSection($items,$sections) {
        foreach ($sections as $key => $section) {
            $sections[$key]['available'] = 0;
            $sections[$key]['exist'] = 0;
            foreach ($items as $item) {
                if( $item->CVE_ART == $section['sku'] ){
                    $sections[$key]['available'] += (is_null((DOUBLE)$item->EXIST) ? 0 : (DOUBLE)$item->EXIST ) - ((is_null((DOUBLE)$section['section']) ? 0 : (DOUBLE)$section['section']) + (is_null((DOUBLE)$section['add_quantity']) ? 0 : (DOUBLE)$section['add_quantity']));
                    $sections[$key]['exist'] += (DOUBLE)$item->EXIST;
                }
            }
        }
        return $sections;
    }

    private function getSectionReview($id,$details,$sections) {
        $sectionReview = [
            'success'   => true,
            'dataFailed' => []
        ];
        foreach ($details as $key => $detail) {
            $found_key = array_search($detail['sku'], array_column($sections, 'sku'));
            if( (INT)$detail['sku'] === (INT)$sections[$found_key]['sku'] ) {
                if( (DOUBLE)$sections[$found_key]['available'] < (DOUBLE)$detail['projected'] ) {
                    $sectionReview['success'] = false;
                    $sectionReview['dataFailed'][] = [
                        'sku'           => $sections[$found_key]['sku'],
                        'product'       => $sections[$found_key]['product'],
                        'section'       => $sections[$found_key]['section'],
                        'projected'     => $detail['projected'],
                        'add_quantity'  => $sections[$found_key]['add_quantity'],
                        'available'     => $sections[$found_key]['available'],
                        'exist'         => $sections[$found_key]['exist'],
                    ];
                }
            }
        }
        return $sectionReview;
    }
}
