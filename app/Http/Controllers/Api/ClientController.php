<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Models\Client;
use App\Services\API\ClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{


    protected $service;

    public function __construct(ClientService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the categories.
     */
    public function index(Request $request)
    {
        $clients = $this->service->list($request);

        if ($clients instanceof JsonResponse) {
            return $clients;
        }

        return $clients->additional([
            'code' => 200,
            'status' => 'success',
            'message' =>  __('message.Categories have been retrieved successfully'),
        ]);
    }

    /**
     * Store a newly created Client in storage.
     */
    public function store(Request $request)
    {
            $data = $request->validated();
            $client = $this->service->store( $data);

            if ($client instanceof JsonResponse) {
                return $client;
            }

            return $this->returnJSON($client, __('message.Client has been created successfully'));
    }

    /**
     * Display the specified Client.
     */
    public function show($id)
    {

        $client = $this->service->show($id);

        if ($client instanceof JsonResponse) {
            return $client;
        }

        return $this->returnJSON($client, __('message.Client has been created successfully'));

    }

    public function getAuthClient()
    {

        $client = $this->service->getAuthClient();

        if ($client instanceof JsonResponse) {
            return $client;
        }

        return $this->returnJSON($client, __('message.Client has been retrieved successfully'));

    }

    /**
     * Update the specified Client in storage.
     */
    public function update(Request $request, Client $client)
    {
            $client = $this->service->update($request,$client);

            if ($client instanceof JsonResponse) {
                return $client;
            }

            return $this->returnJSON($client, __('message.Client has been updated successfully'));

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $client = $this->service->destroy($id);

        if ($client instanceof JsonResponse) {
            return $client;
        }

        return $this->returnJSON($client, __('message.Client has been deleted successfully'));
    }

    public function restore($id)
    {
        $client = $this->service->restore($id);

        if ($client instanceof JsonResponse) {
            return $client;
        }

        return $this->returnJSON($client, __('message.Client has been restored successfully'));
    }

    public function forceDelete($id)
    {
        $client = $this->service->forceDelete($id);

        if ($client instanceof JsonResponse) {
            return $client;
        }

        return $this->returnJSON($client, __('message.Client has been force deleted successfully'));
    }

    public function bulkDelete(Request $request)
    {

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:categories,id',
        ]);


        $client = $this->service->bulkDelete($request->ids);

        if ($client instanceof JsonResponse) {
            return $client;
        }

        return $this->returnJSON($client, __('message.Client has been deleted successfully.'));
    }
}
