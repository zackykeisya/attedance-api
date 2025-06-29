<?php
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

/** @var \Laravel\Lumen\Routing\Router $router */

// Versi App
$router->get('/', function () use ($router) {
    return $router->app->version();
});

// ======================
// AUTH ROUTES
// ======================
$router->post('/login', 'AuthController@login');
$router->post('/register', 'AuthController@register');
$router->post('/logout', ['middleware' => 'auth', 'uses' => 'AuthController@logout']);
$router->get('/me', ['middleware' => 'auth', function () {
    return new \App\Http\Resources\UserResource(auth()->user());
}]);

// ======================
// COMMON (AUTH) ROUTES
// ======================
$router->group(['middleware' => 'auth'], function () use ($router) {
    $router->get('/today', 'AttendanceController@today');

   // Permission - Karyawan
$router->group(['prefix' => 'permissions', 'middleware' => 'auth'], function () use ($router) {
    $router->post('/', 'PermissionController@store');         // Mengajukan izin
    $router->get('/', 'PermissionController@index');          // Melihat semua izin user
    $router->get('/today', 'PermissionController@today');     // Cek status izin hari ini
    $router->put('/{id}/status', 'PermissionController@updateStatus'); // Admin update status izin (optional)
});

});

// ======================
// ADMIN ROUTES
// ======================
$router->group(['prefix' => 'admin', 'middleware' => ['auth', 'role:admin']], function () use ($router) {

    // Absensi Admin
    $router->get('/absensi', 'AttendanceController@allHistory');
    $router->get('/absensi/user', 'AttendanceController@filterByUser');
    $router->get('/absensi/tanggal', 'AttendanceController@filterByDate');
    $router->get('/absensi/filter', 'AttendanceController@filterCombined');
    $router->get('/absensi/statistik', 'AttendanceController@statistik');
    $router->get('/absensi/export/excel', 'AttendanceController@exportExcel');
    $router->get('/absensi/export/pdf', 'AttendanceController@exportPdf');
    $router->put('/absensi/{id}/reset', 'AttendanceController@reset');
    $router->post('/absensi/reset-day', 'AttendanceController@resetDay');
    $router->post('/absensi/skip-day', 'AttendanceController@skipDay');
    $router->get('/absensi/terlambat-hari-ini', 'AttendanceController@terlambatHariIni');


    // Karyawan Admin
    $router->get('/karyawan', 'UserController@karyawan');
    $router->post('/karyawan', 'UserController@createKaryawan');
    $router->get('/karyawan/{id}', 'UserController@showKaryawan');
    $router->put('/karyawan/{id}', 'UserController@updateKaryawan');
    $router->delete('/karyawan/{id}', 'UserController@destroyKaryawan');

    // Permissions Admin
    $router->group(['prefix' => 'permissions'], function () use ($router) {
        $router->get('/', 'PermissionController@adminIndex');
        $router->put('/{id}/status', 'PermissionController@updateStatus');
    });
});
$router->put('/admin/permissions/{id}/reset', 'PermissionController@reset');

// ======================
// KARYAWAN ROUTES
// ======================
$router->group(['middleware' => ['auth', 'role:karyawan']], function () use ($router) {
    $router->post('/clock-in', 'AttendanceController@clockIn');
    $router->post('/clock-out', 'AttendanceController@clockOut');
    $router->get('/history', 'AttendanceController@history');
    $router->get('/history/{id}', 'AttendanceController@showHistory');
    $router->get('/history', 'AttendanceController@myHistory');
});


// ======================
// TANGGAL SERVER (GLOBAL)
// ======================
$router->get('/server-date', function () {
    try {
        $date = Cache::get('current_date', Carbon::now('Asia/Jakarta')->format('Y-m-d'));
        return response()->json(['date' => $date]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Gagal mengambil tanggal server',
            'error' => $e->getMessage()
        ], 500);
    }
});

