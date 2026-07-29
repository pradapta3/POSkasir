<?php

use App\Http\Controllers\Reports\ExportController;
use App\Http\Controllers\Webhooks\MidtransWebhookController;
use App\Livewire\Admin\Categories\Index as CategoriesIndex;
use App\Livewire\Admin\Products\Index as ProductsIndex;
use App\Livewire\Admin\Settings\Index as SettingsIndex;
use App\Livewire\Admin\Users\Index as UsersIndex;
use App\Livewire\Auth\Login;
use App\Livewire\Pos\Terminal;
use App\Livewire\Reports\Dashboard;
use Illuminate\Support\Facades\Route;

// Merge this group into your project's existing routes/web.php.
Route::middleware('guest')->group(function () {
    // Named 'login' — Laravel's default auth middleware redirects
    // unauthenticated users to a route with this exact name automatically.
    Route::get('/login', Login::class)->name('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/pos', Terminal::class)->name('pos.terminal');

    // Manager/Superadmin only — requires the 'role' middleware alias
    // registered in bootstrap/app.php; see SETUP.md.
    Route::middleware('role:superadmin,manager')->group(function () {
        Route::get('/reports', Dashboard::class)->name('reports.dashboard');
        Route::get('/reports/export/transactions', [ExportController::class, 'transactions'])->name('reports.export.transactions');

        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('/products', ProductsIndex::class)->name('products');
            Route::get('/categories', CategoriesIndex::class)->name('categories');
            // A Manager can reach this screen too (staffing is part of the
            // Manager role), but Index::canManage() blocks editing/deactivating
            // a Superadmin account unless the actor is one themselves.
            Route::get('/users', UsersIndex::class)->name('users');

            // Store-wide financial config (tax rate, etc.) — Superadmin only,
            // unlike the routes above which a Manager can also reach.
            Route::middleware('role:superadmin')->group(function () {
                Route::get('/settings', SettingsIndex::class)->name('settings');
            });
        });
    });
});

// Public — hit by Midtrans's servers, not a logged-in user. Must be
// excluded from CSRF verification in bootstrap/app.php; see SETUP.md.
Route::post('/webhooks/midtrans', MidtransWebhookController::class)->name('webhooks.midtrans');
