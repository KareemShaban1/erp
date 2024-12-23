<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Authenticated;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Utils\BusinessUtil;
use App\Models\Business;

class RefreshSessionData
{
    /**
     * Handle the event.
     *
     * @param  \Illuminate\Auth\Events\Authenticated  $event
     * @return void
     */
    public function handle(Authenticated $event)
    {
        $user = Auth::user();
        $business_util = new BusinessUtil;

        $session_data = [
            'id' => $user->id,
            'surname' => $user->surname,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'business_id' => $user->business_id,
            'language' => $user->language,
        ];

        $business = Business::findOrFail($user->business_id);

        $currency = $business->currency;
        $currency_data = [
            'id' => $currency->id,
            'code' => $currency->code,
            'symbol' => $currency->symbol,
            'thousand_separator' => $currency->thousand_separator,
            'decimal_separator' => $currency->decimal_separator,
        ];

        // Refresh session data
        Session::put('user', $session_data);
        Session::put('business', $business);
        Session::put('currency', $currency_data);

        // Set the current financial year in the session
        $financial_year = $business_util->getCurrentFinancialYear($business->id);
        Session::put('financial_year', $financial_year);
    }
}
