# Weekly Sales Analysis

Laravel, React, Vite, and MySQL scaffold for automating weekly Traverse sales analysis reporting.

## Local Setup

1. Install PHP 8.2+ and Composer.
2. Install PHP dependencies:

   ```bash
   composer install
   ```

3. Copy the environment example and generate an application key:

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Create the MySQL database configured in `.env.example`:

   ```sql
   CREATE DATABASE weekly_sales_analysis CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

5. Install frontend dependencies and start Vite:

   ```bash
   npm install
   npm run dev
   ```

6. In another terminal, start Laravel:

   ```bash
   php artisan serve
   ```

## Styling

React components should use class names only. Keep application styles in `resources/css/app.css`.
