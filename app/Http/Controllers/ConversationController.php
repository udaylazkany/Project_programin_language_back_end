<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    // إنشاء محادثة جديدة
    public function create(Request $request)
    {
        $conversation = Conversation::create([
            'owner_id'  => $request->owner_id,
            'tenant_id' => $request->tenant_id,
        ]);

        // رجع المحادثة مع العلاقات حتى يكون شكل الاستجابة موحّد
        return response()->json(
            $conversation->load(['owner', 'tenant', 'messages.sender']),
            201
        );
    }

    // التحقق إذا في محادثة بين مالك ومستأجر
    public function check(Request $request)
{
    $conversation = Conversation::firstOrCreate(
        [
            'owner_id'  => $request->owner_id,
            'tenant_id' => $request->tenant_id,
        ]
    );

    return response()->json(
        $conversation->load(['owner', 'tenant', 'messages.sender']),
        $conversation->wasRecentlyCreated ? 201 : 200
    );
}

    // عرض محادثة معينة مع رسائلها
    public function show($id)
    {
        $conversation = Conversation::with(['owner', 'tenant', 'messages.sender'])
            ->findOrFail($id);

        return response()->json($conversation);
    }

    // عرض جميع المحادثات الخاصة بمستخدم معيّن
    public function userConversations($clientId)
    {
        $conversations = Conversation::with([
                'owner',
                'tenant',
                'messages' => function ($q) {
                    $q->latest()->limit(1)->with('sender'); // رجع آخر رسالة مع بيانات المرسل
                }
            ])
            ->where('owner_id', $clientId)
            ->orWhere('tenant_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($conversations);
    }
}