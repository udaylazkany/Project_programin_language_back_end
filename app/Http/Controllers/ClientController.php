<?php

namespace App\Http\Controllers;

use Hash;
use Illuminate\Http\Request;
use App\Models\Client;

class ClientController extends Controller
{
    public function register(Request $request)
    {
       \Log::info('Login test', $request->all());
      $validated= $request->validate([
            'firstName'=>'required|string',
            'lastName'=>'required|string',
            'phoneNumber'=>'required|size:10|unique:clients,phoneNumber',
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
    public function getAllClients()
{
    $clients = Client::all();

    return response()->json([
        'status' => 200,
        'message' => 'All clients fetched successfully',
        'data' => $clients
    ], 200);
}
    public function login(Request $request)
    {
      \Log::info('Login test', $request->all());
      $validated=$request->validate(['phoneNumber'=>'required|string|size:10',
    'password'=>'required'
    ]);
    $client=Client::where('phoneNumber',$request->phoneNumber)->first();
    if(!$client|| ! Hash::check($validated['password'],$client->password))
    {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }
    $token=$client->createToken('auth_token')->plainTextToken;
    return response()->json(['message'=>'login successful',
    "data"=>$client,
            'token'=>$token,
            
            'status'=>201],201);
    }public function logout(Request $request)
{
   
    $request->user()->currentAccessToken()->delete();

    return response()->json([
        'status' => true,
        'message' => 'Logged out successfully'
    ], 201);
}
////////////////////////////////////////////////////////////
    public function index()
    {
        $clients = Client::all();
        return view('clients', compact('clients'));
    }

    public function toggleStatus($id)
    {
        $client = Client::findOrFail($id);
        $client->is_approved = $client->is_approved ? 0 : 1;
        $client->save();

        return back();
    }

    public function toggleRole($id)
    {
        $client = Client::findOrFail($id);
        $client->role = $client->role === 'owner' ? 'Tenant' : 'owner';
        $client->save();

        return back();
    }
}


