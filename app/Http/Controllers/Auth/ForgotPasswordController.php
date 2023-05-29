<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgetPasswordRequest;
use Illuminate\Http\Request;
use App\Notifications\ResetPasswordNotification;
use App\Models\User;
use App\Notifications\ResetPasswordVerificationNotification;

class ForgotPasswordController extends Controller
{
    public function forgetPassword(ForgetPasswordRequest $request){
        $input=$request->only('email');
        $user=User::where('email',$input)->first();
        $user->notify(new ResetPasswordVerificationNotification());
        $success['success']=true;
        return response()->json($success,200);

    }
}
