@extends('layouts.app')

@section('title', 'Modifier salle de conference')
@section('page-title', 'Modifier salle de conference')

@section('content')
    <div class="section-header">
        <div>
            <h5 style="font-weight:700; margin:0;">Modifier : {{ $room->display_name ?: $room->name }}</h5>
            <p style="color:var(--text-secondary); font-size:0.82rem; margin:0;">
                Numero <code style="color:var(--accent);">{{ $room->conference_number }}</code>
            </p>
        </div>
        <a href="{{ route('conferences.index') }}" class="btn-outline-custom">
            <i class="bi bi-arrow-left me-1"></i> Retour
        </a>
    </div>

    @include('conferences._form', ['room' => $room])
@endsection
