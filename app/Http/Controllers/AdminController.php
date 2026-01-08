<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Client;


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
    public function edit_is_approved(Request $request,$id)
    {
$validated=$request->validate([
    'is_approved'=>'required|boolean'
]);
$approved=Client::findOrFail($id);
$approved->is_approved=$validated['is_approved'];
$approved->save();
 return response()->json([
        'message' => 'Approval status updated successfully',
        'data' => $approved
    ]);

    }

      public function edit_Role(Request $request,$id)
    {
$validated=$request->validate([
    'role'=>'required|in:owner,Tenant'
]);
$client=Client::findOrFail($id);
$client->role=$validated['role'];
$client->save();
 return response()->json([
        'message' => 'role  updated successfully',
        'data' => $client
    ]);

    }

}
