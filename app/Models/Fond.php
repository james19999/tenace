<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fond extends Model
{
    use HasFactory;
    protected $fillable = [
        "amount",
        "fixed",
        "total",
    ];
}
