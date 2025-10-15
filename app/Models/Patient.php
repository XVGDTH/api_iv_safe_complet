<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom', 'prenom', 'numero_patient', 'age', 'sexe', 'diagnostic',
        'esp32_id', 'perfusion_active', 'perfusion_debut', 'perfusion_fin_prevue',
        'debit_ml_h', 'volume_total_ml', 'volume_perfuse_ml'
        
    ];
    

    protected $casts = [
        'perfusion_active' => 'boolean',
        'perfusion_debut' => 'datetime',
        'perfusion_fin_prevue' => 'datetime',
        'debit_ml_h' => 'decimal:2',
        'volume_total_ml' => 'decimal:2',
        'volume_perfuse_ml' => 'decimal:2',
    ];

    public function mesures(): HasMany
    {
        return $this->hasMany(Mesure::class)->orderBy('timestamp_mesure', 'desc');
    }

    public function commandes(): HasMany
    {
        return $this->hasMany(Commande::class)->orderBy('created_at', 'desc');
    }

    public function derniereMesure()
    {
        return $this->hasOne(Mesure::class)->latest('timestamp_mesure');
    }

    public function commandesEnAttente(): HasMany
    {
        return $this->hasMany(Commande::class)->where('statut', 'pending');
    }

    // Calculer le pourcentage de perfusion terminée
    public function getPourcentagePerfusionAttribute(): float
    {
        if ($this->volume_total_ml <= 0) return 0;
        return min(100, ($this->volume_perfuse_ml / $this->volume_total_ml) * 100);
    }

    // Calculer le temps restant estimé
    public function getTempsRestantEstimeAttribute(): ?string
    {
        if (!$this->perfusion_active || $this->debit_ml_h <= 0) return null;

        $volumeRestant = $this->volume_total_ml - $this->volume_perfuse_ml;
        $heuresRestantes = $volumeRestant / $this->debit_ml_h;

        return gmdate('H:i', $heuresRestantes * 3600);
    }
}
