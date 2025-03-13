<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SuggestionProduct;
use App\Notifications\ProductSuggestionCreatedNotification;
use App\Utils\ModuleUtil;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class SuggestionProductController extends Controller
{
          protected $moduleUtil;

          public function __construct(
                    ModuleUtil $moduleUtil
          ) {
                    $this->moduleUtil = $moduleUtil;
          }
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
          
                  // First, create the SuggestionProduct without the image
                  $suggestionProducts = SuggestionProduct::create([
                      'name' => $data['name'],
                      'client_id' => $client->id,
                      'business_id' => $client->contact->business_id
                  ]);
          
                  // Notify admins and users about the order
                  $admins = $this->moduleUtil->get_admins($client->contact->business_id);
                  $users = $this->moduleUtil->getBusinessUsersFromClient($client->contact->business_id, $client);
          
                  \Notification::send($admins, new ProductSuggestionCreatedNotification($suggestionProducts));
                  \Notification::send($users, new ProductSuggestionCreatedNotification($suggestionProducts));
          
                  return response()->json([
                      'success' => true,
                      'message' => __('message.suggestion Products has been created successfully'),
                      'data' => $suggestionProducts
                  ], 201);
              } catch (ModelNotFoundException $e) {
                  return response()->json([
                      'success' => false,
                      'message' => __('message.Resource not found'),
                  ], 404);
              } catch (\Exception $e) {
                  \Log::error('Error in store method: ' . $e->getMessage(), ['exception' => $e]);
          
                  return response()->json([
                      'success' => false,
                      'message' => __('message.Error happened while storing suggestion Products'),
                  ], 500);
              }
          }
          
          

}