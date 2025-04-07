<?php

namespace App\Http\Controllers\ApplicationDashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Models\Client;
use App\Models\Transaction;
use App\Models\User;
use App\Services\API\ClientService;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\Facades\DataTables;

class ClientActivityLogController extends Controller
{
          protected $moduleUtil;

          protected $transactionUtil;

          public function __construct(
                    ModuleUtil $moduleUtil,
                    TransactionUtil $transactionUtil

          ) {
                    $this->moduleUtil = $moduleUtil;
                    $this->transactionUtil = $transactionUtil;

          }

          public function activityLog()
          {

                    try {
                              $business_id = request()->session()->get('user.business_id');
                              $transaction_types = [
                                        'client' => __('report.client'),

                              ];

                              if (request()->ajax()) {
                                        $activities = Activity::with(['subject'])

                                                  ->leftJoin('users as u', 'u.id', '=', 'activity_log.causer_id')
                                                  ->leftJoin('clients as c', 'c.id', '=', 'activity_log.causer_id')
                                                  ->leftJoin('contacts as contact', 'contact.id', '=', 'c.contact_id')
                                                  // ->where('activity_log.business_id', $business_id)
                                                  ->where('subject_type', 'App\Models\Client')
                                                  ->select(
                                                            'activity_log.*',
                                                            DB::raw("
                          CASE 
                              WHEN u.id IS NOT NULL THEN CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))
                              WHEN c.id IS NOT NULL THEN contact.name
                              ELSE 'Unknown'
                          END as created_by
                      ")
                                                  );

                                        if (!empty(request()->start_date) && !empty(request()->end_date)) {
                                                  $start = request()->start_date;
                                                  $end = request()->end_date;
                                                  $activities->whereDate('activity_log.created_at', '>=', $start)
                                                            ->whereDate('activity_log.created_at', '<=', $end);
                                        }

                                        // if (!empty(request()->user_id)) {
                                        //           $activities->where('causer_id', request()->user_id);
                                        // }

                                        // $subject_type = request()->subject_type;
                                        // if (!empty($subject_type)) {
                                        //           if ($subject_type == 'contact') {
                                        //                     $activities->where('subject_type', 'App\Models\Contact');
                                        //           } else if ($subject_type == 'user') {
                                        //                     $activities->where('subject_type', 'App\Models\User');
                                        //           } else if ($subject_type == 'order') {
                                        //                     $activities->where('subject_type', 'App\Models\Order');
                                        //           } else if (
                                        //                     in_array($subject_type, [
                                        //                               'sell',
                                        //                               'purchase',
                                        //                               'sales_order',
                                        //                               'purchase_order',
                                        //                               'sell_return',
                                        //                               'purchase_return',
                                        //                               'sell_transfer',
                                        //                               'expense',
                                        //                               'purchase_order'
                                        //                     ])
                                        //           ) {
                                        //                     $activities->where('subject_type', 'App\Models\Transaction');
                                        //                     $activities->whereHasMorph('subject', Transaction::class, function ($q) use ($subject_type) {
                                        //                               $q->where('type', $subject_type);
                                        //                     });
                                        //           }
                                        // }

                                        $sell_statuses = Transaction::sell_statuses();
                                        $sales_order_statuses = Transaction::sales_order_statuses(true);
                                        $purchase_statuses = $this->transactionUtil->orderStatuses();
                                        $shipping_statuses = $this->transactionUtil->shipping_statuses();

                                        $statuses = array_merge($sell_statuses, $sales_order_statuses, $purchase_statuses);
                                        return DataTables::of($activities)
                                                  ->editColumn('created_at', '{{@format_datetime($created_at)}}')
                                                  ->addColumn('subject_type', function ($row) use ($transaction_types) {
                                                            $subject_type = '';
                                                            if ($row->subject_type == 'App\Models\Contact') {
                                                                      $subject_type = __('contact.contact');
                                                            } else if ($row->subject_type == 'App\Models\Client') {
                                                                      $subject_type = __('report.client');
                                                            } else if ($row->subject_type == 'App\Models\User') {
                                                                      $subject_type = __('report.user');
                                                            } else if ($row->subject_type == 'App\Models\Order') {
                                                                      $subject_type = __('report.order');
                                                            } else if ($row->subject_type == 'App\Models\Transaction' && !empty($row->subject->type)) {
                                                                      $subject_type = isset($transaction_types[$row->subject->type]) ? $transaction_types[$row->subject->type] : '';
                                                            } elseif (($row->subject_type == 'App\Models\TransactionPayment')) {
                                                                      $subject_type = __('lang_v1.payment');
                                                            }


                                                            return $subject_type;
                                                  })
                                                  ->addColumn('note', function ($row) use ($statuses, $shipping_statuses) {
                                                            $html = '';
                                                            if (!empty($row->subject->ref_no)) {
                                                                      $html .= __('purchase.ref_no') . ': ' . $row->subject->ref_no . '<br>';
                                                            }
                                                            if (!empty($row->subject->invoice_no)) {
                                                                      $html .= __('sale.invoice_no') . ': ' . $row->subject->invoice_no . '<br>';
                                                            }
                                                            if ($row->subject_type == 'App\Models\Transaction' && !empty($row->subject) && in_array($row->subject->type, ['sell', 'purchase'])) {
                                                                      $html .= view('sale_pos.partials.activity_row', ['activity' => $row, 'statuses' => $statuses, 'shipping_statuses' => $shipping_statuses])->render();
                                                            } else {
                                                                      $update_note = $row->getExtraProperty('update_note');
                                                                      if (!empty($update_note) && !is_array($update_note)) {
                                                                                $html .= $update_note;
                                                                      }
                                                            }

                                                            if ($row->description == 'contact_deleted') {
                                                                      $html .= $row->getExtraProperty('supplier_business_name') ?? '';
                                                                      $html .= '<br>';
                                                            }

                                                            if (!empty($row->getExtraProperty('name'))) {
                                                                      $html .= __('user.name') . ': ' . $row->getExtraProperty('name') . '<br>';
                                                            }

                                                            if (!empty($row->getExtraProperty('id'))) {
                                                                      $html .= 'id: ' . $row->getExtraProperty('id') . '<br>';
                                                            }
                                                            if (!empty($row->getExtraProperty('invoice_no'))) {
                                                                      $html .= __('sale.invoice_no') . ': ' . $row->getExtraProperty('invoice_no');
                                                            }

                                                            if (!empty($row->getExtraProperty('ref_no'))) {
                                                                      $html .= __('purchase.ref_no') . ': ' . $row->getExtraProperty('ref_no');
                                                            }

                                                            return $html;
                                                  })
                                                  ->filterColumn('created_by', function ($query, $keyword) {
                                                            $query->whereRaw("CONCAT(COALESCE(surname, ''), ' ', COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) like ?", ["%{$keyword}%"]);
                                                  })
                                                  ->editColumn('description', function ($row) {
                                                            return __('lang_v1.' . $row->description);
                                                  })
                                                  ->editColumn('properties', function ($row) {
                                                            return $row->properties;
                                                  })
                                                  ->addColumn('actions', function ($row) {
                                                            $buttons = '<form action="' . route('client_logs.delete', $row->id) . '" method="POST" style="display:inline;" class="delete-activity-form">';
                                                            $buttons .= csrf_field();
                                                            $buttons .= method_field('DELETE');
                                                            $buttons .= '<button type="submit" class="btn btn-xs btn-danger delete_notifications_button">
                                                                            <i class="glyphicon glyphicon-trash"></i> ' . __("messages.delete") . '
                                                                         </button>';
                                                            $buttons .= '</form>';
                                                        
                                                            return $buttons;
                                                        })
                                                        
                                                  ->rawColumns(['note','actions'])
                                                  ->make(true);
                              }

                              $users = User::allUsersDropdown($business_id, false);

                              return view('applicationDashboard.pages.clients.activity_log')->with(compact('users', 'transaction_types'));

                    } catch (\Exception $e) {
                              Log::error('Error in Activity Log:', [
                                        'error_message' => $e->getMessage(),
                                        'file' => $e->getFile(),
                                        'line' => $e->getLine(),
                                        'trace' => $e->getTraceAsString()
                              ]);
                              return response()->json(['error' => 'An error occurred. Please try again later.'], 500);
                    }
          }

          public function deleteActivity(Request $request)
          {
              try {
                  $activityId = $request->id;
          
                  // Optional: Check if user has permission
                  // if (!auth()->user()->can('delete_activity_log')) {
                  //     return response()->json(['error' => 'Unauthorized'], 403);
                  // }
          
                  $activity = Activity::findOrFail($activityId);
                  $activity->delete();
          
                  return redirect()->back()->with(
                      'success',
                      __('lang_v1.activity_log_deleted_successfully')
                  );
              } catch (\Exception $e) {
                  Log::error('Error deleting activity log:', [
                      'error_message' => $e->getMessage(),
                      'file' => $e->getFile(),
                      'line' => $e->getLine(),
                      'trace' => $e->getTraceAsString()
                  ]);
                  return response()->json(['error' => __('lang_v1.something_went_wrong')], 500);
              }
          }

}