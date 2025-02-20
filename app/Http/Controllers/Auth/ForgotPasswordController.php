<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
class ForgotPasswordController extends Controller
{
    /**

        * Write code on Method

        *

        * @return response()

        */

    public function showForgetPasswordFormForClient()
    {

        return view('auth.forget-password');

    }



    /**

     * Write code on Method

     *

     * @return response()

     */

    public function submitForgetPasswordFormForClient(Request $request)
    {

        $request->validate([
        'email' => 'required|email|exists:clients,email_address',
        ]);

        $token = Str::random(64);
        DB::table('password_resets')->insert([
            'email' => $request->email,
            'token' => $token,
            'created_at' => Carbon::now()

        ]);


        Mail::send('auth.email.forget-password', ['token' => $token], function ($message) use ($request) {

            $message->to($request->email);

            $message->subject('Reset Password');

        });



        return back()->with('message', 'We have e-mailed your password reset link!');

    }

    /**

     * Write code on Method

     *

     * @return response()

     */

    public function showResetPasswordFormForClient($token)
    {

        return view('auth.forget-password-link', ['token' => $token]);

    }



    /**

     * Write code on Method

     *

     * @return response()

     */

    public function submitResetPasswordFormForClient(Request $request)
    {

        // dd($request->all());
        $request->validate([
            'email' => 'required|email|exists:clients,email_address',
            'password' => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required'
        ]);

        $updatePassword = DB::table('password_resets')

            ->where([

                    'email' => $request->email,

                    'token' => $request->token

                ])

            ->first();



        if (!$updatePassword) {

            return back()->withInput()->with('error', 'Invalid token!');

        }



        $client = Client::where('email_address', $request->email)

            ->update(['password' => Hash::make($request->password)]);


        DB::table('password_resets')->where(['email' => $request->email])->delete();


        return back()->with('message', 'Your password has been changed!');

        // return redirect('/login')->with('message', 'Your password has been changed!');

    }
}
