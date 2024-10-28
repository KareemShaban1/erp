<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Banner\StoreBannerRequest;
use App\Http\Requests\Banner\UpdateBannerRequest;
use App\Models\Banner;
use App\Services\API\BannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BannerController extends Controller
{


    protected $service;

    public function __construct(BannerService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the categories.
     */
    public function index(Request $request)
    {
        $banners = $this->service->list($request);

        if ($banners instanceof JsonResponse) {
            return $banners;
        }

        return $banners->additional([
            'code' => 200,
            'status' => 'success',
            'message' =>  __('message.Categories have been retrieved successfully'),
        ]);
    }

    /**
     * Store a newly created Banner in storage.
     */
    public function store(Request $request)
    {
            $data = $request->validated();
            $banner = $this->service->store( $data);

            if ($banner instanceof JsonResponse) {
                return $banner;
            }

            return $this->returnJSON($banner, __('message.Banner has been created successfully'));
    }

    /**
     * Display the specified Banner.
     */
    public function show($id)
    {

        $banner = $this->service->show($id);

        if ($banner instanceof JsonResponse) {
            return $banner;
        }

        return $this->returnJSON($banner, __('message.Banner has been created successfully'));

    }

    /**
     * Update the specified Banner in storage.
     */
    public function update(Request $request, Banner $banner)
    {
            $banner = $this->service->update($request,$banner);

            if ($banner instanceof JsonResponse) {
                return $banner;
            }

            return $this->returnJSON($banner, __('message.Banner has been updated successfully'));

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $banner = $this->service->destroy($id);

        if ($banner instanceof JsonResponse) {
            return $banner;
        }

        return $this->returnJSON($banner, __('message.Banner has been deleted successfully'));
    }

    public function restore($id)
    {
        $banner = $this->service->restore($id);

        if ($banner instanceof JsonResponse) {
            return $banner;
        }

        return $this->returnJSON($banner, __('message.Banner has been restored successfully'));
    }

    public function forceDelete($id)
    {
        $banner = $this->service->forceDelete($id);

        if ($banner instanceof JsonResponse) {
            return $banner;
        }

        return $this->returnJSON($banner, __('message.Banner has been force deleted successfully'));
    }

    public function bulkDelete(Request $request)
    {

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:categories,id',
        ]);


        $banner = $this->service->bulkDelete($request->ids);

        if ($banner instanceof JsonResponse) {
            return $banner;
        }

        return $this->returnJSON($banner, __('message.Banner has been deleted successfully.'));
    }
}
