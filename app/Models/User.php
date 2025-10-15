<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
// envoi du lien de reinitialisation depuis la boite mail
use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'Id',
        'name',
        'prenom',
        'statut',
        'email',
        'telephone',
        'age',
        'esp32_id',
        'password',
        'last_login_at',
        'last_logout_at',
        'last_seen_at'
        
        
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_login_at' => 'datetime',
        'last_logout_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    // Include computed attributes in JSON
    protected $appends = [
        'is_online',
        'current_session_seconds',
        'total_connected_seconds',
        'disconnected_since_seconds',
    ];

    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return 'Id';
    }

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->getAttribute('Id');
    }

    public function sendPasswordResetNotification($token)
    {
        $url = url(config('app.frontend_url') . '/reset-password?token=' . $token . '&email=' . urlencode($this->email));
        $this->notify(new ResetPasswordNotification($url));
    }

    // Relationships
    public function sessions()
    {
        return $this->hasMany(UserSession::class);
    }

    // Presence helpers
    public function getIsOnlineAttribute(): bool
    {
        if (!$this->last_seen_at) return false;
        // Consider user online if seen within last 5 minutes
        return $this->last_seen_at->gt(now()->subMinutes(5));
    }

    public function getCurrentSessionSecondsAttribute(): int
    {
        // If there is an open session, return its current duration
        $open = $this->sessions()->whereNull('ended_at')->latest('started_at')->first();
        if ($open) {
            return now()->diffInSeconds($open->started_at);
        }
        return 0;
    }

    public function getTotalConnectedSecondsAttribute(): int
    {
        // Sum closed sessions durations; include open session to now
        $sum = (int) $this->sessions()->sum('duration_seconds');
        $open = $this->sessions()->whereNull('ended_at')->latest('started_at')->first();
        if ($open) {
            $sum += now()->diffInSeconds($open->started_at);
        }
        return $sum;
    }

    public function getDisconnectedSinceSecondsAttribute(): int
    {
        if ($this->getIsOnlineAttribute()) {
            return 0;
        }
        if ($this->last_logout_at) {
            return now()->diffInSeconds($this->last_logout_at);
        }
        return 0;
    }
}
