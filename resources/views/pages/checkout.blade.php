@extends('layouts.app')

@section('title', 'Checkout — Tienda')

@section('content')
    <div class="page-container py-8">
        <h1 class="mb-6 text-2xl font-bold text-slate-900">Checkout</h1>

        <div id="checkout-content" class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            <div class="skeleton h-96 lg:col-span-2"></div>
            <div class="skeleton h-64"></div>
        </div>
    </div>
@endsection
