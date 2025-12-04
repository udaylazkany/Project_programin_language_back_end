<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class AdminController extends Controller
{

    public function login(Request $request)
    {
        
        $credentials =$request->validate(
            ['email'=>'required|email','password'=> 'required'],
        );
        $admin=Admin::where('email',$request->email)->first();
      
         if (! $admin || ! Hash::check($credentials['password'], $admin->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
  
      
     
      $token = $admin->createToken('auth_token')->plainTextToken;

            return response()->json(['message'=>'login successful',
            'token'=>$token,
            
            'status'=>201],201);
        
    }
}
