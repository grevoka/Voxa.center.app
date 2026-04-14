@extends('layouts.app')

@section('title', __('ui.modify') . ' — ' . ($room->display_name ?: $room->name))
@section('page-title', __('ui.modify') . ' ' . __('ui.conference_rooms'))

@section('content')
    <div class="section-header">
        <div>
            <h5 style="font-weight:700; margin:0;">{{ __('ui.modify') }} : {{ $room->display_name ?: $room->name }}</h5>
            <p style="color:var(--text-secondary); font-size:0.82rem; margin:0;">
                {{ __('ui.conf_number') }} <code style="color:var(--accent);">{{ $room->conference_number }}</code>
            </p>
        </div>
        <a href="{{ route('conferences.index') }}" class="btn-outline-custom">
            <i class="bi bi-arrow-left me-1"></i> {{ __('ui.back') }}
        </a>
    </div>

    @include('conferences._form', ['room' => $room])
@endsection
