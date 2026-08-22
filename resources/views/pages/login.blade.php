@extends('layouts.app')

@section('title', 'Ingresar — Tienda')

@section('content')
    <div class="page-container flex min-h-[70vh] items-center justify-center py-12">
        <div class="w-full max-w-sm">
            <h1 class="mb-1 text-center text-2xl font-bold text-slate-900">Ingresá a tu cuenta</h1>
            <p class="mb-6 text-center text-sm text-slate-500">
                ¿No tenés cuenta? <a href="/registro" class="font-medium text-brand-600 hover:text-brand-700">Creá una gratis</a>
            </p>

            <form id="login-form" class="surface space-y-4 p-6">
                <div>
                    <label for="email" class="field-label">Email</label>
                    <input type="email" id="email" name="email" required autocomplete="email" class="field-input">
                    <p class="field-error hidden" data-error-for="email"></p>
                </div>
                <div>
                    <label for="password" class="field-label">Contraseña</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password" class="field-input">
                    <p class="field-error hidden" data-error-for="password"></p>
                </div>
                <p id="login-form-error" class="field-error hidden"></p>
                <button type="submit" class="btn-primary btn-block">Ingresar</button>
            </form>
        </div>
    </div>
@endsection
