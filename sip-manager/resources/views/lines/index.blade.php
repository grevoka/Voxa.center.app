@extends('layouts.app')

@section('title', __('ui.lines'))
@section('page-title', __('ui.lines'))

@section('content')
    <div class="section-header">
        <div>
            <h5 class="mb-1" style="font-weight:700;">{{ __('ui.sip_lines') }}</h5>
            <p class="mb-0" style="font-size:0.82rem;color:var(--text-secondary);">{{ __('ui.manage_extensions') }}</p>
        </div>
        <a href="{{ route('lines.create') }}" class="btn btn-accent">
            <i class="bi bi-plus-lg me-1"></i> {{ __('ui.new_f') }} {{ __('ui.lines') }}
        </a>
    </div>

    <div class="data-table">
        <table class="table">
            <thead>
                <tr>
                    <th>{{ __('ui.extension') }}</th>
                    <th>{{ __('ui.name') }}</th>
                    <th>{{ __('ui.email') }}</th>
                    <th>{{ __('ui.protocol') }}</th>
                    <th>{{ __('ui.status') }}</th>
                    <th>{{ __('ui.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($lines as $line)
                    <tr>
                        <td><span class="ext-number">{{ $line->extension }}</span></td>
                        <td style="font-weight:500;">{{ $line->name }}</td>
                        <td style="color:var(--text-secondary);font-size:0.82rem;">{{ $line->email ?? '—' }}</td>
                        <td><span class="codec-tag">{{ $line->protocol }}</span></td>
                        <td>
                            <span class="status-dot {{ $line->status }}"></span>
                            {{ $line->status === 'online' ? __('ui.line_online') : ($line->status === 'busy' ? __('ui.line_busy') : __('ui.line_offline')) }}
                        </td>
                        <td>
                            <form action="{{ route('lines.toggle', $line) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn-icon me-1" title="{{ __('ui.toggle_status') }}">
                                    <i class="bi bi-power"></i>
                                </button>
                            </form>
                            <a href="{{ route('lines.edit', $line) }}" class="btn-icon me-1" title="{{ __('ui.edit') }}">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('lines.destroy', $line) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('ui.confirm_delete') }}')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon danger" title="{{ __('ui.delete') }}">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4" style="color:var(--text-secondary);">
                            <i class="bi bi-telephone-x me-2"></i>{{ __('ui.no_lines') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $lines->links() }}</div>
@endsection
