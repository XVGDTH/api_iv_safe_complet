<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Device;
use App\Models\PairingToken;
use Illuminate\Support\Facades\Hash;

class DeviceController extends Controller
{
  // Un ESP s'enregistre avec serial, type et pairing_code (token)
  public function register(Request $r) {
    $r->validate([
      'serial' => 'required|string|min:6',
      'device_type' => 'required|in:IV_BOX,CUFF',
      'pairing_code' => 'required|uuid'
    ]);
    $pt = PairingToken::where('token',$r->pairing_code)->first();
    if (!$pt || $pt->expires_at < now()) return response()->json(['error'=>'invalid_or_expired'], 410);

    $dev = Device::firstOrNew(['serial'=>$r->serial]);
    if (!$dev->exists) $dev->id = Str::uuid()->toString();
    $dev->device_type = $r->device_type;
    $dev->patient_id = $pt->patient_id;
    $dev->status = 'ASSIGNED';
    $apiKey = Str::random(40);
    $dev->api_key_hash = Hash::make($apiKey);
    $dev->save();

    return response()->json([
      'device_id' => $dev->id,
      'patient_id' => $dev->patient_id,
      'api_key' => $apiKey,
      'ingest_url' => url('/api/iot/push'),
    ]);
  }
}

