<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PairingToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'qr_url',
        'claimed',
        'patient_id',
    ];
}
