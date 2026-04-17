@if($errors->any())
<div style="background:#f8514915;border:1px solid #f8514940;border-radius:10px;padding:.75rem 1rem;margin-bottom:1rem;">
    @foreach($errors->all() as $error)
        <div style="font-size:.8rem;color:#f85149;">{{ $error }}</div>
    @endforeach
</div>
@endif

<div class="row g-4">
    <div class="col-lg-7">
        <div class="stat-card">
            <h6 style="font-weight:700;font-size:.9rem;margin-bottom:1rem;">
                <i class="bi bi-gear me-1" style="color:var(--accent);"></i> {{ __('ui.configuration') }}
            </h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">{{ __('ui.name') }} *</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $widget->name ?? '') }}" placeholder="My Website Widget" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Domain *</label>
                    <input type="text" name="domain" class="form-control @error('domain') is-invalid @enderror"
                           value="{{ old('domain', $widget->domain ?? '') }}" placeholder="example.com or *.example.com" required>
                    <small style="color:var(--text-secondary);font-size:.68rem;">Allowed origin domain. Use *.domain.com for subdomains.</small>
                    @error('domain') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Target *</label>
                    <div class="d-flex gap-3 mb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="target_type" value="callflow" id="targetCallflow"
                                   {{ old('target_type', $widget->callflow_id ? 'callflow' : ($widget->extension ? 'extension' : 'callflow')) === 'callflow' ? 'checked' : '' }}
                                   onchange="document.getElementById('callflowGroup').style.display='';document.getElementById('extensionGroup').style.display='none';">
                            <label class="form-check-label" for="targetCallflow">Call Flow ({{ __('ui.scenarios') }})</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="target_type" value="extension" id="targetExtension"
                                   {{ old('target_type', $widget->extension ? 'extension' : '') === 'extension' ? 'checked' : '' }}
                                   onchange="document.getElementById('extensionGroup').style.display='';document.getElementById('callflowGroup').style.display='none';">
                            <label class="form-check-label" for="targetExtension">{{ __('ui.extension') }}</label>
                        </div>
                    </div>
                    <div id="callflowGroup" style="{{ old('target_type', $widget->extension ?? '' ? 'extension' : 'callflow') === 'extension' ? 'display:none;' : '' }}">
                        <select name="callflow_id" class="form-select">
                            <option value="">— {{ __('ui.choose') }} —</option>
                            @foreach($callflows as $cf)
                                <option value="{{ $cf->id }}" {{ old('callflow_id', $widget->callflow_id ?? '') == $cf->id ? 'selected' : '' }}>
                                    {{ $cf->name }}{{ $cf->description ? ' — '.$cf->description : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div id="extensionGroup" style="{{ old('target_type', $widget->extension ?? '' ? 'extension' : 'callflow') !== 'extension' ? 'display:none;' : '' }}">
                        <select name="extension" class="form-select">
                            <option value="">— {{ __('ui.choose') }} —</option>
                            @foreach($lines as $line)
                                <option value="{{ $line->extension }}" {{ old('extension', $widget->extension ?? '') == $line->extension ? 'selected' : '' }}>
                                    {{ $line->extension }} — {{ $line->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Max concurrent</label>
                    <input type="number" name="max_concurrent" class="form-control"
                           value="{{ old('max_concurrent', $widget->max_concurrent ?? 5) }}" min="1" max="100">
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" name="enabled" value="1" id="widgetEnabled"
                               {{ old('enabled', $widget->enabled ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="widgetEnabled">{{ __('ui.active') }}</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-3 d-flex gap-2">
            <button type="submit" class="btn btn-accent">
                <i class="bi bi-check-lg me-1"></i> {{ $widget ? __('ui.save') : __('ui.create') }}
            </button>
            <a href="{{ route('widgets.index') }}" class="btn-outline-custom">{{ __('ui.cancel') }}</a>
            @if($widget)
                <form action="{{ route('widgets.regenerate', $widget) }}" method="POST" class="ms-auto" onsubmit="return confirm('Regenerate token? The old embed code will stop working.')">
                    @csrf
                    <button type="submit" class="btn btn-sm" style="background:#d2992220;color:#d29922;border:1px solid #d2992240;">
                        <i class="bi bi-arrow-repeat me-1"></i> Regenerate token
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="col-lg-5">
        @if($widget)
        {{-- Embed snippet --}}
        <div class="stat-card mb-3">
            <h6 style="font-weight:700;font-size:.85rem;margin-bottom:.75rem;">
                <i class="bi bi-code-slash me-1" style="color:var(--accent);"></i> Embed Code
            </h6>

            <div style="position:relative;">
                <pre id="embedCode" style="background:#0d1117;border:1px solid #21262d;border-radius:8px;padding:.75rem;color:#e6edf3;font-family:'JetBrains Mono',monospace;font-size:.7rem;overflow-x:auto;white-space:pre-wrap;margin:0;line-height:1.6;">&lt;script src="{{ rtrim(config('app.url'), '/') }}/js/voxa-widget.js"
  data-token="{{ $widget->token }}"
  data-mode="bubble"
  data-color="#58a6ff"
  data-position="bottom-right"&gt;&lt;/script&gt;</pre>
                <button onclick="navigator.clipboard.writeText(document.getElementById('embedCode').textContent);this.innerHTML='<i class=\'bi bi-check-lg\'></i> Copied!';setTimeout(()=>this.innerHTML='<i class=\'bi bi-clipboard\'></i> Copy',2000);"
                    class="btn btn-sm" style="position:absolute;top:6px;right:6px;background:#21262d;color:#8b949e;border:1px solid #30363d;font-size:.65rem;">
                    <i class="bi bi-clipboard"></i> Copy
                </button>
            </div>

            <div style="margin-top:.75rem;padding:.5rem;background:var(--surface);border:1px solid var(--border);border-radius:6px;font-size:.72rem;color:var(--text-secondary);">
                <div class="mb-1"><strong>Attributes:</strong></div>
                <div><code>data-mode</code> — <code>bubble</code> (floating) or <code>inline</code></div>
                <div><code>data-color</code> — button color (hex)</div>
                <div><code>data-position</code> — <code>bottom-right</code> or <code>bottom-left</code></div>
                <div><code>data-label</code> — button text (inline mode)</div>
            </div>
        </div>

        {{-- Stats --}}
        <div class="stat-card">
            <h6 style="font-weight:700;font-size:.85rem;margin-bottom:.75rem;">
                <i class="bi bi-graph-up me-1" style="color:#3fb950;"></i> Statistics
            </h6>
            <div class="d-flex justify-content-between" style="font-size:.82rem;">
                <span style="color:var(--text-secondary);">Total calls</span>
                <span style="font-weight:700;">{{ number_format($widget->call_count) }}</span>
            </div>
            <div class="d-flex justify-content-between" style="font-size:.82rem;">
                <span style="color:var(--text-secondary);">Last used</span>
                <span style="font-weight:600;">{{ $widget->last_used_at ? $widget->last_used_at->diffForHumans() : '—' }}</span>
            </div>
            <div class="d-flex justify-content-between" style="font-size:.82rem;">
                <span style="color:var(--text-secondary);">Created</span>
                <span>{{ $widget->created_at->format('d/m/Y H:i') }}</span>
            </div>
        </div>
        @else
        <div class="stat-card" style="padding:.75rem;">
            <h6 style="font-size:.82rem;font-weight:700;margin-bottom:.5rem;">
                <i class="bi bi-lightbulb me-1" style="color:#d29922;"></i> How it works
            </h6>
            <ul style="font-size:.72rem;color:var(--text-secondary);margin:0;padding-left:1rem;line-height:1.6;">
                <li>Create a widget with a target (Call Flow or extension)</li>
                <li>Copy the embed code into your website HTML</li>
                <li>Visitors click the call button — a WebRTC call starts</li>
                <li>The call routes to your configured target</li>
                <li>No login required — auth via token + domain</li>
            </ul>
        </div>
        @endif
    </div>
</div>
