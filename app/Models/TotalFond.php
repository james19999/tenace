<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TotalFond extends Model
{
    use HasFactory;
    protected $table = 'total_fonds';
    protected  $fillable=['totals','etat'];
}
