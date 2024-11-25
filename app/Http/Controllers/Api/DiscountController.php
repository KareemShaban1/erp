<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Discount\StoreDiscountRequest;
use App\Http\Requests\Discount\UpdateDiscountRequest;
use App\Models\Discount;
use App\Services\API\DiscountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DiscountController extends Controller
{


    protected $service;

    public function __construct(DiscountService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the categories.
     */
    public function listDiscounts(Request $request)
    {

        $Discounts = $this->service->listDiscounts($request);

        if ($Discounts instanceof JsonResponse) {
            return $Discounts;
        }

        return $Discounts->additional([
            'code' => 200,
            'status' => 'success',
            'message' =>  __('message.Categories have been retrieved successfully'),
        ]);
    }

    public function listFlashSales(Request $request)
    {

        $Discounts = $this->service->listFlashSales($request);

        if ($Discounts instanceof JsonResponse) {
            return $Discounts;
        }

        return $Discounts->additional([
            'code' => 200,
            'status' => 'success',
            'message' =>  __('message.Categories have been retrieved successfully'),
        ]);
    }

    
}
