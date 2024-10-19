<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\BusinessLocationController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\WarrantyController;
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

Route::get('brands', [BrandController::class, 'index']);

Route::get('units', [UnitController::class, 'index']);

Route::get('products/{category_id?}', [ProductController::class, 'index']);
Route::get('category_products/{id}', [ProductController::class, 'categoryProducts']);

Route::get('warranties', [WarrantyController::class, 'index']);

Route::get('business_locations', [BusinessLocationController::class, 'index']);

Route::post('user/login', [AuthController::class, 'userLogin']);
