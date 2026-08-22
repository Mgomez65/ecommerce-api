@extends('layouts.admin')

@section('title', 'Pedidos — Panel admin')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold text-slate-900">Pedidos</h1>
        <select id="status-filter" class="field-input w-auto">
            <option value="">Todos los estados</option>
            <option value="pending">Pendiente</option>
            <option value="confirmed">Confirmado</option>
            <option value="completed">Completado</option>
            <option value="cancelled">Cancelado</option>
        </select>
    </div>

    <div id="admin-orders-list" class="surface overflow-x-auto">
        <div class="skeleton h-64 w-full"></div>
    </div>
@endsection
