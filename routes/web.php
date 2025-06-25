<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->get('/', function () use ($router) {
    return $router->app->version();
});

// Auth routes
$router->post('/login', 'AuthController@login');
$router->post('/register', 'AuthController@register');
$router->post('/logout', ['middleware' => 'auth', 'uses' => 'AuthController@logout']);
$router->get('/me', ['middleware' => 'auth', function () {
    return new \App\Http\Resources\UserResource(auth()->user());
}]);

// Common routes
$router->group(['middleware' => 'auth'], function () use ($router) {
    $router->get('/today', 'AttendanceController@today');
    
    // Permissions
    $router->group(['prefix' => 'permissions'], function () use ($router) {
        $router->post('/', 'PermissionController@store');
        $router->get('/', 'PermissionController@index');
    });
});

// Admin routes
$router->group(['prefix' => 'admin', 'middleware' => ['auth', 'role:admin']], function () use ($router) {
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

    $router->get('/karyawan', 'UserController@karyawan');
    $router->post('/karyawan', 'UserController@createKaryawan');
    $router->get('/karyawan/{id}', 'UserController@showKaryawan');
    $router->put('/karyawan/{id}', 'UserController@updateKaryawan');
    $router->delete('/karyawan/{id}', 'UserController@destroyKaryawan');

    $router->group(['prefix' => 'permissions'], function () use ($router) {
        $router->get('/', 'PermissionController@adminIndex');
        $router->put('/{id}/status', 'PermissionController@updateStatus');
    });
});

// Karyawan routes
$router->group(['middleware' => ['auth', 'role:karyawan']], function () use ($router) {
    $router->post('/clock-in', 'AttendanceController@clockIn');
    $router->post('/clock-out', 'AttendanceController@clockOut');
    $router->get('/history', 'AttendanceController@history');
    $router->get('/history/{id}', 'AttendanceController@showHistory');
});
