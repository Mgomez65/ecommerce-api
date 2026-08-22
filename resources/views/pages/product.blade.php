@extends('layouts.app')

@section('title', 'Producto — Tienda')

@section('content')
    <div class="page-container py-8">
        <nav class="mb-6 text-sm text-slate-500">
            <a href="/" class="hover:text-slate-700">Inicio</a>
            <span class="mx-1.5">/</span>
            <a href="/catalogo" class="hover:text-slate-700">Catálogo</a>
            <span class="mx-1.5">/</span>
            <span id="product-breadcrumb" class="text-slate-700">…</span>
        </nav>

        <div id="product-detail">
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                <div class="skeleton aspect-square w-full"></div>
                <div class="space-y-4">
                    <div class="skeleton h-4 w-24"></div>
                    <div class="skeleton h-8 w-3/4"></div>
                    <div class="skeleton h-6 w-32"></div>
                    <div class="skeleton h-24 w-full"></div>
                </div>
            </div>
        </div>
    </div>
@endsection
