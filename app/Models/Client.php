<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Admin;
use App\Models\Apartment;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class Client extends Authenticatable
{use HasApiTokens,Notifiable,HasFactory;
    protected $table="clients";
    
   
    protected $fillable=[
        'firstName','lastName','phoneNumber','dob',
        'role','admin_Id',
        'password','is_approved',
        'personal_id_photo','personal_photo'];

protected $hidden=['password','remember_token'];
 
public function apartments()
{
    return $this->hasMany(Apartment::class,'owner_Id');
}

}
