<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Models\Client;
use App\Services\API\ClientService;
use App\Utils\ModuleUtil;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientActivityLogController extends Controller
{
          protected $moduleUtil;

          public function __construct(
                    ModuleUtil $moduleUtil

          ) {
                    $this->moduleUtil = $moduleUtil;

          }

          public function logActivity(Request $request)
          {
                    $client_id = Auth::user()->id;
                    $client = Client::find($client_id);

                    $this->moduleUtil->activityLog($client, 'log_client_activity', null, [
                              'client_data' => $request->data,
                    ],true ,$client->contact->business_id);

                    return response()->json([
                              'success' => true,
                              'message' => 'client data logged successfully.'
                    ]);

          }


}