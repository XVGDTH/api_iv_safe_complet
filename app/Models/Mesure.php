<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mesure extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id', 'esp32_id', 'temperature', 'pression', 'bulle_detectee',
        'debit_actuel', 'volume_perfuse', 'batterie_pourcent', 'pompe_active',
        'vanne_ouverte', 'statut', 'message_alerte', 'timestamp_mesure'
    ];

    protected $casts = [
        'temperature' => 'decimal:2',
        'pression' => 'decimal:2',
        'debit_actuel' => 'decimal:2',
        'volume_perfuse' => 'decimal:2',
        'bulle_detectee' => 'boolean',
        'pompe_active' => 'boolean',
        'vanne_ouverte' => 'boolean',
        'timestamp_mesure' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    // Vérifier si la mesure indique une situation critique
    public function estCritique(): bool
    {
        return $this->statut === 'critique' ||
               $this->bulle_detectee ||
               $this->batterie_pourcent < 20 ||
               ($this->temperature && ($this->temperature < 15 || $this->temperature > 40));
    }

    // Vérifier si c'est une alerte
    public function estAlerte(): bool
    {
        return $this->statut === 'alerte' ||
               $this->batterie_pourcent < 30 ||
               ($this->temperature && ($this->temperature < 18 || $this->temperature > 35));
    }
}
