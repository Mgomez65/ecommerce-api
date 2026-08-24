# ecommerce-api

[![Tests](https://github.com/Mgomez65/ecommerce-api/actions/workflows/tests.yml/badge.svg)](https://github.com/Mgomez65/ecommerce-api/actions/workflows/tests.yml)

E-commerce full-stack en Laravel: API REST de gestión de pedidos con stock combinado, pagos con Mercado Pago, y un frontend server-rendered (Blade + Tailwind) que la consume.

## Stack

- Laravel 13 / PHP 8.4
- MySQL
- Laravel Sanctum (autenticación por token)
- Tailwind CSS 4 + Vite (frontend)
- Mercado Pago (Checkout Pro), integrado vía HTTP directo (sin SDK)
- PHPUnit (47 tests)

## Módulos

- **Auth**: registro, login, logout, recuperación de contraseña (`/api/auth/*`)
- **Users**: CRUD de usuarios (solo admin)
- **Categories**: categorías con jerarquía (padre/hijos)
- **Products**: búsqueda, filtros, paginación, imágenes
- **Cart**: carrito de compras por usuario
- **Checkout + Pagos**: crea una orden pendiente, genera una preferencia de Mercado Pago; un webhook verifica el pago contra la API real de MP y recién ahí confirma la orden y descuenta stock (idempotente, resistente a duplicados)
- **Orders**: listar, ver detalle, cancelar (repone stock), reintentar pago, cambiar estado (admin)
- **Dashboard**: resumen de ventas, stock bajo, pedidos recientes, productos más vendidos
- **Frontend**: catálogo, producto, carrito, checkout, login/registro, mis pedidos y panel admin — todo Blade + Tailwind consumiendo la propia API

## Autorización

Los permisos se resuelven con **Policies** de Laravel (`app/Policies/`), no con checks manuales de rol dispersos en los controllers.

## Puesta en marcha

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link
npm run build   # o `npm run dev` para desarrollo con hot-reload
php artisan serve
```

Ver [.env.example](.env.example) para las variables de Mercado Pago (credenciales de test, webhook, URLs de retorno).

### Con Docker

```bash
docker compose up --build
```

Levanta la app (`http://localhost:8000`) y MySQL. La primera vez genera la `APP_KEY`, corre las migraciones y crea el storage link automáticamente — no hace falta ningún paso manual. MySQL queda expuesto en el puerto `3307` del host (no `3306`, para no chocar con una instalación local).

> No corras `php artisan test` dentro de este contenedor: usa la misma base de datos (`ecommerce`) que la app, y los tests la resetean por completo (`RefreshDatabase`). Para tests, corré la suite localmente o en CI, no contra el contenedor de demo.

## Tests

```bash
composer test
```

Corren en CI (GitHub Actions) en cada push/PR a `main`.
