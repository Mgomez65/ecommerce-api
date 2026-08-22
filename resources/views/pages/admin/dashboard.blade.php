@extends('layouts.admin')

@section('title', 'Dashboard — Panel admin')

@section('content')
    <h1 class="mb-6 text-2xl font-bold text-slate-900">Dashboard</h1>

    <div id="admin-summary" class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @for ($i = 0; $i < 4; $i++)
            <div class="skeleton h-24"></div>
        @endfor
    </div>

    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="surface p-5">
            <h2 class="mb-4 text-base font-semibold text-slate-900">Stock bajo</h2>
            <div id="admin-low-stock" class="space-y-2">
                <div class="skeleton h-10 w-full"></div>
            </div>
        </div>

        <div class="surface p-5">
            <h2 class="mb-4 text-base font-semibold text-slate-900">Productos más vendidos</h2>
            <div id="admin-top-products" class="space-y-2">
                <div class="skeleton h-10 w-full"></div>
            </div>
        </div>
    </div>

    <div class="surface mt-6 p-5">
        <h2 class="mb-4 text-base font-semibold text-slate-900">Pedidos recientes</h2>
        <div id="admin-recent-orders" class="overflow-x-auto">
            <div class="skeleton h-32 w-full"></div>
        </div>
    </div>
@endsection
