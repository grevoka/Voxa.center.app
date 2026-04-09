@extends('layouts.operator')

@section('title', 'Aucun poste')
@section('page-title', 'Mon espace')

@section('content')
    <div class="text-center py-5">
        <i class="bi bi-telephone-x" style="font-size:3rem;color:var(--text-secondary);"></i>
        <h5 class="mt-3" style="font-weight:700;">Aucun poste attribue</h5>
        <p style="color:var(--text-secondary);font-size:0.85rem;">Votre compte n'est associe a aucune ligne SIP.<br>Contactez votre administrateur.</p>
    </div>
@endsection
