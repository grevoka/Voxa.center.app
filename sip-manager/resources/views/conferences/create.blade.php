@extends('layouts.app')

@section('title', __('ui.new_f') . ' ' . __('ui.conferences') de conference')
@section('page-title', __('ui.new_f') . ' ' . __('ui.conferences') de conference')

@section('content')
    <div class="section-header">
        <div>
            <h5 style="font-weight:700; margin:0;">Nouvelle salle de conference</h5>
            <p style="color:var(--text-secondary); font-size:0.82rem; margin:0;">Configurez un pont audio ConfBridge</p>
        </div>
        <a href="{{ route('conferences.index') }}" class="btn-outline-custom">
            <i class="bi bi-arrow-left me-1"></i> Retour
        </a>
    </div>

    @include('conferences._form', ['room' => null])
@endsection
