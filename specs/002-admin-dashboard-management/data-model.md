# Data Model: Admin Dashboard Navigation and User Control

## Entities

1. User (`users`)

- `id` INT PK
- `first_name`, `last_name`, `email`, `password_hash`
- `is_verified`, `is_active`, `last_login`
- `role` ENUM(`admin`,`librarian`,`borrower`)

2. Role Profile (`role_profiles`)

- `id` INT PK
- `user_id` INT FK -> users.id (unique per user)
- `role` ENUM(`admin`,`librarian`,`borrower`)
- `role_information` TEXT
- Rule: on role change, previous role profile is removed and replaced.

3. Admin Profile (`admin_profiles`)

- `id` INT PK
- `admin_username` VARCHAR(100) unique
- `full_name`, `email`, `phone`, `address`
- `appointment_date` DATE
- `access_level` VARCHAR(150)

4. Fine Collection (`fine_collections`)

- `id` INT PK
- `borrower_user_id` INT FK -> users.id (nullable)
- `collected_by_user_id` INT FK -> users.id (nullable)
- `receipt_code` unique
- `amount` DECIMAL(10,2)
- `status` ENUM(`collected`,`voided`)
- `notes`, `collected_at`, `created_at`

## Reporting Window

- "Current collected fines" is month-to-date only:
- Start: first day of current month at 00:00:00
- End: current date at 23:59:59
- Include only rows with `status = 'collected'`
