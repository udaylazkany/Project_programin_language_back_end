<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\comment;
use App\Models\contracts;

class CommentController extends Controller
{
    public function store(Request $request)
{
    $request->validate([
        'apartment_id' => 'required|exists:apartments,id',
        'tenant_id' => 'required|exists:clients,id',
        'Comment' => 'required|string',
    ]);

    $comment = Comment::create([
        'apartment_id' => $request->apartment_id,
        'tenant_id' => $request->tenant_id,
        'Comment' => $request->Comment,
    ]);

    return response()->json([
        'message' => 'Comment added successfully',
        'data' => $comment
    ], 201);
}
public function getApartmentComments($apartmentId)
{
    $comments = Comment::where('apartment_id', $apartmentId)
                       ->with('tenant') 
                       ->get();

    return response()->json($comments);
}
public function canComment($apartmentId, $tenantId)
{
    $hasContract = contracts::where('apartment_id', $apartmentId)
        ->where('tenant_id', $tenantId)
        ->exists();

    return response()->json([
        'can_comment' => $hasContract
    ]);
}
}
