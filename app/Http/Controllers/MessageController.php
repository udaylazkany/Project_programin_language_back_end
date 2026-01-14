<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    // ================================
    // جلب الرسائل لمحادثة معينة
    // ================================
    public function index($conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);

        $messages = $conversation->messages()
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            "conversation_id" => $conversation->id,
            "messages" => $messages
        ]);
    }

    // ================================
    // إرسال رسالة (مع تصحيح الانعكاس)
    // ================================
   public function store(Request $request, $conversationId = null)
{
    $request->validate([
        'message' => 'required|string',
    ]);

    $senderId = auth()->id();

    // جلب المحادثة
    $conversation = Conversation::findOrFail($conversationId);

    // تحديد المستلم من داخل المحادثة
    if ($senderId == $conversation->owner_id) {
        $receiverId = $conversation->tenant_id;
    } else {
        $receiverId = $conversation->owner_id;
    }

    // إنشاء الرسالة
    $message = $conversation->messages()->create([
        'sender_id'   => $senderId,
        'receiver_id' => $receiverId,
        'message'     => $request->message,
    ]);

    return response()->json([
        "conversation_id" => $conversation->id,
        "message" => $message
    ], 201);
}

}
