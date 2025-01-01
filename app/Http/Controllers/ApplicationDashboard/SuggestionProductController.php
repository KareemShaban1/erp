<?php

namespace App\Http\Controllers\ApplicationDashboard;

use App\Http\Controllers\Controller;
use App\Models\SuggestionProduct;
use Yajra\DataTables\Facades\DataTables;

class SuggestionProductController extends Controller
{

    public function index()
    {

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $suggestionProducts = SuggestionProduct::
                where('business_id', $business_id)->
                with(['client']);

            return Datatables::of($suggestionProducts)
                ->addColumn('client_name', function ($suggestionProduct) {
                    try {
                        if ($suggestionProduct->client && $suggestionProduct->client->contact) {
                            return $suggestionProduct->client->contact->name ?? 'N/A';
                        }
                        return 'N/A';
                    } catch (\Exception $e) {
                        \Log::error('Error getting client contact name: ' . $e->getMessage());
                        return 'Error';
                    }
                })
                ->addColumn('client_phone', function ($suggestionProduct) {
                    return $suggestionProduct->client->contact->mobile;
                })
                ->addColumn('client_location', function ($suggestionProduct) {
                    return $suggestionProduct->client->business_location->name;
                })
                ->make(true);
        }

        return view('applicationDashboard.pages.suggestionProducts.index');
    }

}