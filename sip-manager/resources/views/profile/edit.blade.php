@extends('layouts.app')

@section('title', 'Mon profil')
@section('page-title', 'Mon profil')

@section('content')
    <div class="section-header">
        <div>
            <h5 class="mb-1" style="font-weight:700;">Mon profil</h5>
            <p class="mb-0" style="font-size:0.82rem;color:var(--text-secondary);">Modifier vos informations et votre mot de passe</p>
        </div>
    </div>

    <div class="row g-4">
        {{-- Informations --}}
        <div class="col-lg-6">
            <div class="stat-card">
                <h6 style="font-weight:700;font-size:0.9rem;margin-bottom:1rem;"><i class="bi bi-person me-1" style="color:var(--accent);"></i>Informations</h6>
                <form action="{{ route('profile.update') }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="mb-3">
                        <label class="form-label">Nom</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', auth()->user()->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', auth()->user()->email) }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-accent">
                        <i class="bi bi-check-lg me-1"></i> Sauvegarder
                    </button>
                    @if(session('status') === 'profile-updated')
                        <span style="font-size:0.8rem;color:#29b6f6;margin-left:0.75rem;"><i class="bi bi-check-circle me-1"></i>Enregistre</span>
                    @endif
                </form>
            </div>
        </div>

        {{-- Mot de passe --}}
        <div class="col-lg-6">
            <div class="stat-card">
                <h6 style="font-weight:700;font-size:0.9rem;margin-bottom:1rem;"><i class="bi bi-lock me-1" style="color:var(--accent);"></i>Mot de passe</h6>
                <form action="{{ route('password.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Mot de passe actuel</label>
                        <input type="password" name="current_password" class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" required>
                        @error('current_password', 'updatePassword')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nouveau mot de passe</label>
                        <input type="password" name="password" class="form-control @error('password', 'updatePassword') is-invalid @enderror" required>
                        @error('password', 'updatePassword')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirmer le mot de passe</label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-accent">
                        <i class="bi bi-key me-1"></i> Changer le mot de passe
                    </button>
                    @if(session('status') === 'password-updated')
                        <span style="font-size:0.8rem;color:#29b6f6;margin-left:0.75rem;"><i class="bi bi-check-circle me-1"></i>Mot de passe mis a jour</span>
                    @endif
                </form>
            </div>
        </div>
    </div>
@endsection
