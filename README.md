<p align="center"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></p>

# Saved Feast 🍽️🇱🇧

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Saved Feast

Saved Feast is an innovative web application designed to combat food waste in Lebanon by connecting restaurants, cafés, and food service providers with consumers. The platform enables businesses to sell surplus meals at discounted prices, reducing waste while making food more affordable for customers.

## Features 🚀

- ✅ **Restaurant Listings** – Restaurants can register and list surplus meals for sale.
- ✅ **Meal Discovery** – Users can browse available meals based on location and availability.
- ✅ **Secure Transactions** – Integrated payment system for seamless purchases.
- ✅ **Real-Time Updates** – Restaurants can update meal availability dynamically.
- ✅ **User Roles** – Separate dashboards for restaurants and consumers.
- ✅ **Order Tracking** – Users can track their purchases and pickup times.

## Technology Stack 🛠️

- **Frontend:** React + Vite
- **Backend:** Laravel (PHP)
- **Database:** MySQL
- **Authentication:** Laravel Sanctum
- **Deployment:** TBD

## How to Run Locally 🏗️

1. Clone the repository:
```bash
git clone https://github.com/GhadiSaab/Saved-Feast-Web.git
cd Saved-Feast-Web
```

2. Install dependencies:
```bash
composer install
npm install
```

3. Set up environment variables:
```bash
cp .env.example .env
php artisan key:generate
```

4. Migrate the database:
```bash
php artisan migrate
```

5. Run the server:
```bash
php artisan serve
```

## Contributing 🤝

Feel free to submit issues or contribute by creating pull requests!

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
