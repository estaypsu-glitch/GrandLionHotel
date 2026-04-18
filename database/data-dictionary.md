# Data Dictionary

Key legend:

- `PK` = Primary Key
- `FK` = Foreign Key
- `UQ` = Unique

## ADMINS

| Key | Field Name | Data Type | Size | Description | Example |
| --- | --- | --- | --- | --- | --- |
| PK | `admin_id` | `bigint` | `20 unsigned` | Unique identifier for each admin account. | `1` |
|  | `name` | `varchar` | `255` | Combined full name of the admin. | `Anne Flores` |
| UQ | `email` | `varchar` | `255` | Admin login email address. | `admin@grandlionhotel.com` |
|  | `phone` | `varchar` | `30` | Contact number of the admin. | `+639171234567` |
|  | `password` | `varchar` | `255` | Hashed password used for authentication. | `$2y$12$...` |
|  | `password_changed_at` | `timestamp` | `-` | Date and time when the password was last updated. | `2026-04-19 10:30:00` |
|  | `remember_token` | `varchar` | `100` | Token used for persistent login sessions. | `kT9xL2pQ7m...` |
|  | `created_at` | `timestamp` | `-` | Date and time when the admin record was created. | `2026-04-01 08:15:00` |
|  | `updated_at` | `timestamp` | `-` | Date and time when the admin record was last updated. | `2026-04-19 11:45:00` |

## STAFF

| Key | Field Name | Data Type | Size | Description | Example |
| --- | --- | --- | --- | --- | --- |
| PK | `staff_id` | `bigint` | `20 unsigned` | Unique identifier for each staff account. | `5` |
|  | `name` | `varchar` | `255` | Combined full name of the staff member. | `Beverly Santos` |
| UQ | `email` | `varchar` | `255` | Staff login email address. | `beverly@grandlionhotel.com` |
|  | `phone` | `varchar` | `30` | Contact number of the staff member. | `09171234567` |
|  | `password` | `varchar` | `255` | Hashed password used for staff authentication. | `$2y$12$...` |
|  | `password_changed_at` | `timestamp` | `-` | Date and time when the password was last changed. | `2026-04-10 09:00:00` |
|  | `remember_token` | `varchar` | `100` | Token used for persistent login sessions. | `6Pj8Lma2...` |
| FK | `admin_id` | `bigint` | `20 unsigned` | References the admin who created or manages the staff account. | `1` |
|  | `created_at` | `timestamp` | `-` | Date and time when the staff record was created. | `2026-04-02 14:20:00` |
|  | `updated_at` | `timestamp` | `-` | Date and time when the staff record was last updated. | `2026-04-19 09:25:00` |

## CUSTOMERS

| Key | Field Name | Data Type | Size | Description | Example |
| --- | --- | --- | --- | --- | --- |
| PK | `customer_id` | `bigint` | `20 unsigned` | Unique identifier for each customer account. | `24` |
|  | `name` | `varchar` | `255` | Combined full name of the customer. | `Jericho Ramos` |
| UQ | `email` | `varchar` | `255` | Customer email address used for login and notifications. | `jericho@gmail.com` |
| UQ | `google_id` | `varchar` | `255` | Google account identifier for Google sign-in. | `114829301928374650123` |
|  | `phone` | `varchar` | `30` | Customer contact number. | `+639181112222` |
|  | `address_line` | `varchar` | `255` | Main address line of the customer. | `Blk 3 Lot 5 Mabini St.` |
|  | `city` | `varchar` | `120` | City or municipality of the customer. | `Naga City` |
|  | `province` | `varchar` | `120` | Province of the customer. | `Camarines Sur` |
|  | `country` | `varchar` | `120` | Country of residence. | `Philippines` |
|  | `email_verified_at` | `timestamp` | `-` | Date and time when the customer email was verified. | `2026-04-18 16:00:00` |
|  | `password` | `varchar` | `255` | Hashed customer password. | `$2y$12$...` |
|  | `password_changed_at` | `timestamp` | `-` | Date and time when the password was last updated. | `2026-04-18 16:10:00` |
|  | `remember_token` | `varchar` | `100` | Token used for persistent customer login sessions. | `v9Lx0QmZ...` |
|  | `created_at` | `timestamp` | `-` | Date and time when the customer account was created. | `2026-04-17 13:30:00` |
|  | `updated_at` | `timestamp` | `-` | Date and time when the customer account was last updated. | `2026-04-19 08:40:00` |

## ROOMS

| Key | Field Name | Data Type | Size | Description | Example |
| --- | --- | --- | --- | --- | --- |
| PK | `room_id` | `bigint` | `20 unsigned` | Unique identifier for each room. | `502` |
| FK | `room_status_id` | `bigint` | `20 unsigned` | References the current status of the room. | `2` |
| FK | `admin_id` | `bigint` | `20 unsigned` | References the admin who last updated the room status. | `1` |
|  | `status_updated_at` | `timestamp` | `-` | Date and time when the room status was last updated. | `2026-04-19 07:15:00` |
|  | `name` | `varchar` | `255` | Display name or room label. | `Room 502 - Penthouse West` |
|  | `type` | `varchar` | `100` | Room classification or room type. | `Penthouse` |
|  | `view_type` | `varchar` | `100` | View category assigned to the room. | `City View` |
|  | `description` | `text` | `65535` | Detailed room description and features. | `Large suite with balcony and lounge area.` |
|  | `price_per_night` | `decimal` | `10,2` | Standard nightly room rate. | `10499.00` |
|  | `capacity` | `int` | `10 unsigned` | Maximum number of guests allowed in the room. | `4` |
|  | `image` | `varchar` | `255` | Room image path or URL. | `https://.../room-502.jpg` |
|  | `created_at` | `timestamp` | `-` | Date and time when the room record was created. | `2026-04-01 09:00:00` |
|  | `updated_at` | `timestamp` | `-` | Date and time when the room record was last updated. | `2026-04-19 07:15:00` |

## ROOM_STATUS

| Key | Field Name | Data Type | Size | Description | Example |
| --- | --- | --- | --- | --- | --- |
| PK | `room_status_id` | `bigint` | `20 unsigned` | Unique identifier for each room status. | `2` |
| UQ | `name` | `varchar` | `50` | Human-readable room status label. | `Clean` |
| UQ | `slug` | `varchar` | `50` | Machine-friendly unique status code. | `clean` |
|  | `description` | `text` | `65535` | Explanation of the room status usage. | `Room is ready for guest occupancy.` |
|  | `created_at` | `timestamp` | `-` | Date and time when the room status was created. | `2026-04-01 08:00:00` |
|  | `updated_at` | `timestamp` | `-` | Date and time when the room status was last updated. | `2026-04-17 10:20:00` |

## BOOKINGS

| Key | Field Name | Data Type | Size | Description | Example |
| --- | --- | --- | --- | --- | --- |
| PK | `booking_id` | `bigint` | `20 unsigned` | Unique identifier for each booking transaction. | `24` |
| FK | `customer_id` | `bigint` | `20 unsigned` | References the customer who made the booking. | `24` |
| FK | `room_id` | `bigint` | `20 unsigned` | References the room reserved by the customer. | `502` |
| FK | `staff_id` | `bigint` | `20 unsigned` | References the assigned staff handling the booking. | `5` |
|  | `check_in` | `date` | `-` | Scheduled check-in date. | `2026-04-17` |
|  | `check_out` | `date` | `-` | Scheduled check-out date. | `2026-04-18` |
|  | `requested_check_in` | `date` | `-` | Requested new check-in date for rescheduling. | `2026-04-19` |
|  | `requested_check_out` | `date` | `-` | Requested new check-out date for rescheduling. | `2026-04-20` |
|  | `status` | `varchar` | `20` | Current booking status. | `confirmed` |
|  | `notes` | `varchar` | `500` | General remarks or booking notes. | `Guest prefers a quiet room.` |
|  | `actual_check_in_at` | `timestamp` | `-` | Actual date and time the guest checked in. | `2026-04-17 16:45:00` |
|  | `actual_check_out_at` | `timestamp` | `-` | Actual date and time the guest checked out. | `2026-04-18 12:05:00` |
|  | `staff_notes` | `text` | `65535` | Internal notes entered by staff. | `Customer requested late arrival assistance.` |
|  | `reschedule_request_notes` | `text` | `65535` | Reason or note for a reschedule request. | `Emergency travel delay.` |
|  | `reschedule_requested_at` | `timestamp` | `-` | Date and time when a reschedule request was filed. | `2026-04-16 18:30:00` |
|  | `created_at` | `timestamp` | `-` | Date and time when the booking record was created. | `2026-04-15 14:00:00` |
|  | `updated_at` | `timestamp` | `-` | Date and time when the booking record was last updated. | `2026-04-17 15:10:00` |

## PAYMENTS

| Key | Field Name | Data Type | Size | Description | Example |
| --- | --- | --- | --- | --- | --- |
| PK | `payment_id` | `bigint` | `20 unsigned` | Unique identifier for each payment record. | `24` |
| FK, UQ | `booking_id` | `bigint` | `20 unsigned` | References the booking linked to this payment; one payment per booking. | `24` |
|  | `amount` | `decimal` | `10,2` | Final amount paid or due for the booking. | `10499.00` |
|  | `method` | `varchar` | `30` | Payment method chosen by the guest. | `gcash` |
|  | `status` | `varchar` | `20` | Current payment status. | `paid` |
|  | `source` | `varchar` | `20` | Origin of the payment record or channel. | `online` |
|  | `qr_reference` | `varchar` | `80` | QR code reference used for online payment validation. | `QR-20260419-001` |
|  | `customer_reference` | `varchar` | `120` | Reference provided by the customer during payment. | `GCASH-REF-889122` |
|  | `payment_proof_path` | `varchar` | `255` | File path or URL of uploaded payment proof. | `payments/proofs/booking-24.png` |
|  | `original_amount` | `decimal` | `10,2` | Original amount before any discount is applied. | `11999.00` |
|  | `discount_rate` | `decimal` | `5,4` | Discount rate applied to the payment. | `0.1250` |
|  | `discount_amount` | `decimal` | `10,2` | Amount deducted because of the discount. | `1500.00` |
| UQ | `transaction_reference` | `varchar` | `255` | Unique transaction code generated for the payment. | `GLH-20260419-B000024-AB12CD` |
|  | `paid_at` | `timestamp` | `-` | Date and time when the payment was completed. | `2026-04-16 10:45:00` |
|  | `verified_at` | `timestamp` | `-` | Date and time when the payment was verified by staff. | `2026-04-16 11:05:00` |
| FK | `staff_id` | `bigint` | `20 unsigned` | References the staff who verified the payment. | `5` |
|  | `created_at` | `timestamp` | `-` | Date and time when the payment record was created. | `2026-04-15 14:02:00` |
|  | `updated_at` | `timestamp` | `-` | Date and time when the payment record was last updated. | `2026-04-16 11:05:00` |

## BOOKING_DISCOUNTS

| Key | Field Name | Data Type | Size | Description | Example |
| --- | --- | --- | --- | --- | --- |
| PK | `booking_discount_id` | `bigint` | `20 unsigned` | Unique identifier for each booking discount record. | `7` |
| FK, UQ | `booking_id` | `bigint` | `20 unsigned` | References the booking with a recorded discount; one discount record per booking. | `24` |
|  | `discount_type` | `varchar` | `30` | Type of discount applied to the booking. | `senior` |
|  | `discount_id` | `varchar` | `80` | Discount reference or ID number presented by the guest. | `SC-2026-998812` |
|  | `discount_id_photo_path` | `varchar` | `255` | File path or URL of the uploaded discount ID proof. | `discounts/ids/booking-24.jpg` |
|  | `created_at` | `timestamp` | `-` | Date and time when the discount record was created. | `2026-04-15 14:05:00` |
|  | `updated_at` | `timestamp` | `-` | Date and time when the discount record was last updated. | `2026-04-15 14:05:00` |

## BOOKING_GUEST_DETAILS

| Key | Field Name | Data Type | Size | Description | Example |
| --- | --- | --- | --- | --- | --- |
| PK | `guest_detail_id` | `bigint` | `20 unsigned` | Unique identifier for each guest detail record. | `24` |
| FK, UQ | `booking_id` | `bigint` | `20 unsigned` | References the booking that owns this guest detail; one guest detail record per booking. | `24` |
|  | `first_name` | `varchar` | `255` | Guest first name used during reservation. | `Jericho` |
|  | `last_name` | `varchar` | `255` | Guest last name used during reservation. | `Ramos` |
|  | `email` | `varchar` | `255` | Guest contact email address. | `jericho@gmail.com` |
|  | `phone` | `varchar` | `30` | Guest contact number. | `09181112222` |
|  | `address_line` | `varchar` | `255` | Primary address line of the guest. | `Zone 2 Purok 1` |
|  | `street_address_line_2` | `varchar` | `255` | Secondary address details such as subdivision or barangay. | `Barangay Concepcion` |
|  | `city` | `varchar` | `255` | Guest city or municipality. | `Naga City` |
|  | `province` | `varchar` | `255` | Guest province. | `Camarines Sur` |
|  | `postal_code` | `varchar` | `20` | Postal or ZIP code of the guest address. | `4400` |
|  | `adults` | `int` | `10 unsigned` | Number of adult guests included in the booking. | `2` |
|  | `kids` | `int` | `10 unsigned` | Number of child guests included in the booking. | `1` |
|  | `payment_preference` | `varchar` | `30` | Preferred payment setup selected during booking. | `pay_at_hotel` |
| FK | `staff_id` | `bigint` | `20 unsigned` | References the staff who encoded or updated the guest detail. | `5` |
|  | `created_at` | `timestamp` | `-` | Date and time when the guest detail record was created. | `2026-04-15 14:03:00` |
|  | `updated_at` | `timestamp` | `-` | Date and time when the guest detail record was last updated. | `2026-04-15 14:03:00` |

## REGISTRATION_VERIFICATIONS

| Key | Field Name | Data Type | Size | Description | Example |
| --- | --- | --- | --- | --- | --- |
| PK | `id` | `bigint` | `20 unsigned` | Unique identifier for each pending registration verification record. | `12` |
|  | `name` | `varchar` | `255` | Combined full name submitted during registration. | `Maria Cruz` |
|  | `email` | `varchar` | `255` | Email address waiting for OTP verification. | `maria@gmail.com` |
| UQ | `google_id` | `varchar` | `255` | Google account identifier when registration started through Google. | `104928374650192837465` |
|  | `phone` | `varchar` | `30` | Phone number submitted during registration. | `+639171234000` |
|  | `otp_channel` | `varchar` | `16` | Channel used to send the OTP code. | `email` |
|  | `password_encrypted` | `text` | `65535` | Encrypted password temporarily stored before account creation. | `eyJpdiI6Ik...` |
|  | `code_hash` | `varchar` | `255` | Hashed one-time password used for registration verification. | `$2y$12$...` |
|  | `code_expires_at` | `timestamp` | `-` | Expiration date and time of the OTP code. | `2026-04-19 12:02:00` |
|  | `attempts` | `tinyint` | `3 unsigned` | Number of OTP verification attempts already used. | `1` |
|  | `last_sent_at` | `timestamp` | `-` | Date and time when the latest OTP was sent. | `2026-04-19 12:00:00` |
|  | `created_at` | `timestamp` | `-` | Date and time when the pending verification record was created. | `2026-04-19 11:58:00` |
|  | `updated_at` | `timestamp` | `-` | Date and time when the pending verification record was last updated. | `2026-04-19 12:00:00` |

## ROOM_DATE_DISCOUNTS

| Key | Field Name | Data Type | Size | Description | Example |
| --- | --- | --- | --- | --- | --- |
| PK | `room_date_discount_id` | `bigint` | `20 unsigned` | Unique identifier for each room date discount entry. | `31` |
| FK | `room_id` | `bigint` | `20 unsigned` | References the room receiving a discount on a specific date. | `502` |
|  | `discount_date` | `date` | `-` | Specific calendar date when the room discount applies. | `2026-04-24` |
|  | `discount_percent` | `decimal` | `5,2` | Percentage discount applied on that room and date. | `15.00` |
| FK | `admin_id` | `bigint` | `20 unsigned` | References the admin who created the discount entry. | `1` |
|  | `created_at` | `timestamp` | `-` | Date and time when the discount entry was created. | `2026-04-19 09:20:00` |
|  | `updated_at` | `timestamp` | `-` | Date and time when the discount entry was last updated. | `2026-04-19 09:20:00` |
