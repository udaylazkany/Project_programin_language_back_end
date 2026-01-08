<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class comment extends Model
{
    use HasFactory;
      protected $fillable = [
    'apartment_id',
    'tenant_id',
    'Comment'
];
public function apartment()
    {
     return   $this->belongsTo(Apartment::class);
    }
    public function tenant()
    {
        return $this->belongsTo(Client::class,'tenant_id');

    }
}
