@extends('layouts.app')

@section('title', 'Mis pedidos — Tienda')

@section('content')
    <div class="page-container py-8">
        <h1 class="mb-6 text-2xl font-bold text-slate-900">Mis pedidos</h1>
        <div id="orders-list" class="space-y-3">
            <div class="skeleton h-20 w-full"></div>
            <div class="skeleton h-20 w-full"></div>
        </div>
    </div>
@endsection
