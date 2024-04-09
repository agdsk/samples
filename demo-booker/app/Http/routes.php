<?php

//-----------------------------------------------------------------------------
// Routes in no group
//-----------------------------------------------------------------------------

Route::get('/',                                       'HomeController@index');

//-----------------------------------------------------------------------------
// Routes for API requests
//-----------------------------------------------------------------------------

Route::group(['middleware' => ['api']], function () {
    Route::get('/api/language',                       'ApiController@getLanguage');
    Route::post('/api/notifications',                 'ApiController@createNotificationSignup');
    Route::get('/api/reservation/{hash}',             'ApiController@getReservation');
    Route::post('/api/reservation/{hash}/cancel',     'ApiController@cancelReservation');
    Route::post('/api/reservation/{hash}',            'ApiController@updateReservation');
    Route::post('/api/reservations',                  'ApiController@createReservation');
    Route::get('/api/promotion/{promotion}',          'ApiController@checkPromoCode');
    Route::get('/api/locations',                      'ApiController@getAllLocations');
    Route::get('/api/location/{id}/{date?}/{range?}', 'ApiController@getLocation');
    Route::get('/api/calendar/{hash}',                'ApiController@getCalendarFile');
    Route::get('/api/brand/{slug}',                   'ApiController@getBrand');
    Route::get('/api/brands',                         'ApiController@listBrands');
});

//-----------------------------------------------------------------------------
// Routes for logged out users only
//-----------------------------------------------------------------------------

Route::group(['middleware' => ['guest']], function () {
    Route::get('login',                               'AuthController@login');
    Route::post('login',                              'AuthController@postLogin');
    Route::get('token/{token}',                       'AuthController@token');
    Route::get('forgot',                              'ForgotPasswordController@forgot');
    Route::post('forgot',                             'ForgotPasswordController@forgotPost');
});

//-----------------------------------------------------------------------------
// Routes for authenticated users
//-----------------------------------------------------------------------------

Route::group(['middleware' => ['auth']], function () {
    Route::any('/logout',                             'AuthController@logout');
    Route::get('/account/password',                   'AccountController@password');
    Route::post('/account/password',                  'AccountController@passwordPost');
});

//-----------------------------------------------------------------------------
// Routes for Admins
//-----------------------------------------------------------------------------

Route::group(['middleware' => ['admin']], function () {
    Route::resource('overrides',                      'OverridesController');
    Route::resource('brands',                         'BrandsController');
    Route::resource('scripts',                        'ScriptsController');
    Route::resource('promotions',                     'PromotionsController');
    Route::get('tools/invalid_reservations',          'ToolsController@findInvalidReservations');
    Route::post('tools/invalid_reservations',         'ToolsController@findInvalidReservationsCancel');
    Route::get('tools/duplicate_locations',           'ToolsController@findDuplicateLocations');
    Route::get('tools/mass_override_creator',         'ToolsController@massOverrideForm');
    Route::post('tools/mass_override_creator',        'ToolsController@massOverrideCreate');
});

//-----------------------------------------------------------------------------
// Routes for Managers
//-----------------------------------------------------------------------------

Route::group(['middleware' => ['manager']], function () {
    Route::resource('locations',                      'LocationsController');
    Route::post('/locations/{id}/repair',             'LocationsController@repair');
    Route::get('/locations/{id}/overrides',           'LocationsOverridesController@create');
    Route::post('/locations/{id}/overrides',          'LocationsOverridesController@store');
    Route::delete('/locations/{id}/overrides/{o_id}', 'LocationsOverridesController@destroy');
    Route::resource('users',                          'UsersController');
});

//-----------------------------------------------------------------------------
// Routes for Ambassadors
//-----------------------------------------------------------------------------

Route::group(['middleware' => ['ambassador']], function () {
    Route::get('/appointments',                       'ReservationsController@index');
    Route::get('/appointments/{id}/create',           'ReservationsController@createReservation');
    Route::get('/appointments/{id}/{date?}',          'ReservationsController@location');
    Route::post('/appointments/save',                 'ReservationsController@saveReservation');
    Route::post('/appointments/cancel',               'ReservationsController@cancelReservation');
    Route::post('/appointments/checkin',              'ReservationsController@checkinReservation');
    Route::post('/appointments/complete',             'ReservationsController@demoReservation');
    Route::post('/appointments/{id}',                 'ReservationsController@update');
    Route::get('/scripts/{id}',                       'ScriptsController@show');
    Route::get('/reminder',                           'ReminderController@index');
    Route::post('/reminder',                          'ReminderController@create');
});