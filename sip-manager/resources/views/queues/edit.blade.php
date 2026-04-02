@extends('layouts.app')

@section('title', 'Modifier — ' . ($queue->display_name ?: $queue->name))
@section('page-title', 'Modifier la file d\'attente')

@section('content')
    <div class="section-header">
        <div>
            <h5 style="font-weight:700; margin:0;">Modifier: {{ $queue->display_name ?: $queue->name }}</h5>
            <p style="color:var(--text-secondary); font-size:0.82rem; margin:0;">Modifiez les parametres de cette file d'attente</p>
        </div>
        <a href="{{ route('queues.index') }}" class="btn-outline-custom">
            <i class="bi bi-arrow-left me-1"></i> Retour
        </a>
    </div>

    @include('queues._form', ['queue' => $queue])
@endsection
