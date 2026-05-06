# ezzyrock

**Ezzyrock** is a handyman / on-demand services platform built with Laravel. It provides admin and API surfaces for managing categories, services, bookings, providers, payments, subscriptions, and related operational workflows.

**Repository:** [github.com/maherasim/ezzyrock](https://github.com/maherasim/ezzyrock)

## Requirements

- **PHP** 8.0 or 8.2+ (see `composer.json` for supported ranges)
- **Composer** 2.x
- **Node.js** and **npm** (for Laravel Mix asset builds)
- **MySQL** (or a database supported by your Laravel configuration)

## Tech stack

| Area | Notes |
|------|--------|
| Framework | Laravel 11 |
| Auth / API tokens | Laravel Sanctum |
| Admin & roles | Spatie Laravel Permission |
| Media uploads | Spatie Laravel Media Library |
| Tables & exports | Yajra DataTables, Maatwebsite Excel |
| PDF | DomPDF, mPDF |
| Payments | Stripe, Razorpay |
| Notifications | OneSignal channel, Twilio SDK |
| Maps / geocoding | Google API client, Geocoder |
| Storage | Local / public; optional AWS S3 (`league/flysystem-aws-s3-v3`) |
| Frontend assets | Laravel Mix, Bootstrap 5, Vue-related UI libs in `package.json` |

## Local setup

1. **Clone the repository**

   ```bash
   git clone https://github.com/maherasim/ezzyrock.git
   cd ezzyrock
   ```

2. **Install PHP dependencies**

   ```bash
   composer install
   ```

3. **Environment file**

   ```bash
   copy .env.example .env   # Windows
   # cp .env.example .env   # macOS / Linux
   ```

   Edit `.env` and set at least `APP_KEY`, `APP_URL`, database credentials (`DB_*`), and any third-party keys you use (mail, maps, payments, etc.). Never commit real secrets.

4. **Application key**

   ```bash
   php artisan key:generate
   ```

5. **Database**

   Create an empty database, then run:

   ```bash
   php artisan migrate
   ```

   Seeders (if your project uses them):

   ```bash
   php artisan db:seed
   ```

6. **Storage link** (if serving uploads from `public`)

   ```bash
   php artisan storage:link
   ```

7. **Front-end build**

   ```bash
   npm install
   npm run dev
   ```

   For production builds: `npm run prod`.

8. **Run the app**

   ```bash
   php artisan serve
   ```

   Open the URL shown in the terminal (often `http://127.0.0.1:8000`).

## Project layout (high level)

- `app/Http/Controllers/API` — REST-style API controllers (e.g. categories, bookings, payments).
- `routes/api.php` / `routes/web.php` — route definitions.
- `database/migrations` — schema changes.
- `public/` — web root and compiled/static assets.

## Configuration tips

- **Timezone & locale:** set `APP_TIMEZONE` and language-related options in `.env` as needed.
- **Mail:** configure `MAIL_*` for your SMTP or development mail catcher.
- **Google Maps:** set `GOOGLE_MAPS_API_KEY` when using map/geocoding features.
- **File storage:** adjust `FILESYSTEM_DISK` / `FILESYSTEM_DRIVER` and AWS variables if using S3.

## Contributing

Use feature branches and open pull requests against `main`. Keep `.env` out of version control and document new env variables in `.env.example` without real credentials.

## License

This project’s `composer.json` declares **MIT** for the Laravel application scaffold. Third-party packages retain their own licenses. Confirm licensing with the project owner if you redistribute the full product.
