@extends('layouts.admin')

@section('title', 'Admin - MG EXPERTISE')

@push('styles')
<style>
  body { font-family:'Montserrat',sans-serif; background:#f7f9fc; padding-top:88px; color:#003366; }
  header { position:fixed; top:0; left:0; right:0; height:72px; background:#003366; color:#fff; display:flex; align-items:center; justify-content:space-between; padding:0 20px; z-index:1000; }
  .card-kpi { border:0; border-left:4px solid #007BFF; box-shadow:0 3px 10px rgba(0,0,0,.06); }
  .dropzone { border:2px dashed #007BFF; border-radius:10px; padding:36px; text-align:center; background:#edf5ff; cursor:pointer; }
  .toast-container-fixed { position:fixed; top:88px; right:16px; z-index:2000; width:min(420px, calc(100vw - 24px)); }
  .import-warning { display:none; }
</style>
@endpush

@section('body')
<div id="toastContainer" class="toast-container-fixed"></div>
<header>
  <div style="display:flex;align-items:center;gap:12px;">
    <img src="{{ asset('assets/images/logo-MG.png') }}" alt="Logo" style="height:48px;width:auto;" />
    <strong>MG PLANNER - Administration</strong>
  </div>
  <div>Bienvenue, <strong>{{ $user_nom }}</strong> | <a href="{{ route('logout.get') }}" class="text-warning text-decoration-none">Déconnexion</a></div>
</header>

<main class="container">
  <ul class="nav nav-tabs" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#create-account">Création de compte</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#manage-account">Gestion comptes</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#stats">Statistiques & KPI</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#data">Données (import)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#export">Export</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#audit">Audit</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#search">Recherche globale</button></li>
  </ul>

  <div class="tab-content mt-3">
    @include('admin.partials.create-account')
    @include('admin.partials.manage-account')
    @include('admin.partials.stats')
    @include('admin.partials.import')
    @include('admin.partials.export')
    @include('admin.partials.audit')
    @include('admin.partials.search')
  </div>
</main>

@include('admin.partials.modals')

<form id="exportBackupForm" action="{{ url('admin_export.php') }}" method="POST" target="_blank" style="display:none;">
  <input type="hidden" name="export_type" value="dossiers">
  <input type="hidden" name="export_format" value="xlsx">
</form>

<script>
    window.AdminDashboardConfig = {!! json_encode([
        'csrfToken' => csrf_token(),
        'routes' => [
            'usersFragment' => url('admin_users_fragment.php'),
            'importDossiers' => url('import_dossiers.php'),
            'createMissingUsers' => url('create_missing_users.php'),
            'activeUsers' => url('get_utilisateurs_actifs.php'),
            'export' => url('admin_export.php'),
        ],
        'fiscalYear' => now()->year,
        'nextFiscalYear' => now()->year + 1
    ]) !!};
</script>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/admin-dashboard.js') }}"></script>
@endpush
