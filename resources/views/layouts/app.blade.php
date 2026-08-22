<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Tienda')</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body data-page="{{ $page ?? '' }}" @foreach($bodyData ?? [] as $key => $value)data-{{ $key }}="{{ $value }}" @endforeach class="flex min-h-screen flex-col bg-slate-50 font-sans text-slate-900 antialiased">

    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:m-2 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:shadow">Saltar al contenido</a>

    <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div class="page-container flex h-16 items-center justify-between gap-4">
            <div class="flex items-center gap-6">
                <a href="/" class="flex shrink-0 items-center gap-2 text-lg font-bold text-slate-900">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-600 text-white">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 1.994-4.694 2.591-7.171.055-.227-.1-.443-.34-.443H5.106M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" /></svg>
                    </span>
                    <span class="hidden sm:inline">Tienda</span>
                </a>
                <nav class="hidden items-center gap-1 lg:flex">
                    <a href="/catalogo" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900">Catálogo</a>
                    <a href="/categorias" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900">Categorías</a>
                </nav>
            </div>

            <form data-search-form class="hidden max-w-sm flex-1 lg:flex">
                <div class="relative w-full">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                    <input type="search" name="search" placeholder="Buscar productos…" class="field-input pl-9" value="{{ request('search') }}">
                </div>
            </form>

            <div class="flex items-center gap-2">
                <a href="/carrito" class="relative rounded-lg p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900" aria-label="Carrito">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 1.994-4.694 2.591-7.171.055-.227-.1-.443-.34-.443H5.106M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" /></svg>
                    <span data-cart-count class="absolute -right-1 -top-1 hidden min-w-[1.1rem] rounded-full bg-brand-600 px-1 text-center text-[10px] font-bold leading-[1.1rem] text-white"></span>
                </a>

                <div id="auth-area-desktop" class="hidden items-center gap-2 lg:flex"></div>

                <button type="button" id="nav-toggle" aria-expanded="false" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Abrir menú">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                </button>
            </div>
        </div>

        <div id="mobile-menu" class="hidden border-t border-slate-200 bg-white lg:hidden">
            <div class="page-container space-y-3 py-4">
                <form data-search-form class="flex">
                    <div class="relative w-full">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                        <input type="search" name="search" placeholder="Buscar productos…" class="field-input pl-9" value="{{ request('search') }}">
                    </div>
                </form>
                <nav class="space-y-1">
                    <a href="/catalogo" class="block rounded-lg px-3 py-2 text-base font-medium text-slate-700 hover:bg-slate-50">Catálogo</a>
                    <a href="/categorias" class="block rounded-lg px-3 py-2 text-base font-medium text-slate-700 hover:bg-slate-50">Categorías</a>
                    <a href="/carrito" class="block rounded-lg px-3 py-2 text-base font-medium text-slate-700 hover:bg-slate-50">Carrito</a>
                </nav>
                <div id="auth-area-mobile" class="space-y-1 border-t border-slate-100 pt-3"></div>
            </div>
        </div>
    </header>

    <main id="main-content" class="flex-1">
        @yield('content')
    </main>

    <footer class="border-t border-slate-200 bg-white">
        <div class="page-container flex flex-col gap-4 py-8 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ date('Y') }} Tienda. Todos los derechos reservados.</p>
            <div class="flex gap-4">
                <a href="/catalogo" class="hover:text-slate-700">Catálogo</a>
                <a href="/categorias" class="hover:text-slate-700">Categorías</a>
                <a href="/mis-pedidos" class="hover:text-slate-700">Mis pedidos</a>
            </div>
        </div>
    </footer>

    <div id="toast-container" class="pointer-events-none fixed inset-x-0 top-4 z-50 flex flex-col items-center gap-2 px-4 sm:top-6"></div>
</body>
</html>
