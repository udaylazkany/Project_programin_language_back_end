<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\contracts;
use App\Models\Apartment;

class ContractsController extends Controller
{
public function viewApartmentStatus(Request $request)
{
    $apartmentId = $request->id;

    // جلب الشقة
    $apartment = Apartment::find($apartmentId);

    if (!$apartment) {
        return response()->json([
            "status" => 404,
            "message" => "Apartment not found"
        ]);
    }

    // جلب آخر عقد للشقة
    $contract = contracts::where('apartment_id', $apartmentId)
                        ->latest()
                        ->first();

    return response()->json([
        "status" => 200,
        "data" => [
            "statusApartments" => $apartment->statusApartments, // 👈 حالة الشقة
            "owner_id" => $apartment->owner_Id,
            "tenant_id" => $contract?->tenant_id ?? 0,
            "contract_id" => $contract?->id ?? 0,
            "contract_status" => $contract?->contractsstatus ?? "none",
        ]
    ]);
}
public function confirmBooking($contractId)
{
    $contract = contracts::findOrFail($contractId);


    // جلب الشقة المرتبطة بالعقد
     $contract = contracts::findOrFail($contractId);
    $apartment = Apartment::findOrFail($contract->apartment_id);

 $client = Auth::user();

    // تحقق أن المستخدم الحالي هو المالك
        if (!$client || $client->role !== 'owner' || $client->id !== $apartment->owner_Id)
    {

        return response()->json([
            "auth"=>Auth::id(),
            ' owner_id'=> $apartment->owner_Id,
            'status' => 403,
            'message' => 'غير مصرح لك بالموافقة على هذا الحجز.'
        ], 403);
    }

    // جلب كل العقود لنفس الشقة بحالة انتظار
    $contracts = contracts::where('apartment_id', $contract->apartment_id)
                         ->where('contractsstatus', 'waiting approve')
                         ->get();

    foreach ($contracts as $c) {
        if ($c->id == $contract->id) {
            $c->contractsstatus = 'active';
        } else {
            $c->contractsstatus = 'cancelled';
        }
        $c->save();
    }

    // تحديث حالة الشقة إلى مشغولة
    $apartment->statusApartments = 'rented';
    $apartment->save();

    return response()->json([
        'status' => 200,
        'message' => 'تمت الموافقة على الحجز وإلغاء باقي الطلبات.',
        'contract' => $contract
    ]);
}
public function approveUpdate($contractId)
{
    $contract = contracts::findOrFail($contractId);
    $apartment = Apartment::findOrFail($contract->apartment_id);

    $client = Auth::user();

    // تحقق أن المستخدم الحالي هو المالك
    if (!$client || $client->role !== 'owner' || $client->id !== $apartment->owner_Id) {
        return response()->json([
            "status" => 403,
            "message" => "غير مصرح لك بقبول التعديل"
        ], 403);
    }

    // قبول التعديل
    $contract->contractsstatus = "active";
    $contract->save();

    return response()->json([
        "status" => 200,
        "message" => "تم قبول التعديل بنجاح",
        "contract" => $contract
    ]);
}
public function rejectUpdate($contractId)
{
    $contract = contracts::findOrFail($contractId);
    $apartment = Apartment::findOrFail($contract->apartment_id);

    $client = Auth::user();

    // تحقق أن المستخدم الحالي هو المالك
    if (!$client || $client->role !== 'owner' || $client->id !== $apartment->owner_Id) {
        return response()->json([
            "status" => 403,
            "message" => "غير مصرح لك برفض التعديل"
        ], 403);
    }

    // رفض التعديل → يرجع العقد كما كان
    $contract->contractsstatus = "cancelled"; // يرجع للوضع الطبيعي
    $contract->save();
    $apartment->statusApartments = 'vacant';
    $apartment->save();


    return response()->json([
        "status" => 200,
        "message" => "تم رفض طلب التعديل",
        "contract" => $contract
    ]);
}

}
