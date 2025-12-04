<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Apartment;

class Apartment_Address extends Model
{
    protected $table="apartment__addresses";
    use HasFactory;
    protected $fillable=[ 'buildingNumber','floorNumber','apartmentNumber','streetName','city' ];
    public function apartments()
    {
        return $this->hasMany(Apartment::class,'adress_Id');
    }
} 
