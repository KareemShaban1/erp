<?php

use App\Http\Controllers\API\CategoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\Transaction;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });


// Route::get('/helpdevelopertosavedata',function(){

//     // DB::transaction(function () {
//         \DB::table('transactions')->delete();

//         $handle = fopen(database_path('seeds/file.csv'), 'r');

//         try {
//             //code...

//         if ($handle) {
//             $headers = fgetcsv($handle);

//             while (($data = fgetcsv($handle)) !== false) {
//                 $record = new Transaction;
//                 foreach ($headers as $index => $header) {
//                     // var_dump($data[$index] );
//                     // ? $data[$index] : "1"
//                     $sacand =$header == 'recur_stopped_on' || $header == 'business_id' ||$header == 'repair_job_sheet_id' || $header == 'pay_term_type' || $header == 'recur_interval_type' || $header == 'packing_charge_type' || $header == 'import_time' || $header == 'res_order_status' || $header == 'adjustment_type' || $header == 'discount_type' || $header == 'repair_completed_on' || $header == 'repair_due_date' ? null : 0;
//                     if(  $header == 'transaction_date'  )
//                     $data[$index] =$data[$index] ?  Carbon::createFromFormat('m/d/Y H:i', $data[$index])->toDateTimeString(): null;
//                     // date_format($data[$index],"Y/m/d")
//                     $record->{$header} = $data[$index] ? $data[$index] : $sacand  ;

//                 }

//                 // dd('dd');
//                 // return $record;
//                 $record->save();
//             }

//             fclose($handle);
//         }
//     // });

// return 1;
// } catch (\Throwable $th) {
//     //throw $th;
//     return $th->getMessage();
// }
// });

