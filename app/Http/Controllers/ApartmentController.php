<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use Illuminate\Http\Request;
use App\Models\Apartment_Address;
use App\Models\contracts;
use Illuminate\Support\Facades\Auth;


class ApartmentController extends Controller

{
 public function addApartment(Request $request)
{
    // Log for debugging
    \Log::info('Add Apartment Request', $request->all());

    // التحقق من أن المستخدم مالك
    $user = $request->user();
    if ($user->role !== 'owner') {
        return response()->json([
            'status' => false,
            'message' => 'Not allowed to add apartment'
        ], 403);
    }

    // التحقق من البيانات
    $validated = $request->validate([
        'price' => 'required|numeric',
        'space' => 'required|numeric',
        'statusApartments' => 'required|in:vacant,rented',
        'owner_Id' => 'required|exists:clients,id',
        'buildingNumber' => 'required|string',
        'floorNumber' => 'required|string',
        'apartmentNumber' => 'required|string',
        'streetName' => 'required|string',
        'city' => 'required|string',
        'image' => 'nullable|image|mimes:jpg,jpeg,png'
    ]);

    // إنشاء العنوان
    $address = Apartment_Address::create([
        'buildingNumber' => $validated['buildingNumber'],
        'floorNumber' => $validated['floorNumber'],
        'apartmentNumber' => $validated['apartmentNumber'],
        'streetName' => $validated['streetName'],
        'city' => $validated['city'],
    ]);

    // حفظ الصورة
    $imageName = null;

    if ($request->hasFile('image')) {
        $imageName = time() . '.' . $request->image->extension();
        $request->image->storeAs('apartments', $imageName, 'public');
    }

    // إنشاء الشقة
    $apartment = Apartment::create([
        'price' => $validated['price'],
        'space' => $validated['space'],
        'statusApartments' => $validated['statusApartments'],
        'owner_Id' => $validated['owner_Id'],
        'adress_Id' => $address->id,
        'image' => $imageName,
    ]);

    // إرجاع رابط الصورة الكامل
    $imageUrl = $imageName ? asset("storage/apartments/$imageName") : null;

    return response()->json([
        'status' => 201,
        'message' => 'تم إضافة الشقة مع عنوانها بنجاح',
        'apartment' => [
            ...$apartment->toArray(),
            'image_url' => $imageUrl
        ],
        'address' => $address
    ]);
}

    public function showAll(Request $request)
{
    \Log::info("TOKEN: " . $request->header('Authorization'));

    $userId = $request->user()->id; // صاحب التوكين

    return response()->json([
        "data" => [
            "Appartment" => Apartment::where('owner_Id', '!=', $userId)->get(),
            "Apartment_Address" => Apartment_Address::all()
        ]
    ]);
}
  public function showOne($id)
{
    $apartment = Apartment::with('address')->find($id);

    if (!$apartment) {
        return response()->json(['message' => 'Apartment not found'], 404);
    }

    return response()->json([
        "status" => 200,
        "data" => $apartment
    ]);
}
public function filterApartments(Request $request)
{    \Log::info('Filter Apartment Request', $request->all());

    $userId = $request->user()->id;

    $query = Apartment::with('Apartment_Address')
        ->where('owner_Id', '!=', $userId);

    // 🔍 فلترة حسب المدينة
    if ($request->city) {
        $query->whereHas('Apartment_Address', function ($q) use ($request) {
            $q->where('city', 'LIKE', '%' . $request->city . '%');
        });
    }

    // 🔍 فلترة حسب الشارع
    if ($request->streetName) {
        $query->whereHas('Apartment_Address', function ($q) use ($request) {
            $q->where('streetName', 'LIKE', '%' . $request->streetName . '%');
        });
    }

    // 🔍 فلترة حسب رقم البناء
    if ($request->buildingNumber) {
        $query->whereHas('Apartment_Address', function ($q) use ($request) {
            $q->where('buildingNumber', 'LIKE', '%' . $request->buildingNumber . '%');
        });
    }

    // 🔍 فلترة حسب رقم الطابق
    if ($request->floorNumber) {
        $query->whereHas('Apartment_Address', function ($q) use ($request) {
            $q->where('floorNumber', 'LIKE', '%' . $request->floorNumber . '%');
        });
    }

    // 🔍 فلترة حسب رقم الشقة
    if ($request->apartmentNumber) {
        $query->whereHas('Apartment_Address', function ($q) use ($request) {
            $q->where('apartmentNumber', 'LIKE', '%' . $request->apartmentNumber . '%');
        });
    }

    // 🔥 السعر <= قيمة محددة
    if ($request->price) {
        $query->where('price', '<=', $request->price);
    }

    // 🔥 المساحة >= قيمة محددة
    if ($request->space) {
        $query->where('space', '>=', $request->space);
    }

    $apartments = $query->get();

    return response()->json([
        'status' => true,
        'data' => $apartments
    ]);
}

 public function bookApartment(Request $request)
{
    // 1. Validation
    $data = $request->validate([
        'apartment_id' => ['required', 'exists:apartments,id'],
        'tenant_id'    => ['required', 'exists:clients,id'],
        'rent_start'   => ['required', 'date', 'before:rent_end'],
        'rent_end'     => ['required', 'date', 'after:rent_start'],
    ]);

    // 2. Check overlapping ACTIVE contracts only
    $overlap = contracts::where('apartment_id', $data['apartment_id'])
        ->where('contractsstatus', 'active')
        ->where(function ($q) use ($data) {
            $q->where('rent_start', '<', $data['rent_end'])
              ->where('rent_end', '>', $data['rent_start']);
        })
        ->exists();

    if ($overlap) {
        return response()->json([
            'status'  => 'error',
            'message' => 'الشقة محجوزة في هذه الفترة الزمنية.',
        ], 422);
    }

    // 3. إضافة حالة العقد من الطلب (افتراضي waiting approve)
    $data['contractsstatus'] = $request->contractsstatus ?? 'waiting approve';

    // 4. إنشاء العقد
    $contract = contracts::create($data);

    // 5. تحديث حالة الشقة
    $apartment = Apartment::find($data['apartment_id']);
    $apartment->statusApartments = 'vacant';
    $apartment->save();

    return response()->json([
        'status'            => 201,
        'message'           => 'تم إرسال طلب الحجز وهو بانتظار الموافقة.',
        'contract'          => $contract,
        'apartment_status'  => $apartment->statusApartments,
    ], 201);
}

public function cancelBooking(Request $request)
{
    $data = $request->validate([
        'contract_id' => ['required', 'exists:contracts,id'],
    ]);

    // جلب العقد بدون tenant_id
    $contract = contracts::where('id', $data['contract_id'])->first();

    if (!$contract) {
        return response()->json([
            'status'  => 'error',
            'message' => 'العقد غير موجود.',
        ], 404);
    }

    $apartment = Apartment::find($contract->apartment_id);
    $client = Auth::user();

    // 🔹 إذا كان المالك هو من يلغي العقد
    if ($client && $client->role === 'owner' && $client->id === $apartment->owner_Id) {

        $contract->contractsstatus = 'cancelled';
        $contract->save();

        $apartment->statusApartments = 'vacant';
        $apartment->save();

        return response()->json([
            'status'            => 200,
            'message'           => 'تم إلغاء العقد من قبل المالك وتم تحرير الشقة.',
            'apartment_status'  => $apartment->statusApartments,
            'contract_status'   => $contract->contractsstatus,
        ], 200);
    }

    // 🔹 إذا كان المستأجر هو من يطلب الإلغاء
    if ($client && $client->id === $contract->tenant_id) {

        $contract->contractsstatus = 'waiting cancel';
        $contract->save();

        return response()->json([
            'status'            => 201,
            'message'           => 'تم إرسال طلب الإلغاء وهو بانتظار موافقة المالك.',
            'apartment_status'  => $apartment->statusApartments,
            'contract_status'   => $contract->contractsstatus,
        ], 200);
    }

    return response()->json([
        'status'  => 'error',
        'message' => 'غير مصرح لك بتنفيذ هذا الإجراء.',
    ], 403);
}
public function updateBooking(Request $request)
{
    $data = $request->validate([
        'contract_id' => ['required', 'exists:contracts,id'],
        'tenant_id'   => ['required', 'exists:clients,id'],
        'rent_start'  => ['required', 'date', 'before:rent_end'],
        'rent_end'    => ['required', 'date', 'after:rent_start'],
    ]);

    $contract = contracts::where('id', $data['contract_id'])
        ->where('tenant_id', $data['tenant_id'])
        ->first();

    if (!$contract) {
        return response()->json([
            'status'  => 404,
            'message' => 'العقد غير موجود أو لا يخص هذا المستأجر.',
        ], 404);
    }

    // منع التداخل مع عقود أخرى
    $overlap = contracts::where('apartment_id', $contract->apartment_id)
        ->where('id', '!=', $contract->id)
        ->where('contractsstatus', 'active')
        ->where(function ($q) use ($data) {
            $q->where('rent_start', '<', $data['rent_end'])
              ->where('rent_end', '>', $data['rent_start']);
        })
        ->exists();

    if ($overlap) {
        return response()->json([
            'status'  => 422,
            'message' => 'التواريخ الجديدة تتداخل مع حجز آخر.',
        ], 422);
    }

    // تحديث العقد
    $contract->rent_start = $data['rent_start'];
    $contract->rent_end   = $data['rent_end'];
    $contract->contractsstatus = $request->contractsstatus ?? 'waiting update';
    $contract->save();

    return response()->json([
        'status'   => 201,
        'message'  => 'تم إرسال طلب التعديل وهو بانتظار الموافقة.',
        'contract' => $contract,
    ], 200);
}
 
public function getStatus(Request $request)
{
    $request->validate([
        'apartment_id' => 'required|integer'
    ]);

    $apartment = Apartment::find($request->apartment_id);

    if (!$apartment) {
        return response()->json([
            'status' => 404,
            'message' => 'Apartment not found'
        ], 404);
    }

    $contract = contracts::where('apartment_id', $apartment->id)
        ->where('contractsstatus', 'active')
        ->first();

    return response()->json([
        'status' => 200,
        'data' => [
            'statusApartments' => $apartment->statusApartments,
            'tenant_id'        => $contract ? $contract->tenant_id : null, // المستأجر الحالي إذا محجوزة
            'contract_id'      => $contract ? $contract->id : null
        ]
    ], 200);
}
public function myContracts()
{
    $tenantId = auth()->id();

    $contracts = contracts::with(['apartment.clients', 'apartment.Apartment_Address'])
        ->where('tenant_id', $tenantId)
        ->select('apartment_id')
        ->distinct()
        ->get();

    return response()->json([
        'status' => true,
        'data' => ['contracts'=> $contracts]
    ]);
}
   public function showmyAll(Request $request)
{
    \Log::info("TOKEN: " . $request->header('Authorization'));

    $userId = $request->user()->id; // صاحب التوكين

    return response()->json([
        "data" => [
            "Appartment" => Apartment::where('owner_Id', $userId)->get(),
            "Apartment_Address" => Apartment_Address::all()
        ]
    ]);
}
public function deleteApartment(Request $request, $id)
{
    // Log for debugging
    \Log::info('Delete Apartment Request', ['apartment_id' => $id]);

    // التحقق من أن المستخدم مالك
    $user = $request->user();
    if ($user->role !== 'owner') {
        return response()->json([
            'status' => false,
            'message' => 'Not allowed to delete apartment'
        ], 403);
    }

    // جلب الشقة
    $apartment = Apartment::find($id);
    if (!$apartment) {
        return response()->json([
            'status' => false,
            'message' => 'Apartment not found'
        ], 404);
    }

    // التحقق أن المستخدم هو مالك الشقة
    if ($apartment->owner_Id !== $user->id) {
        return response()->json([
            'status' => false,
            'message' => 'You are not the owner of this apartment'
        ], 403);
    }

    // التحقق من حالة العقد (لا يمكن الحذف إذا العقد نشط)
    if ($apartment->contracts()->whereIn('contractsstatus', ['active','waiting update','waiting cancel'])->exists()) {
        return response()->json([
            'status' => false,
            'message' => 'Cannot delete apartment with active or pending contract'
        ], 400);
    }

    // حذف الصورة إن وجدت
    if ($apartment->image) {
        \Storage::disk('public')->delete('apartments/' . $apartment->image);
    }

    // حذف العنوان المرتبط
    if ($apartment->adress_Id) {
        Apartment_Address::where('id', $apartment->adress_Id)->delete();
    }

    // حذف الشقة
    $apartment->delete();

    return response()->json([
        'status' => 200,
        'message' => 'تم حذف الشقة مع عنوانها بنجاح'
    ]);
}
public function approveCancel($contractId)
{
    $contract = contracts::find($contractId);

    if (!$contract) {
        return response()->json([
            'status' => 404,
            'message' => 'العقد غير موجود.',
        ], 404);
    }

    $apartment = Apartment::find($contract->apartment_id);
    $client = Auth::user();

    // تحقق أن المالك هو من ينفذ العملية
    if (!$client || $client->role !== 'owner' || $client->id !== $apartment->owner_Id) {
        return response()->json([
            'status' => 403,
            'message' => 'غير مصرح لك بقبول طلب الإلغاء.',
        ], 403);
    }

    // قبول الإلغاء
    $contract->contractsstatus = 'cancelled';
    $contract->save();

    $apartment->statusApartments = 'vacant';
    $apartment->save();

    return response()->json([
        'status' => 200,
        'message' => 'تم قبول طلب الإلغاء وتحرير الشقة.',
    ], 200);
}
public function rejectCancel($contractId)
{
    $contract = contracts::find($contractId);

    if (!$contract) {
        return response()->json([
            'status' => 404,
            'message' => 'العقد غير موجود.',
        ], 404);
    }

    $apartment = Apartment::find($contract->apartment_id);
    $client = Auth::user();

    // تحقق أن المالك هو من ينفذ العملية
    if (!$client || $client->role !== 'owner' || $client->id !== $apartment->owner_Id) {
        return response()->json([
            'status' => 403,
            'message' => 'غير مصرح لك برفض طلب الإلغاء.',
        ], 403);
    }

    // رفض الإلغاء
    $contract->contractsstatus = 'active';
    $contract->save();

    return response()->json([
        'status' => 200,
        'message' => 'تم رفض طلب الإلغاء واستمرار العقد.',
    ], 200);
}
}