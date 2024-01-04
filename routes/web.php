<?php
use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

include_once('install_r.php');
Route::get('/print-test', function(){
    return view('print_test');
});

Route::middleware(['authh'])->group(function () {
    Route::get('/', function () {
        return view('welcome');
    });

    Auth::routes();

    Route::get('/business/register', 'BusinessController@getRegister')->name('business.getRegister');
    Route::post('/business/register', 'BusinessController@postRegister')->name('business.postRegister');
    Route::post('/business/register/check-username', 'BusinessController@postCheckUsername')->name('business.postCheckUsername');
    Route::post('/business/register/check-email', 'BusinessController@postCheckEmail')->name('business.postCheckEmail');

    Route::get('/invoice/{token}', 'SellPosController@showInvoice')
        ->name('show_invoice');
});

//Routes for authenticated users only
Route::middleware(['authh', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu', 'CheckUserLogin'])->group(function () {
    Route::get('/home', 'HomeController@index')->name('home');
    Route::get('/home/get-totals', 'HomeController@getTotals');
    Route::get('/home/product-stock-alert', 'HomeController@getProductStockAlert');
//    Route::get('/home/purchase-payment-dues', 'HomeController@getPurchasePaymentDues');
    Route::get('/home/sales-payment-dues', 'HomeController@getSalesPaymentDues');

    Route::post('/test-email', 'BusinessController@testEmailConfiguration');
    Route::post('/test-sms', 'BusinessController@testSmsConfiguration');
    Route::get('/business/settings', 'BusinessController@getBusinessSettings')->name('business.getBusinessSettings');
    Route::post('/business/update', 'BusinessController@postBusinessSettings')->name('business.postBusinessSettings');
    Route::get('/user/profile', 'UserController@getProfile')->name('user.getProfile');
    Route::post('/user/update', 'UserController@updateProfile')->name('user.updateProfile');
    Route::post('/user/update-password', 'UserController@updatePassword')->name('user.updatePassword');

    Route::resource('brands', 'BrandController');

    Route::resource('payment-account', 'PaymentAccountController');

    Route::resource('tax-rates', 'TaxRateController');

    Route::post('/units/get-units-by-type', 'UnitController@getUnitsByType');
    Route::post('/units/check-unit-existed', 'UnitController@checkUnitExisted');
    Route::resource('units', 'UnitController');

    Route::group(['prefix' => 'contacts'], function () {
        Route::post('/reduce-debts/confirm-bulk', 'ReduceDebtController@confirmBulk');
        Route::get('/reduce-debts/add/{id}', 'ReduceDebtController@add');
        Route::resource('reduce-debts', 'ReduceDebtController');
    });

    Route::get('/contacts/map', 'ContactController@contactMap');
    Route::post('/contacts/debt/{contact_id}', 'ContactController@getDebtCustomer');
//    Route::get('/contacts/debt-after-load/{contact_id}', 'ContactController@getDebtAfterLoad');
    Route::get('/contacts/{contact_id}/export', 'ContactController@exportExcel');
    Route::get('/contacts/suppiler/{contact_id}', 'ContactController@getPurchaseSupplier');
//    Route::get('/contacts/sell/{contact_id}', 'SellPosController@getSellCustomer');
    Route::get('/contacts/update-status/{id}', 'ContactController@updateStatus');
    Route::get('/contacts/stock-report/{supplier_id}', 'ContactController@getSupplierStockReport');
//    Route::get('/contacts/ledger', 'ContactController@getLedger');
    Route::post('/contacts/send-ledger', 'ContactController@sendLedger');
    Route::get('/contacts/import', 'ContactController@getImportContacts')->name('contacts.import');
    Route::post('/contacts/import', 'ContactController@postImportContacts');
    Route::post('/contacts/check-contact-id', 'ContactController@checkContactId');
    Route::post('/contacts/check-price-group', 'ContactController@checkSellPriceGroup');
    Route::post('/contacts/check-mobile', 'ContactController@checkMobile');
    Route::post('/contacts/check-tax-number', 'ContactController@checkTaxNumber');
    Route::get('/contacts/customers', 'ContactController@getCustomers');
    Route::resource('contacts', 'ContactController');

    Route::get('taxonomies-ajax-index-page', 'TaxonomyController@getTaxonomyIndexPage');
    Route::resource('taxonomies', 'TaxonomyController');

    Route::resource('variation-templates', 'VariationTemplateController');

    Route::get('/products/get-sub-units-by-unit', 'ProductController@getSubUnitsByUnit');
    Route::get('/products/get-product-by-cate', 'ProductController@getListProductByCategory');
    Route::post('/products/calculate-area', 'ProductController@calculateArea');
    Route::get('/delete-media/{media_id}', 'ProductController@deleteMedia');
    Route::post('/products/mass-deactivate', 'ProductController@massDeactivate');
    Route::get('/products/activate/{id}', 'ProductController@activate');
    Route::get('/products/view-product-group-price/{id}', 'ProductController@viewGroupPrice');
    Route::get('/products/add-selling-prices/{id}', 'ProductController@addSellingPrices');
    Route::post('/products/save-selling-prices', 'ProductController@saveSellingPrices');
    Route::post('/products/mass-delete', 'ProductController@massDestroy');
    Route::get('/products/view/{id}', 'ProductController@view');
    Route::get('/products/get-plates', 'ProductController@getPlates');
    Route::get('/products/list', 'ProductController@getProducts');
    Route::get('/products/list-no-variation', 'ProductController@getProductsWithoutVariations');
    Route::post('/products/bulk-edit', 'ProductController@bulkEdit');
    Route::post('/products/bulk-update', 'ProductController@bulkUpdate');
    Route::post('/products/bulk-update-location', 'ProductController@updateProductLocation');
    Route::get('/products/get-product-to-edit/{product_id}', 'ProductController@getProductToEdit');

    Route::post('/products/get_sub_categories', 'ProductController@getSubCategories');
    Route::get('/products/get_sub_units', 'ProductController@getSubUnits');
    Route::post('/products/product_form_part', 'ProductController@getProductVariationFormPart');
    Route::post('/products/get_product_variation_row', 'ProductController@getProductVariationRow');
    Route::post('/products/get_variation_template', 'ProductController@getVariationTemplate');
    Route::get('/products/get_variation_value_row', 'ProductController@getVariationValueRow');
    Route::post('/products/check_product_sku', 'ProductController@checkProductSku');
    Route::get('/products/quick_add', 'ProductController@quickAdd');
    Route::post('/products/save_quick_product', 'ProductController@saveQuickProduct');
    Route::get('/products/get-combo-product-entry-row', 'ProductController@getComboProductEntryRow');

    Route::resource('products', 'ProductController');

    Route::get('/purchase/change-warehouse', 'PurchaseController@changeWarehouse');
    Route::post('/purchases/update-status', 'PurchaseController@updateStatus');
    Route::get('/purchases/get_products', 'PurchaseController@getProducts');
    Route::get('/purchases/get_suppliers', 'PurchaseController@getSuppliers');
    Route::post('/purchases/get_purchase_entry_row', 'PurchaseController@getPurchaseEntryRow');
    Route::post('/purchases/check_ref_number', 'PurchaseController@checkRefNumber');
    Route::resource('purchases', 'PurchaseController')->except(['show']);

    Route::post('/sells/get-reverse-quantity', 'SellPosController@getReverseQuantity');
    Route::post('/sells/close-end-of-day', 'BusinessController@closeEndOfDay');
    Route::get('/sells/edit-cod-by-seller/{id}', 'SellController@editCodBySeller');
    Route::post('/sells/update-cod-by-seller', 'SellPosController@updateCodeBySeller');
    Route::get('/sells/check-invoice-cancelled', 'SellPosController@checkInvoiceCancelled');
    Route::post('/sells/export-excel', 'SellPosController@exportExcel');
    Route::post('/sells/cancel/{id}', 'SellPosController@cancel');
    Route::get('/toggle-subscription/{id}', 'SellPosController@toggleRecurringInvoices');
    Route::post('/sells/pos/get-types-of-service-details', 'SellPosController@getTypesOfServiceDetails');
    Route::get('/sells/plate-stock-detail/{id}', 'SellController@getPlateStockDetail');
    Route::get('/sells/plate-stock', 'SellController@getPlateStock');
    Route::get('/sells/subscriptions', 'SellPosController@listSubscriptions');
    Route::get('/sells/duplicate/{id}', 'SellController@duplicateSell');
    Route::get('/sells/drafts', 'SellController@getDrafts');
    Route::get('/sells/quotations', 'SellController@getQuotations');
    Route::get('/sells/draft-dt', 'SellController@getDraftDatables');
    Route::get('/sells/delete-media/{media_id}', 'SellController@deleteMedia');
    Route::get('/sells/add-vat/{id}', 'SellController@addVAT');
    Route::post('/sells/create-vat', 'SellController@storeVAT');
    Route::resource('sells', 'SellController')->except(['show']);

    Route::get('/import-sales', 'ImportSalesController@index');
    Route::post('/import-sales/preview', 'ImportSalesController@preview');
    Route::post('/import-sales', 'ImportSalesController@import');
    Route::get('/revert-sale-import/{batch}', 'ImportSalesController@revertSaleImport');

    Route::get('/sells/pos/get_product_row_by_filter/{variation_id}/{location_id}', 'SellPosController@getProductRowByFilter');
    Route::get('/sells/pos/get_product_row/{variation_id}/{location_id}', 'SellPosController@getProductRow');
    Route::post('/sells/pos/get_payment_row', 'SellPosController@getPaymentRow');
    Route::post('/sells/pos/approve_cashier/{id}', 'SellPosController@approveCashier');
    Route::post('/sells/pos/get-reward-details', 'SellPosController@getRewardDetails');
    Route::get('/sells/pos/get-recent-transactions', 'SellPosController@getRecentTransactions');
    Route::get('/sells/pos/get-product-suggestion', 'SellPosController@getProductSuggestion');
    Route::resource('pos', 'SellPosController');

    Route::resource('roles', 'RoleController');

    Route::resource('users', 'ManageUserController');

    Route::resource('group-taxes', 'GroupTaxController');

    Route::get('/barcodes/set_default/{id}', 'BarcodeController@setDefault');
    Route::resource('barcodes', 'BarcodeController');

    //Invoice schemes..
    Route::get('/invoice-schemes/set_default/{id}', 'InvoiceSchemeController@setDefault');
    Route::resource('invoice-schemes', 'InvoiceSchemeController');

    //Print Labels
    Route::get('/labels/show', 'LabelsController@show');
    Route::get('/labels/add-product-row', 'LabelsController@addProductRow');
    Route::get('/labels/preview', 'LabelsController@preview');

    //Targets
    Route::post('/targets/get_category_row', 'TargetController@getCategoryEntryRow');
    Route::get('/targets/get_products', 'TargetController@getProducts');
    Route::get('/targets/get_suppliers', 'TargetController@getSuppliers');
    Route::post('/targets/get_purchase_entry_row', 'TargetController@getPurchaseEntryRow');
    Route::post('/targets/check_ref_number', 'TargetController@checkRefNumber');
    Route::get('/targets/print/{id}', 'TargetController@printInvoice');
    Route::resource('targets', 'TargetController');

    //Reports...
    Route::get('/reports/revenue-by-month-report', 'ReportController@revenueByMonthReport');
    Route::get('/reports/calculate-total-due', 'ReportController@calculateTotalDue');
    Route::get('/reports/transfer-report', 'ReportController@transferReport');
    Route::post('/reports/get-total-revenue-by-day-report', 'ReportController@getTotalRevenueByDay');
    Route::post('/reports/print-revenue-by-day-report', 'ReportController@printRevenueByDayReport');
    Route::post('/reports/export-revenue-by-day-report', 'ReportController@exportRevenueByDayReport');
    Route::get('/reports/revenue-by-date-report', 'ReportController@receiptRevenueReport');
    Route::get('/reports/expense-revenue-report', 'ReportController@expenseRevenueReport');
    Route::get('/reports/debt-revenue-report', 'ReportController@debtRevenueReport');
    Route::get('/reports/reporting-date', 'ReportController@reportingDate');
    Route::get('/reports/purchase-report', 'ReportController@purchaseReport');
    Route::get('/reports/sale-report', 'ReportController@saleReport');
    Route::get('/reports/service-staff-report', 'ReportController@getServiceStaffReport');
    Route::get('/reports/service-staff-line-orders', 'ReportController@serviceStaffLineOrders');
    Route::get('/reports/table-report', 'ReportController@getTableReport');
    Route::get('/reports/profit-loss', 'ReportController@getProfitLoss');
    Route::get('/reports/get-opening-stock', 'ReportController@getOpeningStock');
    Route::get('/reports/purchase-sell', 'ReportController@getPurchaseSell');
    Route::get('/reports/customer-supplier', 'ReportController@getCustomerSuppliers');
    Route::get('/reports/stock-report/history/{plate_stock_id}', 'ReportController@getStockHistory');
    Route::get('/reports/stock-report', 'ReportController@getStockReport');
    Route::get('/reports/stock-details', 'ReportController@getStockDetails');
    Route::get('/reports/tax-report', 'ReportController@getTaxReport');
    Route::get('/reports/trending-products', 'ReportController@getTrendingProducts');
    Route::get('/reports/expense-report', 'ReportController@getExpenseReport');
    Route::get('/reports/stock-adjustment-report', 'ReportController@getStockAdjustmentReport');
    Route::get('/reports/register-report', 'ReportController@getRegisterReport');
    Route::get('/reports/sales-representative-report', 'ReportController@getSalesRepresentativeReport');
    Route::get('/reports/sales-representative-total-expense', 'ReportController@getSalesRepresentativeTotalExpense');
    Route::get('/reports/sales-representative-total-sell', 'ReportController@getSalesRepresentativeTotalSell');
    Route::get('/reports/sales-representative-total-commission', 'ReportController@getSalesRepresentativeTotalCommission');
    Route::get('/reports/stock-expiry', 'ReportController@getStockExpiryReport');
    Route::get('/reports/stock-expiry-edit-modal/{purchase_line_id}', 'ReportController@getStockExpiryReportEditModal');
    Route::post('/reports/stock-expiry-update', 'ReportController@updateStockExpiryReport')->name('updateStockExpiryReport');
    Route::get('/reports/customer-group', 'ReportController@getCustomerGroup');
    Route::get('/reports/product-purchase-report', 'ReportController@getproductPurchaseReport');
    Route::get('/reports/product-sell-report', 'ReportController@getproductSellReport');
    Route::get('/reports/product-sell-report-with-purchase', 'ReportController@getproductSellReportWithPurchase');
    Route::get('/reports/product-sell-grouped-report', 'ReportController@getproductSellGroupedReport');
    Route::get('/reports/lot-report', 'ReportController@getLotReport');
    Route::get('/reports/purchase-payment-report', 'ReportController@purchasePaymentReport');
    Route::get('/reports/sell-payment-report', 'ReportController@sellPaymentReport');
    Route::get('/reports/product-stock-details', 'ReportController@productStockDetails');
    Route::get('/reports/adjust-product-stock', 'ReportController@adjustProductStock');
    Route::get('/reports/get-profit/{by?}', 'ReportController@getProfit');
    Route::get('/reports/items-report', 'ReportController@itemsReport');
    Route::get('/reports/report-owner-target', 'ReportController@reportOwnerTarget');
    Route::get('/reports/report-import-export', 'ReportController@reportExportImport');
    Route::get('/reports/total-sales', 'ReportController@totalSales');

    Route::get('business-location/activate-deactivate/{location_id}', 'BusinessLocationController@activateDeactivateLocation');

    //Business Location Settings...
    Route::prefix('business-location/{location_id}')->name('location.')->group(function () {
        Route::get('settings', 'LocationSettingsController@index')->name('settings');
        Route::post('settings', 'LocationSettingsController@updateSettings')->name('settings_update');
    });

    //Business Locations...
    Route::post('business-location/check-location-id', 'BusinessLocationController@checkLocationId');
    Route::resource('business-location', 'BusinessLocationController');

    //Warehouses
    Route::resource('warehouses', 'WarehouseController');

    //Invoice layouts..
    Route::resource('invoice-layouts', 'InvoiceLayoutController');

    //Expense Categories...
    Route::resource('expense-categories', 'ExpenseCategoryController');

    //Expenses...
    Route::get('receipts/{transaction_id}/print', 'ExpenseController@printReceiptInvoice')->name('receipts.printReceiptInvoice');
    Route::get('receipts/create-receipt-row', 'ExpenseController@createReceiptRow');
    Route::post('receipts/store-receipt-row', 'ExpenseController@storeReceiptRow');
    Route::get('receipts/edit-receipt-row/{id}', 'ExpenseController@editReceiptRow');
    Route::post('receipts/update-receipt-row/{id}', 'ExpenseController@updateReceiptRow');
    Route::post('receipts/delete-receipt-row/{id}', 'ExpenseController@deleteReceiptRow');

    Route::get('expenses/create-expense-row', 'ExpenseController@createExpenseRow');
    Route::get('expenses/edit-expense-row/{id}', 'ExpenseController@editExpenseRow');
    Route::post('expenses/update-expense-row/{id}', 'ExpenseController@updateExpenseRow');
    Route::post('expenses/delete-expense-row/{id}', 'ExpenseController@deleteExpenseRow');

    Route::post('expenses/store-expense-row', 'ExpenseController@storeExpenseRow');
    Route::post('expenses/add_expense_row', 'ExpenseController@addExpenseRow');
    Route::post('receipts/add_receipt_row', 'ExpenseController@addReceiptRow');
    Route::post('expenses/get-packages', 'ExpenseController@getTransactionForCustomer');
    Route::resource('expenses', 'ExpenseController');

    //Transaction payments...
    Route::get('/payments/check-confirm-bank-transfer-permission', 'TransactionPaymentController@checkConfirmBankTransferPermission');
    Route::get('/payments/approve/{payment_id}', 'TransactionPaymentController@approvePayment');
    Route::get('/payments/reject/{payment_id}', 'TransactionPaymentController@rejectPayment');
    Route::get('/payments/normal/{transaction_id}', 'TransactionPaymentController@editNormal');
    Route::put('/payments/normal/{transaction_id}', 'TransactionPaymentController@updateNormal');
    Route::get('/payments/deposit/{transaction_id}', 'TransactionPaymentController@editDeposit');
    Route::put('/payments/deposit/{transaction_id}', 'TransactionPaymentController@updateDeposit');
    Route::get('/payments/cod/{transaction_id}', 'TransactionPaymentController@editCod');
    Route::put('/payments/cod/{transaction_id}', 'TransactionPaymentController@updateCod');
    Route::get('/payments/add_remaining/{transaction_id}', 'TransactionPaymentController@addRemaining');
//    Route::get('/payments/add_sell_return/{transaction_id}', 'TransactionPaymentController@addSellReturn');
    Route::post('/payments/add_remaining', 'TransactionPaymentController@storeRemaining');
//    Route::post('/payments/add_sell_return', 'TransactionPaymentController@storeSellReturn');
    // Route::get('/payments/opening-balance/{contact_id}', 'TransactionPaymentController@getOpeningBalancePayments');
    Route::get('/payments/show-child-payments/{payment_id}', 'TransactionPaymentController@showChildPayments');
    Route::get('/payments/view-payment/{payment_id}', 'TransactionPaymentController@viewPayment');
    Route::get('/payments/add_payment/{transaction_id}', 'TransactionPaymentController@addPayment');
    Route::get('/payments/pay-contact-due/{contact_id}', 'TransactionPaymentController@getPayContactDue');
    Route::post('/payments/pay-contact-due', 'TransactionPaymentController@postPayContactDue');
    Route::resource('payments', 'TransactionPaymentController');

    //Printers...
//    Route::resource('printers', 'PrinterController');

    Route::get('/stock-adjustments/check-max-quantity-allow', 'StockAdjustmentController@checkMaxQuantityAllow');
    Route::get('/stock-adjustments/approve/{id}', 'StockAdjustmentController@approveStockAdjustment');
    Route::post('/stock-adjustments/get_sell_entry_row', 'StockAdjustmentController@getSellEntryRow');
    Route::get('/stock-adjustments/remove-expired-stock/{purchase_line_id}', 'StockAdjustmentController@removeExpiredStock');
    Route::resource('stock-adjustments', 'StockAdjustmentController');

    Route::get('/cash-register/register-details', 'CashRegisterController@getRegisterDetails');
    Route::get('/cash-register/close-register', 'CashRegisterController@getCloseRegister');
    Route::post('/cash-register/close-register', 'CashRegisterController@postCloseRegister');
    Route::resource('cash-register', 'CashRegisterController');

    //Import products
    Route::get('/import-products', 'ImportProductsController@index');
    Route::post('/import-products/store', 'ImportProductsController@store');

    //Sales Commission Agent
    Route::resource('sales-commission-agents', 'SalesCommissionAgentController');

    //Stock Transfer
    Route::post('/stock-transfers/get_sell_entry_row', 'StockTransferController@getSellEntryRow');
    Route::get('/stock-transfers/print/{id}', 'StockTransferController@printInvoice');
    Route::resource('stock-transfers', 'StockTransferController');

    Route::get('/opening-stock/add/{product_id}', 'OpeningStockController@add');
    Route::post('/opening-stock/save', 'OpeningStockController@save');

    //Stock To Deliver
    Route::put('/stock-to-deliver/{id}/edit', 'SellPosController@updateStockDeliver');
    Route::get('/stock-to-deliver/{id}/edit', 'SellController@editStockDeliver');
    Route::get('/stock-to-deliver/reverse-size/{plate_stock_id}', 'SellPosController@getReverseSize');
    Route::post('/stock-to-deliver/reverse-size/{plate_stock_id}', 'SellPosController@postReverseSize');
    Route::post('/stock-to-deliver/export-excel', 'SellPosController@exportExcelDeliver');
    Route::get('/stock-to-deliver/check-invoice-update', 'SellPosController@checkInvoiceUpdate');
    Route::post('/stock-to-deliver/change_plate_manually', 'SellController@changePlateManually');
    Route::post('/stock-to-deliver/get_sell_entry_row', 'SellController@getSellEntryRow');
    Route::put('/stock-to-deliver/create/{id}', 'SellPosController@storeStockDeliver');
    Route::get('/stock-to-deliver/{id}/create', 'SellController@createStockDeliver');
    Route::get('/stock-to-deliver/{id}', 'SellController@showStockDeliver');
    Route::post('/stock-to-deliver/confirm-export/{id}', 'SellPosController@confirmExport');
    Route::get('/stock-to-deliver', 'SellController@stockDeliverIndex');

    // Cashier
    Route::post('/sells-of-cashier/get-accounts', 'SellPosController@getAccounts');
    Route::post('/sells-of-cashier/export-excel', 'SellPosController@exportExcelCashier');
    Route::post('/sells-of-cashier/export-onday-excel', 'SellPosController@exportOnDayExcelCashier');
    Route::get('/sells-of-cashier/create_debit_paper/{id}', 'SellController@createDebitPaper');
    Route::get('/sells-of-cashier/show_debit_paper/{id}', 'SellController@showDebitPaper');
    Route::post('/sells-of-cashier/store_debit_paper/{id}', 'SellPosController@storeDebitPaper');
    Route::post('/sells-of-cashier/get_total_filter_by_day', 'SellPosController@getTotalFilterByDay');
    Route::get('/sells-of-cashier/toggle-transaction-cod-status/{id}', 'SellPosController@toggleTransactionCodStatus');
    Route::get('/sells-of-cashier/transaction-payments/{notification_id}', 'SellPosController@showTransactionPayment');
    Route::get('/sells-of-cashier/transaction-payments', 'SellPosController@listTransactionPayments');
    Route::get('/sells-of-cashier', 'SellController@sellsOfCashier');
    Route::get('/receipt-of-cashier', 'SellController@receiptOfCashier');
    Route::get('/expense-of-cashier', 'SellController@expenseOfCashier');
    Route::post('/expense-of-cashier/confirm-debit-paper', 'SellPosController@confirmBulkDebitPaper');
    Route::post('/expense-of-cashier/confirm-bulk-remaining', 'SellPosController@confirmBulkRemaining');
    Route::post('/expense-of-cashier/cancel-remaining', 'SellPosController@cancelRemaining');

    //Customer Groups
    Route::resource('customer-group', 'CustomerGroupController');

    //Import opening stock
    Route::get('/import-opening-stock', 'ImportOpeningStockController@index');
    Route::post('/import-opening-stock/store', 'ImportOpeningStockController@store');

    //Sell return
    Route::post('/sell-return/get_sell_return_entry_row', 'SellReturnController@getSellReturnEntryRow');
    Route::resource('sell-return', 'SellReturnController');
    Route::get('sell-return/get-product-row', 'SellReturnController@getProductRow');
    Route::get('/sell-return/print/{id}', 'SellReturnController@printInvoice');
    Route::get('/sell-return/add/{id}', 'SellReturnController@add');
    Route::post('/sell-return/approve', ['as' => 'approval.sell_return', 'uses' => 'SellReturnController@approveReturnSell']);
    Route::post('/sell-return/reject/{id}', ['as' => 'reject.sell_return', 'uses' => 'SellReturnController@rejectReturnSell']);

    //Backup
    Route::get('backup/download/{file_name}', 'BackUpController@download');
    Route::get('backup/delete/{file_name}', 'BackUpController@delete');
    Route::resource('backup', 'BackUpController', ['only' => [
        'index', 'create', 'store'
    ]]);

    Route::get('selling-price-group/activate-deactivate/{id}', 'SellingPriceGroupController@activateDeactivate');
    Route::get('export-selling-price-group', 'SellingPriceGroupController@export');
    Route::post('import-selling-price-group', 'SellingPriceGroupController@import');

    Route::resource('selling-price-group', 'SellingPriceGroupController');

    Route::resource('notification-templates', 'NotificationTemplateController')->only(['index', 'store']);
    Route::get('notification/get-template/{transaction_id}/{template_for}', 'NotificationController@getTemplate');
    Route::post('notification/send', 'NotificationController@send');

    Route::post('/purchase-return/update', 'CombinedPurchaseReturnController@update');
    Route::get('/purchase-return/edit/{id}', 'CombinedPurchaseReturnController@edit');
    Route::post('/purchase-return/save', 'CombinedPurchaseReturnController@save');
    Route::post('/purchase-return/get_product_row', 'CombinedPurchaseReturnController@getProductRow');
    Route::get('/purchase-return/create', 'CombinedPurchaseReturnController@create');
    Route::get('/purchase-return/add/{id}', 'PurchaseReturnController@add');
    Route::resource('/purchase-return', 'PurchaseReturnController', ['except' => ['create']]);

    Route::get('/discount/activate/{id}', 'DiscountController@activate');
    Route::post('/discount/mass-deactivate', 'DiscountController@massDeactivate');
    Route::resource('discount', 'DiscountController');

    Route::group(['prefix' => 'account'], function () {
        Route::resource('/account', 'AccountController');
        Route::get('/fund-transfer/{id}', 'AccountController@getFundTransfer');
        Route::post('/fund-transfer', 'AccountController@postFundTransfer');
        Route::get('/deposit/{id}', 'AccountController@getDeposit');
        Route::post('/deposit', 'AccountController@postDeposit');
        Route::get('/close/{id}', 'AccountController@close');
        Route::get('/activate/{id}', 'AccountController@activate');
        Route::get('/delete-account-transaction/{id}', 'AccountController@destroyAccountTransaction');
        Route::get('/get-account-balance/{id}', 'AccountController@getAccountBalance');
        Route::get('/balance-sheet', 'AccountReportsController@balanceSheet');
        Route::get('/trial-balance', 'AccountReportsController@trialBalance');
        Route::get('/payment-account-report', 'AccountReportsController@paymentAccountReport');
        Route::get('/link-account/{id}', 'AccountReportsController@getLinkAccount');
        Route::post('/link-account', 'AccountReportsController@postLinkAccount');
        Route::get('/cash-flow', 'AccountController@cashFlow');
    });

    Route::resource('account-types', 'AccountTypeController');

    //Restaurant module
    Route::group(['prefix' => 'modules'], function () {
        Route::resource('tables', 'Restaurant\TableController');
        Route::resource('modifiers', 'Restaurant\ModifierSetsController');

        //Map modifier to products
        Route::get('/product-modifiers/{id}/edit', 'Restaurant\ProductModifierSetController@edit');
        Route::post('/product-modifiers/{id}/update', 'Restaurant\ProductModifierSetController@update');
        Route::get('/product-modifiers/product-row/{product_id}', 'Restaurant\ProductModifierSetController@product_row');

        Route::get('/add-selected-modifiers', 'Restaurant\ProductModifierSetController@add_selected_modifiers');

        Route::get('/kitchen', 'Restaurant\KitchenController@index');
        Route::get('/kitchen/mark-as-cooked/{id}', 'Restaurant\KitchenController@markAsCooked');
        Route::post('/refresh-orders-list', 'Restaurant\KitchenController@refreshOrdersList');
        Route::post('/refresh-line-orders-list', 'Restaurant\KitchenController@refreshLineOrdersList');

        Route::get('/orders', 'Restaurant\OrderController@index');
        Route::get('/orders/mark-as-served/{id}', 'Restaurant\OrderController@markAsServed');
        Route::get('/data/get-pos-details', 'Restaurant\DataController@getPosDetails');
        Route::get('/orders/mark-line-order-as-served/{id}', 'Restaurant\OrderController@markLineOrderAsServed');
    });

    Route::get('bookings/get-todays-bookings', 'Restaurant\BookingController@getTodaysBookings');
    Route::resource('bookings', 'Restaurant\BookingController');

    Route::resource('types-of-service', 'TypesOfServiceController');
    Route::get('sells/edit-shipping/{id}', 'SellController@editShipping');
    Route::put('sells/update-shipping/{id}', 'SellController@updateShipping');
    Route::get('shipments', 'SellController@shipments');

    Route::post('upload-module', 'Install\ModulesController@uploadModule');
    Route::resource('manage-modules', 'Install\ModulesController')
        ->only(['index', 'destroy', 'update']);
    Route::resource('warranties', 'WarrantyController');

    Route::resource('dashboard-configurator', 'DashboardConfiguratorController')
        ->only(['edit', 'update']);

    //common controller for document & note
    Route::get('get-document-note-page', 'DocumentAndNoteController@getDocAndNoteIndexPage');
    Route::post('post-document-upload', 'DocumentAndNoteController@postMedia');
    Route::resource('note-documents', 'DocumentAndNoteController');
});

Route::middleware(['EcomApi'])->prefix('api/ecom')->group(function () {
    Route::get('products/{id?}', 'ProductController@getProductsApi');
    Route::get('categories', 'CategoryController@getCategoriesApi');
    Route::get('brands', 'BrandController@getBrandsApi');
    Route::post('customers', 'ContactController@postCustomersApi');
    Route::get('settings', 'BusinessController@getEcomSettings');
    Route::get('variations', 'ProductController@getVariationsApi');
    Route::post('orders', 'SellPosController@placeOrdersApi');
});

//common route
Route::middleware(['auth'])->group(function () {
    Route::get('/logout', 'Auth\LoginController@logout')->name('logout');
});

Route::middleware(['authh', 'auth', 'SetSessionData', 'language', 'timezone'])->group(function () {
    Route::get('/load-more-notifications', 'HomeController@loadMoreNotifications');
    Route::get('/get-total-unread', 'HomeController@getTotalUnreadNotifications');
    Route::get('/purchases/print/{id}', 'PurchaseController@printInvoice');
    Route::get('/purchases/{id}', 'PurchaseController@show');
    Route::get('/sells/{id}', 'SellController@show');
    Route::get('/sells/{transaction_id}/print-deliver-old-template', 'SellPosController@printDeliverInvoiceOldTemplate')->name('sell.printDeliverInvoiceOldTemplate');
    Route::get('/sells/{transaction_id}/print-deliver', 'SellPosController@printDeliverInvoice')->name('sell.printDeliverInvoice');
    Route::get('/sells/{transaction_id}/print-shipping', 'SellPosController@printShippingInvoice')->name('sell.printShippingInvoice');
    Route::get('/sells/{transaction_id}/print-shipping-without-header', 'SellPosController@printShippingInvoiceWithoutHeader')->name('sell.printShippingInvoiceWithoutHeader');
    Route::get('/sells/{transaction_id}/print', 'SellPosController@printInvoice')->name('sell.printInvoice');
    Route::get('/sells/{transaction_id}/print-sub', 'SellController@printSubInvoice')->name('sell.printSubInvoice');
    Route::get('/sells/invoice-url/{id}', 'SellPosController@showInvoiceUrl');
});
