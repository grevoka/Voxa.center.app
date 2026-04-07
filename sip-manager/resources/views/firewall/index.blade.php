@extends('layouts.app')

@section('title', 'Firewall SIP')
@section('page-title', 'Firewall SIP')

@section('content')
    <div class="section-header d-flex align-items-center justify-content-between">
        <div>
            <h5 class="mb-1" style="font-weight:700;">Firewall SIP</h5>
            <p class="mb-0" style="font-size:0.82rem;color:var(--text-secondary);">Gerez les adresses IP autorisees et bloquees sur le port SIP (5060)</p>
        </div>
    </div>

    {{-- Mode toggle --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <form action="{{ route('firewall.mode') }}" method="POST">
                @csrf
                <input type="hidden" name="mode" value="whitelist">
                <div class="stat-card" style="cursor:pointer;padding:1rem;{{ $firewallMode === 'whitelist' ? 'border-color:var(--accent);' : '' }}" onclick="this.closest('form').submit()">
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:10px;height:10px;border-radius:50%;background:{{ $firewallMode === 'whitelist' ? '#00e5a0' : 'var(--text-secondary)' }};"></div>
                        <span style="font-weight:700;font-size:0.85rem;">Whitelist</span>
                    </div>
                    <div style="font-size:0.72rem;color:var(--text-secondary);margin-top:0.3rem;">Seules les IPs autorisees passent. Plus securise.</div>
                </div>
            </form>
        </div>
        <div class="col-md-4">
            <form action="{{ route('firewall.mode') }}" method="POST">
                @csrf
                <input type="hidden" name="mode" value="fail2ban">
                <div class="stat-card" style="cursor:pointer;padding:1rem;{{ $firewallMode === 'fail2ban' ? 'border-color:#d29922;' : '' }}" onclick="this.closest('form').submit()">
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:10px;height:10px;border-radius:50%;background:{{ $firewallMode === 'fail2ban' ? '#d29922' : 'var(--text-secondary)' }};"></div>
                        <span style="font-weight:700;font-size:0.85rem;">Fail2Ban uniquement</span>
                    </div>
                    <div style="font-size:0.72rem;color:var(--text-secondary);margin-top:0.3rem;">Toutes les IPs autorisees, ban apres 5 echecs.</div>
                </div>
            </form>
        </div>
        <div class="col-md-4">
            <form action="{{ route('firewall.mode') }}" method="POST">
                @csrf
                <input type="hidden" name="mode" value="off">
                <div class="stat-card" style="cursor:pointer;padding:1rem;{{ $firewallMode === 'off' ? 'border-color:#f85149;' : '' }}" onclick="this.closest('form').submit()">
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:10px;height:10px;border-radius:50%;background:{{ $firewallMode === 'off' ? '#f85149' : 'var(--text-secondary)' }};"></div>
                        <span style="font-weight:700;font-size:0.85rem;">Desactive</span>
                    </div>
                    <div style="font-size:0.72rem;color:var(--text-secondary);margin-top:0.3rem;">Aucune protection. Non recommande.</div>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4">
        {{-- Whitelist --}}
        <div class="col-lg-6">
            <div class="data-table">
                <div class="px-3 py-2 d-flex align-items-center justify-content-between" style="border-bottom:1px solid var(--border);">
                    <h6 class="mb-0" style="font-size:0.85rem;font-weight:700;">
                        <i class="bi bi-shield-check me-1" style="color:#00e5a0;"></i> Whitelist
                        <span style="font-size:0.65rem;color:var(--text-secondary);font-weight:400;margin-left:0.3rem;">Seules ces IPs peuvent acceder au SIP</span>
                    </h6>
                </div>

                {{-- Add form --}}
                <form action="{{ route('firewall.store') }}" method="POST" class="px-3 py-2" style="border-bottom:1px solid var(--border);background:rgba(var(--accent-rgb),0.02);">
                    @csrf
                    <input type="hidden" name="type" value="whitelist">
                    <div class="row g-2 align-items-end">
                        <div class="col-5">
                            <label class="form-label" style="font-size:0.72rem;">IP ou CIDR</label>
                            <input type="text" name="ip_range" class="form-control form-control-sm" placeholder="91.121.128.0/24" required
                                   style="font-family:'JetBrains Mono',monospace;font-size:0.8rem;">
                        </div>
                        <div class="col-4">
                            <label class="form-label" style="font-size:0.72rem;">Label</label>
                            <input type="text" name="label" class="form-control form-control-sm" placeholder="OVH SIP">
                        </div>
                        <div class="col-3">
                            <button type="submit" class="btn btn-accent btn-sm w-100"><i class="bi bi-plus-lg me-1"></i>Ajouter</button>
                        </div>
                    </div>
                </form>

                {{-- List --}}
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>IP / CIDR</th>
                            <th>Label</th>
                            <th style="width:100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($whitelist as $rule)
                        <tr style="{{ !$rule->enabled ? 'opacity:0.5;' : '' }}">
                            <td style="font-family:'JetBrains Mono',monospace;font-size:0.8rem;font-weight:600;">
                                {{ $rule->ip_range }}
                            </td>
                            <td style="font-size:0.82rem;color:var(--text-secondary);">{{ $rule->label ?: '—' }}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    <form action="{{ route('firewall.toggle', $rule) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn-icon" title="{{ $rule->enabled ? 'Desactiver' : 'Activer' }}" style="width:26px;height:26px;font-size:0.7rem;">
                                            <i class="bi bi-power"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('firewall.destroy', $rule) }}" method="POST" onsubmit="return confirm('Supprimer {{ $rule->ip_range }} ?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-icon" title="Supprimer" style="width:26px;height:26px;font-size:0.7rem;color:#f85149;">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center py-3" style="color:var(--text-secondary);font-size:0.82rem;">
                                <i class="bi bi-info-circle me-1"></i>Aucune regle — tout le trafic SIP est autorise
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Blacklist --}}
        <div class="col-lg-6">
            <div class="data-table">
                <div class="px-3 py-2 d-flex align-items-center justify-content-between" style="border-bottom:1px solid var(--border);">
                    <h6 class="mb-0" style="font-size:0.85rem;font-weight:700;">
                        <i class="bi bi-shield-x me-1" style="color:#f85149;"></i> Blacklist
                        <span style="font-size:0.65rem;color:var(--text-secondary);font-weight:400;margin-left:0.3rem;">IPs bloquees definitivement</span>
                    </h6>
                </div>

                {{-- Add form --}}
                <form action="{{ route('firewall.store') }}" method="POST" class="px-3 py-2" style="border-bottom:1px solid var(--border);background:rgba(248,81,73,0.02);">
                    @csrf
                    <input type="hidden" name="type" value="blacklist">
                    <div class="row g-2 align-items-end">
                        <div class="col-5">
                            <label class="form-label" style="font-size:0.72rem;">IP ou CIDR</label>
                            <input type="text" name="ip_range" class="form-control form-control-sm" placeholder="51.195.20.203" required
                                   style="font-family:'JetBrains Mono',monospace;font-size:0.8rem;">
                        </div>
                        <div class="col-4">
                            <label class="form-label" style="font-size:0.72rem;">Raison</label>
                            <input type="text" name="label" class="form-control form-control-sm" placeholder="SIP scanner">
                        </div>
                        <div class="col-3">
                            <button type="submit" class="btn btn-sm w-100" style="background:#f85149;color:#fff;border:none;"><i class="bi bi-slash-circle me-1"></i>Bloquer</button>
                        </div>
                    </div>
                </form>

                {{-- List --}}
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>IP / CIDR</th>
                            <th>Raison</th>
                            <th style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($blacklist as $rule)
                        <tr>
                            <td style="font-family:'JetBrains Mono',monospace;font-size:0.8rem;font-weight:600;color:#f85149;">
                                {{ $rule->ip_range }}
                            </td>
                            <td style="font-size:0.82rem;color:var(--text-secondary);">{{ $rule->label ?: '—' }}</td>
                            <td>
                                <form action="{{ route('firewall.destroy', $rule) }}" method="POST" onsubmit="return confirm('Debloquer {{ $rule->ip_range }} ?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon" title="Debloquer" style="width:26px;height:26px;font-size:0.7rem;">
                                        <i class="bi bi-unlock"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center py-3" style="color:var(--text-secondary);font-size:0.82rem;">
                                Aucune IP bloquee
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Fail2Ban bans --}}
            @if(count($banned) > 0)
            <div class="data-table mt-3">
                <div class="px-3 py-2" style="border-bottom:1px solid var(--border);">
                    <h6 class="mb-0" style="font-size:0.85rem;font-weight:700;">
                        <i class="bi bi-ban me-1" style="color:#d29922;"></i> Bannies par Fail2Ban
                        <span class="badge" style="background:#d2992220;color:#d29922;font-size:0.6rem;margin-left:0.3rem;">{{ count($banned) }}</span>
                    </h6>
                </div>
                <table class="table mb-0">
                    <tbody>
                        @foreach($banned as $ip)
                        <tr>
                            <td style="font-family:'JetBrains Mono',monospace;font-size:0.8rem;color:#d29922;">{{ $ip }}</td>
                            <td style="width:80px;">
                                <form action="{{ route('firewall.unban') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="ip" value="{{ $ip }}">
                                    <button type="submit" class="btn-icon" title="Debannir" style="width:26px;height:26px;font-size:0.7rem;">
                                        <i class="bi bi-unlock"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            {{-- Info --}}
            <div class="stat-card mt-3" style="padding:1rem;">
                <h6 style="font-size:0.82rem;font-weight:700;margin-bottom:0.5rem;"><i class="bi bi-lightbulb me-1" style="color:#d29922;"></i>Comment ca marche</h6>
                <ul style="font-size:0.78rem;color:var(--text-secondary);margin:0;padding-left:1.2rem;">
                    <li><b>Whitelist</b> : seules les IPs autorisees passent + blacklist appliquee</li>
                    <li><b>Fail2Ban</b> : toutes les IPs autorisees, ban auto apres 5 echecs (10 min)</li>
                    <li><b>Desactive</b> : aucune protection (non recommande)</li>
                    <li>La blacklist est toujours appliquee sauf en mode desactive</li>
                    <li>Ajoutez les ranges de votre fournisseur SIP (ex: OVH <code>91.121.128.0/23</code>)</li>
                </ul>
            </div>
        </div>
    </div>
@endsection
