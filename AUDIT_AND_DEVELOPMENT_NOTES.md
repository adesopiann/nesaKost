# Audit & Development Notes — eKost

## Baseline audited
Source archive: `nesaKost2(8).zip`

## Audit findings
- Guest property detail currently uses a native `<input type="date">`; it cannot disable arbitrary unavailable dates.
- `RoomAvailabilityService::checkPeriod()` already checks room status, scheduled/active rents, confirmed bookings, non-expired pending bookings, and renewal-priority windows.
- Renewal priority was configured as H-30 / H-15. The agreed rule is H-30 / H-7.
- Tenant booking creation revalidates availability inside a database transaction and locks the room row.
- Regular booking payment expiry is 30 minutes. This remains unchanged; the 72-hour grace period is reserved for renewal requests.
- Renewal fields (`booking_type`, `previous_rent_id`) exist, but renewal workflow is not implemented.
- Lifecycle command expires pending bookings and activates/completes rents.

## Changes in this increment
1. Changed renewal priority deadline from 15 days to 7 days.
2. Added `RoomAvailabilityService::getUnavailableRanges()` to return date ranges blocked by:
   - scheduled/active rent,
   - confirmed booking,
   - pending booking whose expiry has not passed,
   - renewal-priority protection.
3. Added `GET /rooms/{roomId}/availability?from=YYYY-MM-DD&to=YYYY-MM-DD` named `guest.rooms.availability`.

## API response shape
```json
{
  "room_id": 1,
  "from": "2026-11-18",
  "to": "2027-05-18",
  "available": true,
  "unavailable_ranges": [
    {"start": "2026-12-01", "end": "2026-12-31", "reason": "rent"}
  ]
}
```

## Validation performed
- JavaScript syntax check passed for the calendar script using `node --check`.
- PHP syntax lint passed for:
- `app/Services/RoomAvailabilityService.php`
- `app/Http/Controllers/Guest/PropertyController.php`
- `routes/web.php`

## Phase 2 changes — Dynamic Date Availability Calendar
1. Replaced the native date input on the guest property detail page with a custom calendar UI.
2. Calendar loads unavailable ranges from the availability endpoint for a rolling two-year window.
3. Dates blocked by rent, booking, or renewal priority are disabled and show a reason on hover.
4. Tenant selects a duration (1, 3, or 6 months) and a start date; the UI checks the entire inclusive rental period.
5. If any part overlaps an unavailable range, the UI explains the conflict and suggests up to three later start dates.
6. The GET availability-check form is enabled only when the selected full period is available. The server-side `checkPeriod()` and booking transaction remain authoritative.

## Not yet implemented
- Renewal request/payment workflow (72-hour expiry).
- Notification delivery (database/email/WhatsApp).
- Automated feature tests and integration tests.

## Important implementation note
The API is advisory UI data. Booking creation must continue to call `checkPeriod()` server-side within a transaction. Availability must never be trusted solely from browser state.

## Environment limitations
- This archive does not include `vendor/` or `node_modules/`, so Laravel route execution, browser testing, Vite build, and database-backed integration tests were not run in this environment.


## Phase 4.2 — Notification Backend

### Added
- `database/migrations/2026_11_18_000001_create_notifications_table.php`
  - Creates Laravel-compatible UUID database notifications.
  - Uses polymorphic `notifiable` columns so notifications can belong to the authenticated user.
  - Includes `data`, `read_at`, and timestamps.
- `app/Notifications/RentExpirationNotification.php`
  - Supports reminder identifiers `H-30`, `H-7`, and `H`.
  - Uses Laravel's database notification channel.
  - Stores a stable payload for the future tenant UI: category, reminder type, title, message, rent identifiers, room identifiers, days remaining, and action URL.
  - H-30/H-7 link to the renewal request page; H links to the tenant dashboard.
- The existing `User` model already uses Laravel's `Notifiable` trait, so no model change is required.

### Intentionally not included yet
- No scheduler command sends reminders in this subphase; that belongs to Phase 4.4.
- No UI/controller/read-state endpoints yet; those belong to Phase 4.3.
- No email or WhatsApp channel yet; email belongs to Phase 4.5 and WhatsApp to Phase 4.6.
- Duplicate-send protection will be implemented together with the automated reminder workflow in Phase 4.4, where reminder execution and tracking can be designed atomically.

### Apply locally
Run:
```bash
php artisan migrate
```

### Validation
- PHP syntax lint is run for the new migration and notification class.
- Full Laravel migration/runtime tests require the project's Composer dependencies and configured database, which are not bundled in this source archive.

## Phase 4.3 — Tenant In-App Notification UI

### Implemented
- Added tenant notification center at `GET /tenant/notifications` with pagination.
- Added a notification bell in the tenant desktop navbar, unread badge, and latest-five dropdown.
- Added a notification link and unread count to the mobile navigation.
- Added notification actions: open (mark as read and follow the notification's internal action URL), mark one as read, and mark all as read.
- Notification queries are scoped through the authenticated tenant's own `notifications()` relationship.
- Added an internal-host check before redirecting to an action URL to avoid external redirects.

### Routes
- `tenant.notifications.index`
- `tenant.notifications.open`
- `tenant.notifications.read`
- `tenant.notifications.read-all`

### Notes / limitations
- The UI will remain empty until notifications are generated. The automated H-30/H-7/H reminder command is planned for Phase 4.4.
- The desktop dropdown shows the latest five notifications; the full page shows ten per page.
- The mobile navigation links to the full notification page rather than using a dropdown.

## Phase 4.3 correction — Tenant notification navbar placement
- Moved the notification bell and desktop dropdown from `components/layouts/guest/navbar.blade.php` to `components/layouts/tenant/navbar.blade.php`.
- Added the notification link and unread count to the tenant mobile menu.
- Updated `components/layouts/tenant/container.blade.php` to include the tenant navbar instead of the guest navbar.
- Removed notification-specific markup and JavaScript from the guest navbar so guest/customer navigation remains separate.


## Revisi 18 November 2026 — Standarisasi Feather Icons

- Menetapkan Feather Icons v4.29.2 sebagai standar ikon UI di seluruh layout guest, tenant, owner, autentikasi, dan halaman welcome.
- Memastikan layout guest dan tenant memuat library Feather serta menjalankan `feather.replace()` saat DOM siap.
- Mengganti inline SVG ikon fasilitas dan peringatan dengan ikon Feather.
- Mengganti karakter panah, centang, jam, salin, refresh, dan simbol lain yang berfungsi sebagai ikon UI dengan Feather.
- Mempertahankan SVG ilustrasi/logo besar pada halaman welcome karena bukan ikon antarmuka.
- Detail aturan tercatat di `FEATHER_ICONS_STANDARD.md`.

## Phase 4.4 — Automated Rent Expiration Reminder

### Audit basis
- `Rent` stores `start_date`, `end_date`, `tenant_id`, and `status`.
- `Rent::tenant()` resolves to `Tenant`; `Tenant::user()` resolves to the authenticated `User` model, which already uses Laravel `Notifiable`.
- Rent statuses defined by the schema are `scheduled`, `active`, `completed`, and `cancelled`.
- `routes/console.php` already schedules `ekost:sync-rental-lifecycle` every minute. The reminder workflow is kept in a separate command so it does not change lifecycle behavior.
- Application timezone is `Asia/Jakarta`.

### Implementation
1. Added `rent_expiration_reminders` as an idempotency ledger with a unique key on `(rent_id, reminder_type)`.
2. Added `ekost:send-rent-expiration-reminders`, which checks rent end dates for H-30, H-7, and H.
3. Only `scheduled` and `active` rents are considered. The command resolves the tenant's user and sends the existing `RentExpirationNotification` through Laravel's database channel.
4. Each reminder is recorded and the database notification is created inside one transaction. A rent row lock plus the unique database index protects against duplicate sends if two processes overlap.
5. Added a daily scheduler at 09:00 application time with `withoutOverlapping()`. The existing lifecycle scheduler remains unchanged.
6. Added `--date=YYYY-MM-DD` to support controlled testing without changing the server clock.

### Commands
```bash
php artisan migrate
php artisan ekost:send-rent-expiration-reminders
php artisan ekost:send-rent-expiration-reminders --date=2026-12-18
php artisan schedule:list
```

### Important behavior
- `end_date` is treated as the final rental day, consistent with the existing lifecycle command.
- Re-running the command on the same date will not create duplicate database notifications for the same rent/reminder type.
- If the tenant has no linked user account, the reminder is skipped and no ledger row is created, allowing a later retry after the account relationship is corrected.
- Email and WhatsApp channels remain outside this phase.

### Validation performed
- PHP syntax lint and ZIP integrity checks are performed for this increment.
- Full Laravel/database integration and scheduler execution require the project's local dependencies and configured database, and are not claimed as completed here.

## Phase 4.4 — Seeder khusus pengujian pengingat

`DatabaseSeeder` sekarang menambahkan tiga akun tenant khusus untuk menguji pengingat masa sewa:

| Akun | Masa sewa berakhir | Jenis pengingat |
|---|---|---|
| `reminder.h30@ekost.test` | Hari seeder + 30 hari | H-30 |
| `reminder.h7@ekost.test` | Hari seeder + 7 hari | H-7 |
| `reminder.h@ekost.test` | Hari seeder dijalankan | H |

Password seluruh akun: `password123`.

Setiap akun memiliki booking berstatus `confirmed`, payment berstatus `paid`, dan rent berstatus `active`. Kode rent menggunakan awalan `TEST-REMINDER-` agar mudah dikenali.

Untuk membuat ulang seluruh data demo (PERHATIAN: menghapus seluruh data database yang digunakan):

```bash
php artisan migrate:fresh --seed
```

Setelah seeder dijalankan, jalankan command pada tanggal hari ini:

```bash
php artisan ekost:send-rent-expiration-reminders
```

Untuk pengujian ulang pada tanggal simulasi lain, seeder harus dijalankan ulang agar tanggal akhir rent test disesuaikan dengan hari ini. Hindari `migrate:fresh --seed` pada database yang berisi data penting.

## Phase 4.4.1 — Renewal policy and Dynamic Calendar synchronization

### Audit findings
- `RenewalController` already enforced the H-30 through H-7 application window and created a 72-hour payment expiry.
- `SyncRentalLifecycle` already expires `pending_payment` bookings and their pending payments when `expires_at <= now`; it runs every minute from the existing scheduler.
- `RoomAvailabilityService` already ignored pending bookings after their expiry timestamp, so the calendar could release the renewal allocation even before the lifecycle command updated the stored status.
- The previous duplicate-renewal check only considered `pending_payment` and `confirmed` bookings. An expired/cancelled renewal could therefore be submitted again, contrary to the confirmed business rule.
- The generic booking controller rejected all new bookings whenever the tenant had any active/scheduled rent, even if the new booking began after that rent's end date. This prevented a tenant whose renewal failed from booking a future period through the regular customer flow.
- The general renewal-priority hold was calculated only from the H-30/H-7 window. A tenant who had already used their one renewal attempt and whose attempt expired could still retain a priority hold until H-7.

### Changes made
1. `RenewalController` now treats any existing renewal booking linked through `previous_rent_id` as the tenant's one renewal attempt for that rent, regardless of its status. The check is applied in both the create page and transactional store action.
2. The renewal page now explains whether the previous attempt is confirmed, still pending, or already ended. Expired/cancelled attempts are not presented as renewable again.
3. `RoomAvailabilityService` no longer creates the general renewal-priority hold for a rent that already has any renewal attempt. A pending, unexpired renewal booking itself blocks its requested dates; after expiry, the pending booking stops blocking immediately because both calendar and booking validation compare `expires_at` with the current time. The old rent continues blocking through its inclusive `end_date`.
4. `BookingController` now allows a tenant with an existing active/scheduled rent to create a regular booking when the new booking's start date is not inside that existing rent period. The shared availability service still validates the entire requested period, preventing overlap with existing rents/bookings and respecting other tenants' allocations.
5. Existing H-30 through H-7 eligibility and 72-hour payment grace behavior are preserved. `SyncRentalLifecycle` remains separate and unchanged.

### Expected behavior
- H-30 through H-7: one renewal attempt may be created.
- H-6 through H: new renewal applications are rejected.
- An unexpired pending renewal blocks its requested period.
- If paid within 72 hours, the booking is confirmed and the new rent starts the day after the old rent ends.
- If unpaid at expiry, the booking and pending payment are expired by lifecycle sync; the old rent remains blocking through its end date, while the renewal period is released from its expiry timestamp.
- A second renewal attempt for the same old rent is rejected permanently, including after an expired/cancelled attempt.
- A regular booking can be made for a non-overlapping period after the existing rent; it remains subject to the normal room availability validation.

### Validation performed
- `php -l` passed for `RenewalController.php`, `BookingController.php`, and `RoomAvailabilityService.php`.
- Laravel runtime, database integration, browser/calendar behavior, payment race-condition, and full feature tests were not run in this packaging environment because `vendor/autoload.php` is not present in the source archive.
