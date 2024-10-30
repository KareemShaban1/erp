<?php

namespace App\Services\API;

use App\Http\Resources\Client\ClientCollection;
use App\Http\Resources\Client\ClientResource;
use App\Models\Client;
use App\Services\BaseService;
use App\Traits\HelperTrait;
use App\Traits\UploadFileTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientService extends BaseService
{
    use UploadFileTrait, HelperTrait;
    /**
     * Get all clients with filters and pagination for DataTables.
     */
    public function list(Request $request)
    {

        try {

            $query = Client::query();

            $query = $this->withTrashed($query, $request);

            $clients = $this->withPagination($query, $request);

            return (new ClientCollection($clients))
            ->withFullData(!($request->full_data == 'false'));


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while listing clients'));
        }
    }

    public function show($id) {

        try {
            $client = Client::businessId()->find($id);

            if(!$client) {
                return null;
            }
            return $client;


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while showing Client'));
        }
    }


    public function getAuthClient() {

        try {

            $client = Client::businessId()->find(Auth::user()->id);

            if(!$client) {
                return null;
            }
            return new ClientResource($client);


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while showing Client'));
        }
    }

    /**
     * Create a new Client.
     */
    public function store($data)
    {

        try {

        // First, create the Client without the image
        $client = Client::create($data);

        // Handle the main image and gallery uploads in a single helper function
            // $this->handleImages($request, 'image', 'Client', $client->id, $fileUploader);
            // $this->handleImages($request, 'gallery', 'Client', $client->id, $fileUploader);

        // Return the created Client
        return new ClientResource($client);


    } catch (\Exception $e) {
        return $this->handleException($e, __('message.Error happened while storing Client'));
    }
    }

    /**
     * Update the specified Client.
     */
    public function update($request,$client)
    {

        try {

        // Validate the request data
        $data = $request->validated();

        $client->update($data);

        return new ClientResource($client);


    } catch (\Exception $e) {
        return $this->handleException($e, __('message.Error happened while updating Client'));
    }
    }

    public function destroy($id)
    {
        try {

            $client = Client::find($id);

            if(!$client) {
                return null;
            }
            $client->delete();
            return $client;


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while deleting Client'));
        }
    }

    public function restore($id)
    {
        try {
            $client = Client::withTrashed()->findOrFail($id);
            $client->restore();
            return new ClientResource($client);
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while restoring Client'));
        }
    }

    public function forceDelete($id)
    {
        try {
            $client = Client::withTrashed()
                ->findOrFail($id);

            $client->forceDelete();
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while force deleting Client'));
        }
    }


    public function bulkDelete(mixed $ids)
    {
        try {
            $trashedRecords = Client::onlyTrashed()->whereIn('id', $ids)->get();

            if ($trashedRecords->isNotEmpty()) {
                Client::whereIn('id', $trashedRecords->pluck('id'))->forceDelete();
            }

            $nonTrashedIds = Client::whereIn('id', $ids)->get()->pluck('id');

            if ($nonTrashedIds->isNotEmpty()) {
                Client::whereIn('id', $nonTrashedIds)->delete();
            }

            return $ids;
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while deleting clients'));
        }
    }
}
