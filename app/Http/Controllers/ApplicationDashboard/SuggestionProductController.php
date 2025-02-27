<?php

namespace App\Http\Controllers\ApplicationDashboard;

use App\Http\Controllers\Controller;
use App\Models\SuggestionProduct;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;

class SuggestionProductController extends Controller
{

    public function index()
    {
        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
    
            $query = SuggestionProduct::join('clients', 'suggestion_products.client_id', '=', 'clients.id')
                ->join('contacts', 'clients.contact_id', '=', 'contacts.id')
                ->join('business_locations', 'clients.business_location_id', '=', 'business_locations.id')
                ->where('suggestion_products.business_id', $business_id)
                ->select(
                    'suggestion_products.*',
                    'contacts.name as client_name',
                    'contacts.mobile as client_phone',
                    'business_locations.name as client_location'
                )
                ->latest();
    
            // Handle date filtering
            $startDate = request()->get('start_date');
            $endDate = request()->get('end_date');
    
            if ($startDate && $endDate) {
                if ($startDate === $endDate) {
                    $query->whereDate('suggestion_products.created_at', $startDate);
                } else {
                    $endDate = Carbon::parse($endDate)->endOfDay();
                    $query->whereBetween('suggestion_products.created_at', [$startDate, $endDate]);
                }
            }
    
            return Datatables::of($query)
                ->addColumn('client_name', function ($suggestionProduct) {
                    return $suggestionProduct->client_name ?? 'N/A';
                })
                ->addColumn('client_phone', function ($suggestionProduct) {
                    return $suggestionProduct->client_phone ?? 'N/A';
                })
                ->addColumn('client_location', function ($suggestionProduct) {
                    return $suggestionProduct->client_location ?? 'N/A';
                })
                ->filterColumn('client_name', function ($query, $keyword) {
                    $query->where('contacts.name', 'LIKE', "%$keyword%");
                })
                ->filterColumn('client_phone', function ($query, $keyword) {
                    $query->where('contacts.mobile', 'LIKE', "%$keyword%");
                })
                ->filterColumn('client_location', function ($query, $keyword) {
                    $query->where('business_locations.name', 'LIKE', "%$keyword%");
                })
                ->make(true);
        }
    
        return view('applicationDashboard.pages.suggestionProducts.index');
    }
    

}