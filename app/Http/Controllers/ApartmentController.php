<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use Illuminate\Http\Request;
use App\Models\Apartment_Address;
use App\Models\contracts;


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
        ->where('contractsstatus', 'active')   // 🔥 أهم سطر
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

    // 3. إضافة حالة العقد
    $data['contractsstatus'] = 'active';

    // 4. إنشاء العقد
    $contract = contracts::create($data);

    // 5. تحديث حالة الشقة
    $apartment = Apartment::find($data['apartment_id']);
    $apartment->statusApartments = 'rented';
    $apartment->save();

    return response()->json([
        'status'            => 201,
        'message'           => 'تم حجز الشقة بنجاح.',
        'contract'          => $contract,
        'apartment_status'  => $apartment->statusApartments,
        'rent_start'        => $contract->rent_start,
        'rent_end'          => $contract->rent_end,
    ], 201);
}
    public function cancelBooking(Request $request)
{
    $data = $request->validate([
        'contract_id'   => ['required', 'exists:contracts,id'],
        'tenant_id'     => ['required', 'exists:clients,id'],
    ]);

    // 1. جلب العقد
    $contract = contracts::where('id', $data['contract_id'])

        ->where('tenant_id', $data['tenant_id']) // حماية: المستأجر يلغي عقده فقط
        ->first();

    if (!$contract) {
        return response()->json([
            'status'  => 'error',
            'message' => 'العقد غير موجود أو لا يخص هذا المستأجر.',
        ], 404);
    }

    // 2. تحرير الشقة
    $apartment = Apartment::find($contract->apartment_id);
    $apartment->statusApartments = 'vacant'; // ← الشقة أصبحت متاحة
    $apartment->save();

    // 3. تحديث حالة العقد (اختياري)
    $contract->contractsstatus = 'cancelled';
    $contract->save();

    return response()->json([
        'status'            => 201,
        'message'           => 'تم إلغاء الحجز وتحرير الشقة.',
        'apartment_status'  => $apartment->statusApartments,
        'contract_status'   => $contract->contractsstatus,
    ], 200);
}
public function updateBooking(Request $request)
{
    // 1. Validate input
    $data = $request->validate([
        'contract_id' => ['required', 'exists:contracts,id'],
        'tenant_id'   => ['required', 'exists:clients,id'],
        'rent_start'  => ['required', 'date', 'before:rent_end'],
        'rent_end'    => ['required', 'date', 'after:rent_start'],
    ]);

    // 2. Fetch contract (must belong to tenant and be active)
    $contract = contracts::where('id', $data['contract_id'])
        ->where('tenant_id', $data['tenant_id'])
        ->where('contractsstatus', 'active')
        ->first();

    if (!$contract) {
        return response()->json([
            'status'  => 404,
            'message' => 'العقد غير موجود أو غير فعال أو لا يخص هذا المستأجر.',
        ], 404);
    }

    // 3. Prevent overlapping with other active contracts
    $overlap = contracts::where('apartment_id', $contract->apartment_id)
        ->where('id', '!=', $contract->id) // استثناء العقد الحالي
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

    // 4. Update contract dates
    $contract->rent_start = $data['rent_start'];
    $contract->rent_end   = $data['rent_end'];
    $contract->save();

    return response()->json([
        'status'   => 201,
        'message'  => 'تم تعديل الحجز بنجاح.',
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

}