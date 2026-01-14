<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    // عرض جميع الرسائل في محادثة معينة
    public function index($conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);

        $messages = $conversation->messages()
            ->with('sender') // جلب بيانات المرسل
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }

    // إرسال رسالة جديدة (مع إنشاء محادثة إذا مش موجودة)
    public function store(Request $request)
    {
        $request->validate([
            'owner_id'   => 'required|exists:clients,id',
            'tenant_id'  => 'required|exists:clients,id',
            'message'    => 'required|string',
        ]);

        // تحقق إذا في محادثة موجودة بين الطرفين
        $conversation = Conversation::where('owner_id', $request->owner_id)
            ->where('tenant_id', $request->tenant_id)
            ->first();

        if (!$conversation) {
            // إذا ما في محادثة، أنشئ وحدة جديدة
            $conversation = Conversation::create([
                'owner_id'  => $request->owner_id,
                'tenant_id' => $request->tenant_id,
            ]);
        }

        // أضف الرسالة للمحادثة
        $conversation->messages()->create([
            'sender_id' => auth()->id(), // المرسل هو المستخدم الحالي
            'message'   => $request->message,
        ]);

        // رجع المحادثة مع كل الرسائل
        return response()->json($conversation->load('messages.sender'), 201);
    }
}