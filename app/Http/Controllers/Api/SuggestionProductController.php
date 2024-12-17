<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SuggestionProduct;
use Illuminate\Http\Request;

class SuggestionProductController extends Controller
{
          public function index()
          {
                    $suggestion = SuggestionProduct::all();
                    return response()->json($suggestion);
          }


          public function store(Request $request)
          {
                    $data = $request->validate([
                              'name' => 'required|string',
                    ]);

                    try {

                              $client = auth()->user();

                              // First, create the Banner without the image
                              $suggestionProducts = SuggestionProduct::create([
                                        'name' => $data['name'],
                                        'client_id' => $client->id,
                                        'business_id' => $client->contact->business_id
                              ]);

                              return $this->returnJSON($suggestionProducts, __('message.suggestion Products has been created successfully'));


                    } catch (\Exception $e) {
                              return $this->handleException($e, __('message.Error happened while storing suggestion Products'));
                    }
          }

}