<?php

namespace App\classes;

use App\Models\CProvider;
use Carbon\Carbon;
use PDF_Code128;
use App\classes\GetTotal;

use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class FPDF
{
    private $pdf = null;
    private $tRowPerCol = 49;
    protected $webService;

    public function __construct(PDF_Code128 $pdf)
    {
        $this->pdf = $pdf;
    }

    public function createLabelsProducts($request,$section)
    {
        $dateNow = Carbon::now();
        $sectionCount = 0;
        foreach ($request->products as $product) { $sectionCount = $product['regs'] + $sectionCount; }
        $sectionStart = $section-$sectionCount;
        foreach ($request->products as $product) {
            while ( $sectionStart < $section ) {
                $this->pdf->AddPage();
                $this->pdf->SetFont('Arial','B',8);
                $this->pdf->Text(70,4,$dateNow);
                $this->pdf->SetFont('Arial','B',80);
                $this->pdf->SetXY(1,1);
                $this->pdf->Text(2,26,$product['provider_nomen'].$sectionStart);
                $this->pdf->SetFont('Arial','B',10);
                $this->pdf->Text(2,34,$product['product']);
                $this->pdf->Text(2,40,'Stock: '.$product['stock'].' ');
                //lado derecho
                // $this->pdf->Text(50,30,utf8_decode('Clase: '.$product['cse_prod']));
                $this->pdf->Text(50,40,'sku: '.$product['sku']);
                $this->pdf->SetFont('Arial','B',10);
                //A,C,B sets
                $this->pdf->Code128(25,44,$product['provider_nomen'].$sectionStart,50,15);
                $this->pdf->SetFont('Arial','B',7);
                // $this->pdf->Text(2,65,'IMPORTADO POR ');
                // $this->pdf->Text(70,65,'RFC ');
                $sectionStart++;
            }
        }
        $now = date('Ydmhis');
        $file = $this->pdf->Output("S") ;
        return [
            "name" => "labels_inventory_".$now.".pdf",
            "file" => base64_encode($file),
        ];
    }
    public function createLabelsPerProvider($inventoryAll)
    {
        $dateNow = Carbon::now();
        foreach ($inventoryAll as $itemAll) {
            foreach ($itemAll as $item) {
                $this->pdf->AddPage();
                $this->pdf->SetFont('Arial','B',8);
                $this->pdf->Text(70,4,$dateNow);
                $this->pdf->SetFont('Arial','B',80);
                $this->pdf->SetXY(1,1);
                $this->pdf->Text(2,26,$item['lot']);
                $this->pdf->SetFont('Arial','B',10);
                $this->pdf->Text(2,34,$item['product']);
                $this->pdf->Text(2,40,'Stock: '.$item['stock'].' ');
                //lado derecho
                // $this->pdf->Text(50,30,utf8_decode('Clase: '.$item['cse_prod']));
                $this->pdf->Text(50,40,'sku: '.$item['sku']);
                $this->pdf->SetFont('Arial','B',10);
                //A,C,B sets
                $this->pdf->Code128(25,44,$item['lot'],50,15);
                $this->pdf->SetFont('Arial','B',7);
                // $this->pdf->Text(2,65,'IMPORTADO POR ');
                // $this->pdf->Text(70,65,'RFC ');
            }

        }
        $now = date('Ydmhis');
        $file = $this->pdf->Output("S") ;
        return [
            "name" => "labels_inventory_".$now.".pdf",
            "file" => base64_encode($file),
        ];
    }

    public function createCutLabels($inventory)
    {
        $dateNow = Carbon::now();
        foreach ($inventory as $reg) {
            $this->pdf->AddPage();
            $this->pdf->SetFont('Arial','B',8);
            $this->pdf->Text(70,4,$dateNow);
            $this->pdf->SetFont('Arial','B',80);
            $this->pdf->SetXY(1,1);
            $this->pdf->Text(2,26,$reg['lot']);
            $this->pdf->SetFont('Arial','B',10);
            $this->pdf->Text(2,34,$reg['product']);
            $this->pdf->Text(2,40,'Stock: '.$reg['stock'].' ');
            //lado derecho
            // $this->pdf->Text(50,30,utf8_decode('Clase: '.$product['cse_prod']));
            $this->pdf->Text(50,40,'sku: '.$reg['sku']);
            $this->pdf->SetFont('Arial','B',10);
            //A,C,B sets
            $this->pdf->Code128(25,44,$reg['lot'],50,15);
            $this->pdf->SetFont('Arial','B',7);
            // $this->pdf->Text(2,65,'IMPORTADO POR ');
            // $this->pdf->Text(70,65,'RFC ');
        }
        $now = date('Ydmhis');
        $file = $this->pdf->Output("S") ;
        return [
            "name" => "labels_inventory_".$now.".pdf",
            "file" => base64_encode($file),
        ];
    }

    public function createPurchaseLabels($inventory)
    {
        $dateNow = Carbon::now();
        foreach ($inventory as $reg) {
            $this->pdf->AddPage();
            $this->pdf->SetFont('Arial','B',8);
            $this->pdf->Text(70,4,$dateNow);
            $this->pdf->SetFont('Arial','B',70);
            $this->pdf->SetXY(1,1);
            $this->pdf->Text(2,26,$reg['lot']);
            $this->pdf->SetFont('Arial','B',10);
            $this->pdf->Text(2,34,$reg['product']);
            $this->pdf->Text(2,40,'Stock: '.$reg['stock'].' ');
            //lado derecho
            // $this->pdf->Text(50,30,utf8_decode('Clase: '.$product['cse_prod']));
            $this->pdf->Text(50,40,'sku: '.$reg['sku']);
            $this->pdf->SetFont('Arial','B',10);
            //A,C,B sets
            $this->pdf->Code128(25,44,$reg['lot'],50,15);
            $this->pdf->SetFont('Arial','B',7);
            // $this->pdf->Text(2,65,'IMPORTADO POR ');
            // $this->pdf->Text(70,65,'RFC ');
        }
        $now = date('Ydmhis');
        $file = $this->pdf->Output("S") ;
        return [
            "name" => "labels_inventory_".$now.".pdf",
            "file" => base64_encode($file),
        ];
    }


    public function createIndividualLabels($inventory)
    {
        $dateNow = Carbon::now();
        $this->pdf->AddPage();
        $this->pdf->SetFont('Arial','B',8);
        $this->pdf->Text(70,4,$dateNow);
        $this->pdf->SetFont('Arial','B',80);
        $this->pdf->SetXY(1,1);
        $this->pdf->Text(2,26,$inventory['lot']);
        $this->pdf->SetFont('Arial','B',10);
        $this->pdf->Text(2,34,$inventory['product']);
        $this->pdf->Text(2,40,'Stock: '.$inventory['stock'].' ');
        //lado derecho
        // $this->pdf->Text(50,30,utf8_decode('Clase: '.$product['cse_prod']));
        $this->pdf->Text(50,40,'sku: '.$inventory['sku']);
        $this->pdf->SetFont('Arial','B',10);
        //A,C,B sets
        $this->pdf->Code128(25,44,$inventory['lot'],50,15);
        $this->pdf->SetFont('Arial','B',7);
        // $this->pdf->Text(2,65,'IMPORTADO POR ');
        // $this->pdf->Text(70,65,'RFC ');
        $now = date('Ydmhis');
        $file = $this->pdf->Output("S") ;
        return [
            "name" => "labels_inventory_".$now.".pdf",
            "file" => base64_encode($file),
        ];
    }

    public function createPurchaseOrder($purchase,$detailsPurchases) {

        $this->pdf->SetMargins(20, 25, 20);
        $this->pdf->SetAutoPageBreak(true, 5);
        $itemsPerPage = 25;
        if(count($detailsPurchases) < $itemsPerPage ) { $itemStop = count($detailsPurchases); } else { $itemStop = $itemsPerPage; }
        $initReg = 0;
        $totalPages = ceil(count($detailsPurchases) / $itemsPerPage);
        for ($i = 1; $i <= $totalPages; $i++) {

            $this->pdf->AddPage();
            $this->pdf->SetXY(1, 1);
            $this->pdf->Image("img/lansonshades.jpeg", 10, 6, 22, 32);

            $this->pdf->SetTextColor(255, 4, 0);
            $this->pdf->SetFont("Arial", "B", 16);
            $idOrder = self::createIDOrder($purchase['id']);
            $this->pdf->Text(152, 12, 'PO-'.$idOrder);
            $this->pdf->SetTextColor(0, 0, 0);
            $this->pdf->SetFont("Arial", "B", 16);
            $this->pdf->Text(152, 20, "ORDEN DE COMPRA");
            $this->pdf->SetFont("Arial", "B", 10);
            $createdAt = date('Y-m-d h:m:s', strtotime($purchase['created_at']));
            $this->pdf->Text(152, 26, $createdAt);
            //
            $this->pdf->SetFont("Arial", "", 10);
            $this->pdf->Text(10, 41, "blindsystems.com");
            $this->pdf->Text(10, 47, utf8_decode("Esfuerzo 5"));
            $this->pdf->Text(10, 53, utf8_decode("San Andres Atoto"));
            $this->pdf->Text(10, 59, utf8_decode("CP 53500, Naucalpan de Juárez"));

            // PROVIDER
            $this->pdf->SetFillColor(241, 241, 241);
            $this->pdf->SetXY(10, 68);
            $this->pdf->Cell(90, 6, "Proveedor", 1, 1, "C", true, "");
            $this->pdf->SetXY(10, 74);
            $this->pdf->Cell(90, 6, utf8_decode('Lanson Shades'), 'LR', 1, "L", false, "");
            $this->pdf->SetXY(10, 80);
            $this->pdf->Cell(90, 6, utf8_decode('Esfuerzo 5'), 'LR', 1, "L", false, "");
            $this->pdf->SetXY(10, 86);
            $this->pdf->Cell(90, 6, utf8_decode('San Andres Atoto'), 'LR', 1, "L", false, "");
            $this->pdf->SetXY(10,92);
            $this->pdf->Cell(90, 6, utf8_decode('CP 53500, Naucalpan de Juárez'), 'LRB', 1, "L", false, "");

            // SHIP TO
            $this->pdf->SetFillColor(241, 241, 241);
            $this->pdf->SetXY(112, 68);
            $this->pdf->Cell(90, 6, "Enviar a", 1, 1, "C", true, "");
            $this->pdf->SetXY(112, 74);
            $this->pdf->Cell(90, 6, utf8_decode('Lanson Shades'), 'LR', 1, "L", false, "");
            $this->pdf->SetXY(112, 80);
            $this->pdf->Cell(90, 6, utf8_decode('Esfuerzo 5'), 'LR', 1, "L", false, "");
            $this->pdf->SetXY(112, 86);
            $this->pdf->Cell(90, 6, utf8_decode('San Andres Atoto'), 'LR', 1, "L", false, "");
            $this->pdf->SetXY(112,92);
            $this->pdf->Cell(90, 6, utf8_decode('CP 53500, Naucalpan de Juárez'), 'LRB', 1, "L", false, "");
            // HEADERS
            $this->pdf->SetFont("Arial", "B", 10);
            $this->pdf->SetXY(10, 104);
            $this->pdf->Cell(40, 6, "SKU", 1, 1, "C", true, "");
            $this->pdf->SetXY(50, 104);
            $this->pdf->Cell(90, 6, "Product", 1, 1, "C", true, "");
            $this->pdf->SetXY(140, 104);
            $this->pdf->Cell(15, 6, "Stock", 1, 1, "C", true, "");
            $this->pdf->SetXY(155, 104);
            $this->pdf->Cell(35, 6, "Lot", 1, 1, "C", true, "");
            $this->pdf->SetXY(190, 104);
            $this->pdf->Cell(12, 6, "Check", 1, 1, "C", true, "");
            // ITEMS
            $x = 110;
            $this->pdf->SetFont("Arial", "", 10);
            while ( $initReg < $itemStop ) {
                $this->pdf->SetXY(10, $x);
                $this->pdf->Cell(40, 6, $detailsPurchases[$initReg]['sku'], 1, 1, "C", false, "");
                $this->pdf->SetXY(50, $x);
                $this->pdf->Cell(90, 6, $detailsPurchases[$initReg]['product'], 1, 1, "C", false, "");
                $this->pdf->SetXY(140, $x);
                $this->pdf->Cell(15, 6, $detailsPurchases[$initReg]['stock'], 1, 1, "C", false, "");
                $this->pdf->SetXY(155, $x);
                $this->pdf->Cell(35, 6, $detailsPurchases[$initReg]['lot'], 1, 1, "C", false, "");
                $this->pdf->SetXY(190, $x);
                $this->pdf->Cell(12, 6, '', 1, 1, "C", false, "");
                $x = $x + 6;
                $initReg++;
            }
            $itemStop = $itemStop + $itemsPerPage;
            if($itemStop > count($detailsPurchases) ) { $itemStop = count($detailsPurchases) ; }

            $this->pdf->Text(175, 45, utf8_decode("Página ".$i." de ".$totalPages));

        }
        $this->pdf->Rect(9, 238, 194, 40, 'F');
        $this->pdf->SetXY(10, 240);
        $this->pdf->MultiCell(192, 6, utf8_decode($purchase['detail']) , 0, 'C', false);

        $file = $this->pdf->Output("S") ;
        return [
            "name" => "orden-compra-".$purchase['id'].".pdf",
            "file" => base64_encode($file),
        ];
    }

    public function createRequestDetail($info,$items,$opt) {

        if($opt <= 1) {
            $this->pdf->SetMargins(20, 25, 20);
            $this->pdf->SetAutoPageBreak(true, 5);
            $itemsPerPage = 35;
            if(count($items) < $itemsPerPage ) { $itemStop = count($items); } else { $itemStop = $itemsPerPage; }
            $initReg = 0;
            $totalPages = ceil(count($items) / $itemsPerPage);
            for ($i = 1; $i <= $totalPages; $i++) {

                $this->pdf->AddPage();
                $this->pdf->SetXY(1, 1);
                $this->pdf->Image("img/lansonshades.jpeg", 10, 6, 22, 32);

                $this->pdf->SetTextColor(255, 4, 0);
                $this->pdf->SetFont("Arial", "B", 16);
                $idOrder = self::createIDOrder($info['id']);
                $this->pdf->Text(152, 12, 'MR-'.$idOrder);
                $this->pdf->SetTextColor(0, 0, 0);
                $this->pdf->SetFont("Arial", "B", 16);
                $this->pdf->Text(152, 20, "Solicitud de material");
                $this->pdf->SetFont("Arial", "B", 10);
                $createdAt = date('Y-m-d h:m:s', strtotime($info['created_at']));
                $this->pdf->Text(152, 26, $createdAt);

                $this->pdf->SetFont("Arial", "B", 16);
                $this->pdf->Text(152, 34, "GENERAL");

                $this->pdf->SetFont("Arial", "", 10);
                $this->pdf->Text(10, 41, "blindsystems.com");
                $this->pdf->Text(10, 47, utf8_decode("Esfuerzo 5"));
                $this->pdf->Text(10, 53, utf8_decode("San Andres Atoto"));
                $this->pdf->Text(10, 59, utf8_decode("CP 53500, Naucalpan de Juárez"));

                $this->pdf->SetFont("Arial", "", 10);
                $this->pdf->SetFillColor(241, 241, 241);
                // HEADERS
                $this->pdf->SetFont("Arial", "B", 10);
                $this->pdf->SetXY(10, 68);
                $this->pdf->Cell(25, 6, "Proveedor", 1, 1, "C", true, "");
                $this->pdf->SetXY(35, 68);
                $this->pdf->Cell(90, 6, "Material", 1, 1, "C", true, "");
                $this->pdf->SetXY(125, 68);
                $this->pdf->Cell(20, 6, "Cantidad", 1, 1, "C", true, "");
                $this->pdf->SetXY(145, 68);
                $this->pdf->Cell(18, 6, "Unidad", 1, 1, "C", true, "");
                $this->pdf->SetXY(163, 68);
                $this->pdf->Cell(25, 6, "Lote", 1, 1, "C", true, "");
                $this->pdf->SetXY(188, 68);
                $this->pdf->Cell(12, 6, "Check", 1, 1, "C", true, "");
                // ITEMS
                $x = 74;
                $this->pdf->SetFont("Arial", "", 10);
                while ( $initReg < $itemStop ) {
                    $this->pdf->SetXY(10, $x);
                    $this->pdf->Cell(25, 6, $items[$initReg]['provider'], 1, 1, "C", false, "");
                    $this->pdf->SetXY(35, $x);
                    $this->pdf->Cell(90, 6, $items[$initReg]['article'], 1, 1, "C", false, "");
                    $this->pdf->SetXY(125, $x);
                    $this->pdf->Cell(20, 6, $items[$initReg]['quantity'], 1, 1, "C", false, "");
                    $this->pdf->SetXY(145, $x);
                    $this->pdf->Cell(18, 6, $items[$initReg]['unit'], 1, 1, "C", false, "");
                    $this->pdf->SetXY(163, $x);
                    $this->pdf->Cell(25, 6, $items[$initReg]['lot'] ?? '', 1, 1, "C", false, "");
                    $this->pdf->SetXY(188, $x);
                    $this->pdf->Cell(12, 6, '', 1, 1, "C", false, "");
                    $x = $x + 6;
                    $initReg++;
                }
                $itemStop = $itemStop + $itemsPerPage;
                if($itemStop > count($items) ) { $itemStop = count($items) ; }

                $this->pdf->Text(175, 280, utf8_decode("Página ".$i." de ".$totalPages));
            }
        }
        if($opt <= 2) {
            //  PER PROVIDER
            $data_providers = [];
            $providers = CProvider::select('id','provider','company')->get();
            foreach ($providers as $provider) {
                $provider_data = [];
                foreach ($items as $item) {
                    if($provider['id'] == $item['provider_id']) {
                        $provider_data[] = $item;
                    }
                }
                $data_providers[] = [
                    'provider' => $provider['provider'],
                    'data' => $provider_data,
                ];
            }
            // FILE PER PROVIDER
            foreach ($data_providers as $dProvider) {
                if(!empty($dProvider['data'])) {

                    $this->pdf->SetMargins(20, 25, 20);
                    $this->pdf->SetAutoPageBreak(true, 5);
                    $itemsPerPage = 35;
                    if(count($dProvider['data']) < $itemsPerPage ) { $itemStop = count($dProvider['data']); } else { $itemStop = $itemsPerPage; }
                    $initReg = 0;
                    $totalPages = ceil(count($dProvider['data']) / $itemsPerPage);
                    for ($i = 1; $i <= $totalPages; $i++) {

                        $this->pdf->AddPage();
                        $this->pdf->SetXY(1, 1);
                        $this->pdf->Image("img/lansonshades.jpeg", 10, 6, 22, 32);

                        $this->pdf->SetTextColor(255, 4, 0);
                        $this->pdf->SetFont("Arial", "B", 16);
                        $idOrder = self::createIDOrder($info['id']);
                        $this->pdf->Text(152, 12, 'MR-'.$idOrder);
                        $this->pdf->SetTextColor(0, 0, 0);
                        $this->pdf->SetFont("Arial", "B", 16);
                        $this->pdf->Text(152, 20, "Solicitud de material");
                        $this->pdf->SetFont("Arial", "B", 10);
                        $createdAt = date('Y-m-d h:m:s', strtotime($info['created_at']));
                        $this->pdf->Text(152, 26, $createdAt);

                        $this->pdf->SetFont("Arial", "B", 16);
                        $this->pdf->Text(152, 34, $dProvider['provider']);

                        $this->pdf->SetFont("Arial", "", 10);
                        $this->pdf->Text(10, 41, "blindsystems.com");
                        $this->pdf->Text(10, 47, utf8_decode("Calle Hipólito Taine 709"));
                        $this->pdf->Text(10, 53, utf8_decode("Polanco V Secc, Miguel Hidalgo"));
                        $this->pdf->Text(10, 59, utf8_decode("CP 11560, Ciudad de México"));

                        $this->pdf->SetFont("Arial", "", 10);
                        $this->pdf->SetFillColor(241, 241, 241);
                        // HEADERS
                        $this->pdf->SetFont("Arial", "B", 10);
                        $this->pdf->SetXY(10, 68);
                        $this->pdf->Cell(25, 6, "Proveedor", 1, 1, "C", true, "");
                        $this->pdf->SetXY(35, 68);
                        $this->pdf->Cell(90, 6, "Material", 1, 1, "C", true, "");
                        $this->pdf->SetXY(125, 68);
                        $this->pdf->Cell(20, 6, "Cantidad", 1, 1, "C", true, "");
                        $this->pdf->SetXY(145, 68);
                        $this->pdf->Cell(18, 6, "Unidad", 1, 1, "C", true, "");
                        $this->pdf->SetXY(163, 68);
                        $this->pdf->Cell(25, 6, "Lote", 1, 1, "C", true, "");
                        $this->pdf->SetXY(188, 68);
                        $this->pdf->Cell(12, 6, "Check", 1, 1, "C", true, "");

                        // ITEMS
                        $x = 74;
                        $this->pdf->SetFont("Arial", "", 10);
                        while ( $initReg < $itemStop ) {
                            $this->pdf->SetXY(10, $x);
                            $this->pdf->Cell(25, 6, $dProvider['data'][$initReg]['provider'], 1, 1, "C", false, "");
                            $this->pdf->SetXY(35, $x);
                            $this->pdf->Cell(90, 6, $dProvider['data'][$initReg]['article'], 1, 1, "C", false, "");
                            $this->pdf->SetXY(125, $x);
                            $this->pdf->Cell(20, 6, $dProvider['data'][$initReg]['quantity'], 1, 1, "C", false, "");
                            $this->pdf->SetXY(145, $x);
                            $this->pdf->Cell(18, 6, $dProvider['data'][$initReg]['unit'], 1, 1, "C", false, "");
                            $this->pdf->SetXY(163, $x);
                            $this->pdf->Cell(25, 6, $dProvider['data'][$initReg]['lot'], 1, 1, "C", false, "");
                            $this->pdf->SetXY(188, $x);
                            $this->pdf->Cell(12, 6, '', 1, 1, "C", false, "");
                            $x = $x + 6;
                            $initReg++;
                        }
                        $itemStop = $itemStop + $itemsPerPage;
                        if($itemStop > count($dProvider['data']) ) { $itemStop = count($dProvider['data']) ; }
                        $this->pdf->Text(175, 280, utf8_decode("Página ".$i." de ".$totalPages));
                    }
                }
            }
        }
        // $file = $this->pdf->Output("") ;
        $file = $this->pdf->Output("S") ;
        return [
            "name" => "material-request-".$info['id'].".pdf",
            "file" => base64_encode($file),
        ];
    }


    public function createSectionRequestDetail($section,$company_id) {

        $this->pdf->SetMargins(20, 25, 20);
        $this->pdf->SetAutoPageBreak(true, 5);
        $itemsPerPage = 35;
        if(count($section['details']) < $itemsPerPage ) { $itemStop = count($section['details']); } else { $itemStop = $itemsPerPage; }
        $initReg = 0;
        $totalPages = ceil(count($section['details']) / $itemsPerPage);
        for ($i = 1; $i <= $totalPages; $i++) {

            $this->pdf->AddPage();
            if( (INT)$company_id === 2) { // Roller
                $this->pdf->SetFont("Arial", "B", 28);
                $this->pdf->Text(9, 18, "ROLLERTEX");
                $this->pdf->SetFont("Arial", "", 10);
                // $this->pdf->Text(10, 24, "Rollertex.com");
                // $this->pdf->Text(10, 39, utf8_decode("Esfuerzo 5"));
                // $this->pdf->Text(10, 45, utf8_decode("San Andres Atoto"));
                // $this->pdf->Text(10, 51, utf8_decode("CP 53500, Naucalpan de Juárez"));
                //
                $this->pdf->SetTextColor(255, 4, 0);
                $this->pdf->SetFont("Arial", "B", 16);
                $idOrder = self::createIDOrder($section['id']);
                $this->pdf->Text(152, 12, 'RTMR-'.$idOrder);
            }
            if( (INT)$company_id === 4) { // INDIGOFF
                $this->pdf->SetFont("Arial", "B", 28);
                $this->pdf->Text(9, 18, "INDIGOFF");
                //
                $this->pdf->SetTextColor(255, 4, 0);
                $this->pdf->SetFont("Arial", "B", 16);
                $idOrder = self::createIDOrder($section['id']);
                $this->pdf->Text(152, 12, 'IOMR-'.$idOrder);
            }
            if( (INT)$company_id === 5) { // WRKS
                $this->pdf->SetFont("Arial", "B", 28);
                $this->pdf->Text(9, 18, "WRKS");
                //
                $this->pdf->SetTextColor(255, 4, 0);
                $this->pdf->SetFont("Arial", "B", 16);
                $idOrder = self::createIDOrder($section['id']);
                $this->pdf->Text(152, 12, 'WKMR-'.$idOrder);
            }

            $this->pdf->SetTextColor(0, 0, 0);
            $this->pdf->SetFont("Arial", "B", 16);
            $this->pdf->Text(152, 20, "Solicitud de material");
            $this->pdf->SetFont("Arial", "B", 10);
            $createdAt = date('Y-m-d h:m:s', strtotime($section['created_at']));
            $this->pdf->Text(152, 26, $createdAt);



            $this->pdf->SetFont("Arial", "", 10);
            $this->pdf->SetFillColor(241, 241, 241);
            // HEADERS
            $this->pdf->SetFont("Arial", "B", 10);
            $this->pdf->SetXY(20, 48);
            $this->pdf->Cell(50, 6, "Clave", 1, 1, "C", true, "");
            $this->pdf->SetXY(70, 48);
            $this->pdf->Cell(90, 6, "Producto", 1, 1, "C", true, "");
            $this->pdf->SetXY(160, 48);
            $this->pdf->Cell(20, 6, "Cantidad", 1, 1, "C", true, "");
            $this->pdf->SetXY(180, 48);
            $this->pdf->Cell(12, 6, "Check", 1, 1, "C", true, "");
            // ITEMS
            $x = 54;
            $this->pdf->SetFont("Arial", "", 10);
            while ( $initReg < $itemStop ) {
                $this->pdf->SetXY(20, $x);
                $this->pdf->Cell(50, 6, $section['details'][$initReg]['sku'], 1, 1, "C", false, "");
                $this->pdf->SetXY(70, $x);
                $this->pdf->Cell(90, 6, $section['details'][$initReg]['product'], 1, 1, "C", false, "");
                $this->pdf->SetXY(160, $x);
                $this->pdf->Cell(20, 6, ( (DOUBLE)$section['details'][$initReg]['section'] + (DOUBLE)$section['details'][$initReg]['add_quantity']), 1, 1, "C", false, "");
                $this->pdf->SetXY(180, $x);
                $this->pdf->Cell(12, 6, '', 1, 1, "C", false, "");
                $x = $x + 6;
                $initReg++;
            }
            $itemStop = $itemStop + $itemsPerPage;
            if($itemStop > count($section['details']) ) { $itemStop = count($section['details']) ; }

            $this->pdf->Text(175, 280, utf8_decode("Página ".$i." de ".$totalPages));
        }
        // $file = $this->pdf->Output("") ;
        // exit;
        $file = $this->pdf->Output("S") ;
        return [
            "name" => "solicitud-apartado-".$section['id'].".pdf",
            "file" => base64_encode($file),
        ];
    }

    public function createIndividualItemOrder($item) {
        $dateNow = Carbon::now();
        $this->pdf->AddPage();

        // QR CODE
        $dataURI = base64_encode(QrCode::size(100)->format('png')->generate('https://red.blindsystems.com/qr/'.$item['temp_order_id'].'/'.$item['id'].'/'.$item['key']));
        $qr = 'data://text/plain;base64,'. $dataURI;



        // $dataURI = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAPCAMAAADarb8dAAAABlBMVEUAAADtHCTeKUOwAAAAF0lEQVR4AWOgAWBE4zISkMbDZQRyaQkABl4ADHmgWUYAAAAASUVORK5CYII=";
        // $img = explode(',',$dataURI,2)[1];
        // $qr = 'data://text/plain;base64,'. $img;


        $this->pdf->Image("img/lansonshades.jpeg",  58, 2, 0, 5);
        $this->pdf->SetFont('Arial','B',12);
        $this->pdf->Text(4,6,'Pedido:');
        $this->pdf->Text(22,6,$item['order_id']);
        // $this->pdf->SetFont('Arial','B',80);
        // $this->pdf->SetXY(1,1);
        // $this->pdf->Text(2,26,$reg['lot']);
        // $this->pdf->SetFont('Arial','B',10);
        // $this->pdf->Text(2,34,$reg['product']);
        // $this->pdf->Text(2,40,'Stock: '.$reg['stock'].' ');
        // //lado derecho
        // // $this->pdf->Text(50,30,utf8_decode('Clase: '.$product['cse_prod']));
        // $this->pdf->Text(50,40,'sku: '.$reg['sku']);
        // $this->pdf->SetFont('Arial','B',10);
        // //A,C,B sets
        // $this->pdf->Code128(25,44,$reg['lot'],50,15);
        // $this->pdf->SetFont('Arial','B',7);
        // // $this->pdf->Text(2,65,'IMPORTADO POR ');
        // $this->pdf->Text(70,65,'RFC ');



        $this->pdf->SetFont('Arial','',8);
        $this->pdf->Text(70,29,$item['order_id']."-".$item['item_id']);
        $this->pdf->Image($qr, 63,30,26,26,'png');

        $now = date('Ydmhis');
        $file = $this->pdf->Output("S") ;
        return [
            "name" => "label_production_".$item['order_id']."_".$now.".pdf",
            "file" => base64_encode($file),
        ];
    }

    // MODULATIONS
    public function createTubeModulation($productLineID,$line,$production_date,$tubes,$type) {
        switch ((INT)$type) {
            case 1: // Files
                // $x = 10;
                // $y = 20;
                // $rows = 0;
                // $cols = 0;
                // $regPerLine = 51;
                // $sumRegs  = 0;
                // $totalRegs = 0;
                // foreach ($tubes as $tube) { $totalRegs += 3 ; $totalRegs += count($tube['items']); }
                // $totalRegs -= 1;

                // $this->cutHeader('Corte tubos',4,$line,$production_date);
                // // dd($tubes);
                // foreach ($tubes as $key => $tube) {
                //     $setTube = 0;
                //     $afterSetTube = 0;
                //     if((INT)$key !== 0 ) {
                //         // BLANK
                //         $this->pdf->SetFont("Arial", "B", 10);
                //         $this->pdf->SetXY($x, $y);
                //         $this->pdf->Cell(60, 5, '', 'T', 1, "C", true);
                //         $y += 5;
                //         $rows++;
                //         $sumRegs++;
                //         if((INT)$rows === (INT)$regPerLine) { $x += 65; $y = 20; $rows = 0; $cols++; }
                //         if((INT)$cols === 3) {  $this->cutHeader('Corte tubos',4,$line,$production_date); $cols = 0; $x = 10; $y = 20; $rows = 0;}
                //     }
                //     // TUBE NOM
                //     $this->pdf->SetFont("Arial", "B", 10);
                //     $this->pdf->SetXY($x, $y);
                //     $this->pdf->setFillColor(211,211,211);
                //     $this->pdf->Cell(60,5, $tube['tube'], 1, 1, "C", true);
                //     $this->pdf->setFillColor(255, 255, 255);
                //     $y += 5;
                //     $rows++;
                //     $sumRegs++;
                //     if((INT)$rows === (INT)$regPerLine) { $x += 65; $y = 20; $rows = 0; $cols++; }
                //     if((INT)$cols === 3) {  $this->cutHeader('Corte tubos',4,$line,$production_date); $cols = 0; $x = 10; $y = 20; $rows = 0;}
                //     // HEADER
                //     $this->pdf->SetFont("Arial", "B", 9);
                //     $this->pdf->SetXY($x, $y);
                //     $this->pdf->Cell(15, 5, 'PEDIDO', 1, 1, "C", true);
                //     $this->pdf->SetXY($x+15, $y);
                //     $this->pdf->Cell(17, 5, 'ITEMS', 1, 1, "C", true);
                //     $this->pdf->SetXY($x+32, $y);
                //     $this->pdf->Cell(13, 5, 'WIDTH', 1, 1, "C", true);
                //     $this->pdf->SetXY($x+45, $y);
                //     $this->pdf->Cell(15, 5, 'UBICA', 1, 1, "C", true);
                //     $y += 5;
                //     $rows++;
                //     $sumRegs++;
                //     if((INT)$rows === (INT)$regPerLine) { $x += 65; $y = 20; $rows = 0; $cols++; }
                //     if((INT)$cols === 3) {  $this->cutHeader('Corte tubos',4,$line,$production_date); $cols = 0; $x = 10; $y = 20; $rows = 0;}
                //     // ITEMS
                //     $this->pdf->SetFont("Arial", "", 9);
                //     foreach ($tube['items'] as $item) {
                //         $setTube =  $item['set_tube_id'];
                //         $linesFin = 'L,R';
                //         if((INT)$setTube !== (INT)$afterSetTube ) { $linesFin = 'L,R,T'; }
                //         if(((INT)$totalRegs-1) === $sumRegs) { $linesFin = 'L,R,B'; }
                //         $this->pdf->SetXY($x, $y);
                //         $this->pdf->Cell(15, 5, $item['order_id'], $linesFin, 1, "C", true);
                //         $this->pdf->SetXY($x+15, $y);
                //         $this->pdf->Cell(17, 5, $item['item_id'], $linesFin, 1, "C", true);
                //         $this->pdf->SetXY($x+32, $y);
                //         $this->pdf->Cell(13, 5, $item['width_discount'], $linesFin, 1, "C", true);
                //         $this->pdf->SetXY($x+45, $y);
                //         $this->pdf->Cell(15, 5, $item['ubica'], $linesFin, 1, "C", true);
                //         $y += 5;
                //         $rows++;
                //         $sumRegs++;
                //         $afterSetTube = $item['set_tube_id'];
                //         if((INT)$rows === (INT)$regPerLine) { $x += 65; $y = 20; $rows = 0; $cols++; }
                //         if((INT)$cols === 3) {  $this->cutHeader('Corte tubos',4,$line,$production_date); $cols = 0; $x = 10; $y = 20; $rows = 0;}
                //     }
                // }
            break;
            case 2: // Stickers
                $this->pdf->SetAutoPageBreak(true,2);
                foreach ($tubes as $key => $tube) {
                    foreach ($tube['items'] as $item) {
                        $this->pdf->AddPage();
                        $this->pdf->SetXY(1, 1);
                        $this->pdf->Image("img/lansonshades.jpeg", 4, 1, 0, 4);

                        $this->pdf->SetFont("Arial", "", 12);
                        $this->pdf->SetXY(40, 1);
                        $this->pdf->Cell(35, 5, $item['order_id'].' - '.$item['item_id'], 0, 1, "R", false);

                        $this->pdf->SetFont("Arial", "", 8);
                        $this->pdf->Text(4,9,$tube['tube']);
                        $this->pdf->Text(4,13,$item['product']);
                        $this->pdf->Text(4,17,$item['width'].' x '.$item['height']);

                        // lefts
                        $this->pdf->Text(48,14,$item['width_discount']);
                        $this->pdf->Text(60,14,$item['ubica']);

                        // code
                        $this->pdf->Code128(48, 16, $item['detail_order_id'], 27, 6);
                        $this->pdf->Text(60,25,Carbon::createFromFormat('Y-m-d H:i:s', $item['created_at'])->format('Y-m-d'));
                        $this->pdf->SetFont("Arial", "B", 8);
                        $this->pdf->Text(48,10,'Corte');
                        $this->pdf->Text(60,10,utf8_decode('Ubicación'));
                        $this->pdf->Text(48,25,'Creado:');

                    }
                }
            break;
        }


        // $this->pdf->Output("") ;
        $nameFile = '';
        switch ((INT)$type) {
            case 1: // Files
                $nameFile = "corte-tubos-";
            break;
            case 2: // Labels
                $nameFile = "etiquetas-tubos-";
            break;
        }
        $file = $this->pdf->Output("S") ;
        return [
            "name" => $nameFile."PL".$productLineID."-".$line."-".$production_date.".pdf",
            "file" => base64_encode($file),
        ];
    }

    public function createTwistbarModulation($productLineID,$line,$production_date,$twistbars,$type) {
        switch ((INT)$type) {
            case 1: // Files
                // $x = 10;
                // $y = 20;
                // $rows = 0;
                // $cols = 0;
                // $regPerLine = 51;
                // $sumRegs  = 0;
                // $totalRegs = 0;
                // foreach ($twistbars as $twistbar) { $totalRegs += 3 ; $totalRegs += count($twistbar['items']); }
                // $totalRegs -= 1;
                // $this->cutHeader('Corte barra giro',14,$line,$production_date);
                // foreach ($twistbars as $key => $twistbar) {
                //     $setTwistbar = 0;
                //     $afterTwistbar = 0;
                //     if((INT)$key !== 0 ) {
                //         // BLANK
                //         $this->pdf->SetFont("Arial", "B", 10);
                //         $this->pdf->SetXY($x, $y);
                //         $this->pdf->Cell(60, 5, '', 'T', 1, "C", true);
                //         $y += 5;
                //         $rows++;
                //         $sumRegs++;
                //         if((INT)$rows === (INT)$regPerLine) { $x += 65; $y = 20; $rows = 0; $cols++; }
                //         if((INT)$cols === 3) {  $this->cutHeader('Corte barra giro',14,$line,$production_date); $cols = 0; $x = 10; $y = 20; $rows = 0;}
                //     }
                //     // TUBE NOM
                //     $this->pdf->SetFont("Arial", "B", 10);
                //     $this->pdf->SetXY($x, $y);
                //     $this->pdf->setFillColor(211,211,211);
                //     $this->pdf->Cell(60,5, $twistbar['article'], 1, 1, "C", true);
                //     $this->pdf->setFillColor(255, 255, 255);
                //     $y += 5;
                //     $rows++;
                //     $sumRegs++;
                //     if((INT)$rows === (INT)$regPerLine) { $x += 65; $y = 20; $rows = 0; $cols++; }
                //     if((INT)$cols === 3) {  $this->cutHeader('Corte barra giro',14,$line,$production_date); $cols = 0; $x = 10; $y = 20; $rows = 0;}
                //     // HEADER
                //     $this->pdf->SetFont("Arial", "B", 9);
                //     $this->pdf->SetXY($x, $y);
                //     $this->pdf->Cell(15, 5, 'PEDIDO', 1, 1, "C", true);
                //     $this->pdf->SetXY($x+15, $y);
                //     $this->pdf->Cell(17, 5, 'ITEMS', 1, 1, "C", true);
                //     $this->pdf->SetXY($x+32, $y);
                //     $this->pdf->Cell(13, 5, 'WIDTH', 1, 1, "C", true);
                //     $this->pdf->SetXY($x+45, $y);
                //     $this->pdf->Cell(15, 5, 'UBICA', 1, 1, "C", true);
                //     $y += 5;
                //     $rows++;
                //     $sumRegs++;
                //     if((INT)$rows === (INT)$regPerLine) { $x += 65; $y = 20; $rows = 0; $cols++; }
                //     if((INT)$cols === 3) {  $this->cutHeader('Corte barra giro',14,$line,$production_date); $cols = 0; $x = 10; $y = 20; $rows = 0;}
                //     // ITEMS
                //     $this->pdf->SetFont("Arial", "", 9);
                //     foreach ($twistbar['items'] as $item) {
                //         $setTwistbar =  $item['set_twistbar_id'];
                //         $linesFin = 'L,R';
                //         if((INT)$setTwistbar !== (INT)$afterTwistbar ) { $linesFin = 'L,R,T'; }
                //         if(((INT)$totalRegs-1) === $sumRegs) { $linesFin = 'L,R,B'; }
                //         $this->pdf->SetXY($x, $y);
                //         $this->pdf->Cell(15, 5, $item['order_id'], $linesFin, 1, "C", true);
                //         $this->pdf->SetXY($x+15, $y);
                //         $this->pdf->Cell(17, 5, $item['item_id'], $linesFin, 1, "C", true);
                //         $this->pdf->SetXY($x+32, $y);
                //         $this->pdf->Cell(13, 5, $item['width_discount'], $linesFin, 1, "C", true);
                //         $this->pdf->SetXY($x+45, $y);
                //         $this->pdf->Cell(15, 5, $item['ubica'], $linesFin, 1, "C", true);
                //         $y += 5;
                //         $rows++;
                //         $sumRegs++;
                //         $afterTwistbar = $item['set_twistbar_id'];
                //         if((INT)$rows === (INT)$regPerLine) { $x += 65; $y = 20; $rows = 0; $cols++; }
                //         if((INT)$cols === 3) {  $this->cutHeader('Corte barra giro',14,$line,$production_date); $cols = 0; $x = 10; $y = 20; $rows = 0;}
                //     }
                // }
            break;
            case 2: // Stickers
                $this->pdf->SetAutoPageBreak(true,2);
                foreach ($twistbars as $key => $twistbar) {
                    foreach ($twistbar['items'] as $item) {
                        $this->pdf->AddPage();
                        $this->pdf->SetXY(1, 1);
                        $this->pdf->Image("img/lansonshades.jpeg", 4, 1, 0, 4);

                        $this->pdf->SetFont("Arial", "", 12);
                        $this->pdf->SetXY(40, 1);
                        $this->pdf->Cell(35, 5, $item['order_id'].' - '.$item['item_id'], 0, 1, "R", false);

                        $this->pdf->SetFont("Arial", "", 8);
                        $this->pdf->Text(4,9,$item['article']);
                        $this->pdf->Text(4,13,$item['product']);
                        $this->pdf->Text(4,17,$item['width'].' x '.$item['height']);

                        // lefts
                        $this->pdf->Text(48,14,$item['width_discount']);
                        $this->pdf->Text(60,14,$item['ubica']);

                        // code
                        $this->pdf->Code128(48, 16, $item['detail_order_id'], 27, 6);
                        $this->pdf->Text(60,25,Carbon::createFromFormat('Y-m-d H:i:s', $item['created_at'])->format('Y-m-d'));
                        $this->pdf->SetFont("Arial", "B", 8);
                        $this->pdf->Text(48,10,'Corte');
                        $this->pdf->Text(60,10,utf8_decode('Ubicación'));
                        $this->pdf->Text(48,25,'Creado:');

                    }
                }
            break;
        }
        // $this->pdf->Output("");
        $nameFile = '';
        switch ((INT)$type) {
            case 1: // Files
                $nameFile = "corte-barra-giro-";
            break;
            case 2: // Labels
                $nameFile = "etiquetas-barra-giro-";
            break;
        }
        $file = $this->pdf->Output("S") ;
        return [
            "name" => $nameFile."PL".$productLineID."-".$line."-".$production_date.".pdf",
            "file" => base64_encode($file),
        ];
    }

    // ALL PERFIL
    public function createAllPerfilesModulation($productLineID,$line,$tubes,$perfiles,$twistbars,$counterweights,$type) {

        // TUBOS
        if( count($tubes) > 0 ) {
            $x = 10;
            $y = 20;
            $rows = 0;
            $cols = 0;
            $regPerLine = 51;
            $sumRegs  = 0;
            $totalRegs = 0;
            foreach ($tubes as $tube) { $totalRegs += 3 ; $totalRegs += count($tube['items']); }
            $totalRegs -= 1;

            $this->cutHeader('Corte tubos',4,$productLineID,$line);
            // dd($tubes);
            foreach ($tubes as $key => $tube) {
                $setTube = 0;
                $afterSetTube = 0;
                if((INT)$key !== 0 ) {
                    // BLANK
                    $this->pdf->SetFont("Arial", "B", 10);
                    $this->pdf->SetXY($x, $y);
                    $this->pdf->Cell(60, 5, '', 'T', 1, "C", true);
                    $y += 5;
                    $rows++;
                    $sumRegs++;
                    if((INT)$rows === (INT)$regPerLine) { $x += 65; $y = 20; $rows = 0; $cols++; }
                    if((INT)$cols === 3) {  $this->cutHeader('Corte tubos',4,$productLineID,$line); $cols = 0; $x = 10; $y = 20; $rows = 0;}
                }
                // TUBE NOM
                $this->pdf->SetFont("Arial", "B", 10);
                $this->pdf->SetXY($x, $y);
                $this->pdf->setFillColor(211,211,211);
                $this->pdf->Cell(60,5, $tube['tube'], 1, 1, "C", true);
                $this->pdf->setFillColor(255, 255, 255);
                $y += 5;
                $rows++;
                $sumRegs++;
                if((INT)$rows === (INT)$regPerLine) { $x += 65; $y = 20; $rows = 0; $cols++; }
                if((INT)$cols === 3) {  $this->cutHeader('Corte tubos',4,$productLineID,$line); $cols = 0; $x = 10; $y = 20; $rows = 0;}
                // HEADER
                $this->pdf->SetFont("Arial", "B", 9);
                $this->pdf->SetXY($x, $y);
                $this->pdf->Cell(15, 5, '#', 1, 1, "C", true);
                $this->pdf->SetXY($x+15, $y);
                $this->pdf->Cell(17, 5, 'ITEMS', 1, 1, "C", true);
                $this->pdf->SetXY($x+32, $y);
                $this->pdf->Cell(13, 5, 'WIDTH', 1, 1, "C", true);
                $this->pdf->SetXY($x+45, $y);
                $this->pdf->Cell(15, 5, 'UBICA', 1, 1, "C", true);
                $y += 5;
                $rows++;
                $sumRegs++;
                if((INT)$rows === (INT)$regPerLine) { $x += 65; $y = 20; $rows = 0; $cols++; }
                if((INT)$cols === 3) {  $this->cutHeader('Corte tubos',4,$productLineID,$line); $cols = 0; $x = 10; $y = 20; $rows = 0;}
                // ITEMS
                $this->pdf->SetFont("Arial", "", 7);
                foreach ($tube['items'] as $item) {
                    $setTube =  $item['set_tube_id'];
                    $linesFin = 'L,R';
                    if((INT)$setTube !== (INT)$afterSetTube ) { $linesFin = 'L,R,T'; }
                    if(((INT)$totalRegs-1) === $sumRegs) { $linesFin = 'L,R,B'; }
                    $this->pdf->SetXY($x, $y);
                    $this->pdf->Cell(15, 5, $item['nomen'].$item['order_id'], $linesFin, 1, "C", true);
                    $this->pdf->SetXY($x+15, $y);
                    $this->pdf->Cell(17, 5, $item['item_id'], $linesFin, 1, "C", true);
                    $this->pdf->SetXY($x+32, $y);
                    $this->pdf->Cell(13, 5, $item['width_discount'], $linesFin, 1, "C", true);
                    $this->pdf->SetXY($x+45, $y);
                    $this->pdf->Cell(15, 5, $item['ubica_id'], $linesFin, 1, "C", true);
                    $y += 5;
                    $rows++;
                    $sumRegs++;
                    $afterSetTube = $item['set_tube_id'];
                    if((INT)$rows === (INT)$regPerLine) { $x += 65; $y = 20; $rows = 0; $cols++; }
                    if((INT)$cols === 3) {  $this->cutHeader('Corte tubos',4,$productLineID,$line); $cols = 0; $x = 10; $y = 20; $rows = 0;}
                }
            }
        }
        // BARRAS GIRO
        if( count($twistbars) > 0 ) {
            $x = 10;
            $y = 20;
            $rows = 0;
            $cols = 0;
            $regPerLine = 51;
            $sumRegs  = 0;
            $totalRegs = 0;
            foreach ($twistbars as $twistbar) { $totalRegs += 3 ; $totalRegs += count($twistbar['items']); }
            $totalRegs -= 1;
            $this->cutHeader('Corte barra giro',14,$productLineID,$line);
            // dd($tubes);
            foreach ($twistbars as $key => $twistbar) {
                $setTwistbar = 0;
                $afterTwistbar = 0;
                if((INT)$key !== 0 ) {
                    // BLANK
                    $this->pdf->SetFont("Arial", "B", 10);
                    $this->pdf->SetXY($x, $y);
                    $this->pdf->Cell(60, 5, '', 'T', 1, "C", true);
                    $y += 5;
                    $rows++;
                    $sumRegs++;
                    if((INT)$rows === (INT)$regPerLine) { $x += 65; $y = 20; $rows = 0; $cols++; }
                    if((INT)$cols === 3) {  $this->cutHeader('Corte barra giro',14,$productLineID,$line); $cols = 0; $x = 10; $y = 20; $rows = 0;}
                }
                // TUBE NOM
                $this->pdf->SetFont("Arial", "B", 10);
                $this->pdf->SetXY($x, $y);
                $this->pdf->setFillColor(211,211,211);
                $this->pdf->Cell(60,5, $twistbar['article'], 1, 1, "C", true);
                $this->pdf->setFillColor(255, 255, 255);
                $y += 5;
                $rows++;
                $sumRegs++;
                if((INT)$rows === (INT)$regPerLine) { $x += 65; $y = 20; $rows = 0; $cols++; }
                if((INT)$cols === 3) {  $this->cutHeader('Corte barra giro',14,$productLineID,$line); $cols = 0; $x = 10; $y = 20; $rows = 0;}
                // HEADER
                $this->pdf->SetFont("Arial", "B", 9);
                $this->pdf->SetXY($x, $y);
                $this->pdf->Cell(15, 5, '#', 1, 1, "C", true);
                $this->pdf->SetXY($x+15, $y);
                $this->pdf->Cell(17, 5, 'ITEMS', 1, 1, "C", true);
                $this->pdf->SetXY($x+32, $y);
                $this->pdf->Cell(13, 5, 'WIDTH', 1, 1, "C", true);
                $this->pdf->SetXY($x+45, $y);
                $this->pdf->Cell(15, 5, 'UBICA', 1, 1, "C", true);
                $y += 5;
                $rows++;
                $sumRegs++;
                if((INT)$rows === (INT)$regPerLine) { $x += 65; $y = 20; $rows = 0; $cols++; }
                if((INT)$cols === 3) {  $this->cutHeader('Corte barra giro',14,$productLineID,$line); $cols = 0; $x = 10; $y = 20; $rows = 0;}
                // ITEMS
                $this->pdf->SetFont("Arial", "", 7);
                foreach ($twistbar['items'] as $item) {
                    $setTwistbar =  $item['set_twistbar_id'];
                    $linesFin = 'L,R';
                    if((INT)$setTwistbar !== (INT)$afterTwistbar ) { $linesFin = 'L,R,T'; }
                    if(((INT)$totalRegs-1) === $sumRegs) { $linesFin = 'L,R,B'; }
                    $this->pdf->SetXY($x, $y);
                    $this->pdf->Cell(15, 5, $item['nomen'].$item['order_id'], $linesFin, 1, "C", true);
                    $this->pdf->SetXY($x+15, $y);
                    $this->pdf->Cell(17, 5, $item['item_id'], $linesFin, 1, "C", true);
                    $this->pdf->SetXY($x+32, $y);
                    $this->pdf->Cell(13, 5, $item['width_discount'], $linesFin, 1, "C", true);
                    $this->pdf->SetXY($x+45, $y);
                    $this->pdf->Cell(15, 5, $item['ubica_id'], $linesFin, 1, "C", true);
                    $y += 5;
                    $rows++;
                    $sumRegs++;
                    $afterTwistbar = $item['set_twistbar_id'];
                    if((INT)$rows === (INT)$regPerLine) { $x += 65; $y = 20; $rows = 0; $cols++; }
                    if((INT)$cols === 3) {  $this->cutHeader('Corte barra giro',14,$productLineID,$line); $cols = 0; $x = 10; $y = 20; $rows = 0;}
                }
            }
        }
        // PERFILES
        if( count($perfiles) > 0 ) {
            $x = 10;
            $y = 20;
            $rows = 0;
            $cols = 0;
            $regPerLine = 51;
            $sumRegs  = 0;
            $totalRegs = 0;
            foreach ($perfiles as $perfil) { $totalRegs += 3 ; $totalRegs += count($perfil['items']); }
            $totalRegs -= 1;

            $this->cutHeader('Corte perfiles',8,$productLineID,$line);
            // dd($perfiles);
            foreach ($perfiles as $key => $perfil) {
                $setPerfiles = 0;
                $afterPerfiles= 0;
                if((INT)$key !== 0 ) {
                    // BLANK
                    $this->pdf->SetFont("Arial", "B", 10);
                    $this->pdf->SetXY($x, $y);
                    $this->pdf->Cell(60, 5, '', 'T', 1, "C", true);
                    $y += 5;
                    $rows++;
                    $sumRegs++;
                    if((INT)$rows === (INT)$regPerLine) { $x += 65; $y = 20; $rows = 0; $cols++; }
                    if((INT)$cols === 3) {  $this->cutHeader('Corte perfiles',8,$productLineID,$line); $cols = 0; $x = 10; $y = 20; $rows = 0;}
                }
                // TUBE NOM
                $this->pdf->SetFont("Arial", "B", 10);
                $this->pdf->SetXY($x, $y);
                $this->pdf->setFillColor(211,211,211);
                $this->pdf->Cell(60,5, 'Perfil color '.$perfil['color_name'], 1, 1, "C", true);
                $this->pdf->setFillColor(255, 255, 255);
                $y += 5;
                $rows++;
                $sumRegs++;
                if((INT)$rows === (INT)$regPerLine) { $x += 65; $y = 20; $rows = 0; $cols++; }
                if((INT)$cols === 3) {  $this->cutHeader('Corte perfiles',8,$productLineID,$line); $cols = 0; $x = 10; $y = 20; $rows = 0;}
                // HEADER
                $this->pdf->SetFont("Arial", "B", 9);
                $this->pdf->SetXY($x, $y);
                $this->pdf->Cell(15, 5, '#', 1, 1, "C", true);
                $this->pdf->SetXY($x+15, $y);
                $this->pdf->Cell(17, 5, 'ITEMS', 1, 1, "C", true);
                $this->pdf->SetXY($x+32, $y);
                $this->pdf->Cell(13, 5, 'WIDTH', 1, 1, "C", true);
                $this->pdf->SetXY($x+45, $y);
                $this->pdf->Cell(15, 5, 'UBICA', 1, 1, "C", true);
                $y += 5;
                $rows++;
                $sumRegs++;
                if((INT)$rows === (INT)$regPerLine) { $x += 65; $y = 20; $rows = 0; $cols++; }
                if((INT)$cols === 3) {  $this->cutHeader('Corte perfiles',8,$productLineID,$line); $cols = 0; $x = 10; $y = 20; $rows = 0;}
                // ITEMS
                $this->pdf->SetFont("Arial", "", 7);
                foreach ($perfil['items'] as $item) {
                    $setPerfiles =  $item['set_perfil_id'];
                    $linesFin = 'L,R';
                    if((INT)$setPerfiles !== (INT)$afterPerfiles ) { $linesFin = 'L,R,T'; }
                    if(((INT)$totalRegs-1) === $sumRegs) { $linesFin = 'L,R,B'; }
                    $this->pdf->SetXY($x, $y);
                    $this->pdf->Cell(15, 5, $item['nomen'].$item['order_id'], $linesFin, 1, "C", true);
                    $this->pdf->SetXY($x+15, $y);
                    $this->pdf->Cell(17, 5, $item['item_id'], $linesFin, 1, "C", true);
                    $this->pdf->SetXY($x+32, $y);
                    $this->pdf->Cell(13, 5, $item['width_discount'], $linesFin, 1, "C", true);
                    $this->pdf->SetXY($x+45, $y);
                    $this->pdf->Cell(15, 5, $item['ubica_id'], $linesFin, 1, "C", true);
                    $y += 5;
                    $rows++;
                    $sumRegs++;
                    $afterPerfiles = $item['set_perfil_id'];
                    if((INT)$rows === (INT)$regPerLine) { $x += 65; $y = 20; $rows = 0; $cols++; }
                    if((INT)$cols === 3) {  $this->cutHeader('Corte perfiles',8,$productLineID,$line); $cols = 0; $x = 10; $y = 20; $rows = 0;}
                }
            }
        }
        // CONTERWEIGTH
        if( count($counterweights) > 0 ) {
            $x = 10;
            $y = 20;
            $rows = 0;
            $cols = 0;
            $regPerLine = 51;
            $sumRegs  = 0;
            $totalRegs = 0;
            foreach ($counterweights as $counterweight) {
                $totalRegs += 3 ;
                foreach ($counterweight['colors'] as $color) {
                    $totalRegs += 1;
                    $totalRegs += count($color['items']);
                }
            }
            $totalRegs -= 1;
            $this->cutHeader('Corte bases',14,$productLineID,$line);
            foreach ($counterweights as $key => $counterweight) {
                $setCounterweight = 0;
                $afterCounterweight = 0;
                if((INT)$key !== 0 ) {
                    // BLANK
                    $this->pdf->SetFont("Arial", "B", 10);
                    $this->pdf->SetXY($x, $y);
                    $this->pdf->Cell(60, 5, '', 'T', 1, "C", true);
                    $y += 5;
                    $rows++;
                    $sumRegs++;
                    if((INT)$rows === (INT)$regPerLine) { $x += 65; $y = 20; $rows = 0; $cols++; }
                    if((INT)$cols === 3) {  $this->cutHeader('Corte bases',14,$productLineID,$line); $cols = 0; $x = 10; $y = 20; $rows = 0;}
                }
                // TUBE NOM
                $this->pdf->SetFont("Arial", "B", 10);
                $this->pdf->SetXY($x, $y);
                $this->pdf->setFillColor(211,211,211);
                $this->pdf->Cell(60,5, 'Base '.$counterweight['counterweight_bar'], 1, 1, "C", true);
                $this->pdf->setFillColor(255, 255, 255);
                $y += 5;
                $rows++;
                $sumRegs++;
                if((INT)$rows === (INT)$regPerLine) { $x += 65; $y = 20; $rows = 0; $cols++; }
                if((INT)$cols === 3) {  $this->cutHeader('Corte bases',14,$productLineID,$line); $cols = 0; $x = 10; $y = 20; $rows = 0;}
                foreach ($counterweight['colors'] as $color) {
                    // COLOR
                    $this->pdf->SetFont("Arial", "B", 10);
                    $this->pdf->SetXY($x, $y);
                    $this->pdf->setFillColor(245,245,245);
                    $this->pdf->Cell(60,5, $color['color_name'], 1, 1, "C", true);
                    $this->pdf->setFillColor(255, 255, 255);
                    $y += 5;
                    $rows++;
                    $sumRegs++;
                    if((INT)$rows === (INT)$regPerLine) { $x += 65; $y = 20; $rows = 0; $cols++; }
                    if((INT)$cols === 3) {  $this->cutHeader('Corte bases',14,$productLineID,$line); $cols = 0; $x = 10; $y = 20; $rows = 0;}

                    // HEADER
                    $this->pdf->SetFont("Arial", "B", 9);
                    $this->pdf->SetXY($x, $y);
                    $this->pdf->Cell(15, 5, '#', 1, 1, "C", true);
                    $this->pdf->SetXY($x+15, $y);
                    $this->pdf->Cell(17, 5, 'ITEMS', 1, 1, "C", true);
                    $this->pdf->SetXY($x+32, $y);
                    $this->pdf->Cell(13, 5, 'WIDTH', 1, 1, "C", true);
                    $this->pdf->SetXY($x+45, $y);
                    $this->pdf->Cell(15, 5, 'UBICA', 1, 1, "C", true);
                    $y += 5;
                    $rows++;
                    $sumRegs++;
                    if((INT)$rows === (INT)$regPerLine) { $x += 65; $y = 20; $rows = 0; $cols++; }
                    if((INT)$cols === 3) {  $this->cutHeader('Corte bases',14,$productLineID,$line); $cols = 0; $x = 10; $y = 20; $rows = 0;}
                    // ITEMS
                    $this->pdf->SetFont("Arial", "", 7);
                    foreach ($color['items'] as $item) {
                        $setCounterweight =  $item['set_counterweight_id'];
                        $linesFin = 'L,R';
                        if((INT)$setCounterweight !== (INT)$afterCounterweight ) { $linesFin = 'L,R,T'; }
                        if(((INT)$totalRegs-1) === $sumRegs) { $linesFin = 'L,R,B'; }
                        $this->pdf->SetXY($x, $y);
                        $this->pdf->Cell(15, 5, $item['nomen'].$item['order_id'], $linesFin, 1, "C", true);
                        $this->pdf->SetXY($x+15, $y);
                        $this->pdf->Cell(17, 5, $item['item_id'], $linesFin, 1, "C", true);
                        $this->pdf->SetXY($x+32, $y);
                        $this->pdf->Cell(13, 5, $item['width_discount'], $linesFin, 1, "C", true);
                        $this->pdf->SetXY($x+45, $y);
                        $this->pdf->Cell(15, 5, $item['ubica_id'], $linesFin, 1, "C", true);
                        $y += 5;
                        $rows++;
                        $sumRegs++;
                        $afterCounterweight = $item['set_counterweight_id'];
                        if((INT)$rows === (INT)$regPerLine) { $x += 65; $y = 20; $rows = 0; $cols++; }
                        if((INT)$cols === 3) {  $this->cutHeader('Corte bases',14,$productLineID,$line); $cols = 0; $x = 10; $y = 20; $rows = 0;}
                    }
                }
            }
        }
        // $file = $this->pdf->Output("") ;
        // exit;
        // $file = $this->pdf->Output("S") ;
        // return [
        //     "name" => "corte-perfiles-PL".$productLineID.'-'.$line."-".$production_date.".pdf",
        //     "file" => base64_encode($file),
        // ];
    }

    // PERFIL
    public function createPerfilModulation($line,$perfiles,$type) {
        switch ((INT)$type) {
            case 1: // Files
                // $x = 10;
                // $y = 20;
                // $rows = 0;
                // $cols = 0;
                // $regPerLine = 51;
                // $sumRegs  = 0;
                // $totalRegs = 0;
                // foreach ($perfiles as $perfil) { $totalRegs += 3 ; $totalRegs += count($perfil['items']); }
                // $totalRegs -= 1;

                // $this->cutHeader('Corte perfiles',8,$line,$production_date);
                // // dd($perfiles);
                // foreach ($perfiles as $key => $perfil) {
                //     $setPerfiles = 0;
                //     $afterPerfiles= 0;
                //     if((INT)$key !== 0 ) {
                //         // BLANK
                //         $this->pdf->SetFont("Arial", "B", 10);
                //         $this->pdf->SetXY($x, $y);
                //         $this->pdf->Cell(60, 5, '', 'T', 1, "C", true);
                //         $y += 5;
                //         $rows++;
                //         $sumRegs++;
                //         if((INT)$rows === (INT)$regPerLine) { $x += 65; $y = 20; $rows = 0; $cols++; }
                //         if((INT)$cols === 3) {  $this->cutHeader('Corte perfiles',8,$line,$production_date); $cols = 0; $x = 10; $y = 20; $rows = 0;}
                //     }
                //     // TUBE NOM
                //     $this->pdf->SetFont("Arial", "B", 10);
                //     $this->pdf->SetXY($x, $y);
                //     $this->pdf->setFillColor(211,211,211);
                //     $this->pdf->Cell(60,5, 'Perfil color '.$perfil['color_name'], 1, 1, "C", true);
                //     $this->pdf->setFillColor(255, 255, 255);
                //     $y += 5;
                //     $rows++;
                //     $sumRegs++;
                //     if((INT)$rows === (INT)$regPerLine) { $x += 65; $y = 20; $rows = 0; $cols++; }
                //     if((INT)$cols === 3) {  $this->cutHeader('Corte perfiles',8,$line,$production_date); $cols = 0; $x = 10; $y = 20; $rows = 0;}
                //     // HEADER
                //     $this->pdf->SetFont("Arial", "B", 9);
                //     $this->pdf->SetXY($x, $y);
                //     $this->pdf->Cell(15, 5, 'PEDIDO', 1, 1, "C", true);
                //     $this->pdf->SetXY($x+15, $y);
                //     $this->pdf->Cell(17, 5, 'ITEMS', 1, 1, "C", true);
                //     $this->pdf->SetXY($x+32, $y);
                //     $this->pdf->Cell(13, 5, 'WIDTH', 1, 1, "C", true);
                //     $this->pdf->SetXY($x+45, $y);
                //     $this->pdf->Cell(15, 5, 'UBICA', 1, 1, "C", true);
                //     $y += 5;
                //     $rows++;
                //     $sumRegs++;
                //     if((INT)$rows === (INT)$regPerLine) { $x += 65; $y = 20; $rows = 0; $cols++; }
                //     if((INT)$cols === 3) {  $this->cutHeader('Corte perfiles',8,$line,$production_date); $cols = 0; $x = 10; $y = 20; $rows = 0;}
                //     // ITEMS
                //     $this->pdf->SetFont("Arial", "", 9);
                //     foreach ($perfil['items'] as $item) {
                //         $setPerfiles =  $item['set_perfil_id'];
                //         $linesFin = 'L,R';
                //         if((INT)$setPerfiles !== (INT)$afterPerfiles ) { $linesFin = 'L,R,T'; }
                //         if(((INT)$totalRegs-1) === $sumRegs) { $linesFin = 'L,R,B'; }
                //         $this->pdf->SetXY($x, $y);
                //         $this->pdf->Cell(15, 5, $item['order_id'], $linesFin, 1, "C", true);
                //         $this->pdf->SetXY($x+15, $y);
                //         $this->pdf->Cell(17, 5, $item['item_id'], $linesFin, 1, "C", true);
                //         $this->pdf->SetXY($x+32, $y);
                //         $this->pdf->Cell(13, 5, $item['width_discount'], $linesFin, 1, "C", true);
                //         $this->pdf->SetXY($x+45, $y);
                //         $this->pdf->SetFont("Arial", "", 6);
                //         $this->pdf->Cell(15, 5, $item['ubica_id'], $linesFin, 1, "C", true);
                //         $y += 5;
                //         $rows++;
                //         $sumRegs++;
                //         $afterPerfiles = $item['set_perfil_id'];
                //         if((INT)$rows === (INT)$regPerLine) { $x += 65; $y = 20; $rows = 0; $cols++; }
                //         if((INT)$cols === 3) {  $this->cutHeader('Corte perfiles',8,$line,$production_date); $cols = 0; $x = 10; $y = 20; $rows = 0;}
                //     }
                // }
            break;
            case 2: // Stickers
                $this->pdf->SetAutoPageBreak(true,2);
                foreach ($perfiles as $key => $perfil) {
                    foreach ($perfil['items'] as $item) {

                        $this->pdf->AddPage();
                        $this->pdf->SetXY(1, 1);
                        $this->pdf->Image("img/lansonshades.jpeg", 4, 1, 0, 4);

                        $this->pdf->SetFont("Arial", "", 12);
                        $this->pdf->SetXY(40, 1);
                        $this->pdf->Cell(35, 5, $item['order_id'].' - '.$item['item_id'], 0, 1, "R", false);

                        $this->pdf->SetFont("Arial", "", 8);
                        $this->pdf->Text(4,9,'Perfil Color '.$perfil['color_name']);
                        $this->pdf->Text(4,13,$item['product']);
                        $this->pdf->Text(4,17,$item['width'].' x '.$item['height']);

                        // lefts
                        $this->pdf->Text(48,14,$item['width_discount']);
                        $this->pdf->Text(60,14,$item['ubica_id']);

                        // code
                        $this->pdf->Code128(48, 16, $item['detail_order_id'], 27, 6);
                        $this->pdf->Text(60,25,Carbon::createFromFormat('Y-m-d H:i:s', $item['created_at'])->format('Y-m-d'));
                        $this->pdf->SetFont("Arial", "B", 8);
                        $this->pdf->Text(48,10,'Corte');
                        $this->pdf->Text(60,10,utf8_decode('Ubicación'));
                        $this->pdf->Text(48,25,'Creado:');
                    }
                }
            break;
        }


        // $this->pdf->Output("") ;
        $nameFile = '';
        switch ((INT)$type) {
            case 1: // Files
                $nameFile = "corte-perfiles-";
            break;
            case 2: // Labels
                $nameFile = "etiquetas-corte-perfiles-";
            break;
        }
        $file = $this->pdf->Output("S") ;
        return [
            "name" => $nameFile.$line.".pdf",
            "file" => base64_encode($file),
        ];
    }

    public function createCounterweightsModulation($productLineID,$line,$production_date,$counterweights,$type) {
        switch ((INT)$type) {
            case 1: // Files
                // $x = 10;
                // $y = 20;
                // $rows = 0;
                // $cols = 0;
                // $regPerLine = 51;
                // $sumRegs  = 0;
                // $totalRegs = 0;
                // foreach ($counterweights as $counterweight) {
                //     $totalRegs += 3 ;
                //     foreach ($counterweight['colors'] as $color) {
                //         $totalRegs += 1;
                //         $totalRegs += count($color['items']);
                //     }
                // }
                // $totalRegs -= 1;
                // $this->cutHeader('Corte bases',14,$line,$production_date);
                // foreach ($counterweights as $key => $counterweight) {
                //     $setCounterweight = 0;
                //     $afterCounterweight = 0;
                //     if((INT)$key !== 0 ) {
                //         // BLANK
                //         $this->pdf->SetFont("Arial", "B", 10);
                //         $this->pdf->SetXY($x, $y);
                //         $this->pdf->Cell(60, 5, '', 'T', 1, "C", true);
                //         $y += 5;
                //         $rows++;
                //         $sumRegs++;
                //         if((INT)$rows === (INT)$regPerLine) { $x += 65; $y = 20; $rows = 0; $cols++; }
                //         if((INT)$cols === 3) {  $this->cutHeader('Corte bases',14,$line,$production_date); $cols = 0; $x = 10; $y = 20; $rows = 0;}
                //     }
                //     // TUBE NOM
                //     $this->pdf->SetFont("Arial", "B", 10);
                //     $this->pdf->SetXY($x, $y);
                //     $this->pdf->setFillColor(211,211,211);
                //     $this->pdf->Cell(60,5, 'Base '.$counterweight['counterweight_bar'], 1, 1, "C", true);
                //     $this->pdf->setFillColor(255, 255, 255);
                //     $y += 5;
                //     $rows++;
                //     $sumRegs++;
                //     if((INT)$rows === (INT)$regPerLine) { $x += 65; $y = 20; $rows = 0; $cols++; }
                //     if((INT)$cols === 3) {  $this->cutHeader('Corte bases',14,$line,$production_date); $cols = 0; $x = 10; $y = 20; $rows = 0;}
                //     foreach ($counterweight['colors'] as $color) {
                //         // COLOR
                //         $this->pdf->SetFont("Arial", "B", 10);
                //         $this->pdf->SetXY($x, $y);
                //         $this->pdf->setFillColor(245,245,245);
                //         $this->pdf->Cell(60,5, $color['color_name'], 1, 1, "C", true);
                //         $this->pdf->setFillColor(255, 255, 255);
                //         $y += 5;
                //         $rows++;
                //         $sumRegs++;
                //         if((INT)$rows === (INT)$regPerLine) { $x += 65; $y = 20; $rows = 0; $cols++; }
                //         if((INT)$cols === 3) {  $this->cutHeader('Corte bases',14,$line,$production_date); $cols = 0; $x = 10; $y = 20; $rows = 0;}

                //         // HEADER
                //         $this->pdf->SetFont("Arial", "B", 9);
                //         $this->pdf->SetXY($x, $y);
                //         $this->pdf->Cell(15, 5, 'PEDIDO', 1, 1, "C", true);
                //         $this->pdf->SetXY($x+15, $y);
                //         $this->pdf->Cell(17, 5, 'ITEMS', 1, 1, "C", true);
                //         $this->pdf->SetXY($x+32, $y);
                //         $this->pdf->Cell(13, 5, 'WIDTH', 1, 1, "C", true);
                //         $this->pdf->SetXY($x+45, $y);
                //         $this->pdf->Cell(15, 5, 'UBICA', 1, 1, "C", true);
                //         $y += 5;
                //         $rows++;
                //         $sumRegs++;
                //         if((INT)$rows === (INT)$regPerLine) { $x += 65; $y = 20; $rows = 0; $cols++; }
                //         if((INT)$cols === 3) {  $this->cutHeader('Corte bases',14,$line,$production_date); $cols = 0; $x = 10; $y = 20; $rows = 0;}
                //         // ITEMS
                //         $this->pdf->SetFont("Arial", "", 9);
                //         foreach ($color['items'] as $item) {
                //             $setCounterweight =  $item['set_counterweight_id'];
                //             $linesFin = 'L,R';
                //             if((INT)$setCounterweight !== (INT)$afterCounterweight ) { $linesFin = 'L,R,T'; }
                //             if(((INT)$totalRegs-1) === $sumRegs) { $linesFin = 'L,R,B'; }
                //             $this->pdf->SetXY($x, $y);
                //             $this->pdf->Cell(15, 5, $item['order_id'], $linesFin, 1, "C", true);
                //             $this->pdf->SetXY($x+15, $y);
                //             $this->pdf->Cell(17, 5, $item['item_id'], $linesFin, 1, "C", true);
                //             $this->pdf->SetXY($x+32, $y);
                //             $this->pdf->Cell(13, 5, $item['width_discount'], $linesFin, 1, "C", true);
                //             $this->pdf->SetXY($x+45, $y);
                //             $this->pdf->Cell(15, 5, $item['ubica'], $linesFin, 1, "C", true);
                //             $y += 5;
                //             $rows++;
                //             $sumRegs++;
                //             $afterCounterweight = $item['set_counterweight_id'];
                //             if((INT)$rows === (INT)$regPerLine) { $x += 65; $y = 20; $rows = 0; $cols++; }
                //             if((INT)$cols === 3) {  $this->cutHeader('Corte bases',14,$line,$production_date); $cols = 0; $x = 10; $y = 20; $rows = 0;}
                //         }
                //     }
                // }
            break;
            case 2: // Stickers
                $this->pdf->SetAutoPageBreak(true,2);
                foreach ($counterweights as $key => $counterweight) {
                    foreach ($counterweight['colors'] as $key => $color) {
                        foreach ($color['items'] as $item) {

                            $this->pdf->AddPage();
                            $this->pdf->SetXY(1, 1);
                            $this->pdf->Image("img/lansonshades.jpeg", 4, 1, 0, 4);

                            $this->pdf->SetFont("Arial", "", 12);
                            $this->pdf->SetXY(40, 1);
                            $this->pdf->Cell(35, 5, $item['order_id'].' - '.$item['item_id'], 0, 1, "R", false);

                            $this->pdf->SetFont("Arial", "", 8);
                            $this->pdf->Text(4,9,'Contrapeso / Base');
                            $this->pdf->Text(4,13,$counterweight['counterweight_bar'].' '.$color['color_name']);
                            $this->pdf->Text(4,17,$item['product']);
                            $this->pdf->Text(4,21,$item['width'].' x '.$item['height']);

                            // lefts
                            $this->pdf->Text(48,14,$item['width_discount']);
                            $this->pdf->Text(60,14,$item['ubica']);

                            // code
                            $this->pdf->Code128(48, 16, $item['detail_order_id'], 27, 6);
                            $this->pdf->Text(60,25,Carbon::createFromFormat('Y-m-d H:i:s', $item['created_at'])->format('Y-m-d'));
                            $this->pdf->SetFont("Arial", "B", 8);
                            $this->pdf->Text(48,10,'Corte');
                            $this->pdf->Text(60,10,utf8_decode('Ubicación'));
                            $this->pdf->Text(48,25,'Creado:');
                        }
                    }
                }
            break;
        }
        // $this->pdf->Output("") ;

        $nameFile = '';
        switch ((INT)$type) {
            case 1: // Files
                $nameFile = "corte-bases-";
            break;
            case 2: // Labels
                $nameFile = "etiquetas-corte-bases-";
            break;
        }
        $file = $this->pdf->Output("S") ;
        return [
            "name" => $nameFile."PL".$productLineID."-".$line."-".$production_date.".pdf",
            "file" => base64_encode($file),
        ];
    }

    public function createCutsModulation($productLineID,$line,$production_date,$cuts,$file, $type) {
        switch ((INT)$type) {
            case 1: // Files
                $this->pdf->SetAutoPageBreak(true,2);
                // GRAFICS
                //  dd($cuts);
                if($file == 'cuts') {
                    $this->cutGraficFileLandscapeHeader('Grafica de corte telas',27,$productLineID,$line,$production_date);
                    $x = 8;
                    $this->pdf->SetDrawColor(230,230,230);
                    $this->pdf->SetXY($x, 18);
                    $this->pdf->Cell(50, 190, '', 1, 1, "C", false);
                    $this->pdf->SetDrawColor(0,0,0);
                    // creamos los cortes
                    $maxHeight = 0;
                    $joinOld = 1;
                    $articleIdOld = 0;
                    $cutArticleOld = $cuts[0]['article_id'];
                    $y = 18;
                    $rowNumber = 1;
                    foreach ($cuts as $cut) {
                        $width = 0.16666 * ( $cut['width_discount'] * 100 );
                        $widthText = $cut['width_discount'];
                        $height = 0.16666 * ( $cut['height_add'] * 100 );
                        $heightText = $cut['height_add'];
                        // join
                        if( (INT)$cut['join_id'] === (INT)$joinOld AND (INT)$cutArticleOld === (INT)$cut['article_id'] ) {
                            // MAX HEIGHT
                            if( !is_null($cut['join_id'] ) ) {
                                if( $height > $maxHeight ) {
                                    $maxHeight = $height;
                                }
                            } else {
                                $y = $y +  $maxHeight;
                                if((INT)$rowNumber === 1 ) { $x = 8; }
                                if((INT)$rowNumber === 2 ) { $x = 66; }
                                if((INT)$rowNumber === 3 ) { $x = 124; }
                                if((INT)$rowNumber === 4 ) { $x = 182; }
                                if((INT)$rowNumber === 5 ) { $x = 240; }
                                $maxHeight = $height;
                            }
                            } else {
                            $y = $y + $maxHeight;
                            if((INT)$rowNumber === 1 ) { $x = 8; }
                            if((INT)$rowNumber === 2 ) { $x = 66; }
                            if((INT)$rowNumber === 3 ) { $x = 124; }
                            if((INT)$rowNumber === 4 ) { $x = 182; }
                            if((INT)$rowNumber === 5 ) { $x = 240; }
                            $maxHeight = $height;
                        }
                        //
                        if((INT)$cut['article_id'] !== (INT)$articleIdOld  ) { $plusY = 5; } else { $plusY = 0; }
                        // si vemos que rebasa e limite seteamos todo
                        if( (DOUBLE)($y+$plusY+$maxHeight) > (DOUBLE)200) {
                            $rowNumber++;
                            // sec page
                            if( (INT)$rowNumber === 6 ) {
                                $this->cutGraficFileLandscapeHeader('Grafica de corte telas',27,$productLineID,$line,$production_date);
                                $x = 8;
                                $this->pdf->SetDrawColor(230,230,230);
                                $this->pdf->SetXY($x, 18);
                                $this->pdf->Cell(50, 190, '', 1, 1, "C", false);
                                $this->pdf->SetDrawColor(0,0,0);
                                $rowNumber = 1;
                            } else {
                                if((INT)$rowNumber === 1 ) { $x = 8; }
                                if((INT)$rowNumber === 2 ) { $x = 66; }
                                if((INT)$rowNumber === 3 ) { $x = 124; }
                                if((INT)$rowNumber === 4 ) { $x = 182; }
                                if((INT)$rowNumber === 5 ) { $x = 240; }
                                $this->pdf->SetDrawColor(230,230,230);
                                $this->pdf->SetXY($x, 18);
                                $this->pdf->Cell(50, 190, '', 1, 1, "C", false);
                                $this->pdf->SetDrawColor(0,0,0);
                            }
                            // creamos los cortes
                            $maxHeight = $height;
                            $joinOld = 1;
                            $articleIdOld = 0;
                            $y = 18;
                        }
                        // HEADER ARTICLE
                        if((INT)$cut['article_id'] !== (INT)$articleIdOld  ) {
                            $cutArticleOld = $cut['article_id'];
                            $this->pdf->setFillColor(211,211,211);
                            $this->pdf->SetFont("Arial", "B", 8);
                            $this->pdf->SetXY($x, $y);
                            $this->pdf->Cell(50, 5, $cut['article'], 0, 1, "C", true);
                            $y = $y + 5;
                            $this->pdf->setFillColor(255,255,255);
                        }
                        $articleIdOld = $cut['article_id'];
                        // CLOTH
                        $this->pdf->SetFont("Arial", "", 8);
                        $this->pdf->SetXY($x, $y);
                        $this->pdf->Cell($width, $height, '', 1, 1, "C", false);
                        // DATOS
                        $this->pdf->SetXY($x, $y+1);
                        $this->pdf->Cell($height, 4, $cut['order_id']." - ".$cut['item_id'], 0, 1, "L", false);
                        $this->pdf->SetXY($x, $y+4);
                        $this->pdf->Cell($height, 4,$widthText." X ", 0, 1, "L", false);
                        $this->pdf->SetXY($x, $y+8);
                        $this->pdf->Cell($height, 4,$heightText, 0, 1, "L", false);
                        // $this->pdf->SetXY($x, $y+12);
                        // $this->pdf->Cell($height, 4,$y, 0, 1, "L", false);
                        // $this->pdf->SetXY($x, $y+16);
                        // $this->pdf->Cell($height, 4,$maxHeight, 0, 1, "L", false);

                        // MAX HEIGHT
                        if(is_null($cut['join_id'])) {
                            if((INT)$rowNumber === 1 ) { $x = 8; }
                            if((INT)$rowNumber === 2 ) { $x = 66; }
                            if((INT)$rowNumber === 3 ) { $x = 124; }
                            if((INT)$rowNumber === 4 ) { $x = 182; }
                            if((INT)$rowNumber === 5 ) { $x = 240; }
                        } else { $x = $x + $width; }
                        $joinOld = $cut['join_id'];
                    }
                }
                // DATA

                $itemStop = 0;
                $itemsPerPage = 13;
                if(count($cuts) < $itemsPerPage ) { $itemStop = count($cuts); } else { $itemStop = $itemsPerPage; }
                $initReg = 0;
                $totalPages = ceil(count($cuts) / $itemsPerPage);
                for ($i = 1; $i <= $totalPages; $i++) {

                    if($file == 'cuts') {
                        $this->cutFileLandscapeHeader('Hoja corte telas',16,$productLineID,$line,$production_date);
                    } else {
                        $this->cutFileLandscapeHeader('Hoja nivelacion',14,$productLineID,$line,$production_date);
                    }
                    // ITEMS
                    $x = 28;
                    $this->pdf->SetFont("Arial", "", 9);
                    while ( $initReg < $itemStop ) {

                        $this->pdf->SetXY(2, $x);
                        $this->pdf->Cell(23, 6, $cuts[$initReg]['order_id'], 1, 1, "C", true);
                        $this->pdf->SetXY(24, $x);
                        $this->pdf->Cell(14, 6, $cuts[$initReg]['item_id'], 1, 1, "C", true);
                        $this->pdf->SetXY(37, $x);
                        $this->pdf->Cell(63, 6, utf8_decode($cuts[$initReg]['article']) , 1, 1, "C", true);
                        if($file == 'cuts') {
                            $this->pdf->SetXY(100, $x);
                            $this->pdf->Cell(15, 6, $cuts[$initReg]['width_discount'], 1, 1, "C", true);
                            $this->pdf->SetXY(115, $x);
                            $this->pdf->Cell(15, 6, $cuts[$initReg]['height_add'], 1, 1, "C", true);
                        } else {
                            $this->pdf->SetXY(100, $x);
                            $this->pdf->Cell(15, 6, $cuts[$initReg]['width'], 1, 1, "C", true);
                            $this->pdf->SetXY(115, $x);
                            $this->pdf->Cell(15, 6, $cuts[$initReg]['height'], 1, 1, "C", true);
                        }
                        $this->pdf->SetXY(130, $x);
                        $this->pdf->Cell(30, 6, utf8_decode($cuts[$initReg]['product']), 1, 1, "C", true);
                        $this->pdf->SetXY(160, $x);
                        $this->pdf->Cell(20, 6, utf8_decode($cuts[$initReg]['operation']) , 1, 1, "C", true);
                        $this->pdf->SetXY(180, $x);
                        $this->pdf->Cell(20, 6, utf8_decode($cuts[$initReg]['counterweight_bar']) , 1, 1, "C", true);
                        $this->pdf->SetXY(200, $x);
                        $this->pdf->Cell(20, 6, utf8_decode($cuts[$initReg]['mechanism_side']) , 1, 1, "C", true);
                        $this->pdf->SetXY(220, $x);
                        $this->pdf->Cell(20, 6, utf8_decode($cuts[$initReg]['fall']) , 1, 1, "C", true);
                        $this->pdf->SetXY(240, $x);
                        $this->pdf->Cell(15, 6, $cuts[$initReg]['height_chain'] , 1, 1, "C", true);
                        $this->pdf->SetXY(255, $x);
                        $this->pdf->Cell(25, 6, $cuts[$initReg]['mechanism']  , 1, 1, "C", true);
                        $this->pdf->SetXY(280, $x);
                        $this->pdf->Cell(15, 6, $cuts[$initReg]['ubica']  , 1, 1, "C", true);
                        $x = $x + 6;

                        $this->pdf->SetFont("Arial", "", 8);
                        $this->pdf->setFillColor(241,241,241);
                        $this->pdf->SetXY(2, $x);
                        $this->pdf->Cell(218, 6, utf8_decode(' '.$this->getComment($cuts[$initReg],$cuts)), 1, 1, "L", true);
                        $this->pdf->SetXY(220, $x);
                        $this->pdf->Cell(75, 6, utf8_decode(' Alineación: '.$cuts[$initReg]['relation']), 1, 1, "L", true);
                        $this->pdf->setFillColor(255,255,255);
                        $x = $x + 6;
                        $initReg++;
                    }
                    $itemStop = $itemStop + $itemsPerPage;
                    $this->pdf->SetFont("Arial", "", 12);
                    if($itemStop > count($cuts) ) { $itemStop = count($cuts) ; }
                    $this->pdf->Text(260, 205, utf8_decode("Página ".$i." de ".$totalPages));
                }
            break;
            case 2: // LABEL
                $this->pdf->SetAutoPageBreak(true,2);
                if($file == 'cuts') {
                    foreach ($cuts as $key => $cut) {
                        $this->pdf->AddPage();
                        $this->pdf->SetXY(1, 1);
                        $this->pdf->Image("img/lansonshades.jpeg", 4, 1, 0, 4);

                        $this->pdf->SetFont("Arial", "", 12);
                        $this->pdf->SetXY(40, 1);
                        $this->pdf->Cell(35, 5, $cut['order_id'].' - '.$cut['item_id'], 0, 1, "R", false);

                        $this->pdf->SetFont("Arial", "", 8);
                        $this->pdf->Text(4,9,$cut['article']);
                        $this->pdf->Text(4,13,$cut['product'].' '.$cut['width'].' x '.$cut['height']);
                        $this->pdf->Text(4,21,$cut['width_discount'].' x '.$cut['height_add']);
                        $this->pdf->Text(24,21,$cut['mechanism']);

                        // lefts
                        $this->pdf->Text(48,14,$cut['mechanism_side']);
                        $this->pdf->Text(62,14,$cut['ubica']);

                        // code
                        $this->pdf->Code128(48, 16, $cut['detail_order_id'], 27, 6);
                        $this->pdf->Text(60,25,Carbon::createFromFormat('Y-m-d H:i:s', $cut['created_at'])->format('Y-m-d'));
                        $this->pdf->SetFont("Arial", "B", 8);
                        $this->pdf->Text(4,17,'Corte tela');
                        $this->pdf->Text(24,17,'Mecanismo');
                        $this->pdf->Text(48,10,'Control');
                        $this->pdf->Text(62,10,utf8_decode('Ubicación'));
                        $this->pdf->Text(48,25,'Creado:');
                    }
                }
            break;
        }
        // $this->pdf->Output("") ;
        $nameFile = '';
        switch ((INT)$type) {
            case 1: // Files
                if($file == 'cuts') { $nameFile = "corte-telas-"; } else { $nameFile = "hoja-nivelacion-"; }
            break;
            case 2: // Labels
                if($file == 'cuts') { $nameFile = "etiquetascorte-telas-"; } else { $nameFile = "etiquetas-nivelacion-"; }
            break;
        }
        $file = $this->pdf->Output("S") ;
        return [
            "name" =>  $nameFile."PL".$productLineID.'-'.$line."-".$production_date.".pdf",
            "file" => base64_encode($file),
        ];
    }

    public function createMechanismsModulation($productLineID,$line,$production_date,$cuts,$file,$type) {

        switch ((INT)$type) {
            case 1: // Files
                $itemStop = 0;
                $itemsPerPage = 20;
                if(count($cuts) < $itemsPerPage ) { $itemStop = count($cuts); } else { $itemStop = $itemsPerPage; }
                $initReg = 0;
                $totalPages = ceil(count($cuts) / $itemsPerPage);
                for ($i = 1; $i <= $totalPages; $i++) {
                    $this->cutMechanismHeader('Mecanismos',10,$productLineID,$line,$production_date);
                    // ITEMS
                    $x = 28;
                    $this->pdf->SetFont("Arial", "", 9);
                    while ( $initReg < $itemStop ) {

                        $this->pdf->SetXY(6, $x);
                        $this->pdf->Cell(23, 6,$cuts[$initReg]['order_id'], 1, 1, "C", true);
                        $this->pdf->SetXY(29, $x);
                        $this->pdf->Cell(14, 6, $cuts[$initReg]['item_id'], 1, 1, "C", true);
                        $this->pdf->SetXY(43, $x);
                        $this->pdf->Cell(20, 6, utf8_decode($cuts[$initReg]['operation']) , 1, 1, "C", true);
                        $this->pdf->SetXY(63, $x);
                        $this->pdf->Cell(25, 6, utf8_decode($cuts[$initReg]['mechanism']) , 1, 1, "C", true);
                        $this->pdf->SetXY(86, $x);
                        $this->pdf->Cell(20, 6, utf8_decode($cuts[$initReg]['fall']) , 1, 1, "C", true);
                        $this->pdf->SetXY(106, $x);
                        $this->pdf->Cell(20, 6, utf8_decode($cuts[$initReg]['color_name']) , 1, 1, "C", true);
                        $this->pdf->SetXY(126, $x);
                        $this->pdf->Cell(20, 6, utf8_decode($cuts[$initReg]['mechanism_side'])  , 1, 1, "C", true);
                        $this->pdf->SetXY(146, $x);
                        $this->pdf->Cell(20, 6, utf8_decode($cuts[$initReg]['chain']) , 1, 1, "C", true);
                        $this->pdf->SetXY(166, $x);
                        $this->pdf->Cell(20, 6, $cuts[$initReg]['height_chain'], 1, 1, "C", true);
                        $this->pdf->SetXY(186, $x);
                        $this->pdf->Cell(18, 6, $cuts[$initReg]['ubica'], 1, 1, "C", true);
                        $x = $x + 6;

                        $this->pdf->SetFont("Arial", "", 8);
                        $this->pdf->setFillColor(241,241,241);
                        $this->pdf->SetXY(6, $x);
                        $this->pdf->Cell(198, 6, utf8_decode(' '.$this->getComment($cuts[$initReg],$cuts)), 1, 1, "L", true);
                        $this->pdf->setFillColor(255,255,255);
                        $x = $x + 6;
                        $initReg++;
                    }
                    $itemStop = $itemStop + $itemsPerPage;
                    $this->pdf->SetFont("Arial", "", 12);
                    if($itemStop > count($cuts) ) { $itemStop = count($cuts) ; }
                    $this->pdf->Text(178, 291, utf8_decode("Página ".$i." de ".$totalPages));
                }
            break;
            case 2: // LABEL
                $this->pdf->SetAutoPageBreak(true,2);
                foreach ($cuts as $key => $cut) {
                    $this->pdf->AddPage();
                    $this->pdf->SetXY(1, 1);
                    $this->pdf->Image("img/lansonshades.jpeg", 4, 1, 0, 4);

                    $this->pdf->SetFont("Arial", "", 12);
                    $this->pdf->SetXY(40, 1);
                    $this->pdf->Cell(35, 5, $cut['order_id'].' - '.$cut['item_id'], 0, 1, "R", false);

                    $this->pdf->SetFont("Arial", "", 8);
                    $this->pdf->Text(2,9,$cut['product']);
                    $this->pdf->Text(2,13,$cut['width'].' x '.$cut['height']);
                    $this->pdf->Text(2,21,$cut['mechanism']);
                    $this->pdf->Text(26,13,utf8_decode($cut['chain'].' '.$cut['color_name']));
                    $this->pdf->Text(26,21,$cut['height_chain']);

                    // lefts
                    $this->pdf->Text(48,14,$cut['mechanism_side']);
                    $this->pdf->Text(62,14,$cut['ubica']);
                    // code
                    $this->pdf->Code128(48, 16, $cut['detail_order_id'], 27, 6);
                    $this->pdf->Text(60,25,Carbon::createFromFormat('Y-m-d H:i:s', $cut['created_at'])->format('Y-m-d'));
                    $this->pdf->SetFont("Arial", "B", 8);
                    $this->pdf->Text(48,10,'Control');
                    $this->pdf->Text(60,10,utf8_decode('Ubicación'));
                    $this->pdf->Text(48,25,'Creado:');
                    $this->pdf->Text(2,17,'Mecanismo');
                    $this->pdf->Text(26,9,'Cadena');
                    $this->pdf->Text(26,17,'Alto C.');
                }
            break;
        }
        // $this->pdf->Output("") ;
        $nameFile = '';
        switch ((INT)$type) {
            case 1: // Files
                $nameFile = "hoja-mecanismos-";
            break;
            case 2: // Labels
                $nameFile = "etiquetas-mecanismos-";
            break;
        }
        $file = $this->pdf->Output("S") ;
        return [
            "name" => $nameFile."PL".$productLineID."-".$line."-".$production_date.".pdf",
            "file" => base64_encode($file),
        ];
    }
    public function createPackagingModulation($line,$production_date,$cuts,$file,$type) {
        switch ((INT)$type) {
            case 1: // Files
            break;
            case 2: // LABEL
                $this->pdf->SetAutoPageBreak(true,2);
                foreach ($cuts as $key => $cut) {
                    $this->pdf->AddPage();
                    $this->pdf->SetXY(1, 1);
                    $this->pdf->Image("img/lansonshades.jpeg", 3, 1, 0, 8);

                    $this->pdf->SetFont("Arial", "", 18);
                    $this->pdf->SetXY(64, 2);
                    $this->pdf->Cell(35, 5, $cut['order_id'].' - '.$cut['item_id'], 0, 1, "R", false);

                    $this->pdf->SetFont("Arial", "", 10);
                    $this->pdf->Text(4,16,$cut['article']);
                    $this->pdf->Text(78,16,$cut['area_description']);
                    $this->pdf->Text(4,24,$cut['product']);
                    $this->pdf->Text(34,24,$cut['width'].' x '.$cut['height']);
                    $this->pdf->Text(58,24,$cut['operation']);
                    $this->pdf->Text(78,24,$cut['counterweight_bar']);
                    $this->pdf->Text(4,32,$cut['mechanism']);
                    $this->pdf->Text(34,32,$cut['mechanism_side']);
                    $this->pdf->Text(58,32,utf8_decode($cut['chain']));
                    $this->pdf->Text(78,32,$cut['height_chain']);
                    $this->pdf->SetFont("Arial", "", 8);


                    // lefts
                    // $this->pdf->Text(48,14,$cut['mechanism_side']);
                    // $this->pdf->Text(62,14,$cut['ubica']);

                    $this->pdf->SetFont("Arial", "B", 8);
                    // code
                    $this->pdf->Code128(70, 42, $cut['detail_order_id'], 30, 8);
                    $this->pdf->Text(4,49,Carbon::createFromFormat('Y-m-d H:i:s', $cut['created_at'])->format('Y-m-d'));
                    // BLACK
                    $this->pdf->Text(4,12,'Tela');
                    $this->pdf->Text(78,12,utf8_decode('Área'));
                    $this->pdf->Text(4,20,'Producto');
                    $this->pdf->Text(34,20,'Medidas');
                    $this->pdf->Text(58,20,utf8_decode('Operación'));
                    $this->pdf->Text(78,20,utf8_decode('Base'));
                    $this->pdf->Text(4,28,utf8_decode('Mecanismo'));
                    $this->pdf->Text(34,28,utf8_decode('Control'));
                    $this->pdf->Text(58,28,utf8_decode('Cadena'));
                    $this->pdf->Text(78,28,utf8_decode('Alto C.'));
                    // $this->pdf->Text(48,10,'Control');
                    // $this->pdf->Text(62,10,utf8_decode('Ubicación'));
                    // $this->pdf->Text(4,45,'Creado:');
                }
            break;
        }
        // $this->pdf->Output("") ;
        $nameFile = '';
        switch ((INT)$type) {
            case 2: // Labels
                $nameFile = "etiquetas-empaques-";
            break;
        }
        $file = $this->pdf->Output("S") ;
        return [
            "name" =>  $nameFile.$line."-".$production_date.".pdf",
            "file" => base64_encode($file),
        ];
    }

    public function createQuotationDetail($quotation) {
        $itemStop = 0;
        $itemsPerPage = 9;
        if(count($quotation['details']) < $itemsPerPage ) { $itemStop = count($quotation['details']); } else { $itemStop = $itemsPerPage; }
        $initReg = 0;
        $totalPages = ceil(count($quotation['details']) / $itemsPerPage);
        for ($i = 1; $i <= $totalPages; $i++) {
            $this->quotationHeader($quotation);
            // ITEMS
            $x = 48;
            $this->pdf->SetFont("Arial", "", 9);
            while ( $initReg < $itemStop ) {

                $this->pdf->SetFont("Arial", "", 8);
                $this->pdf->SetXY(2, $x);
                $this->pdf->Cell(10, 6, $quotation['details'][$initReg]['item_id'], 1, 1, "C", true);
                $this->pdf->SetXY(12, $x);
                $this->pdf->Cell(63, 6, $quotation['details'][$initReg]['article'] , 1, 1, "C", true);
                $this->pdf->SetXY(75, $x);
                $this->pdf->Cell(14, 6, $quotation['details'][$initReg]['width'] , 1, 1, "C", true);
                $this->pdf->SetXY(89, $x);
                $this->pdf->Cell(14, 6, $quotation['details'][$initReg]['height'] , 1, 1, "C", true);
                $this->pdf->SetXY(103, $x);
                $this->pdf->Cell(30, 6, $quotation['details'][$initReg]['product'], 1, 1, "C", true);
                $this->pdf->SetXY(133, $x);
                $this->pdf->Cell(20, 6, $quotation['details'][$initReg]['operation'], 1, 1, "C", true);

                $this->pdf->SetXY(153, $x);
                $this->pdf->Cell(20, 6, $quotation['details'][$initReg]['counterweight_bar'], 1, 1, "C", true);
                $this->pdf->SetXY(173, $x);
                $this->pdf->Cell(20, 6, $quotation['details'][$initReg]['mechanism_side'], 1, 1, "C", true);
                $this->pdf->SetXY(193, $x);
                $this->pdf->Cell(20, 6, $quotation['details'][$initReg]['fall'] , 1, 1, "C", true);
                $this->pdf->SetXY(213, $x);
                $this->pdf->Cell(15, 6, $quotation['details'][$initReg]['height_chain'], 1, 1, "C", true);

                $this->pdf->SetXY(228, $x);
                $this->pdf->Cell(10, 6, $quotation['details'][$initReg]['quantity'] , 1, 1, "C", true);
                $subtotal = app(GetTotal::class)->getIndividualTotalQuotation($quotation['details'][$initReg],$quotation['client_discount']);
                $discount = '';
                if( (INT)$quotation['client_discount'] !== 0 ) {
                    $discount .= $quotation['client_discount'].'%';
                }
                if( (INT)$quotation['details'][$initReg]['article_discount'] !== 0 ) {
                    $discount .= ' + '.$quotation['details'][$initReg]['article_discount'].'%';
                }
                if( (INT)$quotation['details'][$initReg]['request_discount'] !== 0 ) {
                    $discount .= ' + '.$quotation['details'][$initReg]['request_discount'].'%';
                }
                // var_dump($total);
                $this->pdf->SetXY(238, $x);
                $this->pdf->Cell(19, 6, '$'.number_format($quotation['details'][$initReg]['price']), 1, 1, "C", true);
                $this->pdf->SetXY(257, $x);
                $this->pdf->SetFont("Arial", "", 6);
                $this->pdf->Cell(19, 6, $discount, 1, 1, "C", true);
                $this->pdf->SetFont("Arial", "", 9);
                $this->pdf->SetXY(275, $x);
                $this->pdf->Cell(20, 6, '$'.number_format($subtotal,2), 1, 1, "C", true);

                $x = $x + 6;
                $this->pdf->SetFont("Arial", "", 7);
                $this->pdf->setFillColor(241,241,241);
                $this->pdf->SetXY(2, $x);
                $this->pdf->Cell(293, 6, utf8_decode(' '.$this->getCommentQuotation($quotation['details'][$initReg],$quotation['details'])), 1, 1, "L", true);
                $this->pdf->setFillColor(255,255,255);
                $x = $x + 6;
                $initReg++;
            }
            $itemStop = $itemStop + $itemsPerPage;
            $this->pdf->SetFont("Arial", "", 12);
            if($itemStop > count($quotation['details']) ) { $itemStop = count($quotation['details']) ; }
            $this->pdf->Text(260, 205, utf8_decode("Página ".$i." de ".$totalPages));
        }
        // $this->pdf->Output("") ;
        $file = $this->pdf->Output("S") ;
        return [
            "name" => "Cotización-".$quotation['client_name']."-No.".$quotation['id'].".pdf",
            "file" => base64_encode($file),
        ];
    }

    public function createOrderDetail($order) {
        $itemStop = 0;
        $itemsPerPage = 9;
        if(count($order['details']) < $itemsPerPage ) { $itemStop = count($order['details']); } else { $itemStop = $itemsPerPage; }
        $initReg = 0;
        $totalPages = ceil(count($order['details']) / $itemsPerPage);
        for ($i = 1; $i <= $totalPages; $i++) {
            $this->orderHeader($order);
            // ITEMS
            $x = 48;
            $this->pdf->SetFont("Arial", "", 9);
            while ( $initReg < $itemStop ) {

                $this->pdf->SetFont("Arial", "", 8);
                $this->pdf->SetXY(2, $x);
                $this->pdf->Cell(10, 6, $order['details'][$initReg]['item_id'], 1, 1, "C", true);
                $this->pdf->SetXY(12, $x);
                $this->pdf->Cell(63, 6, utf8_decode($order['details'][$initReg]['article']) , 1, 1, "C", true);
                $this->pdf->SetXY(75, $x);
                $this->pdf->Cell(14, 6, $order['details'][$initReg]['width'] , 1, 1, "C", true);
                $this->pdf->SetXY(89, $x);
                $this->pdf->Cell(14, 6, $order['details'][$initReg]['height'] , 1, 1, "C", true);
                $this->pdf->SetXY(103, $x);
                $this->pdf->Cell(30, 6, $order['details'][$initReg]['product'], 1, 1, "C", true);
                $this->pdf->SetXY(133, $x);
                $this->pdf->Cell(20, 6, $order['details'][$initReg]['operation'], 1, 1, "C", true);

                $this->pdf->SetXY(153, $x);
                $this->pdf->Cell(20, 6, $order['details'][$initReg]['counterweight_bar'], 1, 1, "C", true);
                $this->pdf->SetXY(173, $x);
                $this->pdf->Cell(20, 6, $order['details'][$initReg]['mechanism_side'], 1, 1, "C", true);
                $this->pdf->SetXY(193, $x);
                $this->pdf->Cell(20, 6, $order['details'][$initReg]['fall'] , 1, 1, "C", true);
                $this->pdf->SetXY(213, $x);
                $this->pdf->Cell(15, 6, $order['details'][$initReg]['height_chain'], 1, 1, "C", true);

                $this->pdf->SetXY(228, $x);
                $this->pdf->Cell(10, 6, $order['details'][$initReg]['quantity'] , 1, 1, "C", true);
                $subtotal = app(GetTotal::class)->getIndividualTotalOrder($order['details'][$initReg]);
                $discount = '';
                if( (INT)$order['details'][$initReg]['discount1'] !== 0 ) {
                    $discount .= $order['details'][$initReg]['discount1'].'%';
                }
                if( (INT)$order['details'][$initReg]['discount2'] !== 0 ) {
                    $discount .= ' + '.$order['details'][$initReg]['discount2'].'%';
                }
                if( (INT)$order['details'][$initReg]['discount3'] !== 0 ) {
                    $discount .= ' + '.$order['details'][$initReg]['discount3'].'%';
                }
                // var_dump($total);
                $this->pdf->SetXY(238, $x);
                $this->pdf->Cell(19, 6, '$'.number_format($order['details'][$initReg]['price']), 1, 1, "C", true);
                $this->pdf->SetXY(257, $x);
                $this->pdf->SetFont("Arial", "", 6);
                $this->pdf->Cell(19, 6, $discount, 1, 1, "C", true);
                $this->pdf->SetFont("Arial", "", 9);
                $this->pdf->SetXY(275, $x);
                $this->pdf->Cell(20, 6, '$'.number_format($subtotal,2), 1, 1, "C", true);

                $x = $x + 6;
                $this->pdf->SetFont("Arial", "", 7);
                $this->pdf->setFillColor(241,241,241);
                $this->pdf->SetXY(2, $x);
                $this->pdf->Cell(293, 6, utf8_decode(' '.$this->getComment($order['details'][$initReg],$order['details'])), 1, 1, "L", true);
                $this->pdf->setFillColor(255,255,255);
                $x = $x + 6;
                $initReg++;
            }
            $itemStop = $itemStop + $itemsPerPage;
            $this->pdf->SetFont("Arial", "", 12);
            if($itemStop > count($order['details']) ) { $itemStop = count($order['details']) ; }
            $this->pdf->Text(260, 205, utf8_decode("Página ".$i." de ".$totalPages));
        }
        // $this->pdf->Output("") ;
        $file = $this->pdf->Output("S") ;
        return [
            "name" => "Pedido-No.".$order['id'].'-'.$order['client_name'].".pdf",
            "file" => base64_encode($file),
        ];
    }

    public function createGuaranteeDetail($guarantee) {
        $itemStop = 0;
        $itemsPerPage = 9;
        if(count($guarantee['details']) < $itemsPerPage ) { $itemStop = count($guarantee['details']); } else { $itemStop = $itemsPerPage; }
        $initReg = 0;
        $totalPages = ceil(count($guarantee['details']) / $itemsPerPage);
        for ($i = 1; $i <= $totalPages; $i++) {
            $this->guaranteeHeader($guarantee);
            // ITEMS
            $x = 48;
            $this->pdf->SetFont("Arial", "", 9);
            while ( $initReg < $itemStop ) {

                $this->pdf->SetFont("Arial", "", 8);
                $this->pdf->SetXY(2, $x);
                $this->pdf->Cell(10, 6, $guarantee['details'][$initReg]['item_id'], 1, 1, "C", true);
                $this->pdf->SetXY(12, $x);
                $this->pdf->Cell(63, 6, utf8_decode($guarantee['details'][$initReg]['article']) , 1, 1, "C", true);
                $this->pdf->SetXY(75, $x);
                $this->pdf->Cell(14, 6, $guarantee['details'][$initReg]['width'] , 1, 1, "C", true);
                $this->pdf->SetXY(89, $x);
                $this->pdf->Cell(14, 6, $guarantee['details'][$initReg]['height'] , 1, 1, "C", true);
                $this->pdf->SetXY(103, $x);
                $this->pdf->Cell(30, 6, $guarantee['details'][$initReg]['product'], 1, 1, "C", true);
                $this->pdf->SetXY(133, $x);
                $this->pdf->Cell(20, 6, $guarantee['details'][$initReg]['operation'], 1, 1, "C", true);

                $this->pdf->SetXY(153, $x);
                $this->pdf->Cell(20, 6, $guarantee['details'][$initReg]['counterweight_bar'], 1, 1, "C", true);
                $this->pdf->SetXY(173, $x);
                $this->pdf->Cell(20, 6, $guarantee['details'][$initReg]['mechanism_side'], 1, 1, "C", true);
                $this->pdf->SetXY(193, $x);
                $this->pdf->Cell(20, 6, $guarantee['details'][$initReg]['fall'] , 1, 1, "C", true);
                $this->pdf->SetXY(213, $x);
                $this->pdf->Cell(15, 6, $guarantee['details'][$initReg]['height_chain'], 1, 1, "C", true);

                $this->pdf->SetXY(228, $x);
                $this->pdf->Cell(10, 6, $guarantee['details'][$initReg]['quantity'] , 1, 1, "C", true);
                $subtotal = app(GetTotal::class)->getIndividualTotalOrder($guarantee['details'][$initReg]);
                $discount = '';
                if( (INT)$guarantee['details'][$initReg]['discount1'] !== 0 ) {
                    $discount .= $guarantee['details'][$initReg]['discount1'].'%';
                }
                if( (INT)$guarantee['details'][$initReg]['discount2'] !== 0 ) {
                    $discount .= ' + '.$guarantee['details'][$initReg]['discount2'].'%';
                }
                if( (INT)$guarantee['details'][$initReg]['discount3'] !== 0 ) {
                    $discount .= ' + '.$guarantee['details'][$initReg]['discount3'].'%';
                }
                // var_dump($total);
                $this->pdf->SetXY(238, $x);
                $this->pdf->Cell(19, 6, '$'.number_format($guarantee['details'][$initReg]['price']), 1, 1, "C", true);
                $this->pdf->SetXY(257, $x);
                $this->pdf->SetFont("Arial", "", 6);
                $this->pdf->Cell(19, 6, $discount, 1, 1, "C", true);
                $this->pdf->SetFont("Arial", "", 9);
                $this->pdf->SetXY(275, $x);
                $this->pdf->Cell(20, 6, '$'.number_format($subtotal,2), 1, 1, "C", true);

                $x = $x + 6;
                $this->pdf->SetFont("Arial", "", 7);
                $this->pdf->setFillColor(241,241,241);
                $this->pdf->SetXY(2, $x);
                $this->pdf->Cell(293, 6, utf8_decode(' '.$this->getComment($guarantee['details'][$initReg],$guarantee['details'])), 1, 1, "L", true);
                $this->pdf->setFillColor(255,255,255);
                $x = $x + 6;
                $initReg++;
            }
            $itemStop = $itemStop + $itemsPerPage;
            $this->pdf->SetFont("Arial", "", 12);
            if($itemStop > count($guarantee['details']) ) { $itemStop = count($guarantee['details']) ; }
            $this->pdf->Text(260, 205, utf8_decode("Página ".$i." de ".$totalPages));
        }
        // $this->pdf->Output("") ;
        // exit;
        $file = $this->pdf->Output("S") ;
        return [
            "name" => "Garantía-No.".$guarantee['id'].'-'.$guarantee['client_name'].".pdf",
            "file" => base64_encode($file),
        ];
    }

    // SHIPMENT
    public function createReceptionOrderDetail($order) {
        $this->pdf->SetMargins(20, 25, 20);
        $this->pdf->SetAutoPageBreak(true, 5);

        $itemStop = 0;
        // $itemsPerPage = 13;
        $itemsPerPage = 25;
        if(count($order['details']) < $itemsPerPage ) { $itemStop = count($order['details']); } else { $itemStop = $itemsPerPage; }
        $initReg = 0;
        $totalPages = ceil(count($order['details']) / $itemsPerPage);
        for ($i = 1; $i <= $totalPages; $i++) {

            $this->cutShipmentHeader('Embarque / Mostrador',$order);

            // ITEMS
            $x = 40;
            $this->pdf->SetFont("Arial", "", 9);
            while ( $initReg < $itemStop ) {

                $this->pdf->SetXY(2, $x);
                $this->pdf->Cell(14, 6, $order['details'][$initReg]['item_id'], 1, 1, "C", true);
                $this->pdf->SetXY(16, $x);
                $this->pdf->Cell(63, 6, utf8_decode($order['details'][$initReg]['article']) , 1, 1, "C", true);
                $this->pdf->SetXY(79, $x);
                $this->pdf->Cell(15, 6, $order['details'][$initReg]['width'], 1, 1, "C", true);
                $this->pdf->SetXY(94, $x);
                $this->pdf->Cell(15, 6, $order['details'][$initReg]['height'], 1, 1, "C", true);
                $this->pdf->SetXY(109, $x);
                $this->pdf->Cell(30, 6, utf8_decode($order['details'][$initReg]['product']), 1, 1, "C", true);
                $this->pdf->SetXY(139, $x);
                $this->pdf->Cell(30, 6, utf8_decode($order['details'][$initReg]['operation']) , 1, 1, "C", true);
                $this->pdf->SetXY(169, $x);
                $this->pdf->Cell(31, 6, utf8_decode($order['details'][$initReg]['counterweight_bar']) , 1, 1, "C", true);
                $this->pdf->SetXY(200, $x);
                $this->pdf->Cell(20, 6, utf8_decode($order['details'][$initReg]['mechanism_side']) , 1, 1, "C", true);
                $this->pdf->SetXY(220, $x);
                $this->pdf->Cell(20, 6, utf8_decode($order['details'][$initReg]['fall']) , 1, 1, "C", true);
                $this->pdf->SetXY(240, $x);
                $this->pdf->Cell(15, 6, $order['details'][$initReg]['height_chain'] , 1, 1, "C", true);
                $this->pdf->SetXY(255, $x);
                $this->pdf->Cell(25, 6, $order['details'][$initReg]['mechanism']  , 1, 1, "C", true);
                $this->pdf->SetXY(280, $x);
                $this->pdf->Cell(15, 6, $order['details'][$initReg]['location']  , 1, 1, "C", true);
                $x = $x + 6;

                // $this->pdf->SetFont("Arial", "", 8);
                // $this->pdf->setFillColor(241,241,241);
                // $this->pdf->SetXY(2, $x);
                // $this->pdf->Cell(293, 6, utf8_decode(' '.$this->getComment($order['details'][$initReg],$order['details'])), 1, 1, "L", true);
                $this->pdf->setFillColor(255,255,255);
                // $x = $x + 6;
                $initReg++;
            }
            $itemStop = $itemStop + $itemsPerPage;
            $this->pdf->SetFont("Arial", "", 12);
            if($itemStop > count($order['details']) ) { $itemStop = count($order['details']) ; }
            $this->pdf->Text(260, 205, utf8_decode("Página ".$i." de ".$totalPages));
        }

        $identify =  $order['nomen'].$order['id'];
        if ( $order['nomen'] == 'GLS' OR $order['nomen'] == 'SLS' ) { $identify =  $order['nomen'].$order['folio']; }

        // $file = $this->pdf->Output("") ;
        // exit;
        $file = $this->pdf->Output("S") ;
        return [
            "name" => "Embarque-Mostrador-".$identify.".pdf",
            "file" => base64_encode($file),
        ];
    }

    public function createReceptionOrderDetailTicket($order) {


        $this->cutShipmentHeaderTicket('Embarque / Mostrador',$order);
        $x = 112;
        foreach ( $order['details'] as $key => $item) {
            $this->pdf->SetFont("Arial", "", 11);
            $this->pdf->SetXY(6, $x);
            $this->pdf->Cell(10, 8, $item['item_id'], 1, 1, "C", true);
            $this->pdf->SetXY(16, $x);
            $this->pdf->Cell(81, 8, utf8_decode($item['article']) , 1, 1, "C", true);
            $this->pdf->SetXY(97, $x);
            $this->pdf->Cell(15, 8, $item['width'], 1, 1, "C", true);
            $this->pdf->SetXY(112, $x);
            $this->pdf->Cell(15, 8, $item['height'], 1, 1, "C", true);
            $this->pdf->SetXY(127, $x);
            $this->pdf->SetFont("Arial", "B", 11);
            $this->pdf->Cell(16, 8, utf8_decode($item['location']), 1, 1, "C", true);
            $x = $x + 8;
        }

        $identify =  $order['nomen'].$order['id'];
        if ( $order['nomen'] == 'GLS' OR $order['nomen'] == 'SLS' ) { $identify =  $order['nomen'].$order['folio']; }

        // $file = $this->pdf->Output("") ;
        // exit;
        $file = $this->pdf->Output("S") ;
        return [
            "name" => "ticket-PL-Embarque-Mostrador-".$identify.".pdf",
            "file" => base64_encode($file),
        ];
    }

    public function createDeliveryOrderDetail($order) {
        $this->pdf->SetMargins(20, 25, 20);
        $this->pdf->SetAutoPageBreak(true, 5);
        $itemStop = 0;
        // $itemsPerPage = 13;
        $itemsPerPage = 25;
        if(count($order['details']) < $itemsPerPage ) { $itemStop = count($order['details']); } else { $itemStop = $itemsPerPage; }
        $initReg = 0;
        $totalPages = ceil(count($order['details']) / $itemsPerPage);
        for ($i = 1; $i <= $totalPages; $i++) {
            $this->cutShipmentHeader('Embarque / Envío',$order);
            // ITEMS
            $x = 40;
            $this->pdf->SetFont("Arial", "", 9);
            while ( $initReg < $itemStop ) {

                $this->pdf->SetXY(2, $x);
                $this->pdf->Cell(14, 6, $order['details'][$initReg]['item_id'], 1, 1, "C", true);
                $this->pdf->SetXY(16, $x);
                $this->pdf->Cell(63, 6, utf8_decode($order['details'][$initReg]['article']) , 1, 1, "C", true);
                $this->pdf->SetXY(79, $x);
                $this->pdf->Cell(15, 6, $order['details'][$initReg]['width'], 1, 1, "C", true);
                $this->pdf->SetXY(94, $x);
                $this->pdf->Cell(15, 6, $order['details'][$initReg]['height'], 1, 1, "C", true);
                $this->pdf->SetXY(109, $x);
                $this->pdf->Cell(30, 6, utf8_decode($order['details'][$initReg]['product']), 1, 1, "C", true);
                $this->pdf->SetXY(139, $x);
                $this->pdf->Cell(30, 6, utf8_decode($order['details'][$initReg]['operation']) , 1, 1, "C", true);
                $this->pdf->SetXY(169, $x);
                $this->pdf->Cell(31, 6, utf8_decode($order['details'][$initReg]['counterweight_bar']) , 1, 1, "C", true);
                $this->pdf->SetXY(200, $x);
                $this->pdf->Cell(20, 6, utf8_decode($order['details'][$initReg]['mechanism_side']) , 1, 1, "C", true);
                $this->pdf->SetXY(220, $x);
                $this->pdf->Cell(20, 6, utf8_decode($order['details'][$initReg]['fall']) , 1, 1, "C", true);
                $this->pdf->SetXY(240, $x);
                $this->pdf->Cell(15, 6, $order['details'][$initReg]['height_chain'] , 1, 1, "C", true);
                $this->pdf->SetXY(255, $x);
                $this->pdf->Cell(25, 6, $order['details'][$initReg]['mechanism']  , 1, 1, "C", true);
                $this->pdf->SetXY(280, $x);
                $this->pdf->Cell(15, 6, $order['details'][$initReg]['location']  , 1, 1, "C", true);
                $x = $x + 6;

                // $this->pdf->SetFont("Arial", "", 8);
                // $this->pdf->setFillColor(241,241,241);
                // $this->pdf->SetXY(2, $x);
                // $this->pdf->Cell(293, 6, utf8_decode(' '.$this->getComment($order['details'][$initReg],$order['details'])), 1, 1, "L", true);
                $this->pdf->setFillColor(255,255,255);
                // $x = $x + 6;
                $initReg++;
            }
            $itemStop = $itemStop + $itemsPerPage;
            $this->pdf->SetFont("Arial", "", 12);
            if($itemStop > count($order['details']) ) { $itemStop = count($order['details']) ; }
            $this->pdf->Text(260, 205, utf8_decode("Página ".$i." de ".$totalPages));
        }

        $identify =  $order['nomen'].$order['id'];
        if ( $order['nomen'] == 'GLS' OR $order['nomen'] == 'SLS' ) { $identify =  $order['nomen'].$order['folio']; }

        // $file = $this->pdf->Output("") ;
        $file = $this->pdf->Output("S") ;
        return [
            "name" => "Embarque-Envío-".$identify.".pdf",
            "file" => base64_encode($file),
        ];
    }
    public function createDeliveryOrderDetailTicket($order) {


        $this->cutShipmentHeaderTicket('Embarque / Envío',$order);
        $x = 112;
        foreach ( $order['details'] as $key => $item) {
            $this->pdf->SetFont("Arial", "", 11);
            $this->pdf->SetXY(6, $x);
            $this->pdf->Cell(10, 8, $item['item_id'], 1, 1, "C", true);
            $this->pdf->SetXY(16, $x);
            $this->pdf->Cell(81, 8, utf8_decode($item['article']) , 1, 1, "C", true);
            $this->pdf->SetXY(97, $x);
            $this->pdf->Cell(15, 8, $item['width'], 1, 1, "C", true);
            $this->pdf->SetXY(112, $x);
            $this->pdf->Cell(15, 8, $item['height'], 1, 1, "C", true);
            $this->pdf->SetXY(127, $x);
            $this->pdf->SetFont("Arial", "B", 11);
            $this->pdf->Cell(16, 8, utf8_decode($item['location']), 1, 1, "C", true);
            $x = $x + 8;
        }

        $identify =  $order['nomen'].$order['id'];
        if ( $order['nomen'] == 'GLS' OR $order['nomen'] == 'SLS' ) { $identify =  $order['nomen'].$order['folio']; }

        // $file = $this->pdf->Output("") ;
        // exit;
        $file = $this->pdf->Output("S") ;
        return [
            "name" => "ticket-PL-Embarque-Envío-".$identify.".pdf",
            "file" => base64_encode($file),
        ];
    }

    public function createRouteOrderDetail($order) {
        $this->pdf->SetMargins(20, 25, 20);
        $this->pdf->SetAutoPageBreak(true, 5);
        $itemStop = 0;
        // $itemsPerPage = 13;
        $itemsPerPage = 25;
        if(count($order['details']) < $itemsPerPage ) { $itemStop = count($order['details']); } else { $itemStop = $itemsPerPage; }
        $initReg = 0;
        $totalPages = ceil(count($order['details']) / $itemsPerPage);
        for ($i = 1; $i <= $totalPages; $i++) {
            $this->cutShipmentHeader('Embarque / Ruta',$order);
            // ITEMS
            $x = 40;
            $this->pdf->SetFont("Arial", "", 9);
            while ( $initReg < $itemStop ) {

                $this->pdf->SetXY(2, $x);
                $this->pdf->Cell(14, 6, $order['details'][$initReg]['item_id'], 1, 1, "C", true);
                $this->pdf->SetXY(16, $x);
                $this->pdf->Cell(63, 6, utf8_decode($order['details'][$initReg]['article']) , 1, 1, "C", true);
                $this->pdf->SetXY(79, $x);
                $this->pdf->Cell(15, 6, $order['details'][$initReg]['width'], 1, 1, "C", true);
                $this->pdf->SetXY(94, $x);
                $this->pdf->Cell(15, 6, $order['details'][$initReg]['height'], 1, 1, "C", true);
                $this->pdf->SetXY(109, $x);
                $this->pdf->Cell(30, 6, utf8_decode($order['details'][$initReg]['product']), 1, 1, "C", true);
                $this->pdf->SetXY(139, $x);
                $this->pdf->Cell(30, 6, utf8_decode($order['details'][$initReg]['operation']) , 1, 1, "C", true);
                $this->pdf->SetXY(169, $x);
                $this->pdf->Cell(31, 6, utf8_decode($order['details'][$initReg]['counterweight_bar']) , 1, 1, "C", true);
                $this->pdf->SetXY(200, $x);
                $this->pdf->Cell(20, 6, utf8_decode($order['details'][$initReg]['mechanism_side']) , 1, 1, "C", true);
                $this->pdf->SetXY(220, $x);
                $this->pdf->Cell(20, 6, utf8_decode($order['details'][$initReg]['fall']) , 1, 1, "C", true);
                $this->pdf->SetXY(240, $x);
                $this->pdf->Cell(15, 6, $order['details'][$initReg]['height_chain'] , 1, 1, "C", true);
                $this->pdf->SetXY(255, $x);
                $this->pdf->Cell(25, 6, $order['details'][$initReg]['mechanism']  , 1, 1, "C", true);
                $this->pdf->SetXY(280, $x);
                $this->pdf->Cell(15, 6, $order['details'][$initReg]['location']  , 1, 1, "C", true);
                $x = $x + 6;

                // $this->pdf->SetFont("Arial", "", 8);
                // $this->pdf->setFillColor(241,241,241);
                // $this->pdf->SetXY(2, $x);
                // $this->pdf->Cell(293, 6, utf8_decode(' '.$this->getComment($order['details'][$initReg],$order['details'])), 1, 1, "L", true);
                $this->pdf->setFillColor(255,255,255);
                // $x = $x + 6;
                $initReg++;
            }
            $itemStop = $itemStop + $itemsPerPage;
            $this->pdf->SetFont("Arial", "", 12);
            if($itemStop > count($order['details']) ) { $itemStop = count($order['details']) ; }
            $this->pdf->Text(260, 205, utf8_decode("Página ".$i." de ".$totalPages));
        }

        $identify =  $order['nomen'].$order['id'];
        if ( $order['nomen'] == 'GLS' OR $order['nomen'] == 'SLS' ) { $identify =  $order['nomen'].$order['folio']; }

        // $file = $this->pdf->Output("") ;
        $file = $this->pdf->Output("S") ;
        return [
            "name" => "Embarque-Ruta-".$identify.".pdf",
            "file" => base64_encode($file),
        ];
    }


    public function createRouteOrderDetailTicket($order) {

        $this->cutShipmentHeaderTicket('Embarque / Ruta',$order);
        $x = 112;
        foreach ( $order['details'] as $key => $item) {
            $this->pdf->SetFont("Arial", "", 11);
            $this->pdf->SetXY(6, $x);
            $this->pdf->Cell(10, 8, $item['item_id'], 1, 1, "C", true);
            $this->pdf->SetXY(16, $x);
            $this->pdf->Cell(81, 8, utf8_decode($item['article']) , 1, 1, "C", true);
            $this->pdf->SetXY(97, $x);
            $this->pdf->Cell(15, 8, $item['width'], 1, 1, "C", true);
            $this->pdf->SetXY(112, $x);
            $this->pdf->Cell(15, 8, $item['height'], 1, 1, "C", true);
            $this->pdf->SetXY(127, $x);
            $this->pdf->SetFont("Arial", "B", 11);
            $this->pdf->Cell(16, 8, utf8_decode($item['location']), 1, 1, "C", true);
            $x = $x + 8;
        }

        $identify =  $order['nomen'].$order['id'];
        if ( $order['nomen'] == 'GLS' OR $order['nomen'] == 'SLS' ) { $identify =  $order['nomen'].$order['folio']; }

        // $file = $this->pdf->Output("") ;
        // exit;
        $file = $this->pdf->Output("S") ;
        return [
            "name" => "ticket-PL-Embarque-Ruta-".$identify.".pdf",
            "file" => base64_encode($file),
        ];
    }

    public function createLabelsDesc() {

        $this->pdf->SetMargins(20, 25, 20);
        $this->pdf->SetAutoPageBreak(true, 5);

        $this->pdf->AddPage();
        $this->pdf->SetFont("Arial", "", 14);
        $this->pdf->Text(22,10,"ROLLERTEX S.A. DE C.V.");
        $this->pdf->SetFont("Arial", "", 12);
        $this->pdf->Text(30,16,"SALIDA DE MATERIAL");
        $this->pdf->Text(40,20,"ACEPTADO");


        $this->pdf->Text(4,28,"Factura:");
        $this->pdf->Line(21,28,98,28);
        $this->pdf->Text(4,38,"Pedido:");
        $this->pdf->Line(20,38,98,38);
        $this->pdf->Text(4,48,"Fecha:");
        $this->pdf->Line(19,48,98,48);
        $this->pdf->Text(4,58,utf8_decode("Validó:"));
        $this->pdf->Line(19,58,98,58);

        $file = $this->pdf->Output("D","FILE.pdf") ;
        // $file = $this->pdf->Output("S") ;
        // return [
        //     "name" => "Cotización.pdf",
        //     "file" => base64_encode($file),
        // ];
    }

     // PRIVATE
    private function cutHeader($section,$x,$productLineID,$line) {
        $this->pdf->AddPage();
        $this->pdf->SetXY(1, 1);
        $this->pdf->Image("img/lansonshades.jpeg", 4, 2, 0, 10);

        $this->pdf->SetFont("Arial", "", 15);
        $this->pdf->SetXY(17, 5.5);
        $this->pdf->Cell($x, 4,$section, 0, 0, "L");

        // $this->pdf->SetFont("Arial", "", 12);
        // $this->pdf->Text(170,8,"Linea:");
        // $this->pdf->Text(169,14,"Fecha:");
        // $this->pdf->SetFont("Arial", "B", 12);
        // $this->pdf->SetTextColor(255, 4, 0);
        // $this->pdf->Text(194,8,"PL-".$productLineID);
        // $this->pdf->Text(184,8,$line);
        $this->pdf->SetTextColor(0, 0, 0);
        // $this->pdf->Text(184,14,$production_date);
        $this->pdf->SetFont("Arial", "", 9);
    }

    private function cutFileLandscapeHeader($section,$x,$productLineID,$line,$production_date) {

        $this->pdf->AddPage();
        $this->pdf->SetXY(1, 1);
        $this->pdf->Image("img/lansonshades.jpeg", 4, 2, 0, 10);

        $this->pdf->SetFont("Arial", "", 15);
        $this->pdf->SetXY(17, 5.5);
        $this->pdf->Cell($x, 4,$section, 0, 0, "L");

        $this->pdf->SetFont("Arial", "", 12);
        $this->pdf->Text(250,8,"Linea:");
        $this->pdf->Text(249,14,"Fecha:");
        $this->pdf->SetFont("Arial", "B", 12);
        $this->pdf->SetTextColor(255, 4, 0);
        $this->pdf->Text(280,8,"PL-".$productLineID);
        $this->pdf->Text(264,8,$line);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Text(264,14,$production_date);
        $this->pdf->SetFont("Arial", "", 9);

        $this->pdf->setFillColor(211,211,211);
        $this->pdf->SetXY(2, 20);
        $this->pdf->Cell(23, 8, 'Pedido', 1, 1, "C", true);
        $this->pdf->SetXY(24, 20);
        $this->pdf->Cell(14, 8, 'Item ID', 1, 1, "C", true);
        $this->pdf->SetXY(37, 20);
        $this->pdf->Cell(63, 8, utf8_decode('Artículo') , 1, 1, "C", true);
        $this->pdf->SetXY(100, 20);
        $this->pdf->Cell(15, 8, 'Ancho' , 1, 1, "C", true);
        $this->pdf->SetXY(115, 20);
        $this->pdf->Cell(15, 8, 'Alto' , 1, 1, "C", true);
        $this->pdf->SetXY(130, 20);
        $this->pdf->Cell(30, 8, 'Producto' , 1, 1, "C", true);
        $this->pdf->SetXY(160, 20);
        $this->pdf->Cell(20, 8, utf8_decode('Operación') , 1, 1, "C", true);
        $this->pdf->SetXY(180, 20);
        $this->pdf->Cell(20, 8, 'Base' , 1, 1, "C", true);
        $this->pdf->SetXY(200, 20);
        $this->pdf->Cell(20, 8, 'Control' , 1, 1, "C", true);
        $this->pdf->SetXY(220, 20);
        $this->pdf->Cell(20, 8, utf8_decode('Caída') , 1, 1, "C", true);
        $this->pdf->SetXY(240, 20);
        $this->pdf->Cell(15, 8, utf8_decode('A. Cad.') , 1, 1, "C", true);
        $this->pdf->SetXY(255, 20);
        $this->pdf->Cell(25, 8, utf8_decode('Mecanismo') , 1, 1, "C", true);
        $this->pdf->SetXY(280, 20);
        $this->pdf->Cell(15, 8, utf8_decode('Ubica') , 1, 1, "C", true);
        $this->pdf->setFillColor(255, 255, 255);
    }

    private function cutShipmentHeader($section,$order) {

        $this->pdf->AddPage();

        $identify =  $order['nomen'].' '.$order['id'];
        if ( $order['nomen'] == 'GLS' OR $order['nomen'] == 'SLS' ) { $identify =  $order['nomen'].' '.$order['folio']; }

        $this->pdf->Image("img/lansonshades.jpeg", 4, 2, 0, 10);
        $this->pdf->SetDrawColor(237,237,237);
        $this->pdf->SetFont("Arial", "B", 10);
        $this->pdf->SetFillColor(245,245,245);

        $this->pdf->SetXY(2, 12);
        $this->pdf->Cell(74, 10, utf8_decode($section), 'LTR', 1, "C", true);

        $this->pdf->SetFont("Arial", "", 14);
        $this->pdf->SetTextColor(255, 4, 0);
        $this->pdf->SetXY(2, 22);
        $this->pdf->Cell(74, 10, $identify, 'LTR', 1, "C", false);

        $this->pdf->SetTextColor(0, 0, 0);

        $this->pdf->SetFont("Arial", "B", 8);
        $this->pdf->SetXY(76, 2);
        $this->pdf->Cell(74, 4, 'Cliente', 1, 1, "C", true);
        $this->pdf->SetXY(76, 12);
        $this->pdf->Cell(74, 4, 'Creado Por', 1, 1, "C", true);
        $this->pdf->SetXY(76, 22);
        $this->pdf->Cell(74, 4, 'Proyecto', 1, 1, "C", true);
        // NEXT TO 1
        $this->pdf->SetXY(150, 2);
        $this->pdf->Cell(71, 4, utf8_decode('Dirección de entrega'), 1, 1, "C", true);
        $this->pdf->SetXY(150, 22);
        $this->pdf->Cell(71, 4, utf8_decode('Entrega'), 1, 1, "C", true);

        //

        $this->pdf->SetFont("Arial", "", 8);

        $this->pdf->SetXY(76, 6);
        $this->pdf->Cell(74, 6, utf8_decode($order['client_id'].' - '.$order['client_name']), 1, 1, "C", false);
        $this->pdf->SetXY(76, 16);
        $this->pdf->Cell(74, 6, utf8_decode($order['agent_name']), 1, 1, "C", false);
        $this->pdf->SetXY(76, 26);
        $this->pdf->Cell(74, 6, utf8_decode($order['proyect_name']), 1, 1, "C", false);
        // NEXT TO 2
        $this->pdf->SetXY(150, 6);
        $this->pdf->Cell(71, 16, '', 1, 1, "C", false);
        $this->pdf->SetXY(150, 7);
        $this->pdf->MultiCell(71, 4, utf8_decode($order['address']), 0, "L", false);
        $this->pdf->SetXY(150,26);
        $this->pdf->Cell(71, 6, utf8_decode($order['delivery']), 1, 1, "C", false);

        $this->pdf->setFillColor(0,0,0);
        $this->pdf->Code128(230, 5, $identify, 60, 20);
        $this->pdf->setFillColor(254,254,254);
        $this->pdf->SetXY(230, 25);
        $this->pdf->Cell(60, 4, $identify, 0, 1, "C", true);


        $this->pdf->setFillColor(211,211,211);
        $this->pdf->SetXY(2, 32);
        $this->pdf->Cell(14, 8, 'Item ID', 1, 1, "C", true);
        $this->pdf->SetXY(16, 32);
        $this->pdf->Cell(63, 8, utf8_decode('Artículo') , 1, 1, "C", true);
        $this->pdf->SetXY(79, 32);
        $this->pdf->Cell(15, 8, 'Ancho' , 1, 1, "C", true);
        $this->pdf->SetXY(94, 32);
        $this->pdf->Cell(15, 8, 'Alto' , 1, 1, "C", true);
        $this->pdf->SetXY(109, 32);
        $this->pdf->Cell(30, 8, 'Producto' , 1, 1, "C", true);
        $this->pdf->SetXY(139, 32);
        $this->pdf->Cell(30, 8, utf8_decode('Operación') , 1, 1, "C", true);
        $this->pdf->SetXY(169, 32);
        $this->pdf->Cell(31, 8, 'Base' , 1, 1, "C", true);
        $this->pdf->SetXY(200, 32);
        $this->pdf->Cell(20, 8, 'Control' , 1, 1, "C", true);
        $this->pdf->SetXY(220, 32);
        $this->pdf->Cell(20, 8, utf8_decode('Caída') , 1, 1, "C", true);
        $this->pdf->SetXY(240, 32);
        $this->pdf->Cell(15, 8, utf8_decode('A. Cad.') , 1, 1, "C", true);
        $this->pdf->SetXY(255, 32);
        $this->pdf->Cell(25, 8, utf8_decode('Mecanismo') , 1, 1, "C", true);
        $this->pdf->SetXY(280, 32);
        $this->pdf->Cell(15, 8, utf8_decode('Ubica') , 1, 1, "C", true);
        $this->pdf->setFillColor(255, 255, 255);
    }

    private function cutShipmentHeaderTicket($section,$order) {

        $this->pdf->AddPage();

        $identify =  $order['nomen'].' '.$order['id'];
        if ( $order['nomen'] == 'GLS' OR $order['nomen'] == 'SLS' ) { $identify =  $order['nomen'].' '.$order['folio']; }

        $this->pdf->Image("img/lansonshades.jpeg", 6, 8, 0, 10);
        $this->pdf->SetDrawColor(237,237,237);
        $this->pdf->SetFont("Arial", "B", 12);
        $this->pdf->SetXY(4, 16);
        $this->pdf->Cell(74, 10, utf8_decode($section), 0, 0, "C", false);
        // CODE
        $this->pdf->setFillColor(0,0,0);
        $this->pdf->Code128(84, 8, $identify, 60, 16);
        $this->pdf->setFillColor(254,254,254);
        $this->pdf->SetXY(84, 24);
        $this->pdf->Cell(60, 4, $identify, 0, 1, "C", true);
        // ORDER
        $typeDoc = 'Pedido';
        if( $order['nomen'] == 'GLS' ) { $typeDoc = 'Pedido'; }
        $this->pdf->SetFont("Arial", "B", 10);
        $this->pdf->SetXY(6, 34);
        $this->pdf->Cell(36, 4, utf8_decode($typeDoc), 0, 1, "L", false);
        $this->pdf->SetFont("Arial", "", 12);
        $this->pdf->SetXY(6, 38);
        $this->pdf->Cell(36, 6, $identify, 0, 1, "L", false);
        // fecha de auotirxaxion
        $this->pdf->SetFont("Arial", "B", 10);
        $this->pdf->SetXY(42, 34);
        $this->pdf->Cell(57, 4, utf8_decode('Autorizado'), 0, 1, "L", false);
        $this->pdf->SetFont("Arial", "", 12);
        $this->pdf->SetXY(42, 38);
        $this->pdf->Cell(57, 6, $order['authorization_date'], 0, 1, "L", false);
        //fecha empaque
        $this->pdf->SetFont("Arial", "B", 10);
        $this->pdf->SetXY(99, 34);
        $this->pdf->Cell(45, 4, utf8_decode('Empacado'), 0, 1, "L", false);
        $this->pdf->SetFont("Arial", "", 12);
        $this->pdf->SetXY(99, 38);
        $this->pdf->Cell(45, 6, $order['packing_date'], 0, 1, "L", false);
        // CLIENT
        $this->pdf->SetFont("Arial", "B", 12);
        $this->pdf->SetXY(6, 48);
        $this->pdf->Cell(138, 6, utf8_decode('DATOS DEL CLIENTE'), 0, 1, "C", false);
        $this->pdf->SetFont("Arial", "B", 10);
        $this->pdf->SetXY(6, 55);
        $this->pdf->Cell(74, 4, utf8_decode('Cliente'), 0, 1, "L", false);
        $this->pdf->SetFont("Arial", "", 12);
        $this->pdf->SetXY(6, 59);
        $this->pdf->Cell(138, 6, utf8_decode($order['client_id'].' - '.$order['client_name']), 0, 1, "L", false);
        $this->pdf->SetFont("Arial", "B", 10);
        $this->pdf->SetXY(6, 69);
        $this->pdf->Cell(94, 4, utf8_decode('Método de pago'), 0, 1, "L", false);
        $this->pdf->SetFont("Arial", "", 12);
        $this->pdf->SetXY(6, 73);
        $this->pdf->Cell(94, 6, utf8_decode($order['payment_method']), 0, 1, "L", false);
        $this->pdf->SetFont("Arial", "B", 10);
        $this->pdf->SetXY(100, 69);
        $this->pdf->Cell(44, 4, utf8_decode('Forma de pago'), 0, 1, "L", false);
        $this->pdf->SetFont("Arial", "", 12);
        $this->pdf->SetXY(100, 73);
        $this->pdf->Cell(44, 6, utf8_decode($order['payment_option']), 0, 1, "L", false);
        // SHIPING
        $this->pdf->SetFont("Arial", "B", 10);
        $this->pdf->SetXY(6, 83);
        $this->pdf->Cell(138, 4, utf8_decode('Enviar a'), 0, 1, "L", false);
        $this->pdf->SetFont("Arial", "", 12);
        $this->pdf->SetXY(6, 87);
        $this->pdf->MultiCell(138, 6, utf8_decode($order['address']), 0, "L", false);
        // HEADERS
        $this->pdf->SetFont("Arial", "B", 10);
        $this->pdf->setFillColor(247,247,247);
        $this->pdf->SetXY(6, 104);
        $this->pdf->Cell(10, 8, 'ID', 1, 1, "C", true);
        $this->pdf->SetXY(16, 104);
        $this->pdf->Cell(81, 8, utf8_decode('Artículo') , 1, 1, "C", true);
        $this->pdf->SetXY(97, 104);
        $this->pdf->Cell(15, 8, 'Ancho' , 1, 1, "C", true);
        $this->pdf->SetXY(112, 104);
        $this->pdf->Cell(15, 8, 'Alto' , 1, 1, "C", true);
        $this->pdf->SetXY(127, 104);
        $this->pdf->Cell(16, 8, 'Ubica.' , 1, 1, "C", true);
        $this->pdf->setFillColor(255, 255, 255);

    }

    private function cutGraficFileLandscapeHeader($section,$x,$productLineID,$line,$production_date) {
        $this->pdf->AddPage();
        $this->pdf->SetXY(1, 1);
        $this->pdf->Image("img/lansonshades.jpeg", 4, 2, 0, 10);
        $this->pdf->SetFont("Arial", "", 15);
        $this->pdf->SetXY(17, 5.5);
        $this->pdf->Cell($x, 4,$section, 0, 0, "L");
        $this->pdf->SetFont("Arial", "", 12);
        $this->pdf->Text(250,8,"Linea:");
        $this->pdf->Text(249,14,"Fecha:");
        $this->pdf->SetFont("Arial", "B", 12);
        $this->pdf->SetTextColor(255, 4, 0);
        $this->pdf->Text(280,8,"PL-".$productLineID);
        $this->pdf->Text(264,8,$line);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Text(264,14,$production_date);
        $this->pdf->SetFont("Arial", "", 9);
    }



    private function orderLandscapeHeader($section,$x,$order,$line,$production_date) {

        $this->pdf->AddPage();
        $this->pdf->SetXY(1, 1);
        $this->pdf->Image("img/lansonshades.jpeg", 4, 2, 0, 10);

        $this->pdf->SetFont("Arial", "", 15);
        $this->pdf->SetXY(17, 5.5);
        $this->pdf->Cell($x, 4,$section, 0, 0, "L");

        $this->pdf->SetFont("Arial", "", 20);
        $this->pdf->SetTextColor(255, 4, 0);
        $this->pdf->Text(6,24,$order['id']);
        $this->pdf->SetTextColor(0, 0, 0);

        $this->pdf->SetFont("Arial", "B", 12);
        $this->pdf->Text(90,8,'Cliente: ');
        $this->pdf->Text(86,16,'Proyecto: ');
        $this->pdf->Text(92,24,utf8_decode('Destino: '));
        $this->pdf->Text(164,8,'Finaliza: ');
        $this->pdf->Text(164,16,utf8_decode('Entrega: '));

        $this->pdf->SetFont("Arial", "", 12);
        $this->pdf->Text(108,8,$order['client_id'].' - '.$order['client_short_name']);
        $this->pdf->Text(108,16,$order['proyect_name']);
        $this->pdf->Text(108,24,$order['address']);
        $this->pdf->Text(182,8,Carbon::createFromFormat('Y-m-d H:m:s', $order['deadline_date'])->isoFormat(' D \d\e MMMM \d\e\l Y'));
        $this->pdf->Text(183,16,$order['delivery']);


        $this->pdf->SetFont("Arial", "", 9);

        $this->pdf->setFillColor(211,211,211);
        $this->pdf->SetXY(2, 30);
        $this->pdf->Cell(23, 8, 'Pedido', 1, 1, "C", true);
        $this->pdf->SetXY(24, 30);
        $this->pdf->Cell(14, 8, 'Item ID', 1, 1, "C", true);
        $this->pdf->SetXY(37, 30);
        $this->pdf->Cell(63, 8, utf8_decode('Artículo') , 1, 1, "C", true);
        $this->pdf->SetXY(100, 30);
        $this->pdf->Cell(15, 8, 'Ancho' , 1, 1, "C", true);
        $this->pdf->SetXY(115, 30);
        $this->pdf->Cell(15, 8, 'Alto' , 1, 1, "C", true);
        $this->pdf->SetXY(130, 30);
        $this->pdf->Cell(30, 8, 'Producto' , 1, 1, "C", true);
        $this->pdf->SetXY(160, 30);
        $this->pdf->Cell(20, 8, utf8_decode('Operación') , 1, 1, "C", true);
        $this->pdf->SetXY(180, 30);
        $this->pdf->Cell(20, 8, 'Base' , 1, 1, "C", true);
        $this->pdf->SetXY(200, 30);
        $this->pdf->Cell(20, 8, 'Control' , 1, 1, "C", true);
        $this->pdf->SetXY(220, 30);
        $this->pdf->Cell(20, 8, utf8_decode('Caída') , 1, 1, "C", true);
        $this->pdf->SetXY(240, 30);
        $this->pdf->Cell(15, 8, utf8_decode('A. Cad.') , 1, 1, "C", true);
        $this->pdf->SetXY(255, 30);
        $this->pdf->Cell(20, 8, utf8_decode('Ubica') , 1, 1, "C", true);
        $this->pdf->SetXY(275, 30);
        $this->pdf->Cell(20, 8, utf8_decode('Check') , 1, 1, "C", true);
        $this->pdf->setFillColor(255, 255, 255);
    }

    private function cutMechanismHeader($section,$x,$productLineID,$line,$production_date) {

        $this->pdf->AddPage();
        $this->pdf->SetXY(1, 1);
        $this->pdf->Image("img/lansonshades.jpeg", 4, 2, 0, 10);

        $this->pdf->SetFont("Arial", "", 15);
        $this->pdf->SetXY(17, 5.5);
        $this->pdf->Cell($x, 4,$section, 0, 0, "L");

        $this->pdf->SetFont("Arial", "", 12);
        $this->pdf->Text(170,8,"Linea:");
        $this->pdf->Text(169,14,"Fecha:");
        $this->pdf->SetFont("Arial", "B", 12);
        $this->pdf->SetTextColor(255, 4, 0);
        $this->pdf->Text(195,8,"PL".$productLineID);
        $this->pdf->Text(184,8,$line);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Text(184,14,$production_date);
        $this->pdf->SetFont("Arial", "", 9);

        $this->pdf->setFillColor(211,211,211);
        $this->pdf->SetXY(6, 20);
        $this->pdf->Cell(23, 8, 'Pedido', 1, 1, "C", true);
        $this->pdf->SetXY(29, 20);
        $this->pdf->Cell(14, 8, 'Item ID', 1, 1, "C", true);
        $this->pdf->SetXY(43, 20);
        $this->pdf->Cell(20, 8, utf8_decode('Operación') , 1, 1, "C", true);
        $this->pdf->SetXY(63, 20);
        $this->pdf->Cell(25, 8, utf8_decode('Mecanismo') , 1, 1, "C", true);
        $this->pdf->SetXY(86, 20);
        $this->pdf->Cell(20, 8, utf8_decode('Caída') , 1, 1, "C", true);
        $this->pdf->SetXY(106, 20);
        $this->pdf->Cell(20, 8, 'Color' , 1, 1, "C", true);
        $this->pdf->SetXY(126, 20);
        $this->pdf->Cell(20, 8, 'Control' , 1, 1, "C", true);
        $this->pdf->SetXY(146, 20);
        $this->pdf->Cell(20, 8, 'Cadena' , 1, 1, "C", true);
        $this->pdf->SetXY(166, 20);
        $this->pdf->Cell(20, 8, 'A. Cad.' , 1, 1, "C", true);
        $this->pdf->SetXY(186, 20);
        $this->pdf->Cell(18, 8, utf8_decode('Ubica') , 1, 1, "C", true);
        $this->pdf->setFillColor(255, 255, 255);
    }

    private function quotationHeader($quotation) {

        $this->pdf->AddPage();
        $this->pdf->Image("img/lansonshades.jpeg", 4, 2, 0, 10);
        $this->pdf->SetDrawColor(237,237,237);
        $this->pdf->SetFont("Arial", "B", 10);
        $this->pdf->SetFillColor(245,245,245);

        $this->pdf->SetXY(2, 12);
        $this->pdf->Cell(37, 20, utf8_decode('Cotización'), 'LTR', 1, "C", true);

        $this->pdf->SetFont("Arial", "", 14);
        $this->pdf->SetTextColor(255, 4, 0);

        $this->pdf->SetXY(39, 12);
        $this->pdf->Cell(37, 20, $quotation['id'], 'LTR', 1, "C", false);

        $this->pdf->SetTextColor(0, 0, 0);

        $this->pdf->SetFont("Arial", "B", 8);
        $this->pdf->SetXY(76, 2);
        $this->pdf->Cell(74, 4, 'Cliente', 1, 1, "C", true);
        $this->pdf->SetXY(76, 12);
        $this->pdf->Cell(74, 4, 'Vendedor', 1, 1, "C", true);
        $this->pdf->SetXY(76, 22);
        $this->pdf->Cell(74, 4, 'Proyecto', 1, 1, "C", true);
        // NEXT TO 1
        $this->pdf->SetXY(150, 2);
        $this->pdf->Cell(74, 4, utf8_decode('Método de pago'), 1, 1, "C", true);
        $this->pdf->SetXY(150, 12);
        $this->pdf->Cell(74, 4, utf8_decode('Opción de pago'), 1, 1, "C", true);
        $this->pdf->SetXY(150, 22);
        $this->pdf->Cell(74, 4, utf8_decode('Entrga'), 1, 1, "C", true);
        // NEXT TO 2
        $this->pdf->SetXY(224, 2);
        $this->pdf->Cell(71, 4, utf8_decode('Dirección de entrega'), 1, 1, "C", true);
        $this->pdf->SetXY(224, 22);
        $this->pdf->Cell(71, 4, utf8_decode('Paquetería'), 1, 1, "C", true);

        $this->pdf->SetFont("Arial", "", 8);

        $this->pdf->SetXY(76, 6);
        $this->pdf->Cell(74, 6, utf8_decode($quotation['client_id'].' - '.$quotation['client_name']), 1, 1, "C", false);
        $this->pdf->SetXY(76, 16);
        $this->pdf->Cell(74, 6, utf8_decode($quotation['agent_name']), 1, 1, "C", false);
        $this->pdf->SetXY(76, 26);
        $this->pdf->Cell(74, 6, utf8_decode($quotation['proyect_name']), 1, 1, "C", false);
        // NEXT TO 1
        $this->pdf->SetXY(150, 6);
        $this->pdf->Cell(74, 6, utf8_decode($quotation['payment_method']), 1, 1, "C", false);
        $this->pdf->SetXY(150, 16);
        $this->pdf->Cell(74, 6, utf8_decode($quotation['payment_option'].$quotation['account_number']), 1, 1, "C", false);
        $this->pdf->SetXY(150, 26);
        $this->pdf->Cell(74, 6, utf8_decode($quotation['delivery']), 1, 1, "C", false);
        // NEXT TO 2
        $this->pdf->SetXY(224, 6);
        $this->pdf->Cell(71, 16, '', 1, 1, "C", false);
        $this->pdf->SetXY(225, 7);
        $this->pdf->MultiCell(71, 4, utf8_decode($quotation['address']), 0, "L", false);
        $this->pdf->SetXY(224,26);
        $this->pdf->Cell(71, 6, utf8_decode(''), 1, 1, "C", false);

        // TOTAL
        $total = app(GetTotal::class)->getTotalQuotation($quotation['details'],$quotation['client_discount']);

        $this->pdf->SetDrawColor(190,190,190);
        $this->pdf->Line(2,32,295,32);
        $this->pdf->SetDrawColor(237,237,237);
        // TOTALS

        $this->pdf->SetFont("Arial", "", 10);
        $this->pdf->SetXY(76, 32);
        $this->pdf->Cell(30, 10, 'Subtotal', 'LBR', 1, "C", false);
        $this->pdf->SetTextColor(92, 141, 36);

        $this->pdf->SetFont("Arial", "B", 10);
        $this->pdf->SetXY(106, 32);
        $this->pdf->Cell(45, 10, '$'.number_format($total['subtotal'],2), 'LBR', 1, "C", false);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont("Arial", "", 8);
        $this->pdf->SetXY(150, 32);
        $this->pdf->Cell(37, 5, 'Descuento', 'LBR', 1, "C", true);
        $this->pdf->SetXY(150, 37);
        $this->pdf->Cell(37, 5, 'IVA', 1,  1, "C", true);
        $this->pdf->SetFont("Arial", "B", 10);
        $this->pdf->SetTextColor(92, 141, 36);
        $this->pdf->SetXY(187, 32);
        $this->pdf->Cell(37, 5, '$'.number_format($total['discount'],2), 'LBR', 1, "C", false);
        $this->pdf->SetXY(187, 37);
        $this->pdf->Cell(37, 5, '$'.number_format($total['iva'],2), 1, 1, "C", false);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont("Arial", "", 12);
        $this->pdf->SetXY(224, 32);
        $this->pdf->Cell(30, 10, 'Total', 'LBR', 1, "C", false);
        $this->pdf->SetTextColor(92, 141, 36);
        $this->pdf->SetFont("Arial", "B", 14);
        $this->pdf->SetXY(254, 32);
        $this->pdf->Cell(41, 10, '$'.number_format($total['total'],2), 'LBR', 1, "C", false);
        $this->pdf->SetTextColor(0,0,0);
        $this->pdf->SetDrawColor(0,0,0);
        // // WARNING
        $this->pdf->SetFont("Arial", "", 8);
        $this->pdf->Text(4,206,utf8_decode('Precios sujetos a cambio sin previo aviso, los descuentos varían dependiendo los eventos, contacta a tu vendedor para cualquier duda o aclaración.'));
        // HEADERS
        $this->pdf->SetFont("Arial", "", 8);
        $this->pdf->SetXY(2, 42);
        $this->pdf->Cell(10, 8, 'Item', 1, 1, "C", true);
        $this->pdf->SetXY(12, 42);
        $this->pdf->Cell(63, 8, utf8_decode('Artículo') , 1, 1, "C", true);
        $this->pdf->SetXY(75, 42);
        $this->pdf->Cell(14, 8, 'Ancho' , 1, 1, "C", true);
        $this->pdf->SetXY(89, 42);
        $this->pdf->Cell(14, 8, 'Alto' , 1, 1, "C", true);
        $this->pdf->SetXY(103, 42);
        $this->pdf->Cell(30, 8, 'Producto' , 1, 1, "C", true);
        $this->pdf->SetXY(133, 42);
        $this->pdf->Cell(20, 8, utf8_decode('Operación') , 1, 1, "C", true);
        $this->pdf->SetXY(153, 42);
        $this->pdf->Cell(20, 8, 'Base' , 1, 1, "C", true);
        $this->pdf->SetXY(173, 42);
        $this->pdf->Cell(20, 8, 'Control' , 1, 1, "C", true);
        $this->pdf->SetXY(193, 42);
        $this->pdf->Cell(20, 8, utf8_decode('Caída') , 1, 1, "C", true);
        $this->pdf->SetXY(213, 42);
        $this->pdf->Cell(15, 8, 'A. Cad.' , 1, 1, "C", true);
        $this->pdf->SetXY(228, 42);
        $this->pdf->Cell(10, 8, utf8_decode('Cant.') , 1, 1, "C", true);
        $this->pdf->SetXY(238, 42);
        $this->pdf->Cell(19, 8, utf8_decode('Precio') , 1, 1, "C", true);
        $this->pdf->SetXY(257, 42);
        $this->pdf->Cell(19, 8, utf8_decode('Descuento') , 1, 1, "C", true);
        $this->pdf->SetXY(275, 42);
        $this->pdf->Cell(20, 8, utf8_decode('Subotal') , 1, 1, "C", true);
        $this->pdf->setFillColor(255, 255, 255);
    }

    private function orderHeader($order) {

        $this->pdf->AddPage();
        $this->pdf->Image("img/lansonshades.jpeg", 4, 2, 0, 10);
        $this->pdf->SetDrawColor(237,237,237);
        $this->pdf->SetFont("Arial", "B", 10);
        $this->pdf->SetFillColor(245,245,245);

        $this->pdf->SetXY(2, 12);
        $this->pdf->Cell(37, 20, utf8_decode('Pedido'), 'LTR', 1, "C", true);

        $this->pdf->SetFont("Arial", "", 14);
        $this->pdf->SetTextColor(255, 4, 0);

        $this->pdf->SetXY(39, 12);
        $this->pdf->Cell(37, 20, $order['id'], 'LTR', 1, "C", false);

        $this->pdf->SetTextColor(0, 0, 0);

        $this->pdf->SetFont("Arial", "B", 8);
        $this->pdf->SetXY(76, 2);
        $this->pdf->Cell(74, 4, 'Cliente', 1, 1, "C", true);
        $this->pdf->SetXY(76, 12);
        $this->pdf->Cell(74, 4, 'Vendedor', 1, 1, "C", true);
        $this->pdf->SetXY(76, 22);
        $this->pdf->Cell(74, 4, 'Proyecto', 1, 1, "C", true);
        // NEXT TO 1
        $this->pdf->SetXY(150, 2);
        $this->pdf->Cell(74, 4, utf8_decode('Método de pago'), 1, 1, "C", true);
        $this->pdf->SetXY(150, 12);
        $this->pdf->Cell(74, 4, utf8_decode('Opción de pago'), 1, 1, "C", true);
        $this->pdf->SetXY(150, 22);
        $this->pdf->Cell(74, 4, utf8_decode('Entrga'), 1, 1, "C", true);
        // NEXT TO 2
        $this->pdf->SetXY(224, 2);
        $this->pdf->Cell(71, 4, utf8_decode('Dirección de entrega'), 1, 1, "C", true);
        $this->pdf->SetXY(224, 22);
        $this->pdf->Cell(71, 4, utf8_decode('Paquetería'), 1, 1, "C", true);

        $this->pdf->SetFont("Arial", "", 8);

        $this->pdf->SetXY(76, 6);
        $this->pdf->Cell(74, 6, utf8_decode($order['client_id'].' - '.$order['client_name']), 1, 1, "C", false);
        $this->pdf->SetXY(76, 16);
        $this->pdf->Cell(74, 6, utf8_decode($order['agent_name']), 1, 1, "C", false);
        $this->pdf->SetXY(76, 26);
        $this->pdf->Cell(74, 6, utf8_decode($order['proyect_name']), 1, 1, "C", false);
        // NEXT TO 1
        $this->pdf->SetXY(150, 6);
        $this->pdf->Cell(74, 6, utf8_decode($order['payment_method']), 1, 1, "C", false);
        $this->pdf->SetXY(150, 16);
        $this->pdf->Cell(74, 6, utf8_decode($order['payment_option'].$order['account_number']), 1, 1, "C", false);
        $this->pdf->SetXY(150, 26);
        $this->pdf->Cell(74, 6, utf8_decode($order['delivery']), 1, 1, "C", false);
        // NEXT TO 2
        $this->pdf->SetXY(224, 6);
        $this->pdf->Cell(71, 16, '', 1, 1, "C", false);
        $this->pdf->SetXY(225, 7);
        $this->pdf->MultiCell(71, 4, utf8_decode($order['address']), 0, "L", false);
        $this->pdf->SetXY(224,26);
        $this->pdf->Cell(71, 6, utf8_decode(''), 1, 1, "C", false);

        // TOTAL
        $total = app(GetTotal::class)->getTotalOrder($order['details']);

        $this->pdf->SetDrawColor(190,190,190);
        $this->pdf->Line(2,32,295,32);
        $this->pdf->SetDrawColor(237,237,237);
        // TOTALS

        $this->pdf->SetFont("Arial", "", 10);
        $this->pdf->SetXY(76, 32);
        $this->pdf->Cell(30, 10, 'Subtotal', 'LBR', 1, "C", false);
        $this->pdf->SetTextColor(92, 141, 36);

        $this->pdf->SetFont("Arial", "B", 10);
        $this->pdf->SetXY(106, 32);
        $this->pdf->Cell(45, 10, '$'.number_format($total['subtotal'],2), 'LBR', 1, "C", false);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont("Arial", "", 8);
        $this->pdf->SetXY(150, 32);
        $this->pdf->Cell(37, 5, 'Descuento', 'LBR', 1, "C", true);
        $this->pdf->SetXY(150, 37);
        $this->pdf->Cell(37, 5, 'IVA', 1,  1, "C", true);
        $this->pdf->SetFont("Arial", "B", 10);
        $this->pdf->SetTextColor(92, 141, 36);
        $this->pdf->SetXY(187, 32);
        $this->pdf->Cell(37, 5, '$'.number_format($total['discount'],2), 'LBR', 1, "C", false);
        $this->pdf->SetXY(187, 37);
        $this->pdf->Cell(37, 5, '$'.number_format($total['iva'],2), 1, 1, "C", false);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont("Arial", "", 12);
        $this->pdf->SetXY(224, 32);
        $this->pdf->Cell(30, 10, 'Total', 'LBR', 1, "C", false);
        $this->pdf->SetTextColor(92, 141, 36);
        $this->pdf->SetFont("Arial", "B", 14);
        $this->pdf->SetXY(254, 32);
        $this->pdf->Cell(41, 10, '$'.number_format($total['total'],2), 'LBR', 1, "C", false);
        $this->pdf->SetTextColor(0,0,0);
        $this->pdf->SetDrawColor(0,0,0);
        // // WARNING
        $this->pdf->SetFont("Arial", "", 8);
        $this->pdf->Text(4,206,utf8_decode('Precios sujetos a cambio sin previo aviso, los descuentos varían dependiendo los eventos, contacta a tu vendedor para cualquier duda o aclaración.'));
        // HEADERS
        $this->pdf->SetFont("Arial", "", 8);
        $this->pdf->SetXY(2, 42);
        $this->pdf->Cell(10, 8, 'Item', 1, 1, "C", true);
        $this->pdf->SetXY(12, 42);
        $this->pdf->Cell(63, 8, utf8_decode('Artículo') , 1, 1, "C", true);
        $this->pdf->SetXY(75, 42);
        $this->pdf->Cell(14, 8, 'Ancho' , 1, 1, "C", true);
        $this->pdf->SetXY(89, 42);
        $this->pdf->Cell(14, 8, 'Alto' , 1, 1, "C", true);
        $this->pdf->SetXY(103, 42);
        $this->pdf->Cell(30, 8, 'Producto' , 1, 1, "C", true);
        $this->pdf->SetXY(133, 42);
        $this->pdf->Cell(20, 8, utf8_decode('Operación') , 1, 1, "C", true);
        $this->pdf->SetXY(153, 42);
        $this->pdf->Cell(20, 8, 'Base' , 1, 1, "C", true);
        $this->pdf->SetXY(173, 42);
        $this->pdf->Cell(20, 8, 'Control' , 1, 1, "C", true);
        $this->pdf->SetXY(193, 42);
        $this->pdf->Cell(20, 8, utf8_decode('Caída') , 1, 1, "C", true);
        $this->pdf->SetXY(213, 42);
        $this->pdf->Cell(15, 8, 'A. Cad.' , 1, 1, "C", true);
        $this->pdf->SetXY(228, 42);
        $this->pdf->Cell(10, 8, utf8_decode('Cant.') , 1, 1, "C", true);
        $this->pdf->SetXY(238, 42);
        $this->pdf->Cell(19, 8, utf8_decode('Precio') , 1, 1, "C", true);
        $this->pdf->SetXY(257, 42);
        $this->pdf->Cell(19, 8, utf8_decode('Descuento') , 1, 1, "C", true);
        $this->pdf->SetXY(275, 42);
        $this->pdf->Cell(20, 8, utf8_decode('Subotal') , 1, 1, "C", true);
        $this->pdf->setFillColor(255, 255, 255);
    }

    private function guaranteeHeader($guarantee) {

        $this->pdf->AddPage();
        $this->pdf->Image("img/lansonshades.jpeg", 4, 2, 0, 10);
        $this->pdf->SetDrawColor(237,237,237);
        $this->pdf->SetFont("Arial", "B", 10);
        $this->pdf->SetFillColor(245,245,245);

        $this->pdf->SetXY(2, 12);
        $this->pdf->Cell(37, 20, utf8_decode('Garantía'), 'LTR', 1, "C", true);

        $this->pdf->SetFont("Arial", "", 14);
        $this->pdf->SetTextColor(255, 4, 0);

        $this->pdf->SetXY(39, 12);
        $this->pdf->Cell(37, 20, $guarantee['id'], 'LTR', 1, "C", false);

        $this->pdf->SetTextColor(0, 0, 0);

        $this->pdf->SetFont("Arial", "B", 8);
        $this->pdf->SetXY(76, 2);
        $this->pdf->Cell(74, 4, 'Cliente', 1, 1, "C", true);
        $this->pdf->SetXY(76, 12);
        $this->pdf->Cell(74, 4, 'Vendedor', 1, 1, "C", true);
        $this->pdf->SetXY(76, 22);
        $this->pdf->Cell(74, 4, 'Proyecto', 1, 1, "C", true);
        // NEXT TO 1
        $this->pdf->SetXY(150, 2);
        $this->pdf->Cell(74, 4, utf8_decode('Método de pago'), 1, 1, "C", true);
        $this->pdf->SetXY(150, 12);
        $this->pdf->Cell(74, 4, utf8_decode('Opción de pago'), 1, 1, "C", true);
        $this->pdf->SetXY(150, 22);
        $this->pdf->Cell(74, 4, utf8_decode('Entrga'), 1, 1, "C", true);
        // NEXT TO 2
        $this->pdf->SetXY(224, 2);
        $this->pdf->Cell(71, 4, utf8_decode('Dirección de entrega'), 1, 1, "C", true);
        $this->pdf->SetXY(224, 22);
        $this->pdf->Cell(71, 4, utf8_decode('Paquetería'), 1, 1, "C", true);

        $this->pdf->SetFont("Arial", "", 8);

        $this->pdf->SetXY(76, 6);
        $this->pdf->Cell(74, 6, utf8_decode($guarantee['client_id'].' - '.$guarantee['client_name']), 1, 1, "C", false);
        $this->pdf->SetXY(76, 16);
        $this->pdf->Cell(74, 6, utf8_decode($guarantee['agent_name']), 1, 1, "C", false);
        $this->pdf->SetXY(76, 26);
        $this->pdf->Cell(74, 6, utf8_decode($guarantee['proyect_name']), 1, 1, "C", false);
        // NEXT TO 1
        $this->pdf->SetXY(150, 6);
        $this->pdf->Cell(74, 6, utf8_decode($guarantee['payment_method']), 1, 1, "C", false);
        $this->pdf->SetXY(150, 16);
        $this->pdf->Cell(74, 6, utf8_decode($guarantee['payment_option'].$guarantee['account_number']), 1, 1, "C", false);
        $this->pdf->SetXY(150, 26);
        $this->pdf->Cell(74, 6, utf8_decode($guarantee['delivery']), 1, 1, "C", false);
        // NEXT TO 2
        $this->pdf->SetXY(224, 6);
        $this->pdf->Cell(71, 16, '', 1, 1, "C", false);
        $this->pdf->SetXY(225, 7);
        $this->pdf->MultiCell(71, 4, utf8_decode($guarantee['address']), 0, "L", false);
        $this->pdf->SetXY(224,26);
        $this->pdf->Cell(71, 6, utf8_decode(''), 1, 1, "C", false);


        $this->pdf->SetDrawColor(190,190,190);
        $this->pdf->Line(2,32,295,32);
        $this->pdf->SetDrawColor(237,237,237);
        // REQU
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont("Arial", "B", 8);
        $this->pdf->SetXY(2, 32);
        $this->pdf->Cell(37, 5, 'Tipo de Garantia', 1, 1, "C", False);
        $this->pdf->SetXY(76, 32);
        $this->pdf->Cell(37, 5, utf8_decode('Indicación'), 1, 1, "C", False);
        $this->pdf->SetXY(150, 32);
        $this->pdf->Cell(37, 5, utf8_decode('Requiere colocador'), 1, 1, "C", False);
        $this->pdf->SetXY(2, 37);
        $this->pdf->Cell(37, 5, 'Descripcion', 1, 1, "C", False);
        //
        $this->pdf->SetFont("Arial", "", 8);
        $this->pdf->SetXY(39, 32);
        $this->pdf->Cell(37, 5, utf8_decode($guarantee['type_warranty']), 1, 1, "C", False);
        $this->pdf->SetXY(113, 32);
        $this->pdf->Cell(37, 5, utf8_decode($guarantee['type_capture']), 1, 1, "C", False);
        $this->pdf->SetXY(187, 32);
        $this->pdf->Cell(37, 5, utf8_decode((INT)$guarantee['if_installer_required'] === 1 ? "Si" : "No"), 1, 1, "C", False);
        $this->pdf->SetXY(39, 37);
        $this->pdf->Cell(256, 5, utf8_decode($guarantee['description']), 1, 1, "L", False);



        // // WARNING
        $this->pdf->SetFont("Arial", "", 8);
        $this->pdf->Text(4,206,utf8_decode('Precios sujetos a cambio sin previo aviso, los descuentos varían dependiendo los eventos, contacta a tu vendedor para cualquier duda o aclaración.'));
        // HEADERS
        $this->pdf->SetFont("Arial", "", 8);
        $this->pdf->SetXY(2, 42);
        $this->pdf->Cell(10, 8, 'Item', 1, 1, "C", true);
        $this->pdf->SetXY(12, 42);
        $this->pdf->Cell(63, 8, utf8_decode('Artículo') , 1, 1, "C", true);
        $this->pdf->SetXY(75, 42);
        $this->pdf->Cell(14, 8, 'Ancho' , 1, 1, "C", true);
        $this->pdf->SetXY(89, 42);
        $this->pdf->Cell(14, 8, 'Alto' , 1, 1, "C", true);
        $this->pdf->SetXY(103, 42);
        $this->pdf->Cell(30, 8, 'Producto' , 1, 1, "C", true);
        $this->pdf->SetXY(133, 42);
        $this->pdf->Cell(20, 8, utf8_decode('Operación') , 1, 1, "C", true);
        $this->pdf->SetXY(153, 42);
        $this->pdf->Cell(20, 8, 'Base' , 1, 1, "C", true);
        $this->pdf->SetXY(173, 42);
        $this->pdf->Cell(20, 8, 'Control' , 1, 1, "C", true);
        $this->pdf->SetXY(193, 42);
        $this->pdf->Cell(20, 8, utf8_decode('Caída') , 1, 1, "C", true);
        $this->pdf->SetXY(213, 42);
        $this->pdf->Cell(15, 8, 'A. Cad.' , 1, 1, "C", true);
        $this->pdf->SetXY(228, 42);
        $this->pdf->Cell(10, 8, utf8_decode('Cant.') , 1, 1, "C", true);
        $this->pdf->SetXY(238, 42);
        $this->pdf->Cell(19, 8, utf8_decode('Precio') , 1, 1, "C", true);
        $this->pdf->SetXY(257, 42);
        $this->pdf->Cell(19, 8, utf8_decode('Descuento') , 1, 1, "C", true);
        $this->pdf->SetXY(275, 42);
        $this->pdf->Cell(20, 8, utf8_decode('Subotal') , 1, 1, "C", true);
        $this->pdf->setFillColor(255, 255, 255);
    }

    private function getComment($item,$cuts) {
        $textDetail = '';
        switch ((INT)$item['product_id']) {
            case 1:
                if((INT)$item['is_inverted'] === 1) { $textDetail .= 'Invertida, '; }
                if((INT)$item['relation_cassette'] > 0) {
                    if((INT)$item['divisions'] > 1 ) {
                        if((INT)$item['relation_cassette'] > 0) { $textDetail .= $this->isCasseteDetail($item,$cuts); }
                    } else {
                        $textDetail .= 'Con cassette, ';
                    }
                }
                if((INT)$item['relation_lambrequin'] > 0) { $textDetail .= 'Con Lambrequin, '; }
                if((INT)$item['relation_bracket'] > 0) { $textDetail .= $this->isBracketRelation($item,$cuts); }
                if((INT)$item['mechanism_id'] > 0) { $textDetail .= $item['mechanism'].', '; }
                $textDetail .= $item['tube'].', ';
            break;
            case 2:
                if((INT)$item['divisions'] > 1 ) { $textDetail .= $this->isCasseteDetail($item,$cuts); }
                if((INT)$item['mechanism_id'] > 0) { $textDetail .= $item['mechanism'].', '; }
                $textDetail .= $item['tube'].', ';
            break;
            case 4:
                // Si es un cassete
                if((INT)$item['relation_cassette'] > 0) { $textDetail .= $this->isCasseteDetail($item,$cuts); }
                // Si es un lambrequin
                if((INT)$item['relation_lambrequin'] > 0) { $textDetail .= $this->isLambrequinDetail($item,$cuts); }
                // Si es un motor
                if((INT)$item['relation_motor'] > 0) { $textDetail .= $this->isMotorDetail($item,$cuts); }
                // accesorio relacionado
                if((INT)$item['relation_accesories'] > 0) { $textDetail .= $this->isAccesoriesRelation($item,$cuts); }
            break;
        }
        return substr($textDetail,0,-2);

    }

    private function isCasseteDetail($item,$cuts) {

        $textDetail = 'Misma fascia [ ';
        $numberItems = '';
        foreach ($cuts as $key => $cut) {
            if( (INT)$cut['relation_cassette'] === (INT)$item['relation_cassette'] AND  (INT)$cut['order_id'] === (INT)$item['order_id'] AND ( (INT)$cut['product_id'] === 1 OR (INT)$cut['product_id'] === 2 ) ) {
                $numberItems .= $cut['item_id'].',';
            }
        }
        $textDetail .= substr($numberItems,0,-1).' ], ' ;
        return $textDetail;
    }

    private function isBracketRelation($item,$cuts) {
        $textDetail = 'Soporte intermedio [ ';
        $numberItems = '';
        foreach ($cuts as $key => $cut) {
            if( (INT)$cut['relation_bracket'] === (INT)$item['relation_bracket'] AND  (INT)$cut['order_id'] === (INT)$item['order_id'] ) {
                $numberItems .= $cut['item_id'].',';
            }
        }
        $textDetail .= substr($numberItems,0,-1).' ], ' ;
        return $textDetail;
    }

    private function isLambrequinDetail($item,$cuts) {
        $textDetail = 'Lambrequin para partida [ ';
        $numberItems = '';
        foreach ($cuts as $key => $cut) {
            if((INT)$cut['relation_lambrequin'] === (INT) $item['relation_lambrequin'] ) {
                if((INT)$cut['product_id'] === 1 ) {
                    $numberItems .= $cut['item_id'].',';
                }
            }
        }
        $textDetail .= substr($numberItems,0,-1).' ], ' ;
        return $textDetail;
    }

    private function isMotorDetail($item,$cuts) {
        $textDetail = 'Motores para partidas [ ';
        $numberItems = '';
        foreach ($cuts as $key => $cut) {
            if((INT)$cut['relation_motor'] === (INT) $item['relation_motor'] ) {
                if((INT)$cut['product_id'] === 1 || (INT)$cut['product_id'] === 2) {
                    $numberItems .= $cut['item_id'].',';
                }
            }
        }
        $textDetail .= substr($numberItems,0,-1).' ], ' ;
        return $textDetail;

    }

    private function isAccesoriesRelation($item,$cuts) {
        $textDetail = 'Adecuación partidas [ ';
        $numberItems = '';
        foreach ($cuts as $key => $cut) {
            if((INT)$cut['relation_accesories'] === (INT) $item['relation_accesories'] ) {
                if((INT)$cut['product_id'] === 1 || (INT)$cut['product_id'] === 2) {
                    $numberItems .= $cut['item_id'].',';
                }
            }
        }
        $textDetail .= substr($numberItems,0,-1).' ], ' ;
        return $textDetail;

    }

    // QUOTATION

    private function getCommentQuotation($item,$cuts) {
        $textDetail = '';
        switch ((INT)$item['quotation_product_id']) {
            case 1:
                if((INT)$item['is_inverted'] === 1) { $textDetail .= 'Invertida, '; }
                if((INT)$item['relation_cassette'] > 0) {
                    if((INT)$item['divisions'] > 1 ) {
                        if((INT)$item['relation_cassette'] > 0) { $textDetail .= $this->isCasseteQuotationDetail($item,$cuts); }
                    } else {
                        $textDetail .= 'Con cassette, ';
                    }
                }
                if((INT)$item['relation_lambrequin'] > 0) { $textDetail .= 'Con Lambrequin, '; }
                if((INT)$item['relation_bracket'] > 0) { $textDetail .= $this->isBracketQuotationRelation($item,$cuts); }
                $textDetail .= $item['tube'].', ';
            break;
            case 2:
                if((INT)$item['divisions'] > 1 ) { $textDetail .= $this->isCasseteQuotationDetail($item,$cuts); }
                $textDetail .= $item['tube'].', ';
            break;
            case 4:
                // Si es un cassete
                if((INT)$item['relation_cassette'] > 0) { $textDetail .= $this->isCasseteQuotationDetail($item,$cuts); }
                // Si es un lambrequin
                if((INT)$item['relation_lambrequin'] > 0) { $textDetail .= $this->isLambrequinQuotationDetail($item,$cuts); }
                // Si es un motor
                // if((INT)$item['relation_motor'] > 0) { $textDetail .= $this->isMotorDetail($item,$cuts); }
            break;
        }
        return substr($textDetail,0,-2);

    }

    private function isCasseteQuotationDetail($item,$cuts) {

        if( (INT)$item['quotation_product_id'] === 1 OR (INT)$item['quotation_product_id'] === 2 ) { $textDetail = 'Misma fascia [ '; } else { $textDetail = 'Fascia para [ '; }
        $numberItems = '';
        foreach ($cuts as $key => $cut) {
            if( (INT)$cut['relation_cassette'] === (INT)$item['relation_cassette'] AND  (INT)$cut['quotation_id'] === (INT)$item['quotation_id'] AND ( (INT)$cut['quotation_product_id'] === 1 OR (INT)$cut['quotation_product_id'] === 2 ) ) {
                $numberItems .= $cut['item_id'].',';
            }
        }
        $textDetail .= substr($numberItems,0,-1).' ], ' ;
        return $textDetail;
    }

    private function isBracketQuotationRelation($item,$cuts) {
        $textDetail = 'Soporte intermedio [ ';
        $numberItems = '';
        foreach ($cuts as $key => $cut) {
            if( (INT)$cut['relation_bracket'] === (INT)$item['relation_bracket'] AND  (INT)$cut['quotation_id'] === (INT)$item['quotation_id'] ) {
                $numberItems .= $cut['item_id'].',';
            }
        }
        $textDetail .= substr($numberItems,0,-1).' ], ' ;
        return $textDetail;
    }

    private function isLambrequinQuotationDetail($item,$cuts) {
        $textDetail = 'Lambrequin para partida [ ';
        $numberItems = '';
        foreach ($cuts as $key => $cut) {
            if((INT)$cut['relation_lambrequin'] === (INT) $item['relation_lambrequin'] ) {
                if((INT)$cut['quotation_product_id'] === 1 ) {
                    $numberItems .= $cut['item_id'].',';
                }
            }
        }
        $textDetail .= substr($numberItems,0,-1).' ], ' ;
        return $textDetail;
    }

    private function createIDOrder($id) {
        $idOrder = $id;
        if($idOrder < 10) {
            $idOrder = '00000'.$idOrder;
        } else if($idOrder < 100 ) {
            $idOrder = '0000'.$idOrder;
        } else if($idOrder < 1000 ) {
            $idOrder = '000'.$idOrder;
        } else if($idOrder < 10000) {
            $idOrder = '00'.$idOrder;
        } else if($idOrder < 100000 ) {
            $idOrder = '0'.$idOrder;
        }
        return $idOrder;
    }
}