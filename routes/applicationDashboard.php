<?php

use App\Http\Controllers\ApplicationDashboard\HomeController;
use Illuminate\Support\Facades\Route;

Route::middleware(
          [
                    // 'setData', 
                    // 'auth', 
                    // 'SetSessionData', 
                    // 'language', 
                    // 'timezone', 
                    'ApplicationSidebarMenu', 
                    // 'CheckUserLogin'
                    ]
          )->group(function () {

Route::get('applicationDashboard/home', [HomeController::class, 'index'])
    ->name('application.home');

Route::resource('products', 'ProductController');

Route::get('/products/stock-history/{id}', 'ProductController@productStockHistory');
Route::get('/delete-media/{media_id}', 'ProductController@deleteMedia');
Route::post('/products/mass-deactivate', 'ProductController@massDeactivate');
Route::get('/products/activate/{id}', 'ProductController@activate');
Route::get('/products/view-product-group-price/{id}', 'ProductController@viewGroupPrice');
Route::get('/products/add-selling-prices/{id}', 'ProductController@addSellingPrices');
Route::post('/products/save-selling-prices', 'ProductController@saveSellingPrices');
Route::post('/products/mass-delete', 'ProductController@massDestroy');
Route::get('/products/view/{id}', 'ProductController@view');
Route::get('/getProducts', 'ProductController@getProducts');
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
Route::post('/products/toggle-woocommerce-sync', 'ProductController@toggleWooCommerceSync');

Route::get('taxonomies-ajax-index-page', 'TaxonomyController@getTaxonomyIndexPage');
Route::resource('taxonomies', 'TaxonomyController');

Route::resource('brands', 'BrandController');

Route::resource('units', 'UnitController');

Route::resource('variation-templates', 'VariationTemplateController');

//Print Labels
Route::get('/labels/show', 'LabelsController@show');
Route::get('/labels/add-product-row', 'LabelsController@addProductRow');
Route::get('/labels/preview', 'LabelsController@preview');

//Import products
Route::get('/import-products', 'ImportProductsController@index');
Route::post('/import-products/store', 'ImportProductsController@store');


//Import opening stock
Route::get('/import-opening-stock', 'ImportOpeningStockController@index');
Route::post('/import-opening-stock/store', 'ImportOpeningStockController@store');

Route::get('selling-price-group/activate-deactivate/{id}', 'SellingPriceGroupController@activateDeactivate');
Route::get('export-selling-price-group', 'SellingPriceGroupController@export');
Route::post('import-selling-price-group', 'SellingPriceGroupController@import');

Route::resource('selling-price-group', 'SellingPriceGroupController');

Route::resource('warranties', 'WarrantyController');

Route::get('applicationDashboard/client-orders-report', 'ApplicationDashboard\OrderReportsController@index')->name('orders.reports');
Route::get('applicationDashboard/client-orders/{id}', 'ApplicationDashboard\OrderReportsController@clientOrders')->name('client.orders');


Route::resource('applicationDashboard/orders', 'ApplicationDashboard\OrderController');
Route::get('applicationDashboard/orders/{orderId}/details', 'ApplicationDashboard\OrderController@getOrderDetails')->name('orders.details');

Route::resource('applicationDashboard/refundOrders', 'ApplicationDashboard\RefundOrderController');
Route::get('applicationDashboard/refundOrders/{orderId}/details', 'ApplicationDashboard\RefundOrderController@getOrderDetails')->name('orders.details');
Route::post('applicationDashboard/refundOrders/{orderId}/change-order-status', 'ApplicationDashboard\RefundOrderController@changeOrderStatus');
Route::post('applicationDashboard/refundOrders/{orderId}/change-payment-status', 'ApplicationDashboard\RefundOrderController@changePaymentStatus');


Route::resource('applicationDashboard/transferOrders', 'ApplicationDashboard\TransferOrderController');
Route::get('applicationDashboard/transferOrders/{orderId}/details', 'ApplicationDashboard\TransferOrderController@getOrderDetails')->name('orders.details');
Route::post('applicationDashboard/transferOrders/{orderId}/change-order-status', 'ApplicationDashboard\TransferOrderController@changeOrderStatus');
Route::post('applicationDashboard/transferOrders/{orderId}/change-payment-status', 'ApplicationDashboard\TransferOrderController@changePaymentStatus');


// Route::get('applicationDashboard/orderDeliveries', 'ApplicationDashboard\DeliveryController@orderDeliveries');

Route::get('/order-deliveries', 'ApplicationDashboard\DeliveryController@orderDeliveries')->name('order.deliveries');

Route::get('applicationDashboard/allDeliveries', 'ApplicationDashboard\DeliveryController@allDeliveries');


Route::get('applicationDashboard/deliveries/{orderId}/list', 'ApplicationDashboard\DeliveryController@getAvailableDeliveries');

Route::post('applicationDashboard/deliveries/assign-delivery', 'ApplicationDashboard\DeliveryController@assignDelivery');
Route::post('applicationDashboard/deliveries/{orderId}/change-payment-status', 'ApplicationDashboard\DeliveryController@changePaymentStatus');


Route::post('applicationDashboard/orders/{orderId}/change-order-status', 'ApplicationDashboard\OrderController@changeOrderStatus');
Route::post('applicationDashboard/orders/{orderId}/change-payment-status', 'ApplicationDashboard\OrderController@changePaymentStatus');


// changePaymentStatus
Route::resource('applicationDashboard/order-cancellations', 'ApplicationDashboard\OrderCancellationController');
Route::post(
    'applicationDashboard/order-cancellations/{orderCancellationId}/change-status',
    'ApplicationDashboard\OrderCancellationController@changeOrderCancellationStatus'
);


Route::resource('applicationDashboard/order-refunds', 'ApplicationDashboard\OrderRefundController');
Route::post(
    'applicationDashboard/order-refunds/{orderRefundId}/change-status',
    'ApplicationDashboard\OrderRefundController@changeOrderRefundStatus'
);

Route::post(
    'applicationDashboard/order-refunds/{orderRefundId}/change-refund-status',
    'ApplicationDashboard\OrderRefundController@changeRefundStatus'
);

Route::resource('banners', 'ApplicationDashboard\BannerController');
Route::get('banners/products', 'ApplicationDashboard\BannerController@getProducts');
Route::get('banners/categories', 'ApplicationDashboard\BannerController@getCategories');

// Route::resource('applicationDashboard/settings', 'ApplicationDashboard\ApplicationSettingsController');
Route::get('applicationDashboard/settings', 'ApplicationDashboard\ApplicationSettingsController@index')
    ->name('application_settings.index');

Route::get('applicationDashboard/settings/create', 'ApplicationDashboard\ApplicationSettingsController@create')
    ->name('application_settings.create');

Route::get('applicationDashboard/settings/show/{id}', 'ApplicationDashboard\ApplicationSettingsController@show')
    ->name('application_settings.show');


Route::post('applicationDashboard/settings/store', 'ApplicationDashboard\ApplicationSettingsController@store')
    ->name('application_settings.store');

Route::put('applicationDashboard/settings/update/{id}', 'ApplicationDashboard\ApplicationSettingsController@update')
    ->name('application_settings.update');

Route::delete('applicationDashboard/settings/destroy/{id}', 'ApplicationDashboard\ApplicationSettingsController@destroy')
    ->name('application_settings.destroy');

Route::resource('applicationDashboard/expenses', 'ApplicationDashboard\ExpenseController');


});