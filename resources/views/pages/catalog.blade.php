@extends('layouts.app')

@section('title', 'Catálogo — Tienda')

@section('content')
    <div class="page-container py-8">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Catálogo</h1>
                <p id="catalog-result-count" class="mt-1 text-sm text-slate-500">Cargando productos…</p>
            </div>
            <button type="button" id="filters-toggle" class="btn-secondary lg:hidden">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" /></svg>
                Filtros
            </button>
        </div>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-[16rem_1fr]">
            <aside id="filters-panel" class="hidden lg:block">
                <form id="filters-form" class="surface space-y-5 p-5">
                    <div>
                        <label for="filter-search" class="field-label">Buscar</label>
                        <input type="search" id="filter-search" name="search" class="field-input" placeholder="Nombre del producto">
                    </div>

                    <div>
                        <label for="filter-category" class="field-label">Categoría</label>
                        <select id="filter-category" name="category_id" class="field-input">
                            <option value="">Todas</option>
                        </select>
                    </div>

                    <div>
                        <span class="field-label">Precio</span>
                        <div class="flex items-center gap-2">
                            <input type="number" min="0" step="0.01" name="min_price" placeholder="Mín" class="field-input">
                            <span class="text-slate-400">–</span>
                            <input type="number" min="0" step="0.01" name="max_price" placeholder="Máx" class="field-input">
                        </div>
                        <p class="field-error hidden" data-error-for="max_price"></p>
                    </div>

                    <div class="flex flex-col gap-2">
                        <button type="submit" class="btn-primary btn-block">Aplicar filtros</button>
                        <button type="button" id="filters-clear" class="btn-ghost btn-block">Limpiar filtros</button>
                    </div>
                </form>
            </aside>

            <div>
                <div id="catalog-grid" class="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4">
                    @for ($i = 0; $i < 8; $i++)
                        <div class="surface overflow-hidden">
                            <div class="skeleton aspect-square w-full"></div>
                            <div class="space-y-2 p-4">
                                <div class="skeleton h-4 w-3/4"></div>
                                <div class="skeleton h-4 w-1/2"></div>
                            </div>
                        </div>
                    @endfor
                </div>

                <nav id="catalog-pagination" class="mt-8 flex items-center justify-center gap-1"></nav>
            </div>
        </div>
    </div>
@endsection
