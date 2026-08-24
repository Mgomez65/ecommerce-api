@extends('layouts.app')

@section('title', 'Recuperar contraseña — Tienda')

@section('content')
    <div class="page-container flex min-h-[70vh] items-center justify-center py-12">
        <div class="w-full max-w-sm">
            <h1 class="mb-1 text-center text-2xl font-bold text-slate-900">Recuperar contraseña</h1>
            <p class="mb-6 text-center text-sm text-slate-500">
                Ingresá tu email y te mandamos un link para restablecerla.
            </p>

            <form id="forgot-password-form" class="surface space-y-4 p-6">
                <div>
                    <label for="email" class="field-label">Email</label>
                    <input type="email" id="email" name="email" required autocomplete="email" class="field-input">
                    <p class="field-error hidden" data-error-for="email"></p>
                </div>
                <p id="forgot-password-success" class="alert-success hidden"></p>
                <p id="forgot-password-error" class="field-error hidden"></p>
                <button type="submit" class="btn-primary btn-block">Enviar link</button>
            </form>

            <p class="mt-4 text-center text-sm text-slate-500">
                <a href="/login" class="font-medium text-brand-600 hover:text-brand-700">Volver a ingresar</a>
            </p>
        </div>
    </div>
@endsection
