@extends('layouts.app')

@section('title', 'Caller ID')
@section('page-title', 'Caller ID')

@section('content')
    <div class="section-header d-flex align-items-center justify-content-between">
        <div>
            <h5 class="mb-1" style="font-weight:700;">Numeros sortants</h5>
            <p class="mb-0" style="font-size:0.82rem;color:var(--text-secondary);">Gerez les Caller ID presentes aux operateurs et les groupes d'acces</p>
        </div>
    </div>

    <div class="row g-4">
        {{-- ═══════════ Colonne gauche : Caller IDs ═══════════ --}}
        <div class="col-lg-7">
            <div class="data-table">
                <div class="px-3 py-2 d-flex align-items-center justify-content-between" style="border-bottom:1px solid var(--border);">
                    <h6 class="mb-0" style="font-size:0.85rem;font-weight:700;">
                        <i class="bi bi-telephone-forward me-1" style="color:var(--accent);"></i> Caller IDs
                        <span class="badge" style="background:var(--accent-dim);color:var(--accent);font-size:0.6rem;margin-left:0.3rem;">{{ $callerIds->total() }}</span>
                    </h6>
                </div>

                {{-- Add form --}}
                <form action="{{ route('caller-ids.store') }}" method="POST" class="px-3 py-2" style="border-bottom:1px solid var(--border);background:rgba(var(--accent-rgb),0.02);">
                    @csrf
                    <div class="row g-2 align-items-end">
                        <div class="col-4">
                            <label class="form-label" style="font-size:0.72rem;">Numero</label>
                            <input type="text" name="number" class="form-control form-control-sm" placeholder="+33185090002" required
                                   style="font-family:'JetBrains Mono',monospace;font-size:0.8rem;">
                        </div>
                        <div class="col-3">
                            <label class="form-label" style="font-size:0.72rem;">Nom</label>
                            <input type="text" name="label" class="form-control form-control-sm" placeholder="Support client" required>
                        </div>
                        <div class="col-3">
                            <label class="form-label" style="font-size:0.72rem;">Trunk</label>
                            <select name="trunk_id" class="form-control form-control-sm">
                                <option value="">—</option>
                                @foreach($trunks as $t)
                                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-2">
                            <button type="submit" class="btn btn-accent btn-sm w-100"><i class="bi bi-plus-lg"></i></button>
                        </div>
                    </div>
                </form>

                {{-- Table --}}
                <table class="table mb-0">
                    <thead>
                        <tr><th>Numero</th><th>Nom</th><th>Trunk</th><th style="width:90px;">Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($callerIds as $cid)
                        <tr style="{{ !$cid->is_active ? 'opacity:0.45;' : '' }}">
                            <td style="font-family:'JetBrains Mono',monospace;font-size:0.82rem;font-weight:600;">{{ $cid->number }}</td>
                            <td>
                                <div style="font-weight:600;font-size:0.82rem;">{{ $cid->label }}</div>
                                @if($cid->groups->count())
                                    <div class="d-flex gap-1 mt-1">
                                        @foreach($cid->groups as $g)
                                            <span style="font-size:0.6rem;background:var(--surface-2);border:1px solid var(--border);border-radius:4px;padding:0 4px;color:var(--text-secondary);">{{ $g->name }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td style="font-size:0.78rem;color:var(--text-secondary);">{{ $cid->trunk?->name ?? '—' }}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    <form action="{{ route('caller-ids.toggle', $cid) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn-icon" title="{{ $cid->is_active ? 'Desactiver' : 'Activer' }}" style="width:26px;height:26px;font-size:0.7rem;">
                                            <i class="bi bi-power"></i>
                                        </button>
                                    </form>
                                    <button class="btn-icon" title="Editer" style="width:26px;height:26px;font-size:0.7rem;"
                                        onclick="cidEdit({{ $cid->id }}, '{{ addslashes($cid->number) }}', '{{ addslashes($cid->label) }}', '{{ $cid->trunk_id }}')">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="{{ route('caller-ids.destroy', $cid) }}" method="POST" onsubmit="return confirm('Supprimer ?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-icon" title="Supprimer" style="width:26px;height:26px;font-size:0.7rem;color:#f85149;">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center py-3" style="color:var(--text-secondary);font-size:0.82rem;">
                            <i class="bi bi-telephone-plus me-1"></i>Aucun Caller ID — ajoutez un numero ci-dessus
                        </td></tr>
                        @endforelse
                    </tbody>
                </table>
                @if($callerIds->hasPages())
                    <div class="px-3 py-2">{{ $callerIds->links() }}</div>
                @endif
            </div>
        </div>

        {{-- ═══════════ Colonne droite : Groupes ═══════════ --}}
        <div class="col-lg-5">
            <div class="data-table mb-3">
                <div class="px-3 py-2 d-flex align-items-center justify-content-between" style="border-bottom:1px solid var(--border);">
                    <h6 class="mb-0" style="font-size:0.85rem;font-weight:700;">
                        <i class="bi bi-people-fill me-1" style="color:#bc6ff1;"></i> Groupes d'acces
                        <span class="badge" style="background:#bc6ff120;color:#bc6ff1;font-size:0.6rem;margin-left:0.3rem;">{{ $groups->count() }}</span>
                    </h6>
                    <button class="btn btn-sm" style="background:#bc6ff120;color:#bc6ff1;border:1px solid #bc6ff140;font-size:0.72rem;font-weight:600;" onclick="document.getElementById('newGroupForm').style.display = document.getElementById('newGroupForm').style.display === 'none' ? 'block' : 'none'">
                        <i class="bi bi-plus-lg me-1"></i>Nouveau
                    </button>
                </div>

                {{-- New group form (hidden by default) --}}
                <form id="newGroupForm" action="{{ route('caller-ids.groups.store') }}" method="POST" class="px-3 py-2" style="display:none;border-bottom:1px solid var(--border);background:rgba(188,111,241,0.03);">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label" style="font-size:0.72rem;">Nom du groupe</label>
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="Equipe commerciale" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label" style="font-size:0.72rem;">Description</label>
                        <input type="text" name="description" class="form-control form-control-sm" placeholder="Acces aux numeros commerciaux">
                    </div>
                    <div class="mb-2">
                        <label class="form-label" style="font-size:0.72rem;">Caller IDs du groupe</label>
                        <div style="max-height:120px;overflow-y:auto;border:1px solid var(--border);border-radius:6px;padding:0.3rem;">
                            @foreach(\App\Models\CallerId::where('is_active', true)->get() as $c)
                            <label class="d-flex align-items-center gap-2" style="font-size:0.78rem;padding:2px 4px;cursor:pointer;">
                                <input type="checkbox" name="caller_id_ids[]" value="{{ $c->id }}">
                                <span style="font-weight:600;">{{ $c->label }}</span>
                                @if($c->label !== $c->number)
                                    <span style="font-family:'JetBrains Mono',monospace;font-size:0.68rem;color:var(--text-secondary);">{{ $c->number }}</span>
                                @endif
                            </label>
                            @endforeach
                            @if(\App\Models\CallerId::where('is_active', true)->count() === 0)
                                <div style="font-size:0.75rem;color:var(--text-secondary);padding:4px;">Creez d'abord des Caller IDs</div>
                            @endif
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label" style="font-size:0.72rem;">Operateurs autorises</label>
                        <div style="max-height:120px;overflow-y:auto;border:1px solid var(--border);border-radius:6px;padding:0.3rem;">
                            @foreach($operators as $op)
                            <label class="d-flex align-items-center gap-2" style="font-size:0.78rem;padding:2px 4px;cursor:pointer;">
                                <input type="checkbox" name="user_ids[]" value="{{ $op->id }}">
                                <span style="font-weight:600;">{{ $op->name }}</span>
                                <span style="color:var(--text-secondary);font-size:0.7rem;">{{ $op->sipLine?->extension ?? '' }}</span>
                            </label>
                            @endforeach
                            @if($operators->isEmpty())
                                <div style="font-size:0.75rem;color:var(--text-secondary);padding:4px;">Aucun operateur</div>
                            @endif
                        </div>
                    </div>
                    <button type="submit" class="btn btn-sm w-100" style="background:#bc6ff1;color:#fff;border:none;font-weight:600;">
                        <i class="bi bi-check-lg me-1"></i>Creer le groupe
                    </button>
                </form>

                {{-- Groups list --}}
                @forelse($groups as $group)
                <div class="px-3 py-2" style="border-bottom:1px solid var(--border);">
                    <div class="d-flex align-items-start justify-content-between">
                        <div style="flex:1;min-width:0;">
                            <div class="d-flex align-items-center gap-2">
                                <span style="font-weight:700;font-size:0.85rem;">{{ $group->name }}</span>
                                @if($group->description)
                                    <span style="font-size:0.68rem;color:var(--text-secondary);">{{ $group->description }}</span>
                                @endif
                            </div>
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                @foreach($group->callerIds as $c)
                                    <span style="font-size:0.65rem;background:var(--accent-dim);color:var(--accent);border-radius:4px;padding:1px 6px;font-family:'JetBrains Mono',monospace;">{{ $c->number }}</span>
                                @endforeach
                                @if($group->callerIds->isEmpty())
                                    <span style="font-size:0.68rem;color:var(--text-secondary);font-style:italic;">aucun numero</span>
                                @endif
                            </div>
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                @foreach($group->users as $u)
                                    <span style="font-size:0.65rem;background:#bc6ff115;color:#bc6ff1;border-radius:4px;padding:1px 6px;">
                                        <i class="bi bi-person-fill" style="font-size:0.55rem;"></i> {{ $u->name }}
                                    </span>
                                @endforeach
                                @if($group->users->isEmpty())
                                    <span style="font-size:0.68rem;color:var(--text-secondary);font-style:italic;">aucun operateur</span>
                                @endif
                            </div>
                        </div>
                        <div class="d-flex gap-1 ms-2">
                            <button class="btn-icon" title="Editer" style="width:26px;height:26px;font-size:0.7rem;"
                                onclick="cidGroupEdit({{ $group->id }}, {{ json_encode(['name' => $group->name, 'description' => $group->description, 'caller_ids' => $group->callerIds->pluck('id'), 'users' => $group->users->pluck('id')]) }})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form action="{{ route('caller-ids.groups.destroy', $group) }}" method="POST" onsubmit="return confirm('Supprimer le groupe {{ $group->name }} ?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon" title="Supprimer" style="width:26px;height:26px;font-size:0.7rem;color:#f85149;">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                @empty
                <div class="px-3 py-4 text-center" style="color:var(--text-secondary);font-size:0.82rem;">
                    <i class="bi bi-collection me-1"></i>Aucun groupe — cliquez "Nouveau" pour en creer
                </div>
                @endforelse
            </div>

            {{-- Info box --}}
            <div class="stat-card" style="padding:1rem;">
                <h6 style="font-size:0.82rem;font-weight:700;margin-bottom:0.5rem;"><i class="bi bi-lightbulb me-1" style="color:#d29922;"></i>Comment ca marche</h6>
                <ul style="font-size:0.78rem;color:var(--text-secondary);margin:0;padding-left:1.2rem;line-height:1.6;">
                    <li>Creez des <b>Caller IDs</b> avec le numero et le trunk associe</li>
                    <li>Organisez-les dans des <b>groupes</b> et assignez des operateurs</li>
                    <li>Chaque operateur verra uniquement les numeros de ses groupes dans le <b>softphone</b></li>
                    <li>Par defaut, le Caller ID de la ligne SIP est utilise</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Edit modal (Caller ID) --}}
    <div id="cidEditModal" style="display:none;position:fixed;inset:0;z-index:1050;background:rgba(0,0,0,.6);align-items:center;justify-content:center;" onclick="if(event.target===this)this.style.display='none'">
        <div style="padding:1.5rem;width:400px;max-width:90vw;background:#1c1f26;border:1px solid var(--border);border-radius:12px;box-shadow:0 20px 60px rgba(0,0,0,.5);">
            <h6 style="font-weight:700;margin-bottom:1rem;"><i class="bi bi-pencil me-2" style="color:var(--accent);"></i>Modifier Caller ID</h6>
            <form id="cidEditForm" method="POST">
                @csrf @method('PUT')
                <div class="mb-2">
                    <label class="form-label" style="font-size:0.72rem;">Numero</label>
                    <input type="text" name="number" id="cidEditNumber" class="form-control form-control-sm" required style="font-family:'JetBrains Mono',monospace;">
                </div>
                <div class="mb-2">
                    <label class="form-label" style="font-size:0.72rem;">Nom</label>
                    <input type="text" name="label" id="cidEditLabel" class="form-control form-control-sm" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" style="font-size:0.72rem;">Trunk</label>
                    <select name="trunk_id" id="cidEditTrunk" class="form-control form-control-sm">
                        <option value="">—</option>
                        @foreach($trunks as $t)
                            <option value="{{ $t->id }}">{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="d-flex gap-2 justify-content-end">
                    <button type="button" class="btn btn-sm" onclick="document.getElementById('cidEditModal').style.display='none'" style="background:var(--surface-2);color:var(--text-primary);border:1px solid var(--border);">Annuler</button>
                    <button type="submit" class="btn btn-accent btn-sm">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit modal (Group) --}}
    <div id="grpEditModal" style="display:none;position:fixed;inset:0;z-index:1050;background:rgba(0,0,0,.6);align-items:center;justify-content:center;" onclick="if(event.target===this)this.style.display='none'">
        <div style="padding:1.5rem;width:440px;max-width:90vw;background:#1c1f26;border:1px solid var(--border);border-radius:12px;box-shadow:0 20px 60px rgba(0,0,0,.5);">
            <h6 style="font-weight:700;margin-bottom:1rem;"><i class="bi bi-pencil me-2" style="color:#bc6ff1;"></i>Modifier le groupe</h6>
            <form id="grpEditForm" method="POST">
                @csrf @method('PUT')
                <div class="mb-2">
                    <label class="form-label" style="font-size:0.72rem;">Nom</label>
                    <input type="text" name="name" id="grpEditName" class="form-control form-control-sm" required>
                </div>
                <div class="mb-2">
                    <label class="form-label" style="font-size:0.72rem;">Description</label>
                    <input type="text" name="description" id="grpEditDesc" class="form-control form-control-sm">
                </div>
                <div class="mb-2">
                    <label class="form-label" style="font-size:0.72rem;">Caller IDs</label>
                    <div id="grpEditCids" style="max-height:120px;overflow-y:auto;border:1px solid var(--border);border-radius:6px;padding:0.3rem;">
                        @foreach(\App\Models\CallerId::where('is_active', true)->get() as $c)
                        <label class="d-flex align-items-center gap-2" style="font-size:0.78rem;padding:2px 4px;cursor:pointer;">
                            <input type="checkbox" name="caller_id_ids[]" value="{{ $c->id }}" class="grp-cid-chk">
                            <span style="font-weight:600;">{{ $c->label }}</span>
                            @if($c->label !== $c->number)
                                <span style="font-family:'JetBrains Mono',monospace;font-size:0.68rem;color:var(--text-secondary);">{{ $c->number }}</span>
                            @endif
                        </label>
                        @endforeach
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" style="font-size:0.72rem;">Operateurs</label>
                    <div id="grpEditUsers" style="max-height:120px;overflow-y:auto;border:1px solid var(--border);border-radius:6px;padding:0.3rem;">
                        @foreach($operators as $op)
                        <label class="d-flex align-items-center gap-2" style="font-size:0.78rem;padding:2px 4px;cursor:pointer;">
                            <input type="checkbox" name="user_ids[]" value="{{ $op->id }}" class="grp-user-chk">
                            <span style="font-weight:600;">{{ $op->name }}</span>
                            <span style="color:var(--text-secondary);font-size:0.7rem;">{{ $op->sipLine?->extension ?? '' }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                <div class="d-flex gap-2 justify-content-end">
                    <button type="button" class="btn btn-sm" onclick="document.getElementById('grpEditModal').style.display='none'" style="background:var(--surface-2);color:var(--text-primary);border:1px solid var(--border);">Annuler</button>
                    <button type="submit" class="btn btn-sm" style="background:#bc6ff1;color:#fff;border:none;font-weight:600;">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function cidEdit(id, number, label, trunkId) {
        document.getElementById('cidEditForm').action = '/caller-ids/' + id;
        document.getElementById('cidEditNumber').value = number;
        document.getElementById('cidEditLabel').value = label;
        document.getElementById('cidEditTrunk').value = trunkId || '';
        document.getElementById('cidEditModal').style.display = 'flex';
    }

    function cidGroupEdit(id, data) {
        document.getElementById('grpEditForm').action = '/caller-ids/groups/' + id;
        document.getElementById('grpEditName').value = data.name;
        document.getElementById('grpEditDesc').value = data.description || '';
        document.querySelectorAll('.grp-cid-chk').forEach(function(cb) {
            cb.checked = data.caller_ids.includes(parseInt(cb.value));
        });
        document.querySelectorAll('.grp-user-chk').forEach(function(cb) {
            cb.checked = data.users.includes(parseInt(cb.value));
        });
        document.getElementById('grpEditModal').style.display = 'flex';
    }
    </script>
    <style>
    #cidEditModal .form-control, #cidEditModal .form-control-sm,
    #grpEditModal .form-control, #grpEditModal .form-control-sm {
        background: #262a33 !important;
        color: #e2e4eb !important;
        border-color: #383c47 !important;
    }
    #cidEditModal .form-label, #grpEditModal .form-label {
        color: var(--text-secondary) !important;
    }
    #grpEditModal div[style*="overflow-y"], #grpEditModal div[style*="border:1px"] > label,
    #cidEditModal div[style*="overflow-y"], #cidEditModal div[style*="border:1px"] > label {
        color: #e2e4eb;
    }
    #grpEditModal div[style*="overflow-y:auto"], #newGroupForm div[style*="overflow-y:auto"] {
        background: #262a33;
        border-color: #383c47 !important;
    }
    </style>
@endsection
