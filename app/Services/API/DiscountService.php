<?php

namespace App\Services\API;

use App\Http\Resources\Discount\DiscountCollection;
use App\Http\Resources\Discount\DiscountResource;
use App\Http\Resources\DiscountModule\DiscountModuleCollection;
use App\Models\Discount;
use App\Services\BaseService;
use App\Traits\HelperTrait;
use App\Traits\UploadFileTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DiscountService extends BaseService
{
    use UploadFileTrait, HelperTrait;
    /**
     * Get all Discounts with filters and pagination for DataTables.
     */
    public function listDiscounts(Request $request)
    {

        try {

            $query = Discount::
            businessId()->
            where('type','discount');

            $query = $this->withTrashed($query, $request);

            $Discounts = $this->withPagination($query, $request);

            return (new DiscountModuleCollection($Discounts))
            ->withFullData(!($request->full_data == 'false'));


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while listing Discounts'));
        }
    }


    public function listFlashSales(Request $request)
    {

        try {

            $query = Discount::
            businessId()->
            where('type','flash_sale');

            $query = $this->withTrashed($query, $request);

            $Discounts = $this->withPagination($query, $request);

            return (new DiscountModuleCollection($Discounts))
            ->withFullData(!($request->full_data == 'false'));


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while listing Discounts'));
        }
    }

    public function show($id) {

        try {
            $Discount = Discount::businessId()->find($id);

            if(!$Discount) {
                return null;
            }
            return $Discount;


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while showing Discount'));
        }
    }

    
}
