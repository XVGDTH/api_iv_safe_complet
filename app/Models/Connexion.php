<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Connexion extends Model
{
    use HasFactory;

    /**
     * Nom de la table
     */
    protected $table = 'connexions';

    /**
     * Les attributs qui peuvent être assignés en masse
     */
    protected $fillable = [
        'serial',
        'is_connected',
        'last_seen_at',
        'last_data',
        'patient_registered'
    ];

    /**
     * Les attributs qui doivent être castés
     */
    protected $casts = [
        'last_data' => 'array',
        'last_seen_at' => 'datetime',
        'is_connected' => 'boolean',
        'patient_registered' => 'boolean'
    ];

    /**
     * Les attributs qui doivent être cachés pour les tableaux
     */
    protected $hidden = [];

    /**
     * Relation avec les patients (si nécessaire)
     */
    public function patient()
    {
        return $this->hasOne(\App\Models\GestionPatient::class, 'serial', 'serial');
    }
}