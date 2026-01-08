<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PhpParser\Node\Expr\FuncCall;
use App\Models\Apartment;
use App\Models\Client;

class contracts extends Model
{
    use HasFactory;
    protected $fillable = [
    'apartment_id',
    'tenant_id',
    'rent_start',
    'rent_end',
    'contractsstatus',
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
