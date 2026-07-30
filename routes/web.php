<?php

use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Pos\ReceiptController;
use App\Http\Controllers\Reports\ExportController;
use App\Http\Controllers\Webhooks\MidtransWebhookController;
use App\Livewire\Admin\Categories\Index as CategoriesIndex;
use App\Livewire\Admin\Products\Index as ProductsIndex;
use App\Livewire\Admin\Settings\Index as SettingsIndex;
use App\Livewire\Admin\Users\Index as UsersIndex;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\PendingApproval;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Platform\Companies\Index as PlatformCompaniesIndex;
use App\Livewire\Pos\Terminal;
use App\Livewire\Reports\Dashboard;
use App\Livewire\Transactions\Index as TransactionsIndex;
use Illuminate\Support\Facades\Route;

// Merge this group into your project's existing routes/web.php.
Route::middleware('guest')->group(function () {
    // Named 'login' — Laravel's default auth middleware redirects
    // unauthenticated users to a route with this exact name automatically.
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
    Route::get('/forgot-password', ForgotPassword::class)->name('password.request');
    // {token} only — the notification appends ?email= as a query string,
    // route('password.reset', [...]) does the same when generating it.
    Route::get('/reset-password/{token}', ResetPassword::class)->name('password.reset');
});

Route::middleware('auth')->group(function () {
    // Reachable regardless of approval status — a pending/rejected owner
    // has to be able to see why, and a rejected/approved one shouldn't be
    // stuck seeing a stale waiting screen either; see PendingApproval::mount().
    Route::get('/menunggu-persetujuan', PendingApproval::class)->name('company.pending');

    // Hit directly from the link in the VerifyEmail email, never in-app —
    // 'signed' checks the URL hasn't been tampered with, 'throttle' caps
    // how many times it can be replayed. Verifying an email doesn't need
    // company approval, so this also sits outside the 'approved' group.
    Route::get('/email/verify/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    // The SaaS operator's own review queue, not part of any tenant's
    // operational app — deliberately outside the 'approved' group entirely
    // (EnsureCompanyIsApproved also exempts Platform Admin explicitly).
    Route::prefix('platform')->name('platform.')->middleware('role:platform_admin')->group(function () {
        Route::get('/companies', PlatformCompaniesIndex::class)->name('companies');
    });

    // Everything below requires the owning company to be approved — see
    // EnsureCompanyIsApproved (registered as the 'approved' alias).
    Route::middleware('approved')->group(function () {
        Route::get('/pos', Terminal::class)->name('pos.terminal');
        // Open to any authenticated role (not just Manager/Superadmin) — a
        // Cashier needs to print/reprint the receipt for a sale they just rang up.
        Route::get('/pos/receipt/{transaction}', ReceiptController::class)->name('pos.receipt');
        Route::get('/riwayat', TransactionsIndex::class)->name('transactions.index');

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
});

// Public — hit by Midtrans's servers, not a logged-in user. Must be
// excluded from CSRF verification in bootstrap/app.php; see SETUP.md.
Route::post('/webhooks/midtrans', MidtransWebhookController::class)->name('webhooks.midtrans');
