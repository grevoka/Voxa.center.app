@extends('layouts.app')

@section('title', 'Nouvel operateur')
@section('page-title', 'Nouvel operateur')

@section('content')
    <div class="section-header">
        <h5 class="mb-1" style="font-weight:700;">Nouvel operateur</h5>
    </div>

    <form action="{{ route('operators.store') }}" method="POST">
        @csrf
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="stat-card">
                    <h6 style="font-weight:700;font-size:0.9rem;margin-bottom:1rem;">Informations</h6>
                    <div class="mb-3">
                        <label class="form-label">Nom</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label">Mot de passe</label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label">Confirmer</label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="stat-card">
                    <h6 style="font-weight:700;font-size:0.9rem;margin-bottom:1rem;">Ligne SIP</h6>
                    <div class="mb-3">
                        <label class="form-label">Poste associe</label>
                        <select name="sip_line_id" class="form-select @error('sip_line_id') is-invalid @enderror" required>
                            <option value="">— Choisir un poste —</option>
                            @foreach($lines as $line)
                                <option value="{{ $line->id }}" {{ old('sip_line_id') == $line->id ? 'selected' : '' }}>
                                    {{ $line->extension }} — {{ $line->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('sip_line_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <p style="font-size:0.78rem;color:var(--text-secondary);">
                        <i class="bi bi-info-circle me-1"></i>Seules les lignes sans operateur sont affichees.
                    </p>
                </div>
            </div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-accent"><i class="bi bi-check-lg me-1"></i> Creer</button>
            <a href="{{ route('operators.index') }}" class="btn btn-outline-custom">Annuler</a>
        </div>
    </form>
@endsection
