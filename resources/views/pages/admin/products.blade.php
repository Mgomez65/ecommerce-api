@extends('layouts.admin')

@section('title', 'Productos — Panel admin')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold text-slate-900">Productos</h1>
        <a href="/admin/productos/nuevo" class="btn-primary">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            Nuevo producto
        </a>
    </div>

    <div class="surface overflow-x-auto">
        <div id="admin-products-list" class="p-1">
            <div class="skeleton h-64 w-full"></div>
        </div>
    </div>

    <nav id="admin-products-pagination" class="mt-6 flex items-center justify-center gap-1"></nav>
@endsection
