<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->post('/login', 'AuthController@login');

$router->group(['middleware' => ['auth', 'role:admin']], function () use ($router) {
    $router->get('/users', 'UserController@index');
    $router->post('/users', 'UserController@store');
    $router->get('/users/{id}', 'UserController@show');
    $router->put('/users/{id}', 'UserController@update');
    $router->delete('/users/{id}', 'UserController@destroy');
});

$router->group(['middleware' => ['auth', 'role:karyawan']], function () use ($router) {
    $router->post('/clock-in', 'AttendanceController@clockIn');
    $router->post('/clock-out', 'AttendanceController@clockOut');
    $router->get('/history', 'AttendanceController@history');
});
$router->group(['middleware' => ['auth', 'role:admin']], function () use ($router) {
    $router->get('/absensi', 'AttendanceController@allHistory');
});

$router->group(['middleware' => 'auth'], function () use ($router) {
    $router->get('/me', function () {
        return new \App\Http\Resources\UserResource(auth()->user());
    });
});

$router->get('/absensi/export/excel', ['middleware' => ['auth', 'role:admin'], function () {
    return Excel::download(new \App\Exports\AttendanceExport, 'absensi.xlsx');
}]);

$router->get('/absensi/statistik', ['middleware' => ['auth', 'role:admin'], function () {
    $data = \App\Models\Attendance::selectRaw('DATE_FORMAT(date, "%Y-%m") as bulan, COUNT(*) as total')
        ->groupBy('bulan')
        ->orderBy('bulan', 'desc')
        ->get();

    return response()->json($data);
}]);


