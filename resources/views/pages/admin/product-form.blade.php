@extends('layouts.admin')

@section('title', 'Producto — Panel admin')

@section('content')
    <a href="/admin/productos" class="mb-4 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
        Productos
    </a>
    <h1 id="product-form-title" class="mb-6 text-2xl font-bold text-slate-900">Producto</h1>

    <div id="product-form-container" class="max-w-2xl">
        <div class="skeleton h-96 w-full"></div>
    </div>
@endsection
