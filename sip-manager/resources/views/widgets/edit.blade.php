@extends('layouts.app')

@section('title', 'Edit Widget — ' . $widget->name)
@section('page-title', 'Edit Widget')

@section('content')
    <div class="section-header">
        <div>
            <h5 class="mb-1" style="font-weight:700;"><i class="bi bi-pencil me-1" style="color:var(--accent);"></i> {{ $widget->name }}</h5>
            <p class="mb-0" style="font-size:0.82rem;color:var(--text-secondary);">{{ $widget->domain }} — {{ number_format($widget->call_count) }} calls</p>
        </div>
        <a href="{{ route('widgets.index') }}" class="btn-outline-custom"><i class="bi bi-arrow-left me-1"></i> {{ __('ui.back') }}</a>
    </div>

    <form action="{{ route('widgets.update', $widget) }}" method="POST">
        @csrf @method('PUT')
        @include('widgets._form', ['widget' => $widget])
    </form>
@endsection
