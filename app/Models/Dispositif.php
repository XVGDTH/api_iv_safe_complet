<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dispositif extends Model
{
    use HasFactory;

    protected $fillable = [
        'volume_initial',
        'debit_courant',
        'batterie',
        'temps_restant'
    ];
}
