<?php

use App\Models\Business;
use App\Utils\Util;

class OrderUtil extends Util
{
          public function addSellReturnForRefund($input, $business_id, $user_id, $uf_number = true)
          { 
      
              \Log::info('sell_return',[$input]);
              $discount = [
                      'discount_type' => $input['discount_type'] ?? 'fixed',
                      'discount_amount' => $input['discount_amount'] ?? 0
                  ];
      
              $business = Business::with(['currency'])->findOrFail($business_id);
      
              $productUtil = new \App\Utils\ProductUtil();
      
              $input['tax_id'] = $input['tax_id'] ?? null;
      
              $invoice_total = $productUtil->calculateInvoiceTotal($input['products'], $input['tax_id'], $discount, $uf_number);
      
              //Get parent sale
              $sell = Transaction::where('business_id', $business_id)
                              ->with(['sell_lines', 'sell_lines.sub_unit'])
                              ->findOrFail($input['transaction_id']);
      
              //Check if any sell return exists for the sale
              // $sell_return = Transaction::where('business_id', $business_id)
              //         ->where('type', 'sell_return')
              //         ->where('return_parent_id', $sell->id)
              //         ->first();
      
              $sell_return_data = [
                  'invoice_no' => $input['invoice_no'] ?? null,
                  'discount_type' => $discount['discount_type'],
                  'discount_amount' => $uf_number ? $this->num_uf($discount['discount_amount']) : $discount['discount_amount'],
                  'tax_id' => $input['tax_id'],
                  'tax_amount' => $invoice_total['tax'] ?? '0.0000',
                  'total_before_tax' => $invoice_total['total_before_tax'],
                  'final_total' => $invoice_total['final_total'],
                  'transaction_date'=>$input['transaction_date']
              ];
      
              // if (!empty($input['transaction_date'])) {
              //     $sell_return_data['transaction_date'] = $uf_number ? $this->uf_date($input['transaction_date'], true) : $input['transaction_date'];
              // }
              
              //Generate reference number
              if (empty($sell_return_data['invoice_no'])) {
                  //Update reference count
                  $ref_count = $this->setAndGetReferenceCount('sell_return', $business_id);
                  $sell_return_data['invoice_no'] = $this->generateReferenceNumber('sell_return', $ref_count, $business_id);
              }
      
              // if (empty($sell_return)) {
                  $sell_return_data['transaction_date'] = $sell_return_data['transaction_date'] ?? \Carbon::now();
                  $sell_return_data['business_id'] = $business_id;
                  $sell_return_data['order_id'] = $input['order_id'] ?? null;
                  $sell_return_data['location_id'] = $sell->location_id;
                  $sell_return_data['contact_id'] = $sell->contact_id;
                  $sell_return_data['customer_group_id'] = $sell->customer_group_id;
                  $sell_return_data['type'] = 'sell_return';
                  $sell_return_data['status'] = 'final';
                  $sell_return_data['created_by'] = $user_id;
                  $sell_return_data['return_parent_id'] = $sell->id;
                  $sell_return = Transaction::create($sell_return_data);
                  $this->activityLog($sell_return, 'added');
              // } else {
              //     $sell_return_data['invoice_no'] = $sell_return_data['invoice_no'] ?? $sell_return->invoice_no;
              //     $sell_return_before = $sell_return->replicate();
              //     $sell_return->update($sell_return_data);
      
              //     $this->activityLog($sell_return, 'edited', $sell_return_before);
              // }
      
      
              //Update payment status
              $this->updatePaymentStatus($sell_return->id, $sell_return->final_total);
      
              //Update quantity returned in sell line
              $returns = [];
              $product_lines = $input['products'];
              foreach ($product_lines as $product_line) {
                  $returns[$product_line['sell_line_id']] = $uf_number ? $this->num_uf($product_line['quantity']) : $product_line['quantity'];
              }
              foreach ($sell->sell_lines as $sell_line) {
                  if (array_key_exists($sell_line->id, $returns)) {
                      $multiplier = 1;
                      if (!empty($sell_line->sub_unit)) {
                          $multiplier = $sell_line->sub_unit->base_unit_multiplier;
                      }
      
                      $quantity = $returns[$sell_line->id] * $multiplier;
      
                      $quantity_before = $sell_line->quantity_returned;
      
                      $sell_line->quantity_returned = $quantity;
                      $sell_line->save();
      
                      //update quantity sold in corresponding purchase lines
                      $this->updateQuantitySoldFromSellLine($sell_line, $quantity, $quantity_before, false);
      
                      // Update quantity in variation location details
                      $productUtil->updateProductQuantity($sell_return->location_id, $sell_line->product_id, $sell_line->variation_id, $quantity, $quantity_before, null, false);
                  
                  
                  }
              }
              
      
              return $sell_return;     
          }
      
      
          protected function makeSalePayment($salePaymentData)
          {
              try {
                  $business_id = $salePaymentData['business_id'];
                  $transaction_id = $salePaymentData['transaction_id'];
                  $transaction = Transaction::where('business_id', $business_id)->with(['contact'])->findOrFail($transaction_id);
      
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
                              $salePaymentData['business_location_id']->default_payment_accounts, true
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
      
                  $output = ['success' => true,
                                  'msg' => __('purchase.payment_added_success')
                              ];
              } catch (\Exception $e) {
                  DB::rollBack();
                  $msg = __('messages.something_went_wrong');
      
      
                  $output = ['success' => false,
                                'msg' => $msg
                            ];
              }
      
              return redirect()->back()->with(['status' => $output]);
          } 
      
      
          
      
          public function addSellReturnForCancellation($input, $business_id, $user_id, $uf_number = true)
          { 
              $discount = [
                      'discount_type' => $input['discount_type'] ?? 'fixed',
                      'discount_amount' => $input['discount_amount'] ?? 0
                  ];
      
              $business = Business::with(['currency'])->findOrFail($business_id);
      
              $productUtil = new \App\Utils\ProductUtil();
      
              $input['tax_id'] = $input['tax_id'] ?? null;
      
              $invoice_total = $productUtil->calculateInvoiceTotal($input['products'], $input['tax_id'], $discount, $uf_number);
      
              //Get parent sale
              $sell = Transaction::where('business_id', $business_id)
                              ->with(['sell_lines', 'sell_lines.sub_unit'])
                              ->findOrFail($input['transaction_id']);
      
              //Check if any sell return exists for the sale
              $sell_return = Transaction::where('business_id', $business_id)
                      ->where('type', 'sell_return')
                      ->where('return_parent_id', $sell->id)
                      ->first();
      
                      \Log::info($invoice_total);
              $sell_return_data = [
                  'invoice_no' => $input['invoice_no'] ?? null,
                  'order_id' => $input['order_id'] ?? null,
                  'discount_type' => $discount['discount_type'],
                  'discount_amount' => $uf_number ? $this->num_uf($discount['discount_amount']) : $discount['discount_amount'],
                  'tax_id' => $input['tax_id'],
                  'tax_amount' => $invoice_total['tax'] ?? '0.0000',
                  'total_before_tax' => $invoice_total['total_before_tax'],
                  'final_total' => $invoice_total['final_total']
              ];
      
              // if (!empty($input['transaction_date'])) {
              //     $sell_return_data['transaction_date'] = $uf_number ? $this->uf_date($input['transaction_date'], true) : $input['transaction_date'];
              // }
              
              //Generate reference number
              if (empty($sell_return_data['invoice_no']) && empty($sell_return)) {
                  //Update reference count
                  $ref_count = $this->setAndGetReferenceCount('sell_return', $business_id);
                  $sell_return_data['invoice_no'] = $this->generateReferenceNumber('sell_return', $ref_count, $business_id);
              }
      
              if (empty($sell_return)) {
                  $sell_return_data['transaction_date'] = \Carbon::now();
                  $sell_return_data['business_id'] = $business_id;
                  $sell_return_data['location_id'] = $sell->location_id;
                  $sell_return_data['contact_id'] = $sell->contact_id;
                  $sell_return_data['customer_group_id'] = $sell->customer_group_id;
                  $sell_return_data['type'] = 'sell_return';
                  $sell_return_data['status'] = 'final';
                  $sell_return_data['created_by'] = $user_id;
                  $sell_return_data['return_parent_id'] = $sell->id;
                  Log::info('sell_return_data',[$sell_return_data]);
                  $sell_return = Transaction::create($sell_return_data);
      
                  $this->activityLog($sell_return, 'added');
              } else {
                  $sell_return_data['invoice_no'] = $sell_return_data['invoice_no'] ?? $sell_return->invoice_no;
                  $sell_return_before = $sell_return->replicate();
                  
                  $sell_return->update($sell_return_data);
      
                  $this->activityLog($sell_return, 'edited', $sell_return_before);
              }
      
              if ($business->enable_rp == 1 && !empty($sell->rp_earned)) {
                  $is_reward_expired = $this->isRewardExpired($sell->transaction_date, $business_id);
                  if (!$is_reward_expired) {
                      $diff = $sell->final_total - $sell_return->final_total;
                      $new_reward_point = $this->calculateRewardPoints($business_id, $diff);
                      $this->updateCustomerRewardPoints($sell->contact_id, $new_reward_point, $sell->rp_earned);
      
                      $sell->rp_earned = $new_reward_point;
                      $sell->save();
                  }
              }
      
              //Update payment status
              $this->updatePaymentStatus($sell_return->id, $sell_return->final_total);
      
              //Update quantity returned in sell line
              $returns = [];
              $product_lines = $input['products'];
              foreach ($product_lines as $product_line) {
                  $returns[$product_line['sell_line_id']] = $uf_number ? $this->num_uf($product_line['quantity']) : $product_line['quantity'];
              }
              foreach ($sell->sell_lines as $sell_line) {
                  if (array_key_exists($sell_line->id, $returns)) {
                      $multiplier = 1;
                      if (!empty($sell_line->sub_unit)) {
                          $multiplier = $sell_line->sub_unit->base_unit_multiplier;
                      }
      
                      $quantity = $returns[$sell_line->id] * $multiplier;
      
                      $quantity_before = $sell_line->quantity_returned;
      
                      $sell_line->quantity_returned = $quantity;
                      $sell_line->save();
      
                      //update quantity sold in corresponding purchase lines
                      $this->updateQuantitySoldFromSellLine($sell_line, $quantity, $quantity_before, false);
      
                      // Update quantity in variation location details
                      // $productUtil->updateProductQuantity($sell_return->location_id, $sell_line->product_id, $sell_line->variation_id, $quantity, $quantity_before, null, false);
                  }
              }
      
              return $sell_return;     
          }
}