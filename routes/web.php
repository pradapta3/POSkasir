<?php

use App\Http\Controllers\Admin\LabelPrintController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Pos\ReceiptController;
use App\Http\Controllers\Reports\ExportController;
use App\Livewire\Admin\Categories\Index as CategoriesIndex;
use App\Livewire\Admin\Labels\Index as LabelsIndex;
use App\Livewire\Admin\Members\Index as MembersIndex;
use App\Livewire\Admin\Outlets\Index as OutletsIndex;
use App\Livewire\Admin\Products\Index as ProductsIndex;
use App\Livewire\Admin\Purchasing\Index as PurchasingIndex;
use App\Livewire\Admin\Settings\Index as SettingsIndex;
use App\Livewire\Admin\Suppliers\Index as SuppliersIndex;
use App\Livewire\Admin\Users\Index as UsersIndex;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\PendingApproval;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Billing\Index as BillingIndex;
use App\Livewire\Platform\Companies\Index as PlatformCompaniesIndex;
use App\Livewire\Platform\PaymentRequests\Index as PlatformPaymentRequestsIndex;
use App\Livewire\Platform\SubscriptionPlans\Index as PlatformSubscriptionPlansIndex;
use App\Livewire\Pos\Terminal;
use App\Livewire\Reports\Dashboard;
use App\Livewire\Reports\Inventory as InventoryReport;
use App\Livewire\Reports\OutletComparison;
use App\Livewire\Transactions\Index as TransactionsIndex;
use Illuminate\Support\Facades\Route;

// No auth/approval gate and no company data — sw.js serves this from its
// cache when a navigation fails offline (see that file's docblock), but it
// still needs a real, fetchable route so the service worker can precache
// it on install.
Route::view('/offline', 'offline')->name('offline');

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
    // Reachable regardless of approval status — a pending/rejected/expired
    // owner has to be able to see why, and a cleared-to-operate one
    // shouldn't be stuck seeing a stale waiting screen either; see
    // PendingApproval::mount().
    Route::get('/menunggu-persetujuan', PendingApproval::class)->name('company.pending');

    // Hit directly from the link in the VerifyEmail email, never in-app —
    // 'signed' checks the URL hasn't been tampered with, 'throttle' caps
    // how many times it can be replayed. Verifying an email doesn't need
    // company approval, so this also sits outside the 'approved' group.
    Route::get('/email/verify/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    // "Langganan Saya" — deliberately outside the 'approved' group too: a
    // company whose trial/subscription just expired must still be able to
    // reach this to renew, or EnsureCompanyIsApproved would lock them out
    // of the one screen that lets them fix it.
    Route::middleware('role:superadmin')->group(function () {
        Route::get('/langganan', BillingIndex::class)->name('billing.index');
    });

    // The SaaS operator's own review queue, not part of any tenant's
    // operational app — deliberately outside the 'approved' group entirely
    // (EnsureCompanyIsApproved also exempts Platform Admin explicitly).
    Route::prefix('platform')->name('platform.')->middleware('role:platform_admin')->group(function () {
        Route::get('/companies', PlatformCompaniesIndex::class)->name('companies');
        Route::get('/payment-requests', PlatformPaymentRequestsIndex::class)->name('payment-requests');
        Route::get('/plans', PlatformSubscriptionPlansIndex::class)->name('plans');
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
            Route::get('/reports/inventory', InventoryReport::class)->name('reports.inventory');
            Route::get('/reports/export/transactions', [ExportController::class, 'transactions'])->name('reports.export.transactions');

            Route::prefix('admin')->name('admin.')->group(function () {
                Route::get('/products', ProductsIndex::class)->name('products');
                Route::get('/categories', CategoriesIndex::class)->name('categories');
                Route::get('/suppliers', SuppliersIndex::class)->name('suppliers');
                Route::get('/purchasing', PurchasingIndex::class)->name('purchasing');
                Route::get('/labels', LabelsIndex::class)->name('labels');
                // Plain controller, not Livewire — opened in a new tab via
                // window.open() from Labels\Index::printLabels(), never
                // wire:navigate; see LabelPrintController's docblock.
                Route::get('/labels/print', LabelPrintController::class)->name('labels.print');
                Route::get('/members', MembersIndex::class)->name('members');
                // A Manager can reach this screen too (staffing is part of the
                // Manager role), but Index::canManage() blocks editing/deactivating
                // a Superadmin account unless the actor is one themselves.
                Route::get('/users', UsersIndex::class)->name('users');

                // Store-wide structural/financial config — Superadmin only,
                // unlike the routes above which a Manager can also reach.
                Route::middleware('role:superadmin')->group(function () {
                    Route::get('/outlets', OutletsIndex::class)->name('outlets');
                    Route::get('/settings', SettingsIndex::class)->name('settings');
                });
            });

            // Cross-outlet comparison is a Superadmin-level decision, not a
            // per-branch Manager one — see OutletComparison's docblock.
            Route::middleware('role:superadmin')->group(function () {
                Route::get('/reports/outlets', OutletComparison::class)->name('reports.outlets');
            });
        });
    });
});
