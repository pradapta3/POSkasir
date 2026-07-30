# POS Kasir — Setup

This folder currently contains only the **application-layer code** for
Phases 1–5 plus a login portal and admin CRUD screens (migrations, models,
enums, seeders, the POS Livewire terminal, QRIS payment service + webhook,
WhatsApp invoice job, analytics dashboard + Excel export, authentication,
Products/Categories/Users management). PHP/Composer are not available in
this environment, so the Laravel framework skeleton itself has not been
generated here yet.

## To assemble the project locally

1. Scaffold a fresh Laravel app in this folder (requires PHP 8.2+ and Composer):

   ```bash
   composer create-project laravel/laravel . 
   ```

2. Require Livewire 3 (Alpine.js ships bundled with it — no separate install needed) and Laravel Excel (used for the reconciliation export):

   ```bash
   composer require livewire/livewire maatwebsite/excel
   ```

3. Copy these directories into the newly created project, overwriting the
   generated `database/seeders/DatabaseSeeder.php`:

   - `app/Contracts/`
   - `app/Enums/`
   - `app/Events/`
   - `app/Exports/`
   - `app/Http/Controllers/Reports/` and `app/Http/Controllers/Webhooks/`
   - `app/Http/Middleware/EnsureUserHasRole.php`
   - `app/Jobs/`
   - `app/Listeners/`
   - `app/Livewire/`
   - `app/Models/` (overwrite the generated `app/Models/User.php`)
   - `app/Providers/PaymentServiceProvider.php` and `app/Providers/WhatsAppServiceProvider.php` (additive — don't remove the generated `AppServiceProvider.php`)
   - `app/Services/`
   - `config/midtrans.php` and `config/fonnte.php`
   - `database/migrations/` (adds to the default migrations — do not remove
     the default `0001_01_01_000000_create_users_table.php`, this Phase 1
     schema extends it)
   - `database/seeders/`
   - `resources/views/layouts/pos.blade.php` and `resources/views/layouts/guest.blade.php`
   - `resources/views/partials/admin-nav.blade.php`
   - `resources/views/livewire/pos/terminal.blade.php`
   - `resources/views/livewire/reports/dashboard.blade.php`
   - `resources/views/livewire/auth/login.blade.php`
   - `resources/views/livewire/admin/`
   - merge `routes/web.php` into the generated one (keep the default `/` route)
   - append the contents of `.env.example.pos-additions` to your `.env` and `.env.example`, and fill in your Midtrans sandbox keys (from https://dashboard.sandbox.midtrans.com) and Fonnte token (from https://fonnte.com after connecting a WhatsApp device)

4. Register the new providers in `bootstrap/providers.php`, **in this order**
   — `WhatsAppServiceProvider` must come after `PaymentServiceProvider` so
   the QRIS-generation listener runs before the WhatsApp-queueing listener
   for the same event (matters when `QUEUE_CONNECTION=sync`; see Phase 4 notes below):

   ```php
   return [
       App\Providers\AppServiceProvider::class,
       App\Providers\PaymentServiceProvider::class,
       App\Providers\WhatsAppServiceProvider::class,
   ];
   ```

5. In `bootstrap/app.php`, exclude the webhook route from CSRF verification
   (Midtrans posts to it without a Laravel session/CSRF token), register the
   `role` middleware alias used to gate `/reports` to Manager/Superadmin, and
   set where an already-logged-in user hitting `/login` gets sent (Laravel 11
   removed the old `RouteServiceProvider::HOME` constant this used to read):

   ```php
   ->withMiddleware(function (Middleware $middleware) {
       $middleware->redirectUsersTo(fn () => route('pos.terminal'));
       $middleware->validateCsrfTokens(except: ['webhooks/*']);
       $middleware->alias(['role' => \App\Http\Middleware\EnsureUserHasRole::class]);
   })
   ```

   Nothing needs to change for the *unauthenticated*-user redirect: Laravel's
   built-in auth middleware automatically sends guests to whatever route is
   named `login`, and `routes/web.php` already names it that.

6. Configure your `.env` for MySQL, then run:

   ```bash
   php artisan migrate --seed
   php artisan storage:link
   ```

   Migrate+seed creates all tables and seeds the three roles plus a default
   superadmin login: `admin@poskasir.test` / `password` (change immediately).
   `storage:link` is required for product images uploaded via `/admin/products`
   to actually be reachable at a public URL — without it they'll upload fine
   but 404 when the terminal tries to display them.

7. Visit `/login` and sign in as the seeded admin (`admin@poskasir.test` /
   `password`) — you'll land on `/pos`. Open the **Admin** menu in the header
   → **Categories** to add at least one category, then **Products** to add a
   product with some initial stock, before ringing up a sale. You'll be asked
   to open a shift with a starting cash amount before the terminal unlocks.

8. To test the QRIS flow locally, expose your app with a tunnel (e.g. `ngrok
   http 8000`) and set the tunnel's `https://.../webhooks/midtrans` URL as the
   **Payment Notification URL** in the Midtrans dashboard (Settings →
   Configuration). Ring up a QRIS/GoPay sale, then use Midtrans's sandbox
   simulator to fire a `settlement` notification for that `order_id`.

9. Set `QUEUE_CONNECTION=database` in `.env` (the default `sync` runs jobs
   inline, defeating the purpose of `SendWhatsAppInvoiceJob`), then run the
   default jobs migration if you haven't already and start a worker:

   ```bash
   php artisan queue:work
   ```

   Ring up a sale with a customer phone number filled in at checkout — an
   invoice should arrive on WhatsApp within a few seconds. Failed sends
   land in the `failed_jobs` table (`php artisan queue:failed`).

10. Visit `/reports` while logged in as a `superadmin` or `manager` user (the
    seeded admin qualifies) — a `cashier` gets a 403. Ring up a few paid
    sales first so the dashboard has something to show.

## Schema overview

- `roles` → `users` (role_id)
- `categories` → `products` (category_id, nullable)
- `products` ← `transaction_items` (snapshotted, not live-joined) and `stock_movements`
- `users` → `shifts` (a cashier opens/closes a shift with starting/expected/actual cash)
- `shifts` → `transactions` (every sale belongs to the shift it was made in)
- `customers` → `transactions` (nullable, used for WhatsApp billing in Phase 4)
- `transactions` → `transaction_items` (line items snapshot product name/SKU/price/cost at time of sale, so historical receipts and gross-profit reports stay accurate even if a product is later edited or deleted)
- `transactions` already carry `payment_gateway_reference`, `qris_payload`, `qris_url`, `payment_status` columns reserved for the Phase 3 QRIS/webhook integration
- `stock_movements` is an append-only audit log (`quantity_before`/`quantity_after`) with a polymorphic `reference` so future movement sources (purchase orders, etc.) can link back without a schema change

## Phase 2 — POS Livewire terminal

- `app/Livewire/Pos/Terminal.php` — the full-page Livewire component (route `/pos`).
- `app/Services/Pos/CartCalculator.php` — pure subtotal/discount/tax/grand-total math, no DB access, unit-testable in isolation.
- `app/Services/Pos/CheckoutService.php` — the only place that writes transactions/items/stock movements to the DB (checkout + hold). Keeps the Livewire component a thin presentation layer.
- `app/Events/TransactionCheckedOut.php` — fired after every completed sale. Phase 3 will add a listener to generate the QRIS code for non-cash payments; Phase 4 will add a listener to queue the WhatsApp invoice. Nothing in the terminal itself needs to change for either.
- Stock is deducted at checkout time for **every** payment method, including pending QRIS/GoPay — in an in-person POS the goods leave the shelf at the counter, not when a webhook later confirms payment.
- "Hold" persists the cart as a `HELD` transaction (no stock impact) so it can be resumed by any cashier; "Resume" reloads it into the cart and checkout() updates that same row instead of creating a duplicate.
- Opening/closing a shift is handled inline in the terminal (blocking modal if no shift is open; "End Shift" computes expected cash from paid cash transactions vs. the actual counted amount).

## Phase 3 — QRIS Service & Webhook

- `app/Services/Payment/PaymentService.php` — Midtrans Core API integration (`payment_type=qris`). Both the "QRIS" and "GoPay" options in the terminal route through this one call, since GoPay can scan a standard QRIS code — no separate GoPay-specific integration needed.
- `app/Contracts/PaymentGatewayInterface.php` — `CheckoutService`'s listener, the webhook controller, and the terminal all depend on this contract, not on `PaymentService` directly, so a different gateway can be swapped in via `PaymentServiceProvider` alone.
- `app/Listeners/GenerateQrisCode.php` — listens for `TransactionCheckedOut` and, for a pending non-cash sale, calls the gateway and saves `qris_url`/`qris_payload`/`payment_gateway_reference` onto the transaction. Runs synchronously (not queued) because the cashier needs the code on screen immediately; a gateway failure is logged and swallowed rather than blocking the sale.
- `app/Http/Controllers/Webhooks/MidtransWebhookController.php` — public endpoint, protected by SHA-512 signature verification (not auth). Idempotent: a transaction already marked `paid` is acknowledged without reprocessing, since Midtrans retries notifications.
- **Fixed while building this phase**: `CheckoutService::checkout()` previously dispatched `TransactionCheckedOut` *inside* the `DB::transaction()` block. Since the QRIS listener now makes a blocking HTTP call, that would have held row locks (including the `lockForUpdate()` on stock) open for the duration of a network round-trip. The event now fires after the transaction commits.
- The terminal shows a "Scan to Pay" modal with the QR image and polls (`wire:poll.3s`) until `payment_status` flips to `paid`, then shows the same completion toast as a cash sale.

## Phase 4 — WhatsApp Notification Job

- `app/Jobs/SendWhatsAppInvoiceJob.php` — the actual queued work: formats an itemized invoice (WhatsApp `*bold*` markdown) and sends it via the bound `WhatsAppGatewayInterface`. Sends the QRIS code as an image with the invoice as its caption when payment is still pending and a QR exists; otherwise sends the invoice as plain text (covers an already-paid cash sale where the customer just wants a digital receipt, and the case where QRIS generation failed).
- `app/Listeners/QueueWhatsAppInvoice.php` — the trigger: reacts to the same `TransactionCheckedOut` event as Phase 3's QRIS listener, and dispatches the job whenever a customer phone number was captured at checkout (that's the "opts to pay later or needs a digital invoice" signal — the terminal already asks for it in the payment modal from Phase 2).
- `app/Services/WhatsApp/FonnteGateway.php` — swappable for Wablas or a self-hosted Baileys bridge via `WhatsAppGatewayInterface`; nothing else in the app references Fonnte directly.
- **Why this needed a real queue, not `event()`**: the QRIS listener (Phase 3) is synchronous because the cashier needs the code on screen immediately. The WhatsApp send is a slow third-party API call with no such immediacy requirement, so it's a `ShouldQueue` job — `queue:work` picks it up in the background and checkout returns instantly regardless of Fonnte's response time.
- **Ordering dependency, handled defensively**: `SendWhatsAppInvoiceJob` re-fetches the transaction fresh from the database when it runs (not the in-memory copy from checkout), so it always sees whatever `qris_url` is saved by that point — correct under a real queue driver regardless of provider order, since the QRIS listener always finishes (same request) before a worker later picks up the queued job. The provider-order requirement only matters for `QUEUE_CONNECTION=sync`, where "dispatch" runs the job inline immediately.
- `whatsapp_notified_at` (new nullable timestamp on `transactions`, migration `2024_01_01_000010`) makes the job idempotent — a job that's already succeeded won't re-send if retried, and it gives you a column to check "did this customer actually get their invoice?" without digging through logs.
- **Not built** (out of this phase's stated scope, flagging in case you want it next): the requirement mentions "a secure payment link" as an alternative to the QR image — that implies a public, signed invoice-viewing page (`/invoice/{token}`), which is a new user-facing feature in its own right, not just a formatting change to this job. Happy to build it as a follow-on if useful.

## Phase 5 — Analytics & Excel Reporting

- `app/Services/Reports/SalesReportService.php` — every figure is a SQL aggregate (`SUM`/`COUNT`/`GROUP BY`), not a PHP loop over loaded models, so it stays fast as transaction volume grows. Everything is scoped to `payment_status = paid` — a still-pending QRIS sale isn't revenue or profit yet.
- `app/Livewire/Reports/Dashboard.php` (route `/reports`) — Today/This Week/This Month presets (the spec asked for daily and monthly; weekly is a low-cost, commonly-expected third option, same pattern Moka/Qasir use). KPI cards (revenue, gross profit, transaction count, average order value), a dependency-free CSS bar chart for sales-by-day, a top-products table, and the low-stock alert list required back in Phase 1 but never surfaced in any UI until now.
- `app/Exports/TransactionsExport.php` + `app/Http/Controllers/Reports/ExportController.php` — "Export to Excel" downloads an itemized ledger (one row per sale, with cost of goods and gross profit broken out) for whatever date range is currently on screen — built for reconciliation, not just a revenue summary.
- `app/Http/Middleware/EnsureUserHasRole.php` — the first actual enforcement of the "Multi-role Access Control" requirement from your original brief; `/reports` is gated to Superadmin/Manager (`role:superadmin,manager`), a Cashier gets a 403. The POS terminal itself is intentionally left open to any authenticated role, since a Manager or Superadmin should be able to run the till too.

## Phase 6 — Login portal

- `app/Livewire/Auth/Login.php` (route `/login`, `layouts/guest.blade.php`) — email/password + "remember me", rate-limited to 5 attempts per email+IP pair (Laravel's `RateLimiter` facade), redirects to `/pos` on success via Livewire's `wire:navigate`-powered `redirectRoute()`.
- **No public registration screen.** Deliberately not built: staff accounts (Cashier/Manager/Superadmin) are provisioned by whoever runs the store, not self-service signup — the same reasoning Moka/Qasir use. Account creation moved to `/admin/users` in Phase 7 below.
- **`is_active` is baked into the login credentials check itself** (`Auth::attempt(['email' => ..., 'password' => ..., 'is_active' => true])`), not verified after the fact — a deactivated account fails exactly like a wrong password, with the same generic error message, so the login form never reveals whether an email belongs to a disabled account.
- `app/Livewire/Actions/Logout.php` — the actual auth-clearing logic lives in one shared invokable class; every full-page component (`Terminal`, `Dashboard`, and the three Phase 7 admin screens) defines its own thin `logout(Logout $logout)` method that calls it and handles its own redirect, following the same pattern Laravel Breeze's Livewire stack uses.

## Phase 7 — Admin CRUD (Products, Categories, Users)

- `resources/views/partials/admin-nav.blade.php` — a shared header nav (`@include`d by every full-page component: Terminal, Dashboard, and all three admin screens) with an "Admin" dropdown, a link back to the POS terminal, and Logout. This replaced two rounds of copy-pasted header markup — worth calling out since it's the second time in this build that "add a nav link" turned into "extract a partial" once a third page needed the same thing.
- `app/Livewire/Admin/Products/Index.php` (`/admin/products`) — full CRUD with image upload (`WithFileUploads`), search, and category filter. **`stock_quantity` is deliberately not an editable field on the edit form** — only settable as "Initial Stock" at creation. Editing it directly would silently break the `StockMovement` audit log Phase 1 was built around; adjusting stock on an existing product goes through a separate "Adjust Stock" modal instead, which logs a proper `StockMovementType::ADJUSTMENT` entry.
- `app/Services/Inventory/StockAdjustmentService.php` — extracted from `CheckoutService`'s old private `deductStock()` method once the admin "Adjust Stock" feature needed to do the exact same "lock row, compute before/after, write a movement" sequence a second time. `CheckoutService` now calls this shared service too, instead of duplicating the logic — the sale-deduction path and manual-adjustment path are now provably consistent by construction.
- `app/Livewire/Admin/Categories/Index.php` (`/admin/categories`) — simpler CRUD; slug is auto-generated from the name (with a numeric suffix on collision) rather than user-edited.
- `app/Livewire/Admin/Users/Index.php` (`/admin/users`) — the one with real access-control logic: a Manager can create/edit Cashier and Manager accounts (matches the "Manages inventory, staff, and store reports" description seeded for that role back in Phase 1), but `canManage()` blocks a Manager from creating, editing, or deactivating a **Superadmin** account — that privilege stays Superadmin-only. A user also can't deactivate their own account (self-lockout guard). No hard delete for users: the `users` table has `restrictOnDelete()` foreign keys from `shifts`/`transactions`/`stock_movements` specifically to protect financial history, so a real delete would throw a DB error the moment that staff member had ever rung up a sale — "Deactivate" is the only destructive-ish action offered, which is also the operationally correct one (you don't erase an ex-employee's sales history).
- Products use Laravel's soft deletes (`Product::delete()` sets `deleted_at`, doesn't actually remove the row), so "Delete" there is safe even though `stock_movements.product_id` is `restrictOnDelete()` — no real row deletion ever happens. Categories have no soft deletes, but `products.category_id` is `nullOnDelete()`, so deleting a category just uncategorizes its products rather than failing or cascading.

All five core-feature phases plus authentication and catalog/staff management are now in place: login → schema → terminal → QRIS/webhook → WhatsApp job → analytics/export → admin CRUD. What's next — the public payment-link page flagged back in Phase 4, automated tests, or something else?
