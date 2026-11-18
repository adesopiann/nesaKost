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
