<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Client;
use App\Models\Apartment_Address;

class Apartment extends Model
{ 
    protected $table="apartments";
    use HasFactory; 
    protected $fillable = ['price','space','status','owner_Id','adress_Id','rent_start','rent_end'];
    public function clients()
    {

        return $this->belongsTo(Client::class,'owner_Id');
    }
    public function Apartment_Address()
    {
        return $this->belongsTo(Apartment_Address::class,'adress_Id');
    }
     
}
