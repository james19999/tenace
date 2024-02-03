<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CostomerNew extends Model
{
    use HasFactory;
    protected  $fillable=[
        'name',
        'phone',
        'email',
        'adresse',
        'user_id',

  ];
}
