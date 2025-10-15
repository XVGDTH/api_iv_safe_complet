<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pressure extends Model
{
    use HasFactory;

    protected $table = 'pressures';

    protected $fillable = [
        'serial',
        'temperature',
        'bpm',
        'spo2',
        'battery',
    ];
}
