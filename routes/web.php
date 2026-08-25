<?php

use App\Http\Controllers\BI\Dashboard_1Controller;
use App\Http\Controllers\BI\Dashboard_5Controller;
use App\Http\Controllers\BI\DashboardInventoryController;
use App\Http\Controllers\BLogController;
use App\Http\Controllers\CArticleController;
use App\Http\Controllers\CatalogsController;
use App\Http\Controllers\CCategoryController;
use App\Http\Controllers\CCompanyController;
use App\Http\Controllers\CLocalInventoryController;
use App\Http\Controllers\CErpInfoUserController;
use App\Http\Controllers\CErpUserController;
use App\Http\Controllers\CInventoryProductController;
use App\Http\Controllers\CProviderController;
use App\Http\Controllers\CUnitController;
use App\Http\Controllers\CUserAddressController;
use App\Http\Controllers\CUserController;
use App\Http\Controllers\CWarehouseController;
use App\Http\Controllers\CWarehouseLevelController;
use App\Http\Controllers\Dashboard_2Controller;
use App\Http\Controllers\Dashboard_4Controller;
use App\Http\Controllers\Dashboard_6Controller;
use App\Http\Controllers\Dashboard_7Controller;
use App\Http\Controllers\Dashboard_9Controller;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DClientsNoteController;
use App\Http\Controllers\DComplementController;
use App\Http\Controllers\DInventoryController;
use App\Http\Controllers\DLeadsNoteController;
use App\Http\Controllers\DModulationController;
use App\Http\Controllers\DMovementController;
use App\Http\Controllers\DPurchaseController;
use App\Http\Controllers\DQuotationController;
use App\Http\Controllers\DScanController;
use App\Http\Controllers\DSectionController;
use App\Http\Controllers\DTempOrderController;
use App\Http\Controllers\DWarehouseLocationController;
use App\Http\Controllers\EDashboardController;
use App\Http\Controllers\EGuarantyController;
use App\Http\Controllers\ELeadController;
use App\Http\Controllers\ELeadScheduleController;
use App\Http\Controllers\EMaterialRequestController;
use App\Http\Controllers\ETempOrderController;
use App\Http\Controllers\EMovementController;
use App\Http\Controllers\ENotificationController;
use App\Http\Controllers\EOrderController;
use App\Http\Controllers\EPurchaseController;
use App\Http\Controllers\EQuotationController;
use App\Http\Controllers\EQuotationDiscountRequestController;
use App\Http\Controllers\EScanController;
use App\Http\Controllers\EScheduleController;
use App\Http\Controllers\ESectionController;
use App\Http\Controllers\ExportFileController;
use App\Http\Controllers\ImportFileController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ModulationFileController;
use App\Http\Controllers\SectionsController;
use App\Http\Controllers\updateTablePanelController;
use Illuminate\Support\Facades\Route;


Route::get('csrf', function () {
    return csrf_token();
});


Route::group(['middleware' => [], 'prefix' => 'auth'], function () {
    Route::post('/login', [CErpUserController::class, 'login']);
    Route::post('/verify', [CErpUserController::class, 'verify']);
    Route::post('/register', [CErpUserController::class, 'register']);
});


Route::group(['middleware' => ['jwt.auth'], 'prefix' => 'auth/tkn'], function () {
    Route::post('/logout', [CErpUserController::class, 'logout']);
    Route::post('/updateProfile', [CErpUserController::class, 'updateProfile']);
 });

Route::group(['middleware' => ['jwt.auth'], 'prefix' => 'verify'], function () {
    Route::post('/login', [CErpUserController::class, 'verifyToken']);
    Route::post('/allLogin', [CErpUserController::class, 'verifyTokenAll']);
    Route::post('/login-child', [CErpUserController::class, 'verifyChildToken']);
    Route::post('/moduleAccess', [CErpUserController::class, 'moduleAccess']);
 });

// SETTING - USERS
Route::group(['middleware' => ['jwt.auth'], 'prefix' => 'users'], function () {
    Route::post('/getUsers', [CErpUserController::class, 'index']);
    Route::get('/getUsersSelected/{id}', [CErpUserController::class, 'show']);
    Route::post('/updateCredentialsUser', [CErpUserController::class, 'update']);
    Route::post('/updateModules', [CErpUserController::class, 'updateModules']);
    Route::post('/updateUserGeneral', [CErpUserController::class, 'updateUserGeneral']);
    Route::post('/updatePassBD', [CErpUserController::class, 'updatePassBD']);
    Route::post('/saveNewUser', [CErpUserController::class, 'store']);
    Route::post('/inActiveUser', [CErpUserController::class, 'inActiveUser']);
    Route::post('/getUserInfo', [CErpInfoUserController::class, 'show']);
 });

// SETTING - CLIENTS
Route::group(['middleware' => ['jwt.auth'], 'prefix' => 'clients'], function () {
    Route::post('/getClients', [CUserController::class, 'index']);
    Route::post('/getDataClient', [CUserController::class, 'getDataClient']);
    Route::post('/updatePassclientBD', [CUserController::class, 'updatePassclientBD']);
    Route::get('/getClientsSelected/{id}', [CUserController::class, 'show']);
    Route::post('/updateCredentialsUser', [CUserController::class, 'update']);
    Route::post('/updateModules', [CUserController::class, 'updateModules']);
    Route::post('/updateClientGeneral', [CUserController::class, 'updateClientGeneral']);
    Route::post('/saveNewClient', [CUserController::class, 'store']);
    Route::post('/inActiveClient', [CUserController::class, 'inActiveClient']);
    Route::post('/getClientInfo', [CUserController::class, 'show']);
    Route::get('/getClientAddresses/{client_id}', [CUserAddressController::class, 'show']);
    Route::post('/addAddress', [CUserAddressController::class, 'store']);
    //LEADS
    Route::post('/getLeads', [ELeadController::class, 'index']);
    Route::post('/saveNewLead', [ELeadController::class, 'store']);
    Route::put('/changeStatusLead', [ELeadController::class, 'update']);
    Route::get('/viewLeadDetail/{lead_id}/{user_id}', [ELeadController::class, 'show']);
    Route::post('/saveLeadNote', [DClientsNoteController::class, 'store']);
    Route::post('/saveOptionLead', [ELeadController::class, 'saveOptionLead']);
    Route::delete('/inactiveNote/{id}', [DClientsNoteController::class, 'destroy']);
    Route::post('/saveLeadActivity', [EScheduleController::class, 'store']);
    Route::put('/cancelLeadActivity/{id}', [EScheduleController::class, 'cancel']);
    Route::put('/editLeadActivity/{id}/{is_lead}', [EScheduleController::class, 'update']);
    Route::put('/sendRequestLead/{id}', [ELeadController::class, 'edit']);
    // CLIENTS ERP
    Route::post('/saveClientNote', [DClientsNoteController::class, 'store']);
    Route::delete('/deleteNote/{id}', [DClientsNoteController::class, 'destroy']);
    Route::post('/getDataActivities', [EScheduleController::class, 'index']);
    Route::post('/saveOptionClient', [EScheduleController::class, 'saveOptionClient']);
    Route::get('/getInformationActivity/{client_id}', [EScheduleController::class, 'show']);
    Route::post('/saveClientActivity', [EScheduleController::class, 'store']);
    Route::put('/editClientActivity/{id}/{is_lead}', [EScheduleController::class, 'update']);
    Route::put('/cancelClientActivity/{id}', [EScheduleController::class, 'cancel']);
    Route::get('/getClientsERP/{user_id}', [CUserController::class, 'getClientsERP']);
    Route::get('/filterClientsERP/{filter}', [CUserController::class, 'filterClientsERP']);
    Route::post('/downloadClients', [ExportFileController::class, 'downloadClients']);
    Route::post('/saveComplements', [DComplementController::class, 'store']);
});

// ORDERS
Route::group(['middleware' => ['jwt.auth'], 'prefix' => 'orders'], function () {
    // ORDERS
    Route::post('/getOrders', [EOrderController::class, 'index']);
    Route::post('/getOrdersPag', [EOrderController::class, 'getOrdersPags']);
    Route::post('/downloadOrdersExcel', [EOrderController::class, 'downloadOrdersExcel']);
    Route::post('/getPreOrders', [EOrderController::class, 'getPreOrders']);
    Route::post('/getMaterialRequestOrders', [EOrderController::class, 'getMaterialRequestOrders']);
    Route::post('/getMaterialRequests', [EOrderController::class, 'getMaterialRequests']);
    Route::post('/getMaterialAssortment', [EOrderController::class, 'getMaterialAssortment']);
    Route::post('/getCheckMaterialAssortment', [EOrderController::class, 'getCheckMaterialAssortment']);
    Route::post('/getValidateMaterial', [EOrderController::class, 'getValidateMaterial']);
    Route::post('/getProductionOrders', [EOrderController::class, 'getProductionOrders']);
    Route::post('/getPlOrders', [EOrderController::class, 'getPlOrders']);
    Route::get('/getInfoOrder/{order_id}/{client_id}', [EOrderController::class, 'getInfoOrder']);
    Route::post('/setStopOrder', [EOrderController::class, 'setStopOrder']);
    Route::post('/generateKeyEditOrder', [EOrderController::class, 'generateKeyEditOrder']);
    Route::post('/validKeyEditOrder', [EOrderController::class, 'validKeyEditOrder']);
    Route::post('/getRelationOrders', [EOrderController::class, 'getRelationOrders']);
    Route::post('/saveRelationInvoiceOrder', [EOrderController::class, 'saveRelationInvoiceOrder']);
    Route::put('/updateInvoiceOrder/{order_id}', [EOrderController::class, 'updateInvoiceOrder']);
    // STATUS
    Route::post('/setStatus', [EOrderController::class, 'setStatus']);
    Route::post('/createMaterialRequest', [EOrderController::class, 'createMaterialRequest']);
    // REQUEST
    Route::get('/getRequestDetail/{request_id}', [EMaterialRequestController::class, 'show']);
    Route::get('/getRequestInventoryDetail/{request_id}/{company_id}', [EMaterialRequestController::class, 'getRequestInventoryDetail']);
    Route::get('/getModulationRequest/{request_id}', [EMaterialRequestController::class, 'getModulationRequest']);
    Route::get('/getSetRequest/{request_id}', [EMaterialRequestController::class, 'getSetRequest']);
    Route::post('/downloadRequestDetail', [EMaterialRequestController::class, 'downloadRequestDetail']);
    Route::post('/downloadRequestDetailAssortment', [EMaterialRequestController::class, 'downloadRequestDetailAssortment']);
    Route::put('/updateModulation', [DModulationController::class, 'update']);
    Route::post('/sendRequest', [EMaterialRequestController::class, 'sendRequest']);
    Route::post('/sendMaterialAssortment', [EMaterialRequestController::class, 'sendMaterialAssortment']);
    Route::post('/checkMaterialAssortment', [EMaterialRequestController::class, 'checkMaterialAssortment']);
    Route::post('/sendValidMaterial', [EMaterialRequestController::class, 'sendValidMaterial']);
    Route::post('/saveSetRequestChanges', [EMaterialRequestController::class, 'saveSetRequestChanges']);
    // Route::post('/saveAssignOrders', [EMaterialRequestController::class, 'saveAssignOrders']);
    Route::get('/downloadModulationFiles/{user_id}/{file}/{type}/{productLineID}', [ModulationFileController::class, 'downloadModulationFiles']);
    Route::get('/downloadRequestFile/{user_id}/{file}/{type}/{materialRequestID}', [ModulationFileController::class, 'downloadRequestFile']);
    // QR TEMP
    Route::post('/getTempOrders', [ETempOrderController::class, 'index']);
    Route::post('/saveNewTempOrder', [ETempOrderController::class, 'store']);
    Route::post('/saveNewItem', [DTempOrderController::class, 'store']);
    Route::post('/getLabelsAll', [ETempOrderController::class, 'getLabelsAll']);
    Route::post('/getIndividualLabels', [ETempOrderController::class, 'getIndividualLabels']);
    // SUCCESS
    Route::get('/getSuccessOrder/{order_id}/{client_id}', [EOrderController::class, 'getSuccessOrder']);
    // PACKAGING AND LOCATION
    Route::post('/savePackedLocationItem', [EOrderController::class, 'savePackedLocationItem']);
    Route::post('/saveLocation', [EOrderController::class, 'saveLocation']);
    Route::post('/getViewLocations', [EOrderController::class, 'getViewLocations']);
    Route::get('/searchLocationOrders/{nomen}/{order_id}', [EOrderController::class, 'searchLocationOrders']);
    //SHIPMENT
    Route::post('/getReceptionOrders', [EOrderController::class, 'getReceptionOrders']);
    Route::get('/downloadReceptionOrderDetail/{order_id}/{nomen}', [EOrderController::class, 'downloadReceptionOrderDetail']);
    Route::get('/downloadReceptionOrderDetailTicket/{order_id}/{nomen}', [EOrderController::class, 'downloadReceptionOrderDetailTicket']);
    Route::post('/getDeliveryOrders', [EOrderController::class, 'getDeliveryOrders']);
    Route::get('/downloadDeliveryOrderDetail/{order_id}/{nomen}', [EOrderController::class, 'downloadDeliveryOrderDetail']);
    Route::get('/downloadDeliveryOrderDetailTicket/{order_id}/{nomen}', [EOrderController::class, 'downloadDeliveryOrderDetailTicket']);
    Route::post('/getRouteOrders', [EOrderController::class, 'getRouteOrders']);
    Route::get('/downloadRouteOrderDetail/{order_id}/{nomen}', [EOrderController::class, 'downloadRouteOrderDetail']);
    Route::get('/downloadRouteOrderDetailTicket/{order_id}/{nomen}', [EOrderController::class, 'downloadRouteOrderDetailTicket']);
    Route::post('/onRouteOrder', [EOrderController::class, 'onRouteOrder']);
    Route::post('/saveFinalizeOrder', [EOrderController::class, 'saveFinalizeOrder']);
    // DOWNLOADS
    Route::get('/downloadOrderDetail/{order_id}', [EOrderController::class, 'downloadOrderDetail']);
    // COLLECT
    Route::put('/saveOrderCollect/{order_id}', [EOrderController::class, 'saveOrderCollect']);
});
// QUOTATIONS
Route::group(['middleware' => ['jwt.auth'], 'prefix' => 'quotations'], function () {
    Route::post('/getQuotations', [EQuotationController::class, 'index']);
    Route::post('/getQuotationsInitPag', [EQuotationController::class, 'getQuotationsInitPag']);
    Route::post('/getCatalogsQuotation', [CatalogsController::class, 'getCatalogsQuotation']);
    Route::post('/getCatalogsRecordsQuotation', [CatalogsController::class, 'getCatalogsRecordsQuotation']);
    Route::post('/createQuotation', [EQuotationController::class, 'store']);
    Route::post('/createQuotationLead', [EQuotationController::class, 'storeQLead']);
    Route::put('/updateQuotation', [EQuotationController::class, 'update']);
    Route::get('/getArticles/{client_id}', [CArticleController::class, 'show']);
    Route::post('/getArticles', [CArticleController::class, 'show']);

    //CANCEL
    Route::put('/setQuotationCancel', [EQuotationController::class, 'setQuotationCancel']);
    Route::put('/setQuotationReturn', [EQuotationController::class, 'setQuotationReturn']);
    // DETAILS
    Route::get('/getDetailQuotation/{quotation_id}', [DQuotationController::class, 'show']);
    Route::post('/createRecordQuotation', [DQuotationController::class, 'store']);
    Route::post('/saveCopyRegsElement', [DQuotationController::class, 'saveCopyRegsElement']);
    Route::delete('/deleteRegElement/{quotation_id}/{relation_id}/{user_id}/{client_id}', [DQuotationController::class, 'destroy']);
    Route::put('/saveTubeChange', [DQuotationController::class, 'saveTubeChange']);
    Route::put('/saveMechanismChange', [DQuotationController::class, 'saveMechanismChange']);

    // UPDATE TABLE
    Route::put('/updateArticle', [DQuotationController::class, 'updateArticle']);
    // START ORDER
    Route::post('/startOrder', [EQuotationController::class, 'startOrder']);
    // TABLE PANEL
    Route::put('/updateTablePanel', [updateTablePanelController::class, 'updateTablePanel']);
    Route::get('/downloadQuotationDetail/{quotation_id}', [EQuotationController::class, 'downloadQuotationDetail']);
    Route::post('/sendEmailQuotation', [EQuotationController::class, 'sendEmailQuotation']);
    Route::post('/sendRequestDiscount', [EQuotationDiscountRequestController::class, 'store']);
    Route::put('/acceptRequestDiscount', [EQuotationDiscountRequestController::class, 'update']);
    Route::put('/sendDenyRequestDiscount', [EQuotationDiscountRequestController::class, 'sendDenyRequestDiscount']);
    Route::put('/deleteRequestDiscount', [EQuotationDiscountRequestController::class, 'deleteRequestDiscount']);
    // LEAD
    Route::get('/viewQuotationLead/{lead_id}', [EQuotationController::class, 'viewQuotationLead']);


});

// COTIZACION ARTICULOS CRUD
Route::group(['middleware' => ['jwt.auth'], 'prefix' => 'cotizacion'], function () {
    Route::post('/getArticles', [CArticleController::class, 'index']);
    Route::post('/saveArticle', [CArticleController::class, 'store']);
    Route::post('/updateArticle', [CArticleController::class, 'update']);
    Route::post('/deleteArticle', [CArticleController::class, 'destroy']);
    Route::post('/getCatalogs', [CArticleController::class, 'getCatalogs']);
});

//Warehouse
Route::group(['middleware' => ['jwt.auth'], "prefix" => "warehouses"], function () {
    // LOCAL INVENTORY (fallback ERP)
    Route::post('/getLocalInventory', [CLocalInventoryController::class, 'index']);
    Route::post('/saveLocalInventory', [CLocalInventoryController::class, 'store']);
    Route::post('/deleteLocalInventory', [CLocalInventoryController::class, 'destroy']);
    Route::post('/importLocalInventory', [CLocalInventoryController::class, 'importCsv']);
    Route::post('/getProducts', [CInventoryProductController::class, 'index']);
    Route::post('/saveProducts', [EMovementController::class, 'storeEntry']);
    Route::post('/saveProductsLabels', [EMovementController::class, 'saveProductsLabels']);
    //INVENTORY
    Route::post('/getInventory', [DInventoryController::class, 'index']);
    Route::post('/getAllInventory', [DInventoryController::class, 'indexAll']);
    // MOVEMENTS
    Route::post('/getMovements', [EMovementController::class, 'index']);
    Route::get('/getMovements/{id}', [DMovementController::class, 'show']);
    Route::post('/getLabels', [EMovementController::class, 'getLabels']);
    Route::post('/getIndividualLabel', [DMovementController::class, 'getIndividualLabel']);
    Route::get('/getMovementPerProduct/{id}', [DMovementController::class, 'getMovementPerProduct']);
    Route::post('/saveTransfer', [EMovementController::class, 'storeTransfer']);
    Route::post('/saveOutputs', [EMovementController::class, 'storeOutputs']);
    // WAREHOUSE
    Route::post('/getWarehouses', [CWarehouseController::class, 'index']);
    Route::get('/getWarehousesCompany/{company_id}', [CWarehouseController::class, 'getWarehousesCompany']);
    Route::get('/getFindWarehouses/{warehouse_id}', [CWarehouseController::class, 'show']);
    Route::post('/saveWarehouse', [CWarehouseController::class, 'store']);
    Route::put('/editWarehouse', [CWarehouseController::class, 'update']);
    Route::delete('/deleteWarehouse/{id}/{user_id}', [CWarehouseController::class, 'edit']);
    Route::post('/getCompanies', [CCompanyController::class, 'index']);
    Route::get('/getInventoryProducts/{company_id}', [CInventoryProductController::class, 'show']);
    Route::post('/getCategories', [CCategoryController::class, 'index']);
    Route::post('/getProviders', [CProviderController::class, 'index']);
    Route::post('/getUnits', [CUnitController::class, 'index']);
    // PRODUCTS
    Route::post('/saveProduct', [CInventoryProductController::class, 'store']);
    Route::put('/editProduct', [CInventoryProductController::class, 'update']);
    Route::delete('/deleteProduct/{product_id}/{user_id}', [CInventoryProductController::class, 'edit']);
    Route::post('/importFileProducts', [CInventoryProductController::class, 'importFile']);
    // PROVIDERS
    Route::post('/saveProvider', [CProviderController::class, 'store']);
    Route::put('/updateProvider', [CProviderController::class, 'update']);
    Route::delete('/deleteProvider/{id}/{user_id}', [CProviderController::class, 'edit']);
    // LOCTIONS
    Route::get('/getLocations/{warehouse_id}', [DWarehouseLocationController::class, 'show']);
    Route::post('/saveLocation', [DWarehouseLocationController::class, 'store']);
    Route::get('/getLevels/{warehouse_id}', [CWarehouseLevelController::class, 'show']);
    // PURCHASES
    Route::post('/getPurchases', [EPurchaseController::class, 'index']);
    Route::get('/getDetailsPruchase/{id}', [DPurchaseController::class, 'show']);
    Route::get('/getValidLots/{purchase_id}', [DPurchaseController::class, 'getValidLots']);
    Route::post('/savePurchases', [EPurchaseController::class, 'store']);
    Route::post('/getPurchaseLabels', [EPurchaseController::class, 'getPurchaseLabels']);
    Route::post('/getPurchaseOrder', [EPurchaseController::class, 'getPurchaseOrder']);
    Route::post('/getPurchaseIndividualLabel', [DPurchaseController::class, 'getPurchaseIndividualLabel']);

});

// BUSINESS INTELIGENCE
Route::group(['middleware' => ['jwt.auth'], 'prefix' => 'bi'], function () {
    Route::post('/getDashboards', [EDashboardController::class, 'index']);
    Route::post('/saveNewDashboard', [EDashboardController::class, 'store']);
    Route::put('/updateDashboard', [EDashboardController::class, 'update']);
    // MY DASHBOARDS
    Route::get('/getMyDashboards/{user_id}', [EDashboardController::class, 'show']);
    // D1
    Route::post('/getDataDashboard1', [Dashboard_1Controller::class, 'getDataDashboard1']);
    Route::post('/getDetailTypeInvoicesD1', [Dashboard_1Controller::class, 'getDetailTypeInvoicesD1']);
    Route::post('/downloadExcelD1', [Dashboard_1Controller::class, 'downloadExcelD1']);
    // D2
    Route::post('/getDataDashboard2', [Dashboard_2Controller::class, 'getDataDashboard2']);
    Route::post('/getDetailTypeInvoicesD2', [Dashboard_2Controller::class, 'getDetailTypeInvoicesD2']);
    Route::post('/downloadExcelD2', [Dashboard_2Controller::class, 'downloadExcelD2']);
    // D4
    Route::post('/getDataDashboard4', [Dashboard_4Controller::class, 'getDataDashboard4']);
    Route::post('/getDetailTypeInvoicesD4', [Dashboard_4Controller::class, 'getDetailTypeInvoicesD4']);
    Route::post('/downloadExcelD4', [Dashboard_4Controller::class, 'downloadExcelD4']);
    // D5
    Route::post('/getDataDashboard5', [Dashboard_5Controller::class, 'getDataDashboard5']);
    Route::post('/downloadExcelDashboard5', [Dashboard_5Controller::class, 'downloadExcelDashboard5']);
    // D7
    Route::post('/getDataDashboard7', [Dashboard_7Controller::class, 'getDataDashboard7']);
    Route::post('/downladFileClientsD7', [Dashboard_7Controller::class, 'downladFileClientsD7']);
    // D6
    Route::post('/getDataDashboard6', [Dashboard_6Controller::class, 'getDataDashboard6']);
    Route::post('/getDataDashboard6LastYear', [Dashboard_6Controller::class, 'getDataDashboard6LastYear']);
    Route::post('/getDataDashboard6_4', [Dashboard_6Controller::class, 'getDataDashboard6_4']);
    Route::post('/getDataDashboard65', [Dashboard_6Controller::class, 'getDataDashboard65']);
    Route::post('/downloadExcelInvoice', [Dashboard_6Controller::class, 'downloadExcelInvoice']);
    // D9
    Route::post('/getDataDashboard9', [Dashboard_9Controller::class, 'getDataDashboard9']);


    // HOME
    Route::post('/getDataDasboardHome', [DashboardController::class, 'getDataDasboardHome']);
    Route::post('/getDataDasboardCV', [DashboardController::class, 'getDataDasboardCV']);
    Route::post('/downloadExcelPDP', [DashboardController::class, 'downloadExcelPDP']);
    // INVENTORY
    Route::post('/downloadInventoryLotsExcel', [DashboardInventoryController::class, 'downloadInventoryLotsExcel']);
});

// SCANS
Route::group(['middleware' => ['jwt.auth'], 'prefix' => 'scans'], function () {
    Route::post('/saveNewScan', [EScanController::class, 'store']);
    Route::post('/getScans', [EScanController::class, 'index']);
    Route::get('/getDetailsScan/{id}', [DScanController::class, 'show']);
    Route::post('/saveScan', [DScanController::class, 'store']);
});

// PROVIDERS
Route::group(['middleware' => ['jwt.auth'], 'prefix' => 'providers'], function () {
    //  TEMPORAL
    Route::post('/getLabelProviders', [CProviderController::class, 'getLabelProviders']);
});

// GUARANTEES
Route::group(['middleware' => ['jwt.auth'], 'prefix' => 'guarantees'], function () {
    Route::post('/getGuarantees', [EGuarantyController::class, 'index']);
    Route::post('/getCatalogsGuarantees', [CatalogsController::class, 'getCatalogsGuarantees']);
    Route::post('/getFindOrder', [EGuarantyController::class, 'getFindOrder']);
    Route::post('/getArticles', [EGuarantyController::class, 'getArticles']);
    Route::post('/getCatalogsRecordsQuotation', [CatalogsController::class, 'getCatalogsRecordsQuotation']);
    Route::post('/generateGuarantee', [EGuarantyController::class, 'store']);
    Route::get('/viewGuaranteeDetail/{waranty_id}', [EGuarantyController::class, 'show']);
    Route::put('/updateChargeGuarantee/{waranty_id}', [EGuarantyController::class, 'updateChargeGuarantee']);
    //
    Route::get('/downloadGuaranteeDetail/{guarantee_id}', [EGuarantyController::class, 'downloadGuaranteeDetail']);
    Route::post('/downloadGuaranteeExcel', [EGuarantyController::class, 'downloadGuaranteeExcel']);
});

// SECTIONS
Route::group([ 'prefix' => 'sections'], function () {
    Route::get('/getSectionsData/{company_id}', [SectionsController::class, 'getSectionsData']);
    Route::post('/saveProject', [ESectionController::class, 'store']);
    Route::post('/requestSection', [ESectionController::class, 'requestSection']);
    Route::get('/getDetailsSection/{id}/{company_id}', [SectionsController::class, 'getDetailsSection']);
    Route::get('/getRequestSectionsOrders/{company_id}', [SectionsController::class, 'getRequestSectionsOrders']);
    Route::get('/getSetRequestSections/{id}/{company_id}', [ESectionController::class, 'show']);
    Route::post('/saveSetSectionRequestChanges', [ESectionController::class, 'saveSetSectionRequestChanges']);
    Route::post('/sendSection', [ESectionController::class, 'sendSection']);
    Route::post('/sendSectionRequest', [ESectionController::class, 'sendSectionRequest']);
    Route::post('/downloadSectionRequestDetail', [ESectionController::class, 'downloadSectionRequestDetail']);
    Route::post('/getSectionsRequest', [ESectionController::class, 'getSectionsRequest']);
    Route::put('/updateTableItem/{id}/{status_id}', [DSectionController::class, 'update']);
});

// LOGS
Route::group(['middleware' => ['jwt.auth'], 'prefix' => 'logs'], function () {
    Route::get('/getLogs/{identifier_number}/{identifier_type}', [BLogController::class, 'show']);
});

// NOTIFICATIONS
Route::group(['middleware' => ['jwt.auth'], 'prefix' => 'notifications'], function () {
    Route::get('/getNotifications/{user_id}', [ENotificationController::class, 'show']);
    Route::put('/viewNotification', [ENotificationController::class, 'update']);
    Route::put('/viewAllNotification', [ENotificationController::class, 'viewAllNotification']);
 });

// INFO
Route::group([ 'prefix' => 'info'], function () {
    Route::post('/getTempItem', [DTempOrderController::class, 'getTempItem']);
});

// TEST
Route::group(['middleware' => [], 'prefix' => 'test'], function () {
    Route::post('/getDataDasboardHome', [DashboardController::class, 'getDataDasboardHome']);
    Route::post('/getDataDashboard4', [Dashboard_4Controller::class, 'getDataDashboard4']);
    Route::post('/downloadExcelD4', [Dashboard_4Controller::class, 'downloadExcelD4']);
    Route::post('/getDataDashboard7', [Dashboard_7Controller::class, 'getDataDashboard7']);
    Route::get('/getSectionsData/{company_id}', [SectionsController::class, 'getSectionsData']);
    Route::post('/downloadSectionRequestDetail', [ESectionController::class, 'downloadSectionRequestDetail']);
    Route::post('/getMaterialRequests', [EOrderController::class, 'getMaterialRequests']);
    Route::get('/getModulationRequest/{request_id}', [EMaterialRequestController::class, 'getModulationRequest']);
    Route::get('/downloadRequestFile/{user_id}/{file}/{type}/{materialRequestID}', [ModulationFileController::class, 'downloadRequestFile']);
    // QR TEMP
});
 // EXPORTS
Route::group(['middleware' => [], 'prefix' => 'export'], function () {
    Route::get('/downloadImportOrder/{token}', [ExportFileController::class, 'downloadImportOrder']);
});
// IMPORTS
Route::group(['middleware' => [], 'prefix' => 'import'], function () {
    Route::post('/importItemsOrder', [ImportFileController::class, 'importItemsOrder']);
});

// VISTA SIMPLE DE ARTICULOS CON PRECIOS L1 Y L2 — frontend legacy (Blade, habla directo
// a c_articles, sin pasar por /api/*). Mismo flag que el SPA legacy: config('app.legacy_ui_enabled')
// (env LEGACY_UI_ENABLED, default false). No se borró nada, solo se dejó de servir por default,
// para que el único frontend alcanzable sea el MVP (/mvp) y no haya mezcla de frontends.
if (config('app.legacy_ui_enabled')) {
Route::middleware(['web'])->group(function () {
    Route::get('/articles/prices', function () {
        $articles = DB::table('c_articles')
            ->leftJoin('c_models', 'c_models.id', '=', 'c_articles.model_id')
            ->leftJoin('c_units', 'c_units.id', '=', 'c_articles.unit_id')
            ->leftJoin('c_products', 'c_products.id', '=', 'c_models.product_id')
            ->select(
                'c_articles.id',
                'c_articles.article',
                'c_articles.sku',
                'c_articles.price',
                'c_articles.price_list_2',
                'c_models.model',
                'c_units.unit',
                'c_products.product as category'
            )
            ->where('c_articles.is_active', 1)
            ->orderBy('c_articles.article')
            ->get();
        return view('articles.prices', compact('articles'));
    });

    Route::post('/articles/prices/update', function (\Illuminate\Http\Request $request) {
        $request->validate([
            'id' => 'required|integer',
            'article' => 'required|string',
            'sku' => 'required|string',
            'price' => 'required|numeric',
            'price_list_2' => 'nullable|numeric',
        ]);
        DB::table('c_articles')->where('id', $request->id)->update([
            'article' => $request->article,
            'sku' => $request->sku,
            'price' => $request->price,
            'price_list_2' => $request->price_list_2,
            'updated_at' => now(),
        ]);
        return redirect('/articles/prices')->with('success', 'Artículo actualizado correctamente');
    });

    Route::post('/articles/prices/delete', function (\Illuminate\Http\Request $request) {
        $request->validate(['id' => 'required|integer']);
        DB::table('c_articles')->where('id', $request->id)->update([
            'is_active' => 0,
            'updated_at' => now(),
        ]);
        return redirect('/articles/prices')->with('success', 'Artículo eliminado correctamente');
    });

    Route::post('/articles/prices/create', function (\Illuminate\Http\Request $request) {
        $request->validate([
            'article' => 'required|string',
            'sku' => 'required|string|unique:c_articles,sku',
            'price' => 'required|numeric',
            'price_list_2' => 'nullable|numeric',
        ]);
        DB::table('c_articles')->insert([
            'article' => $request->article,
            'sku' => $request->sku,
            'price' => $request->price,
            'price_list_2' => $request->price_list_2,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return redirect('/articles/prices')->with('success', 'Artículo creado correctamente');
    });
});
}

// Frontend legacy (Vue) aislado detrás de config('app.legacy_ui_enabled') — no se borró
// nada, solo se dejó de servir por default. Ver docs/mvp.md. Para depurar el sistema
// viejo: LEGACY_UI_ENABLED=true en .env (necesita las tablas c_erp_* reconstruidas).
if (config('app.legacy_ui_enabled')) {
    Route::get('/{any}', function () {
        return view('app');
    })->where('any', '.*');
} else {
    Route::get('/{any}', function () {
        return redirect('/mvp/index.html');
    })->where('any', '.*');
}

