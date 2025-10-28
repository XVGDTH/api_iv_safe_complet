<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GestionPatient extends Model
{
    use HasFactory;

    protected $table = 'gestion_patients';

    protected $fillable = [
        'nom',
        'prenom',
        'age',
        'poids',
        'telephone',
        'serial',
        // ✅ AJOUTER CES 4 LIGNES
        'temperature',
        'bpm',
        'spo2',
        'batterie',
    ];

    /**
     * Les attributs qui doivent être castés.
     */
    protected $casts = [
        'age' => 'integer',
        'poids' => 'decimal:2',
        'temperature' => 'decimal:1',
        'bpm' => 'integer',
        'spo2' => 'integer',
        'batterie' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Accessor pour formater la date
     */
    public function getFormattedDateAttribute()
    {
        return $this->created_at ? $this->created_at->format('d/m/Y') : '';
    }

    /**
     * Accessor pour le nom complet
     */
    public function getFullNameAttribute()
    {
        return "{$this->prenom} {$this->nom}";
    }
}