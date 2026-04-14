@extends('layouts.app')

@section('title', __('ui.codecs'))
@section('page-title', __('ui.codecs'))

@section('content')
    <div class="section-header">
        <div>
            <h5 class="mb-1" style="font-weight:700;">{{ __('ui.audio_codecs') }}</h5>
            <p class="mb-0" style="font-size:0.82rem;color:var(--text-secondary);">{{ __('ui.codecs_desc') }}</p>
        </div>
    </div>

    @php
        $qualityMap = [
            'Excellent' => __('ui.quality_excellent'),
            'HD Voice' => __('ui.quality_hd'),
            'Bonne' => __('ui.quality_good'),
            'Supérieure' => __('ui.quality_superior'),
            'Moyenne' => __('ui.quality_average'),
            'Variable' => __('ui.quality_variable'),
        ];
    @endphp

    <div class="row g-3">
        @foreach(config('asterisk.codecs') as $key => $codec)
            <div class="col-md-6 col-lg-4">
                <div class="stat-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 style="font-weight:700;font-size:0.95rem;margin:0;">{{ $codec['name'] }}</h6>
                        <span class="codec-tag">{{ $key }}</span>
                    </div>
                    <div class="d-flex gap-3" style="font-size:0.82rem;color:var(--text-secondary);">
                        <div>
                            <i class="bi bi-speedometer2 me-1"></i>
                            {{ $codec['bitrate'] }}
                        </div>
                        <div>
                            <i class="bi bi-star me-1"></i>
                            {{ $qualityMap[$codec['quality']] ?? $codec['quality'] }}
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
