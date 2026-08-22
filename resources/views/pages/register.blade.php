@extends('layouts.app')

@section('title', 'Crear cuenta — Tienda')

@section('content')
    <div class="page-container flex min-h-[70vh] items-center justify-center py-12">
        <div class="w-full max-w-sm">
            <h1 class="mb-1 text-center text-2xl font-bold text-slate-900">Creá tu cuenta</h1>
            <p class="mb-6 text-center text-sm text-slate-500">
                ¿Ya tenés cuenta? <a href="/login" class="font-medium text-brand-600 hover:text-brand-700">Ingresá</a>
            </p>

            <form id="register-form" class="surface space-y-4 p-6">
                <div>
                    <label for="name" class="field-label">Nombre</label>
                    <input type="text" id="name" name="name" required autocomplete="name" class="field-input">
                    <p class="field-error hidden" data-error-for="name"></p>
                </div>
                <div>
                    <label for="email" class="field-label">Email</label>
                    <input type="email" id="email" name="email" required autocomplete="email" class="field-input">
                    <p class="field-error hidden" data-error-for="email"></p>
                </div>
                <div>
                    <label for="password" class="field-label">Contraseña</label>
                    <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password" class="field-input">
                    <p class="field-hint">Mínimo 8 caracteres.</p>
                    <p class="field-error hidden" data-error-for="password"></p>
                </div>
                <div>
                    <label for="password_confirmation" class="field-label">Confirmar contraseña</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" class="field-input">
                </div>
                <p id="register-form-error" class="field-error hidden"></p>
                <button type="submit" class="btn-primary btn-block">Crear cuenta</button>
            </form>
        </div>
    </div>
@endsection
