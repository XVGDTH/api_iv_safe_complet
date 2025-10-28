<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
// imports des routes ds esp c3 du dispositifs et brassard
use App\Http\Controllers\PairingController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\ReadingController;

// ========== IMPORTS EXISTANTS ==========
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\PasswordResetController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\API\PresenceController;

// ========== NOUVEAUX IMPORTS POUR LA PERFUSION ==========
use App\Http\Controllers\API\PatientController;
use App\Http\Controllers\API\MesureController;
use App\Http\Controllers\API\CommandeController;

// ========== NOUVEL IMPORT POUR LE DASHBOARD AUTH ==========
use App\Http\Controllers\Api\DashboardAuthController;
// ROUTE POUR LA TABLE BD
use App\Http\Controllers\API\PressionController;
use App\Http\Controllers\DispositifController;

// les routes 
use App\Http\Controllers\API\GestionPatientController;
use App\Http\Controllers\API\NotificationMedicaleController;

// import de ma web socket
use App\Http\Controllers\API\ConnectionController;
//
use App\Http\Controllers\API\ConnexionController;

/*
|--------------------------------------------------------------------------
| Routes API existantes (CONSERVÉES)
|--------------------------------------------------------------------------
*/

// Route de test (CONSERVÉE)
Route::get('/test', function () {
    return response()->json(['message' => 'API fonctionne !', 'status' => 'success']);
});

// CRUD public users (si souhaité)
Route::post('/users/public', [UserController::class, 'storePublic']);
Route::get('/users/public', [UserController::class, 'indexPublic']);
Route::get('/users/public/{id}', [UserController::class, 'showPublic']);
Route::get('/users/public/{id}/history', [UserController::class, 'history']);
Route::put('/users/public/{id}', [UserController::class, 'updatePublic']);
Route::delete('/users/public/{id}', [UserController::class, 'destroyPublic']);

// Auth publique
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
// (on garde bien le GET /register commenté pour éviter les collisions)
// Route::get('/register', [AuthController::class, 'register']);

/*
|--------------------------------------------------------------------------
| Routes Dashboard Auth (PUBLIQUES)
|--------------------------------------------------------------------------
| register & login = publics
*/
Route::prefix('dashboard')->group(function () {
    Route::post('/register', [DashboardAuthController::class, 'register']);
    Route::post('/login', [DashboardAuthController::class, 'login']);
});

/*
|--------------------------------------------------------------------------
| Routes ESP32 (PUBLIQUES)
|--------------------------------------------------------------------------
*/
Route::prefix('esp32')->name('esp32.')->group(function () {
    Route::post('/mesures', [MesureController::class, 'store'])->name('mesures.store');
    Route::get('/commandes/{esp32_id}', [CommandeController::class, 'getCommandesEsp32'])->name('commandes.get');
    Route::post('/commandes/{commande_id}/confirmer', [CommandeController::class, 'confirmerExecution'])->name('commandes.confirmer');

    Route::get('/status/{esp32_id}', function ($esp32_id) {
        $patient = \App\Models\Patient::where('esp32_id', $esp32_id)->first();
        return response()->json([
            'success'         => true,
            'esp32_id'        => $esp32_id,
            'patient_associe' => $patient ? $patient->nom . ' ' . $patient->prenom : 'Aucun',
            'timestamp'       => now(),
            'status'          => 'ESP32 connecté',
        ]);
    })->name('status');
});

/*
|--------------------------------------------------------------------------
| Routes protégées par Sanctum
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // Auth existante
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::apiResource('users', UserController::class);
    // Presence ping (mobile/app)
    Route::post('/presence/ping', [PresenceController::class, 'ping']);
    Route::post('/change-password', [PasswordResetController::class, 'changePassword']);

    // Patients
    Route::apiResource('patients', PatientController::class)->names([
        'index'   => 'patients.index',
        'store'   => 'patients.store',
        'show'    => 'patients.show',
        'update'  => 'patients.update',
        'destroy' => 'patients.destroy',
    ]);

    // Mesures
    Route::prefix('mesures')->name('mesures.')->group(function () {
        Route::get('/patient/{patient_id}', [MesureController::class, 'index'])->name('patient.index');
        Route::post('/patient/{patient_id}', [MesureController::class, 'storeForPatient'])->name('patient.store');
        Route::get('/patient/{patient_id}/statistiques', [MesureController::class, 'statistiques'])->name('patient.stats');

        Route::get('/{id}', [MesureController::class, 'show'])->name('show');
        Route::put('/{id}', [MesureController::class, 'update'])->name('update');
        Route::delete('/{id}', [MesureController::class, 'destroy'])->name('destroy');

        Route::get('/patient/{patient_id}/export', function ($patient_id) {
            $patient = \App\Models\Patient::findOrFail($patient_id);
            $mesures = $patient->mesures()->orderBy('timestamp_mesure', 'desc')->get();

            return response()->json([
                'success'       => true,
                'patient'       => $patient->nom . ' ' . $patient->prenom,
                'total_mesures' => $mesures->count(),
                'data'          => $mesures,
                'export_date'   => now(),
            ]);
        })->name('export');
    });

    // Commandes
    Route::prefix('commandes')->name('commandes.')->group(function () {
        Route::get('/patient/{patient_id}', [CommandeController::class, 'index'])->name('patient.index');
        Route::post('/', [CommandeController::class, 'store'])->name('store');
        Route::get('/{id}', [CommandeController::class, 'show'])->name('show');
        Route::put('/{id}', [CommandeController::class, 'update'])->name('update');
        Route::delete('/{id}', [CommandeController::class, 'destroy'])->name('destroy');

        Route::post('/urgence/{patient_id}', function (Request $request, $patient_id) {
            $patient = \App\Models\Patient::findOrFail($patient_id);

            $commande = \App\Models\Commande::create([
                'patient_id'     => $patient_id,
                'esp32_id'       => $patient->esp32_id,
                'type_commande'  => 'emergency_stop',
                'parametres'     => ['raison' => $request->input('raison', "Arrêt d'urgence")],
                'statut'         => 'pending',
                'envoye_par'     => auth()->user()->name ?? 'URGENCE',
            ]);

            return response()->json([
                'success' => true,
                'message' => "Commande d'arrêt d'urgence envoyée",
                'data'    => $commande,
            ], 201);
        })->name('urgence');
    });
    // ✅ NOUVELLES ROUTES POUR ESP32
    Route::get('/esp32/{esp32_id}', [CommandeController::class, 'getCommandesEsp32'])->name('esp32.commandes');
    Route::post('/{commande_id}/confirmer', [CommandeController::class, 'confirmerExecution'])->name('confirmer.execution');
});

    // Dashboard protégées
    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/overview', function () {
            $patients = \App\Models\Patient::with(['derniereMesure', 'commandesEnAttente'])->get();
            $stats = [
                'total_patients'        => $patients->count(),
                'perfusions_actives'    => $patients->where('perfusion_active', true)->count(),
                'alertes_critiques'     => $patients->filter(function ($p) {
                    return $p->derniereMesure && $p->derniereMesure->statut === 'critique';
                })->count(),
                'commandes_en_attente'  => \App\Models\Commande::whereIn('statut', ['pending', 'sent'])->count(),
            ];

            return response()->json([
                'success'  => true,
                'stats'    => $stats,
                'patients' => $patients->map(function ($patient) {
                    return [
                        'id'                  => $patient->id,
                        'nom_complet'         => $patient->nom . ' ' . $patient->prenom,
                        'esp32_id'            => $patient->esp32_id,
                        'perfusion_active'    => $patient->perfusion_active,
                        'pourcentage_perfusion' => $patient->pourcentage_perfusion,
                        'statut_actuel'       => $patient->derniereMesure?->statut ?? 'inconnu',
                        'derniere_mesure'     => $patient->derniereMesure?->timestamp_mesure,
                        'commandes_attente'   => $patient->commandesEnAttente->count(),
                    ];
                }),
            ]);
        })->name('overview');

        Route::get('/alertes', function () {
            $alertes = \App\Models\Mesure::whereIn('statut', ['alerte', 'critique'])
                ->with('patient')
                ->orderBy('timestamp_mesure', 'desc')
                ->limit(50)
                ->get();

            return response()->json([
                'success' => true,
                'alertes' => $alertes,
                'count'   => $alertes->count(),
            ]);
        })->name('alertes');

        // === Routes Dashboard protégées par Sanctum ===
        Route::post('/logout', [DashboardAuthController::class, 'logout']);
        Route::get('/me', [DashboardAuthController::class, 'me']); // <= ICI (pas de double prefix)
    });


/*
|--------------------------------------------------------------------------
| Routes publiques pour tests et monitoring
|--------------------------------------------------------------------------
*/
Route::get('/status', function () {
    return response()->json([
        'success'   => true,
        'message'   => 'API Système de Perfusion opérationnelle',
        'timestamp' => now(),
        'version'   => '1.0.0',
        'services'  => [
            'database'          => 'OK',
            'patients_total'    => \App\Models\Patient::count(),
            'esp32_connectes'   => \App\Models\Patient::whereNotNull('esp32_id')->count(),
            'mesures_24h'       => \App\Models\Mesure::where('timestamp_mesure', '>=', now()->subHours(24))->count(),
        ],
    ]);
});
// routes pour le code qr connecter aek l"appli mobile et le dashboard
Route::post('/pairing/token', [PairingController::class, 'token']);
Route::post('/pairing/claim', [PairingController::class, 'claim']);

Route::get('/patients/public', function () {
    return response()->json([
        'message'             => 'Route patients publique accessible',
        'total_patients'      => \App\Models\Patient::count(),
        'perfusions_actives'  => \App\Models\Patient::where('perfusion_active', true)->count(),
    ]);
});


// routes pour les differenst esp c3 dispositif et brassard

Route::post('/pairing/create', [PairingController::class, 'create']);      // admin/dashboard
Route::get ('/pairing/qr/{token}', [PairingController::class, 'qr']);      // image PNG du QR
Route::post('/pairing/claim', [PairingController::class, 'claim']);        // appli mobile

Route::post('/iot/register', [DeviceController::class, 'register']);       // ESP boot/register
Route::post('/iot/push',     [ReadingController::class, 'ingest']);        // ESP push data

Route::get('/patients/{id}/readings', [ReadingController::class, 'list']); // dashboard/appli

// route definis de la table pression:
// Route::get('/pressure', [MesureController::class, 'store'])->name('mesures.store');
// // Route::post('/esp32/mesures', [MesureController::class, 'store'])->name('mesures.store');

// Route::get('/pressure', [PressureController::class, 'index']);
use App\Http\Controllers\PressureController;

Route::post('/pressures', [PressureController::class, 'store']);
Route::get('/pressures/latest', [PressureController::class, 'latest']);
Route::get('/pressures', [PressureController::class, 'all']);
Route::get('/pressures/latest', [PressureController::class, 'latest']);


Route::get('/dispositifs', [DispositifController::class, 'index']);
Route::get('/dispositifs/latest', [DispositifController::class, 'latest']); // ⬅️ AVANT {id}
Route::get('/dispositifs/{id}', [DispositifController::class, 'show']);
Route::post('/dispositifs', [DispositifController::class, 'store']);

// Routes ESP32
Route::get('/fetch-esp32', [DispositifController::class, 'fetchFromESP32']);

// routes pour la gestion patient

Route::apiResource('gestionpatients', GestionPatientController::class);

//les routespour les notifications 
Route::get('notifications/unread', [NotificationMedicaleController::class, 'unread']);
Route::put('notifications/{id}/read', [NotificationMedicaleController::class, 'markAsRead']);
Route::apiResource('notifications', NotificationMedicaleController::class);


// websocket


// ✅ Routes pour détecter les nouvelles connexions
Route::get('connections/check', [ConnectionController::class, 'checkNewConnections']);
Route::get('connections/latest-serial', [ConnectionController::class, 'getLatestSerial']);


// ✅ Routes pour l'API Connexion (sans conflit avec device_connections)
Route::prefix('connexions')->group(function () {
    Route::get('check', [ConnexionController::class, 'checkNewConnections']);
    Route::get('latest-serial', [ConnexionController::class, 'getLatestSerial']);
    Route::post('{serial}/mark-registered', [ConnexionController::class, 'markAsRegistered']);
    Route::get('/', [ConnexionController::class, 'index']);
    Route::post('{serial}/disconnect', [ConnexionController::class, 'disconnect']);
});
Route::prefix('connexions')->group(function () {
    Route::get('check', [ConnexionController::class, 'checkNewConnections']);
    Route::get('latest-serial', [ConnexionController::class, 'getLatestSerial']);
    Route::post('{serial}/mark-registered', [ConnexionController::class, 'markAsRegistered']);
    Route::get('detect-change', [ConnexionController::class, 'detectSerialChange']);
});

Route::get('/notifications', [NotificationMedicaleController::class, 'index']);
Route::post('/notifications', [NotificationMedicaleController::class, 'store']);
Route::get('/notifications/unread', [NotificationMedicaleController::class, 'unread']);
Route::patch('/notifications/{id}/mark-read', [NotificationMedicaleController::class, 'markAsRead']);
Route::delete('/notifications/{id}', [NotificationMedicaleController::class, 'destroy']);





Route::get('/health', function () {
    try {
        \App\Models\Patient::count();
        return response()->json([
            'status'    => 'healthy',
            'timestamp' => now(),
            'checks'    => [
                'database' => 'OK',
                'models'   => 'OK',
            ],
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status'    => 'unhealthy',
            'error'     => 'Database connection failed',
            'timestamp' => now(),
        ], 503);
    }
});
