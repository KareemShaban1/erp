<?php

namespace App\Services\ApplicationDashboard;

use App\Events\TransactionPaymentAdded;
use App\Http\Resources\OrderCancellation\OrderCancellationResource;
use App\Models\BusinessLocation;
use App\Models\Order;
use App\Models\OrderCancellation;
use App\Models\OrderTracking;
use App\Models\Transaction;
use App\Models\TransactionPayment;
use App\Models\TransactionSellLine;
use App\Notifications\OrderCancellationCreatedNotification;
use App\Services\API\CancellationTransferQuantityService;
use App\Services\BaseService;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class OrderService extends BaseService
{
          /**
           * All Utils instance.
           *
           */
          protected $moduleUtil;
          protected $orderService;
          protected $productUtil;
          protected $transactionUtil;
          protected $transferQuantityService;

          public function __construct(
                    ModuleUtil $moduleUtil,
                    ProductUtil $productUtil,
                    OrderService $orderService,
                    CancellationTransferQuantityService $transferQuantityService,
                    TransactionUtil $transactionUtil

          ) {
                    $this->moduleUtil = $moduleUtil;
                    $this->orderService = $orderService;
                    $this->productUtil = $productUtil;
                    $this->transactionUtil = $transactionUtil;
                    $this->transferQuantityService = $transferQuantityService;
          }
         
          public function makeSalePayment($salePaymentData)
          {
              try {
                  $business_id = $salePaymentData['business_id'];
                  $transaction_id = $salePaymentData['transaction_id'];
                  $transaction = Transaction::where('business_id', $business_id)->with(['contact'])->findOrFail($transaction_id);
      
                  $location = BusinessLocation::find($salePaymentData['business_location_id']);
                  $transaction_before = $transaction->replicate();
      
                  if ($transaction->payment_status != 'paid') {
                      // $inputs = $request->only(['amount', 'method', 'note', 'card_number', 'card_holder_name',
                      // 'card_transaction_number', 'card_type', 'card_month', 'card_year', 'card_security',
                      // 'cheque_number', 'bank_account_number']);
                      $salePaymentData['paid_on'] = Carbon::now();
                      $salePaymentData['transaction_id'] = $transaction->id;
                      $salePaymentData['amount'] = $this->transactionUtil->num_uf($salePaymentData['amount']);
                      // $inputs['amount'] = $this->transactionUtil->num_uf($inputs['amount']);
                      $salePaymentData['created_by'] = 1;
                      $salePaymentData['payment_for'] = $transaction->contact_id;
      
                      // $salePaymentData['account_id'] =2;
                      if (!empty($location->default_payment_accounts)) {
                          $default_payment_accounts = json_decode(
                              $location->default_payment_accounts,
                              true
                          );
                          // Check for cash account and set account_id
                          if (!empty($default_payment_accounts['cash']['is_enabled']) && !empty($default_payment_accounts['cash']['account'])) {
                              $salePaymentData['account_id'] = $default_payment_accounts['cash']['account'] ?? 1;
                          }
                      }
      
      
                      $prefix_type = 'purchase_payment';
                      if (in_array($transaction->type, ['sell', 'sell_return'])) {
                          $prefix_type = 'sell_payment';
                      } elseif (in_array($transaction->type, ['expense', 'expense_refund'])) {
                          $prefix_type = 'expense_payment';
                      }
      
                      DB::beginTransaction();
      
                      $ref_count = $this->transactionUtil->setAndGetReferenceCount($prefix_type);
                      //Generate reference number
                      $salePaymentData['payment_ref_no'] = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);
      
                      //Pay from advance balance
                      $payment_amount = $salePaymentData['amount'];
                      // $contact_balance = !empty($transaction->contact) ? $transaction->contact->balance : 0;
                      // if ($inputs['method'] == 'advance' && $inputs['amount'] > $contact_balance) {
                      //     throw new AdvanceBalanceNotAvailable(__('lang_v1.required_advance_balance_not_available'));
                      // }
      
                      Log::info('salePaymentData', [$salePaymentData]);
      
                      if (!empty($salePaymentData['amount'])) {
                          $tp = TransactionPayment::create($salePaymentData);
                          $salePaymentData['transaction_type'] = $transaction->type;
                          event(new TransactionPaymentAdded($tp, $salePaymentData));
                      }
      
                      //update payment status
                      $payment_status = $this->transactionUtil->updatePaymentStatus($transaction_id, $transaction->final_total);
                      $transaction->payment_status = $payment_status;
      
                      $this->transactionUtil->activityLog($transaction, 'payment_edited', $transaction_before);
      
                      DB::commit();
                  }
      
                  $output = [
                      'success' => true,
                      'msg' => __('purchase.payment_added_success')
                  ];
              } catch (\Exception $e) {
                  DB::rollBack();
                  $msg = __('messages.something_went_wrong');
      
      
                  $output = [
                      'success' => false,
                      'msg' => $msg
                  ];
              }
      
              return redirect()->back()->with(['status' => $output]);
          }
}