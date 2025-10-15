<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\Reading;
use Illuminate\Support\Facades\Hash;

class ReadingController extends Controller
{
  // ESP push: Authorization: Bearer <api_key>
  public function ingest(Request $r) {
    $auth = $r->header('Authorization');
    if (!$auth || !str_starts_with($auth,'Bearer ')) return response()->json(['error'=>'unauthorized'],401);
    $api = trim(substr($auth,7));
    $serial = $r->input('serial');

    $dev = Device::where('serial',$serial)->first();
    if (!$dev || !Hash::check($api, $dev->api_key_hash)) return response()->json(['error'=>'forbidden'],403);

    $payload = $r->input('data', []); // tableau de mesures
    $created = [];
    foreach ($payload as $m) {
      // m = { "metric":"temp", "value":36.8, "unit":"C" }
      $rec = Reading::create([
        'device_id' => $dev->id,
        'patient_id'=> $dev->patient_id,
        'metric'    => $m['metric'],
        'value'     => $m['value'],
        'unit'      => $m['unit'] ?? null,
      ]);
      $created[] = $rec->id;
    }
    $dev->last_seen = now(); $dev->save();

    return response()->json(['status'=>'ok','count'=>count($created)]);
  }

  public function list($patientId) {
    $q = Reading::where('patient_id',$patientId)
      ->latest()->limit(500)->get();
    return response()->json($q);
  }
}
