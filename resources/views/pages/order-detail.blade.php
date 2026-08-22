@extends('layouts.app')

@section('title', 'Detalle del pedido — Tienda')

@section('content')
    <div class="page-container py-8">
        <a href="/mis-pedidos" class="mb-4 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            Mis pedidos
        </a>
        <div id="order-detail" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="skeleton h-64 lg:col-span-2"></div>
            <div class="skeleton h-48"></div>
        </div>
    </div>
@endsection
