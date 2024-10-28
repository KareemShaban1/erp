<?php

namespace App\Services\API;

use App\Http\Resources\Banner\BannerCollection;
use App\Http\Resources\Banner\BannerResource;
use App\Models\Banner;
use App\Services\BaseService;
use App\Traits\HelperTrait;
use App\Traits\UploadFileTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BannerService extends BaseService
{
    use UploadFileTrait, HelperTrait;
    /**
     * Get all Banners with filters and pagination for DataTables.
     */
    public function list(Request $request)
    {

        try {

            $query = Banner::active();

            $query = $this->withTrashed($query, $request);

            $banners = $this->withPagination($query, $request);

            return (new BannerCollection($banners))
            ->withFullData(!($request->full_data == 'false'));


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while listing Banners'));
        }
    }

    public function show($id) {

        try {
            $banner = Banner::businessId()->find($id);

            if(!$banner) {
                return null;
            }
            return $banner;


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while showing Banner'));
        }
    }

    /**
     * Create a new Banner.
     */
    public function store($data)
    {

        try {

        // First, create the Banner without the image
        $banner = Banner::create($data);
        // Return the created Banner
        return new BannerResource($banner);


    } catch (\Exception $e) {
        return $this->handleException($e, __('message.Error happened while storing Banner'));
    }
    }

    /**
     * Update the specified Banner.
     */
    public function update($request,$banner)
    {

        try {

        // Validate the request data
        $data = $request->validated();

        $banner->update($data);

        return new BannerResource($banner);


    } catch (\Exception $e) {
        return $this->handleException($e, __('message.Error happened while updating Banner'));
    }
    }

    public function destroy($id)
    {
        try {

            $banner = Banner::find($id);

            if(!$banner) {
                return null;
            }
            $banner->delete();
            return $banner;


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while deleting Banner'));
        }
    }

    public function restore($id)
    {
        try {
            $banner = Banner::withTrashed()->findOrFail($id);
            $banner->restore();
            return new BannerResource($banner);
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while restoring Banner'));
        }
    }

    public function forceDelete($id)
    {
        try {
            $banner = Banner::withTrashed()
                ->findOrFail($id);

            $banner->forceDelete();
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while force deleting Banner'));
        }
    }


    public function bulkDelete(mixed $ids)
    {
        try {
            $trashedRecords = Banner::onlyTrashed()->whereIn('id', $ids)->get();

            if ($trashedRecords->isNotEmpty()) {
                Banner::whereIn('id', $trashedRecords->pluck('id'))->forceDelete();
            }

            $nonTrashedIds = Banner::whereIn('id', $ids)->get()->pluck('id');

            if ($nonTrashedIds->isNotEmpty()) {
                Banner::whereIn('id', $nonTrashedIds)->delete();
            }

            return $ids;
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while deleting Banners'));
        }
    }
}
