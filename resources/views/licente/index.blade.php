@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1>Licență locală</h1>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($error)
        <div class="alert alert-warning">{{ $error }}</div>
    @endif

    @if(!$license)
        <p>Nu există licență instalată pe această aplicație.</p>
    @else
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title">Cheie: {{ $license['license_key'] ?? '—' }}</h5>
                <p class="card-text">Domeniu: {{ $license['domain'] ?? '—' }}</p>
                <p class="card-text">Informații suplimentare:</p>
                <pre class="small bg-light p-2">{{ json_encode($license['data'] ?? [], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        </div>

        <form method="POST" action="{{ route('license-client.licente.verify') }}" class="d-inline">
            @csrf
            <button class="btn btn-primary">Verifică licența</button>
        </form>

        <form method="POST" action="{{ route('license-client.licente.destroy') }}" class="d-inline ms-2">
            @csrf
            @method('DELETE')
            <button class="btn btn-danger" onclick="return confirm('Șterge fișierul local de licență?')">Șterge licența</button>
        </form>
    @endif
    <hr />

    <h3>Instalează manual o licență</h3>
    <form method="POST" action="{{ route('license-client.licente.upload') }}" class="mb-3">
        @csrf
        <div class="mb-2">
            <label class="form-label">Cheie de licență (paste)</label>
            <input name="license_key" class="form-control" placeholder="Introduceți cheie de licență">
        </div>
        <button type="submit" class="btn btn-primary">Salvează și verifică</button>
    </form>
</div>
@endsection
