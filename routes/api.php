<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\BusinessLocationController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\OrderCancellationController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OrderRefundController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\WarrantyController;
use App\Http\Controllers\Api\DeliveryController;
use App\Http\Resources\OrderRefund\OrderRefundCollection;
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
      });

      Route::middleware('auth:sanctum-client')->group(function () {

        Route::get('brands', [BrandController::class, 'index']);


        Route::get('orders', [OrderController::class, 'index']);
        Route::get('orders/{id}', [OrderController::class, 'show']);
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

    
    });

    Route::middleware('auth:sanctum-delivery')->group(function () {
        Route::get('getNotAssignedOrders', [DeliveryController::class, 'getNotAssignedOrders']);

        Route::get('getAssignedOrders', [DeliveryController::class, 'getAssignedOrders']);
        Route::post('assignDelivery', [DeliveryController::class, 'assignDelivery']);

        Route::post('changeOrderStatus/{orderId}', [DeliveryController::class, 'changeOrderStatus']);

    });

    Route::get('banners', [BannerController::class, 'index']);


Route::post('client/register', [AuthController::class, 'clientRegister']);
Route::post('client/login', [AuthController::class, 'clientLogin']);

Route::post('delivery/login', [AuthController::class, 'deliveryLogin']);

Route::post('user/login', [AuthController::class, 'userLogin']);
