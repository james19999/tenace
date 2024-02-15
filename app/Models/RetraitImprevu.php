<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RetraitImprevu extends Model
{
    use HasFactory;
    protected $fillable = ['amount','raison'];

}
