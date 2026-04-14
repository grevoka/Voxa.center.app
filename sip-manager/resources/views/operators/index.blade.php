@extends('layouts.app')

@section('title', __('ui.operators'))
@section('page-title', __('ui.operators'))

@section('content')
    <div class="section-header d-flex align-items-center justify-content-between">
        <div>
            <h5 class="mb-1" style="font-weight:700;">{{ __('ui.operators') }}</h5>
            <p class="mb-0" style="font-size:0.82rem;color:var(--text-secondary);">{{ __('ui.operator_accounts') }}</p>
        </div>
        <a href="{{ route('operators.create') }}" class="btn btn-accent">
            <i class="bi bi-plus-lg me-1"></i> {{ __('ui.new_operator') }}
        </a>
    </div>

    <div class="data-table">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>{{ __('ui.name') }}</th>
                    <th>{{ __('ui.email') }}</th>
                    <th>{{ __('ui.post') }}</th>
                    <th>{{ __('ui.extension') }}</th>
                    <th>{{ __('ui.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($operators as $op)
                <tr>
                    <td style="font-weight:600;">{{ $op->name }}</td>
                    <td style="font-size:0.82rem;color:var(--text-secondary);">{{ $op->email }}</td>
                    <td>{{ $op->sipLine?->name ?? '—' }}</td>
                    <td><span class="codec-tag">{{ $op->sipLine?->extension ?? '—' }}</span></td>
                    <td>
                        <div class="d-flex gap-1">
                            <form action="{{ route('admin.impersonate', $op) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn-icon" title="{{ __('ui.login_as') }}" style="width:28px;height:28px;font-size:0.75rem;">
                                    <i class="bi bi-person-badge"></i>
                                </button>
                            </form>
                            <a href="{{ route('operators.edit', $op) }}" class="btn-icon" title="{{ __('ui.edit') }}" style="width:28px;height:28px;font-size:0.75rem;">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('operators.destroy', $op) }}" method="POST" onsubmit="return confirm('{{ __('ui.confirm_delete') }}')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon" title="{{ __('ui.delete') }}" style="width:28px;height:28px;font-size:0.75rem;color:var(--danger);">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center py-4" style="color:var(--text-secondary);">
                    <i class="bi bi-people me-1"></i>{{ __('ui.no_operators') }} — <a href="{{ route('operators.create') }}" style="color:var(--accent);">{{ __('ui.create_first') }}</a>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
