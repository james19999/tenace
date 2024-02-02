<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Imprevu extends Model
{
    use HasFactory;
    protected $fillable = [
        "amount",
        "fixed",
        "total",
    ];
}
