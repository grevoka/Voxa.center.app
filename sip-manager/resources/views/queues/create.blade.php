@extends('layouts.app')

@section('title', __('ui.new_queue'))
@section('page-title', __('ui.new_queue'))

@section('content')
    <div class="section-header">
        <div>
            <h5 style="font-weight:700; margin:0;">{{ __('ui.new_queue') }}</h5>
            <p style="color:var(--text-secondary); font-size:0.82rem; margin:0;">{{ __('ui.configure_queue') }}</p>
        </div>
        <a href="{{ route('queues.index') }}" class="btn-outline-custom">
            <i class="bi bi-arrow-left me-1"></i> {{ __('ui.back') }}
        </a>
    </div>

    @include('queues._form', ['queue' => null])
@endsection
