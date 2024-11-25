<?php

use App\Http\Controllers\Api\ApplicationSettingsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\BusinessLocationController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\DiscountController;
use App\Http\Controllers\Api\OrderCancellationController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OrderRefundController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\WarrantyController;
use App\Http\Controllers\Api\DeliveryController;
use App\Http\Controllers\Api\FcmController;
use App\Http\Controllers\Api\ClientNotificationController;
use App\Http\Controllers\Api\DeliveryNotificationController;
use App\Http\Controllers\Api\PushNotificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::get('categories', [CategoryController::class, 'index']);
Route::patch('categories/{id}/restore', [CategoryController::class , 'restore']);
Route::delete('categories/{id}/force-delete', [CategoryController::class , 'forceDelete']);


Route::get('units', [UnitController::class, 'index']);

Route::get('products/{category_id?}', [ProductController::class, 'index']);
Route::get('category_products/{id}', [ProductController::class, 'categoryProducts']);

Route::get('warranties', [WarrantyController::class, 'index']);

Route::get('business_locations', [BusinessLocationController::class, 'index']);


Route::middleware('auth:sanctum-client')->group(function () {
          Route::get('cart_get_items', [CartController::class, 'index']);
          Route::post('add_to_cart', [CartController::class, 'store']);
          Route::post('update_cart/{id}', [CartController::class, 'update']);
          Route::delete('delete_cart/{id}', [CartController::class, 'destroy']);
          Route::delete('clear_cart', [CartController::class, 'clear']);

          Route::get('brands', [BrandController::class, 'index']);

          Route::get('orders', [OrderController::class, 'index']);
          Route::post('orders', [OrderController::class, 'store']);
          Route::post('orders/update/{id}', [OrderController::class, 'update']);
          Route::delete('orders/delete/{id}', [OrderController::class, 'destroy']);
          Route::get('checkQuantityAndLocation', [OrderController::class, 'checkQuantityAndLocation']);
  
          Route::get('clients', [ClientController::class, 'index']);
          Route::get('clients/getAuthClient', [ClientController::class, 'getAuthClient']);
      
          Route::get('orders-cancellation', [OrderCancellationController::class, 'index']);
          Route::post('orders-cancellation', [OrderCancellationController::class, 'store']);
          Route::get('getAuthClientOrderCancellations', [OrderCancellationController::class, 'getAuthClientOrderCancellations']);
  
          Route::get('orders-refunds', [OrderRefundController::class, 'index']);
          Route::post('orders-refunds', [OrderRefundController::class, 'store']);
  
          Route::get('sendNotification', [ClientNotificationController::class, 'sendNotification']);
  
          Route::put('update-device-token', [FcmController::class, 'updateDeviceToken']);
  
          Route::post('send-fcm-notification', [FcmController::class, 'sendFcmNotification']);
      
          Route::post('send-push-notification', [PushNotificationController::class, 'sendPushNotification']);
  
          Route::get('client/delete-account', [AuthController::class, 'deleteClientAccount']);

          Route::get('client/account-info', [AuthController::class, 'getClientAccount']);

          Route::post('client/update-password', [AuthController::class, 'updateClientPassword']);


          Route::get('client/all-notifications', [ClientNotificationController::class, 'getClientNotifications']);

          Route::get('client/get-un-read-notifications', [ClientNotificationController::class, 'getUnreadNotificationsCount']);

          Route::post('client/mark-notification-as-read/{id}', [ClientNotificationController::class, 'markNotificationAsRead']);

          Route::post('client/mark-all-notifications-as-read', [ClientNotificationController::class, 'markAllNotificationsAsRead']);
        

          Route::get('discounts', [DiscountController::class, 'listDiscounts']);
          Route::get('flash_sales', [DiscountController::class, 'listFlashSales']);
      
      

          //   getClientNotifications
      });
    Route::middleware('auth:sanctum-delivery')->group(function () {
        Route::get('getNotAssignedOrders', [DeliveryController::class, 'getNotAssignedOrders']);

        // getDeliveryOrders
        Route::get('getDeliveryOrders/{status}', [DeliveryController::class, 'getDeliveryOrders']);

        Route::get('getAssignedOrders', [DeliveryController::class, 'getAssignedOrders']);
        Route::post('assignDelivery', [DeliveryController::class, 'assignDelivery']);

        Route::post('changeOrderStatus/{orderId}', [DeliveryController::class, 'changeOrderStatus']);

        Route::get('delivery/delete-account', [AuthController::class, 'deleteClientAccount']);

        Route::get('delivery/account-info', [AuthController::class, 'getDeliveryAccount']);

        Route::post('delivery/update-password', [AuthController::class, 'updateDeliveryPassword']);

        Route::get('delivery/all-notifications', [DeliveryNotificationController::class, 'getDeliveryNotifications']);

        Route::get('delivery/get-un-read-notifications', [DeliveryNotificationController::class, 'getUnreadNotificationsCount']);

        Route::post('delivery/mark-notification-as-read/{id}', [DeliveryNotificationController::class, 'markNotificationAsRead']);

        Route::post('delivery/mark-all-notifications-as-read', [DeliveryNotificationController::class, 'markAllNotificationsAsRead']);

    });

    Route::get('banners', [BannerController::class, 'index']);


    Route::get('orders/{id}', [OrderController::class, 'show']);

    Route::get('applicationSettings', [ApplicationSettingsController::class, 'index']);

    Route::post('client/register', [AuthController::class, 'clientRegister']);
    Route::post('client/login', [AuthController::class, 'clientLogin']);

    Route::post('delivery/login', [AuthController::class, 'deliveryLogin']);
    // sendNotification

    Route::post('user/login', [AuthController::class, 'userLogin']);
