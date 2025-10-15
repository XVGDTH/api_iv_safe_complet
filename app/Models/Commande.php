<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commande extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id', 'esp32_id', 'type_commande', 'parametres',
        'statut', 'envoye_at', 'execute_at', 'reponse_esp32', 'envoye_par'
    ];

    protected $casts = [
        'parametres' => 'array',
        'envoye_at' => 'datetime',
        'execute_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    // Marquer la commande comme envoyée
    public function marquerEnvoye(): void
    {
        $this->update([
            'statut' => 'sent',
            'envoye_at' => now()
        ]);
    }

    // Marquer la commande comme exécutée
    public function marquerExecute(string $reponseEsp32 = null): void
    {
        $this->update([
            'statut' => 'executed',
            'execute_at' => now(),
            'reponse_esp32' => $reponseEsp32
        ]);
    }

    // Marquer la commande comme échouée
    public function marquerEchoue(string $erreur): void
    {
        $this->update([
            'statut' => 'failed',
            'reponse_esp32' => $erreur
        ]);
    }
}
