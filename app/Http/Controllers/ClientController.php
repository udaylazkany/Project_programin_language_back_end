<?php

namespace App\Http\Controllers;

use Hash;
use Illuminate\Http\Request;
use App\Models\Client;

class ClientController extends Controller
{
    public function register(Request $request)
    {
      $validated= $request->validate([
            'firstName'=>'required|string',
            'lastName'=>'required|string',
            'phoneNumber'=>'required|digits:10',
            'dob'=>'required|date',
            'password'=>'required',
            'personal_id_photo'=>'required|image|mimes:jpeg,png,jpg,gif,webp',
            'personal_photo'=>'required|image|mimes:jpeg,png,jpg,gif,webp'
            
        ]);
          $path_personal_id_photo=$request->file('personal_id_photo')->store('personal_id_photo','public');
          $path_personal_photo=$request->file('personal_photo')->store('personal_photo','public');
          $client_data=Client::create([
            'firstName'=>$validated['firstName'],
            'lastName'=>$validated['lastName'],
            'phoneNumber'=>$validated['phoneNumber'],
            'dob'=>$validated['dob'],
            'password'=>Hash::make($validated['password']),
            'personal_id_photo'=>$path_personal_id_photo,
            'personal_photo'=>$path_personal_photo
          

          ]);
          $token=$client_data->createToken('clientToken')->plainTextToken;
return response()->json([
    'status' => 201,
    'message' => 'Client registered successfully',
    'data' => $client_data,
    'token'=>$token
], 201);
          

    }
}
