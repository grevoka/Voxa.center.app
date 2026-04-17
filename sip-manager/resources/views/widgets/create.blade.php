@extends('layouts.app')

@section('title', 'New Widget')
@section('page-title', 'New Widget')

@section('content')
    <div class="section-header">
        <div>
            <h5 class="mb-1" style="font-weight:700;"><i class="bi bi-plus-circle me-1" style="color:var(--accent);"></i> New Call Widget</h5>
            <p class="mb-0" style="font-size:0.82rem;color:var(--text-secondary);">Create an embeddable WebRTC call button for a website</p>
        </div>
        <a href="{{ route('widgets.index') }}" class="btn-outline-custom"><i class="bi bi-arrow-left me-1"></i> {{ __('ui.back') }}</a>
    </div>

    <form action="{{ route('widgets.store') }}" method="POST">
        @csrf
        @include('widgets._form', ['widget' => null])
    </form>
@endsection
