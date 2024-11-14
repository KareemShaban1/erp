<?php

namespace App\Services\API;

use App\Http\Resources\ApplicationSetting\ApplicationSettingCollection;
use App\Http\Resources\ApplicationSetting\ApplicationSettingResource;
use App\Http\Resources\ApplicationSettings\ApplicationSettingsCollection;
use App\Models\ApplicationSetting;
use App\Models\ApplicationSettings;
use App\Services\BaseService;
use App\Traits\HelperTrait;
use App\Traits\UploadFileTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApplicationSettingsService extends BaseService
{
    use UploadFileTrait, HelperTrait;
    /**
     * Get all ApplicationSettings with filters and pagination for DataTables.
     */
    public function list(Request $request)
    {

        try {

            $query = ApplicationSettings::query();

            $query = $this->withTrashed($query, $request);

            $ApplicationSettings = $this->withPagination($query, $request);

            return (new ApplicationSettingsCollection($ApplicationSettings))
            ->withFullData(!($request->full_data == 'false'));


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while listing ApplicationSettings'));
        }
    }

    public function show($id) {

        try {
            $ApplicationSetting = ApplicationSettings::find($id);

            if(!$ApplicationSetting) {
                return null;
            }
            return $ApplicationSetting;


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while showing ApplicationSetting'));
        }
    }

    
}
