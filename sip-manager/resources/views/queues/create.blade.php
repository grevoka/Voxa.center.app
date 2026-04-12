@extends('layouts.app')

@section('title', __('ui.new_f') . ' ' . __('ui.queues') d\')
@section('page-title', __('ui.new_f') . ' ' . __('ui.queues') d\')

@section('content')
    <div class="section-header">
        <div>
            <h5 style="font-weight:700; margin:0;">Nouvelle file d'attente</h5>
            <p style="color:var(--text-secondary); font-size:0.82rem; margin:0;">Configurez une file d'attente pour les appels entrants</p>
        </div>
        <a href="{{ route('queues.index') }}" class="btn-outline-custom">
            <i class="bi bi-arrow-left me-1"></i> Retour
        </a>
    </div>

    @include('queues._form', ['queue' => null])
@endsection
