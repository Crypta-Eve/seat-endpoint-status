<?php
Route::group([
    'namespace' => 'CryptaEve\Seat\EndpointStatus\Http\Controllers',
    'middleware' => ['web', 'auth', 'can:global.superuser'],
    'prefix' => 'endstatus'
], function () {
    Route::get('/endpoint-status', [
        'as'   => 'cryptaendpointstatus::status',
        'uses' => 'EndpointStatusController@getStatusView'
        // 'middleware' => ''
    ]);

});