<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use Illuminate\Http\Request;


class ApartmentController extends Controller
{
    public function showAll()
    {
        return response()->json(["data"=> Apartment::all()]);
    }
    public function showOne($id)
{
    $apartment = Apartment::find($id);

    if (!$apartment) {
        return response()->json(['message' => 'Apartment not found'], 404);
    }

    return response()->json($apartment);
}
}
