@extends('layouts.app')

@section('title', 'Carrito — Tienda')

@section('content')
    <div class="page-container py-8">
        <h1 class="mb-6 text-2xl font-bold text-slate-900">Tu carrito</h1>
        <div id="cart-content" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="skeleton h-24 w-full"></div>
            <div class="skeleton h-24 w-full"></div>
        </div>
    </div>
@endsection
