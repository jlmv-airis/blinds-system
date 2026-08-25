<?php
namespace App\classes;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7;

class WebService {

    protected $client;
    protected $lastHttpFailed = false;
    //
    public function __construct(Client $client)
    {
        $this->client = new $client(['base_uri' => env('MIX_API_ASPEL_SAE','http://aspelroller3.ddns.net:81'), 'timeout' => 5, 'connect_timeout' => 5]);
    }
    public function httpFailed()
    {
        return $this->lastHttpFailed;
    }
    // INVOICES
    public function getInvoiceReport($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/lb/getInvoicesDashboard/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getInvoicesDetails($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/lb/getInvoicesDetails/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getInvoiceReportLS($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/ls/getInvoicesDashboard/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getInvoiceReportRT($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/rt/getInvoicesDashboard/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getInvoiceClientReport($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/lb/getInvoicesClientDashboard/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getInvoiceClientReportLS($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/ls/getInvoicesClientDashboard/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getInvoiceClientDownload($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/lb/getInvoiceClientDownload/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getInvoiceClientDownloadLS($payload) // reporte de vesntas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/ls/getInvoiceClientDownload/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getInvoiceClientDownloadRT($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/rt/getInvoiceClientDownload/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getInvoiceClientReportRT($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/rt/getInvoicesClientDashboard/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getInvoiceProductsReport($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/lb/getInvoicesProductsDashboard/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getInvoiceProductsReportLS($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/ls/getInvoicesProductsDashboard/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getInvoiceProductsReportRT($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/rt/getInvoicesProductsDashboard/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getInvoiceSellersReport($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/lb/getInvoiceSellersDashboard/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getInvoiceSellersReportLS($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/ls/getInvoiceSellersDashboard/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getInvoiceSellersReportRT($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/rt/getInvoiceSellersDashboard/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    // CREDIT NOTES
    public function getCreditNotesClientReport($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/lb/getCreditNotesClientDashboard/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getCreditNotesReport($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/lb/getCreditNotesDashboard/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getCreditNotesReportLS($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/ls/getCreditNotesDashboard/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getCreditNotesReportRT($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/rt/getCreditNotesDashboard/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getCreditNotesProductsReport($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/lb/getCreditNotesProductsDashboard/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getCreditNotesSellersReport($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/lb/getCreditNotesSellersDashboard/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    // RETURNS
    public function getReturnsClientReport($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/lb/getReturnsClientDashboard/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getReturnsReport($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/lb/getReturnsDashboard/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getReturnsReportLS($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/ls/getReturnsDashboard/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getReturnsReportRT($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/rt/getReturnsDashboard/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getReturnsProductsReport($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/lb/getReturnsProductsDashboard/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getReturnsSellersReport($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/lb/getReturnsSellersDashboard/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    // DETAILS TYPE INVOICE
    public function getDetailsInvoiceReport($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/lb/getDetailsInvoiceReport/".$payload['folio']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getDetailsCreditNotesReport($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/lb/getDetailsCreditNotesReport/".$payload['folio']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getDetailsReturnsReport($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/lb/getDetailsReturnsReport/".$payload['folio']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    // DETAILS TYPE INVOICE D2
    public function getDetailsInvoiceReportRT($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/rt/getDetailsInvoiceReport/".$payload['folio']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getDetailsCreditNotesReportRT($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/rt/getDetailsCreditNotesReport/".$payload['folio']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getDetailsReturnsReportRT($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/rt/getDetailsReturnsReport/".$payload['folio']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    // DETAILS TYPE INVOICE
    public function getDetailsInvoiceReportLS($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/ls/getDetailsInvoiceReport/".$payload['folio']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getDetailsCreditNotesReportLS($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/ls/getDetailsCreditNotesReport/".$payload['folio']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getDetailsReturnsReportLS($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/ls/getDetailsReturnsReport/".$payload['folio']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }

    // D1
    public function getDownloadInvoicesDetails($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/lb/getDownloadInvoicesDetails/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getDownloadCreditNotesDetails($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/lb/getDownloadCreditNotesDetails/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getDownloadReturnsDetails($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/lb/getDownloadReturnsDetails/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    // D2
    public function getDownloadInvoicesDetailsRT($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/rt/getDownloadInvoicesDetails/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getDownloadCreditNotesDetailsRT($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/rt/getDownloadCreditNotesDetails/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getDownloadReturnsDetailsRT($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/rt/getDownloadReturnsDetails/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }

    // D1
    public function getDownloadInvoicesDetailsLS($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/ls/getDownloadInvoicesDetails/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getDownloadCreditNotesDetailsLS($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/ls/getDownloadCreditNotesDetails/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getDownloadReturnsDetailsLS($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/ls/getDownloadReturnsDetails/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    //
    public function getDataLBDetails($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/lb/getDataTotalDetails/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getDataRTDetails($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/rt/getDataTotalDetails/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getDataLSDetails($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/ls/getDataTotalDetails/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getDataLSInvoicesDate($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/ls/getInvoicesDate/".$payload['dateInit']."/".$payload['dateEnd']."?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getDataCVRT($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/rt/getDataCV?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getDataCVLB($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/lb/getDataCV?api_token=".$payload['api_token']."&id=".$payload['user_id'], "form_params");
    }
    public function getClienLS() // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/ls/getClients", "form_params");
    }
    public function getAgentsLS() // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/ls/getAgents", "form_params");
    }
    public function getClienLB() // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/lb/getClients", "form_params");
    }
    public function getAgentsLB() // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/lb/getAgents", "form_params");
    }
    public function getLSProducts() // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/ls/getProducts", "form_params");
    }
    public function getLBProducts() // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/lb/getProducts", "form_params");
    }
    public function getRTProducts() // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/rt/getProducts", "form_params");
    }
    public function getInventory() // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/lb/getInventory", "form_params");
    }
    public function getInventoryRT() // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/rt/getInventory", "form_params");
    }
    public function getMInventoryRT() // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/rt/getMInventory", "form_params");
    }
    public function getLotesRT() // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/rt/getLotes", "form_params");
    }
    public function getClienRT() // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/rt/getClients", "form_params");
    }
    // INVENTORY
    public function getInventoryINDF() // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/rt/getInventoryINDF", "form_params");
    }
    public function getInventoryWRKS() // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/rt/getInventoryWRKS", "form_params");
    }

    public function getInventoryItemRT($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/rt/getInventoryItemRT/".$payload['sku'], "form_params");
    }
    public function getInventoryItemINDF($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/rt/getInventoryItemINDF/".$payload['sku'], "form_params");
    }
    public function getInventoryItemWRKS($payload) // reporte de ventas mediantee la factura lanson beckman
    {
        return $this->dataHttpRequest("api/v1/rt/getInventoryItemWRKS/".$payload['sku'], "form_params");
    }

    // SENDD
    protected function dataHttpRequest($url, $option)
    {
        try {
            $jsonResponse = null;
            switch ($option) {
                case "form_params":
                    $response = $this->client->get($url);
                    $jsonResponse = json_decode($response->getBody()->getContents());
                    break;
                case "json":
                    $response = $this->client->get($url);
                    $jsonResponse = json_decode($response->getBody()->getContents());
                    break;
            }
            $this->lastHttpFailed = false;
            return $jsonResponse;
        } catch (RequestException $e) {
            $this->lastHttpFailed = true;
            return (object) ['items' => []];
        } catch (ConnectException $ex) {
            $this->lastHttpFailed = true;
            return (object) ['items' => []];
        }
    }
}