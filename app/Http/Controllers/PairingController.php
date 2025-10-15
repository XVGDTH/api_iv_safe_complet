<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\PairingToken;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PairingController extends Controller
{
    // Générer un token + QR code (non rattaché à un patient)
    public function create()
    {
        $token = Str::uuid()->toString();

        PairingToken::create([
            'token'      => $token,
            'patient_id' => null, // pas encore lié
            'expires_at' => now()->addMinutes(10),
        ]);

        return response()->json([
            'token'     => $token,
            'qr_url'    => url("/api/pairing/qr/$token"),
            'deeplink'  => "ivsafe://pair?token=$token",
        ]);
    }

    // Générer l’image PNG du QR
    public function qr($token)
    {
        $pt = PairingToken::where('token', $token)->firstOrFail();

        $img = QrCode::format('png')->size(300)->margin(1)
            ->generate("ivsafe://pair?token=$token");

        return response($img)->header('Content-Type', 'image/png');
    }

    // L’appli scanne et revendique le token → assignation patient
    public function claim(Request $r)
    {
        $r->validate([
            'token'      => 'required|uuid',
            'patient_id' => 'required|exists:patients,id',
        ]);

        $pt = PairingToken::where('token', $r->token)->first();

        if (!$pt || $pt->expires_at < now()) {
            return response()->json(['error' => 'expired'], 410);
        }

        $pt->patient_id = $r->patient_id;
        $pt->claimed_at = now();
        $pt->save();

        return response()->json([
            'message' => 'Appairage patient actif',
            'token'   => $pt->token,
            'patient' => $pt->patient,
        ]);
    }
}
