<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class conversation extends Model
{
    use HasFactory;
      protected $fillable = ['owner_id', 'tenant_id'];

    public function owner()
    {
        return $this->belongsTo(Client::class, 'owner_id');
    }

    public function tenant()
    {
        return $this->belongsTo(Client::class, 'tenant_id');
    }
 public function messages()
    {
        return $this->hasMany(Message::class);
    }

}
