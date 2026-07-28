# New Life Leadership Academy (NLA)

Website for **New Life Leadership Academy** — an outreach of Africa New Life Ministries offering Christ-centred ACE education that develops Christian character and leadership.

- **ACE curriculum** — individualised, mastery-based learning with biblical integration
- **Registered** with AEE (South Africa) and Rwanda Education Board (REB)
- **Stack:** Laravel 10, Livewire 3, MySQL

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
# Configure DB in .env, then:
php artisan migrate --seed
```

## Admin access

- Super Admin: `admin@iremetech.com` / `Ireme@2021`
- Website Admin: `admin@nla.ac.rw` / `password`

Admin panel: `/admin/login`

## Public pages

| Page | Route |
|------|-------|
| Home | `/` |
| Our School (About) | `/about` |
| About ACE | `/academics/about-ace` |
| Programs / Academics | `/departments` |
| School Activities / Updates | `/school-activities` |
| Facilities | `/facilities` |
| Gallery | `/gallery` |
| Staff | `/leadership` |
| Register | `/appointment` |
| Tuition & Fees | `/academics/tuition-fees` |
| Contacts | `/contact` |

## Branding

Logo: `public/images/nla-logo.png`

Design uses Playfair Display (headings) + Source Sans 3 (body) with slate/blue palette.
