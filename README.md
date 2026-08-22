# ecommerce-api

API REST en Laravel para gestión de pedidos con stock combinado. Expone autenticación, catálogo de productos y categorías, carrito de compras y pedidos con control de stock.

## Stack

- Laravel 13 / PHP 8.3
- Laravel Sanctum (autenticación por token)
- Spatie Laravel Permission (roles y permisos)
- Intervention Image (imágenes de producto)

## Módulos

- **Auth**: registro, login, logout y usuario autenticado (`/api/auth/*`)
- **Users**: CRUD de usuarios
- **Categories**: categorías con soporte de jerarquía (categoría padre)
- **Products**: productos con imágenes asociadas
- **Cart**: carrito de compras por usuario, con checkout
- **Orders**: pedidos generados desde el checkout, con items y movimientos de stock

## Puesta en marcha

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

## Tests

```bash
composer test
```
