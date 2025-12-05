<?php
/**
 * TCT-YMS Web Routes
 *
 * All HTTP routes are defined here.
 */

use App\Router;

// ============================================================
// Public Routes (Guest Only)
// ============================================================

Router::middleware('guest')->group(function () {
    Router::get('/login', 'AuthController@showLogin')->name('login');
    Router::post('/login', 'AuthController@login');
    Router::get('/forgot-password', 'AuthController@showForgotPassword')->name('password.forgot');
    Router::post('/forgot-password', 'AuthController@sendResetLink');
    Router::get('/reset-password/{token}', 'AuthController@showResetPassword')->name('password.reset');
    Router::post('/reset-password', 'AuthController@resetPassword');
});

// ============================================================
// Authenticated Routes
// ============================================================

Router::middleware('auth')->group(function () {
    // Logout
    Router::post('/logout', 'AuthController@logout')->name('logout');
    Router::get('/logout', 'AuthController@logout'); // Allow GET for convenience

    // Dashboard
    Router::get('/', 'DashboardController@index')->name('home');
    Router::get('/dashboard', 'DashboardController@index')->name('dashboard');
    Router::get('/dashboard/stats', 'DashboardController@stats')->name('dashboard.stats');

    // Profile
    Router::get('/profile', 'ProfileController@show')->name('profile');
    Router::post('/profile', 'ProfileController@update');
    Router::post('/profile/password', 'ProfileController@updatePassword');
    Router::post('/profile/preferences', 'ProfileController@updatePreferences');

    // ============================================================
    // Yard Map
    // ============================================================
    Router::get('/yard-map', 'YardMapController@index')->name('yard-map');
    Router::get('/yard-map/data', 'YardMapController@getData')->name('yard-map.data');
    Router::post('/yard-map/move', 'YardMapController@moveTrailer')->name('yard-map.move');

    // ============================================================
    // Gate Module
    // ============================================================
    Router::prefix('/gate')->group(function () {
        Router::get('/', 'GateController@index')->name('gate');
        Router::get('/check-in', 'GateController@showCheckIn')->name('gate.check-in');
        Router::post('/check-in', 'GateController@checkIn');
        Router::get('/check-out', 'GateController@showCheckOut')->name('gate.check-out');
        Router::get('/check-out/{id}', 'GateController@showCheckOutTrailer')->name('gate.check-out.trailer');
        Router::post('/check-out/{id}', 'GateController@checkOut');
        Router::get('/history', 'GateController@history')->name('gate.history');
        Router::get('/search-trailer', 'GateController@searchTrailer')->name('gate.search-trailer');
    });

    // ============================================================
    // Trailers Module
    // ============================================================
    Router::prefix('/trailers')->group(function () {
        Router::get('/', 'TrailerController@index')->name('trailers');
        Router::get('/create', 'TrailerController@create')->name('trailers.create');
        Router::post('/', 'TrailerController@store');
        Router::get('/{id}', 'TrailerController@show')->name('trailers.show');
        Router::get('/{id}/edit', 'TrailerController@edit')->name('trailers.edit');
        Router::post('/{id}', 'TrailerController@update');
        Router::post('/{id}/status', 'TrailerController@updateStatus')->name('trailers.status');
        Router::post('/{id}/delete', 'TrailerController@delete')->name('trailers.delete');
        Router::get('/{id}/history', 'TrailerController@history')->name('trailers.history');
        Router::get('/search', 'TrailerController@search')->name('trailers.search');
        Router::get('/export', 'TrailerController@export')->name('trailers.export');
    });

    // ============================================================
    // Dock Doors Module
    // ============================================================
    Router::prefix('/dock-doors')->group(function () {
        Router::get('/', 'DockDoorController@index')->name('dock-doors');
        Router::get('/data', 'DockDoorController@getData')->name('dock-doors.data');
        Router::post('/{id}/assign', 'DockDoorController@assign')->name('dock-doors.assign');
        Router::post('/{id}/release', 'DockDoorController@release')->name('dock-doors.release');
        Router::post('/{id}/status', 'DockDoorController@updateStatus')->name('dock-doors.status');
    });

    // ============================================================
    // Move Tasks Module
    // ============================================================
    Router::prefix('/moves')->group(function () {
        Router::get('/', 'MoveTaskController@index')->name('moves');
        Router::get('/create', 'MoveTaskController@create')->name('moves.create');
        Router::post('/', 'MoveTaskController@store');
        Router::get('/{id}', 'MoveTaskController@show')->name('moves.show');
        Router::post('/{id}/assign', 'MoveTaskController@assign')->name('moves.assign');
        Router::post('/{id}/claim', 'MoveTaskController@claim')->name('moves.claim');
        Router::post('/{id}/start', 'MoveTaskController@start')->name('moves.start');
        Router::post('/{id}/complete', 'MoveTaskController@complete')->name('moves.complete');
        Router::post('/{id}/cancel', 'MoveTaskController@cancel')->name('moves.cancel');
        Router::get('/queue', 'MoveTaskController@queue')->name('moves.queue');
    });

    // ============================================================
    // Reports Module
    // ============================================================
    Router::prefix('/reports')->group(function () {
        Router::get('/', 'ReportController@index')->name('reports');
        Router::get('/yard-inventory', 'ReportController@yardInventory')->name('reports.yard-inventory');
        Router::get('/gate-activity', 'ReportController@gateActivity')->name('reports.gate-activity');
        Router::get('/move-history', 'ReportController@moveHistory')->name('reports.move-history');
        Router::get('/dwell-time', 'ReportController@dwellTime')->name('reports.dwell-time');
        Router::get('/export/{type}', 'ReportController@export')->name('reports.export');
    });

    // ============================================================
    // Spotter Mobile App
    // ============================================================
    Router::prefix('/spotter')->group(function () {
        Router::get('/', 'SpotterController@index')->name('spotter');
        Router::get('/tasks', 'SpotterController@tasks')->name('spotter.tasks');
        Router::get('/task/{id}', 'SpotterController@showTask')->name('spotter.task');
        Router::post('/task/{id}/start', 'SpotterController@startTask')->name('spotter.task.start');
        Router::post('/task/{id}/complete', 'SpotterController@completeTask')->name('spotter.task.complete');
        Router::get('/yard-view', 'SpotterController@yardView')->name('spotter.yard-view');
    });

    // ============================================================
    // Admin Routes
    // ============================================================
    Router::middleware('role:admin,supervisor')->prefix('/admin')->group(function () {
        Router::get('/', 'Admin\\DashboardController@index')->name('admin');

        // Users
        Router::prefix('/users')->group(function () {
            Router::get('/', 'Admin\\UserController@index')->name('admin.users');
            Router::get('/create', 'Admin\\UserController@create')->name('admin.users.create');
            Router::post('/', 'Admin\\UserController@store');
            Router::get('/{id}/edit', 'Admin\\UserController@edit')->name('admin.users.edit');
            Router::post('/{id}', 'Admin\\UserController@update');
            Router::post('/{id}/delete', 'Admin\\UserController@delete')->name('admin.users.delete');
            Router::post('/{id}/toggle-active', 'Admin\\UserController@toggleActive');
        });

        // Roles (admin only)
        Router::middleware('role:admin')->prefix('/roles')->group(function () {
            Router::get('/', 'Admin\\RoleController@index')->name('admin.roles');
            Router::get('/{id}/edit', 'Admin\\RoleController@edit')->name('admin.roles.edit');
            Router::post('/{id}', 'Admin\\RoleController@update');
        });

        // Carriers
        Router::prefix('/carriers')->group(function () {
            Router::get('/', 'Admin\\CarrierController@index')->name('admin.carriers');
            Router::get('/create', 'Admin\\CarrierController@create')->name('admin.carriers.create');
            Router::post('/', 'Admin\\CarrierController@store');
            Router::get('/{id}/edit', 'Admin\\CarrierController@edit')->name('admin.carriers.edit');
            Router::post('/{id}', 'Admin\\CarrierController@update');
            Router::post('/{id}/delete', 'Admin\\CarrierController@delete');
            Router::get('/import', 'Admin\\CarrierController@showImport')->name('admin.carriers.import');
            Router::post('/import', 'Admin\\CarrierController@import');
        });

        // Yard Layout
        Router::middleware('role:admin')->prefix('/yard-layout')->group(function () {
            Router::get('/', 'Admin\\YardLayoutController@index')->name('admin.yard-layout');
            // Zones
            Router::get('/zones', 'Admin\\YardLayoutController@zones')->name('admin.yard-layout.zones');
            Router::post('/zones', 'Admin\\YardLayoutController@storeZone');
            Router::post('/zones/{id}', 'Admin\\YardLayoutController@updateZone');
            Router::post('/zones/{id}/delete', 'Admin\\YardLayoutController@deleteZone');
            // Rows
            Router::get('/rows', 'Admin\\YardLayoutController@rows')->name('admin.yard-layout.rows');
            Router::post('/rows', 'Admin\\YardLayoutController@storeRow');
            Router::post('/rows/{id}', 'Admin\\YardLayoutController@updateRow');
            Router::post('/rows/{id}/delete', 'Admin\\YardLayoutController@deleteRow');
            // Dock Doors
            Router::get('/doors', 'Admin\\YardLayoutController@doors')->name('admin.yard-layout.doors');
            Router::post('/doors', 'Admin\\YardLayoutController@storeDoor');
            Router::post('/doors/{id}', 'Admin\\YardLayoutController@updateDoor');
            Router::post('/doors/{id}/delete', 'Admin\\YardLayoutController@deleteDoor');
        });

        // Statuses
        Router::middleware('role:admin')->prefix('/statuses')->group(function () {
            Router::get('/', 'Admin\\StatusController@index')->name('admin.statuses');
            Router::get('/create', 'Admin\\StatusController@create')->name('admin.statuses.create');
            Router::post('/', 'Admin\\StatusController@store');
            Router::get('/{id}/edit', 'Admin\\StatusController@edit')->name('admin.statuses.edit');
            Router::post('/{id}', 'Admin\\StatusController@update');
            Router::post('/{id}/delete', 'Admin\\StatusController@delete');
            // Transitions
            Router::get('/transitions', 'Admin\\StatusController@transitions')->name('admin.statuses.transitions');
            Router::post('/transitions', 'Admin\\StatusController@updateTransitions');
        });

        // Settings
        Router::middleware('role:admin')->prefix('/settings')->group(function () {
            Router::get('/', 'Admin\\SettingController@index')->name('admin.settings');
            Router::post('/', 'Admin\\SettingController@update');
            Router::get('/features', 'Admin\\SettingController@features')->name('admin.settings.features');
            Router::post('/features', 'Admin\\SettingController@updateFeatures');
        });

        // Import/Export
        Router::prefix('/import-export')->group(function () {
            Router::get('/', 'Admin\\ImportExportController@index')->name('admin.import-export');
            Router::get('/templates/{type}', 'Admin\\ImportExportController@downloadTemplate');
            Router::post('/import/{type}', 'Admin\\ImportExportController@import');
            Router::get('/export/{type}', 'Admin\\ImportExportController@export');
        });
    });
});

// ============================================================
// API Routes (for AJAX calls)
// ============================================================

Router::middleware('auth')->prefix('/api')->group(function () {
    Router::get('/trailers', 'Api\\TrailerController@index');
    Router::get('/trailers/{id}', 'Api\\TrailerController@show');
    Router::get('/dock-doors', 'Api\\DockDoorController@index');
    Router::get('/yard-slots', 'Api\\YardSlotController@index');
    Router::get('/carriers', 'Api\\CarrierController@index');
    Router::get('/statuses', 'Api\\StatusController@index');
    Router::get('/dashboard/stats', 'Api\\DashboardController@stats');
    Router::get('/moves/pending', 'Api\\MoveTaskController@pending');
});
