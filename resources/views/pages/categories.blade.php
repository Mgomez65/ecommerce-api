@extends('layouts.app')

@section('title', 'Categorías — Tienda')

@section('content')
    <div class="page-container py-8">
        <h1 class="mb-6 text-2xl font-bold text-slate-900">Categorías</h1>
        <div id="categories-list" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @for ($i = 0; $i < 6; $i++)
                <div class="skeleton h-28"></div>
            @endfor
        </div>
    </div>
@endsection
