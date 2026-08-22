@extends('layouts.app')

@section('title', 'Tienda — Inicio')

@section('content')
    <section class="bg-gradient-to-br from-brand-700 via-brand-600 to-brand-800">
        <div class="page-container flex flex-col items-start gap-6 py-16 sm:py-20 lg:py-24">
            <span class="badge bg-white/15 text-white">Nuevo catálogo disponible</span>
            <h1 class="max-w-2xl text-3xl font-bold tracking-tight text-white sm:text-4xl lg:text-5xl">
                Todo lo que necesitás, en un solo lugar
            </h1>
            <p class="max-w-xl text-base text-brand-50 sm:text-lg">
                Productos seleccionados, stock en tiempo real y pagos seguros con Mercado Pago.
            </p>
            <a href="/catalogo" class="btn-primary bg-white text-brand-700 hover:bg-brand-50">
                Ver catálogo
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3" /></svg>
            </a>
        </div>
    </section>

    <section class="page-container py-10">
        <div class="mb-5 flex items-center justify-between">
            <h2 class="text-xl font-bold text-slate-900">Categorías</h2>
            <a href="/categorias" class="text-sm font-medium text-brand-600 hover:text-brand-700">Ver todas</a>
        </div>
        <div id="home-categories" class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            @for ($i = 0; $i < 6; $i++)
                <div class="skeleton h-20"></div>
            @endfor
        </div>
    </section>

    <section class="page-container py-10">
        <div class="mb-5 flex items-center justify-between">
            <h2 class="text-xl font-bold text-slate-900">Productos destacados</h2>
            <a href="/catalogo" class="text-sm font-medium text-brand-600 hover:text-brand-700">Ver todo</a>
        </div>
        <div id="home-products" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            @for ($i = 0; $i < 8; $i++)
                <div class="surface overflow-hidden">
                    <div class="skeleton aspect-square w-full"></div>
                    <div class="space-y-2 p-4">
                        <div class="skeleton h-4 w-3/4"></div>
                        <div class="skeleton h-4 w-1/2"></div>
                    </div>
                </div>
            @endfor
        </div>
    </section>
@endsection
