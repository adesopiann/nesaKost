# eKost — Phase 3: Rent Renewal System

## Implemented

- Tenant can open a renewal request page from the active rent card during H-30 through H-7.
- Renewal period starts one day after the previous rent's `end_date`.
- Supported durations are 1, 3, and 6 months; end date is inclusive and calculated with `addMonthsNoOverflow(...)->subDay()`.
- Renewal booking uses the existing `booking_type=renewal` and `previous_rent_id` fields.
- Renewal booking and its initial pending payment receive a 72-hour expiry. Regular booking remains 30 minutes.
- Existing payment selection, failed-attempt retry, and payment simulation flows are reused.
- Payment success creates the next rent linked to the renewal booking; the previous rent row is not modified.
- Availability validation can exclude the tenant's own previous rent while still checking other rents, bookings, and other tenants' renewal priority windows.
- The lifecycle scheduler already expires pending bookings/payments from `expires_at`; therefore the 72-hour renewal hold is released automatically when that expiry is processed.
- Seeder's sample renewal dates and expiry were aligned with the inclusive date convention and 72-hour window.

## Business-rule interpretation

The 72-hour payment hold continues for its full duration even if it crosses the H-7 application deadline. H-7 is the latest time to submit a new request; it does not shorten a request already submitted.

A renewal rent may be `scheduled` while the previous rent is still `active`, because the two date ranges are consecutive and non-overlapping. This is the intentional exception to the normal one-active-rent restriction; the regular new-booking flow remains blocked for tenants with an active/scheduled rent.

## Files changed

- `app/Http/Controllers/Tenant/RenewalController.php` (new)
- `app/Services/RoomAvailabilityService.php`
- `app/Http/Controllers/Tenant/PaymentController.php`
- `routes/web.php`
- `resources/views/pages/tenant/renewals/create.blade.php` (new)
- `resources/views/pages/tenant/dashboard/index.blade.php`
- `resources/views/pages/tenant/bookings/show.blade.php`
- `database/seeders/DatabaseSeeder.php`

## Validation and limitations

PHP syntax checks passed for all modified/new PHP files. The uploaded archive does not include Composer dependencies (`vendor/`), so Laravel route boot, database integration tests, scheduler execution, and browser-level testing could not be performed here. Run the migration status and test the flow locally in Laragon before treating this as production-ready.
