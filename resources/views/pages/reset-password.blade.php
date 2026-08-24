@extends('layouts.app')

@section('title', 'Restablecer contraseña — Tienda')

@section('content')
    <div class="page-container flex min-h-[70vh] items-center justify-center py-12">
        <div class="w-full max-w-sm">
            <h1 class="mb-1 text-center text-2xl font-bold text-slate-900">Elegí una nueva contraseña</h1>
            <p class="mb-6 text-center text-sm text-slate-500" id="reset-password-email"></p>

            <form id="reset-password-form" class="surface space-y-4 p-6">
                <div>
                    <label for="password" class="field-label">Nueva contraseña</label>
                    <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password" class="field-input">
                    <p class="field-hint">Mínimo 8 caracteres.</p>
                    <p class="field-error hidden" data-error-for="password"></p>
                </div>
                <div>
                    <label for="password_confirmation" class="field-label">Confirmar contraseña</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" class="field-input">
                </div>
                <p id="reset-password-error" class="field-error hidden"></p>
                <button type="submit" class="btn-primary btn-block">Restablecer contraseña</button>
            </form>
        </div>
    </div>
@endsection
