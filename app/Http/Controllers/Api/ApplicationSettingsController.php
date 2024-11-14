<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApplicationSetting\StoreApplicationSettingRequest;
use App\Http\Requests\ApplicationSetting\UpdateApplicationSettingRequest;
use App\Models\ApplicationSetting;
use App\Services\API\ApplicationSettingService;
use App\Services\API\ApplicationSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApplicationSettingsController extends Controller
{


    protected $service;

    public function __construct(ApplicationSettingsService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the Application Settings.
     */
    public function index(Request $request)
    {
        Log::info('Reached CartController index method');

        $ApplicationSettings = $this->service->list($request);

        if ($ApplicationSettings instanceof JsonResponse) {
            return $ApplicationSettings;
        }

        return $ApplicationSettings->additional([
            'code' => 200,
            'status' => 'success',
            'message' =>  __('message.Application Settings have been retrieved successfully'),
        ]);
    }

   
    /**
     * Display the specified ApplicationSetting.
     */
    public function show($id)
    {

        $ApplicationSetting = $this->service->show($id);

        if ($ApplicationSetting instanceof JsonResponse) {
            return $ApplicationSetting;
        }

        return $this->returnJSON($ApplicationSetting, __('message.ApplicationSetting has been created successfully'));

    }

   
}
