<?php

namespace App\Models;

use App\Models\User;
use App\Models\TypeExpensive;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Expensive extends Model
{
    use HasFactory;
    protected $fillable=[
        'description',
        'date_create',
        'type_expensive_id',
        'user_id',
        'amount',
    ];
    protected $casts = [
        'date_create' => 'date',
        'amount' => 'integer',
    ];

    public function user(){
        return $this->belongsTo(User::class,'user_id');
    }
    public function typeexpensive(){
        return $this->belongsTo(TypeExpensive::class,'type_expensive_id');
    }
}
