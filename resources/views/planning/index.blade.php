@extends('layouts.planning')

@section('title', 'MG PLANNER')

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets/css/planning.css') }}">
@endpush

@php
  if (!function_exists('formatDateLocale')) {
      function formatDateLocale($date) {
          if (empty($date)) {
              return '';
          }
          $timestamp = strtotime((string) $date);
          return $timestamp ? date('d/m/Y', $timestamp) : (string) $date;
      }
  }

  if (!function_exists('extractClientName')) {
      function extractClientName($nom) {
          if (empty($nom)) {
              return '';
          }
          $parts = preg_split('/[-|]/', (string) $nom);
          return trim((string) ($parts[0] ?? $nom));
      }
  }
@endphp

@section('body')
  <header style="display: flex; align-items: center; justify-content: space-between; padding: 10px 20px; background-color: #003366; color: white;">
    <div style="display: flex; align-items: center; gap: 15px;">
      <img src="{{ asset('assets/images/logo-MG.png') }}" alt="Logo MG EXPERTISE" style="height: 50px; width: auto;" />
      <h1 style="margin: 0; font-size: 1.8rem; font-weight: 700;">MG PLANNER</h1>
    </div>
    <div class="user-info">
      <img src="https://ui-avatars.com/api/?name={{ urlencode($user_nom) }}&background=007BFF&color=fff&rounded=true" alt="Avatar {{ htmlspecialchars($user_nom) }}" />
      <div class="user-details">
        <h5>{{ $user_nom }}</h5>
        <small>{{ $user_role }}</small>
      </div>
      <a href="{{ route('logout.get') }}" class="logout-btn">Déconnexion</a>
    </div>
  </header>

  <main class="container">
    <section class="quick-info" aria-label="Informations rapides">
      <h4>Informations rapides au {{ formatDateLocale($today) }}</h4>
      @if($current_dossier)
        <p><strong>Dossier en cours :</strong> {{ $current_dossier['code'] }} - {{ extractClientName($current_dossier['dossier_nom']) }} ({{ formatDateLocale($current_dossier['date_debut']) }} -> {{ formatDateLocale($current_dossier['date_fin']) }})</p>
      @else
        <p><em>Aucun dossier en cours.</em></p>
      @endif
      @if($next_dossier)
        <p><strong>Prochain dossier :</strong> {{ $next_dossier['code'] }} - {{ extractClientName($next_dossier['dossier_nom']) }} (Début le {{ formatDateLocale($next_dossier['date_debut']) }})</p>
      @else
        <p><em>Aucun prochain dossier planifié.</em></p>
      @endif
      <p><strong>Dossiers en retard non validés :</strong> {{ $retard_count }}</p>
    </section>

    <ul class="nav nav-tabs" id="myTab" role="tablist" aria-label="Onglets de navigation">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="global-tab" data-bs-toggle="tab" data-bs-target="#view-global" type="button" role="tab" aria-controls="view-global" aria-selected="true">Planning global</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="perso-tab" data-bs-toggle="tab" data-bs-target="#view-perso" type="button" role="tab" aria-controls="view-perso" aria-selected="false">Mon planning</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="dossiers-tab" data-bs-toggle="tab" data-bs-target="#view-dossiers" type="button" role="tab" aria-controls="view-dossiers" aria-selected="false">Mes dossiers</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="calendrier-tab" data-bs-toggle="tab" data-bs-target="#view-calendrier" type="button" role="tab" aria-controls="view-calendrier" aria-selected="false">Calendrier</button>
      </li>
      @if(in_array(strtolower($user_role), ['chef', 'associe']))
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="echeances-tab" data-bs-toggle="tab" data-bs-target="#view-echeances" type="button" role="tab" aria-controls="view-echeances" aria-selected="false">&Eacute;ch&eacute;ances</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="repartition-tab" data-bs-toggle="tab" data-bs-target="#view-repartition" type="button" role="tab" aria-controls="view-repartition" aria-selected="false">R&eacute;partition</button>
        </li>
      @endif
    </ul>

    <div class="tab-content" id="myTabContent">
      <div class="tab-pane fade show active" id="view-global" role="tabpanel" aria-labelledby="global-tab" tabindex="0">
        <div class="search-wrapper">
          <svg viewBox="0 0 24 24"><path d="M21.71 20.29l-3.388-3.388A7.936 7.936 0 0 0 18 10a8 8 0 1 0-8 8 7.936 7.936 0 0 0 6.902-3.678l3.388 3.388a1 1 0 0 0 1.414-1.414zM4 10a6 6 0 1 1 6 6 6.007 6.007 0 0 1-6-6z"/></svg>
          <input type="text" id="searchGlobal" class="form-control search-input" placeholder="Rechercher dans planning global..." aria-label="Rechercher dans planning global" />
        </div>
        <div class="table-responsive" id="table-responsive-global">
          @include('planning.partials.global')
        </div>
      </div>

      <div class="tab-pane fade" id="view-perso" role="tabpanel" aria-labelledby="perso-tab" tabindex="0">
        <div class="search-wrapper">
          <svg viewBox="0 0 24 24"><path d="M21.71 20.29l-3.388-3.388A7.936 7.936 0 0 0 18 10a8 8 0 1 0-8 8 7.936 7.936 0 0 0 6.902-3.678l3.388 3.388a1 1 0 0 0 1.414-1.414zM4 10a6 6 0 1 1 6 6 6.007 6.007 0 0 1-6-6z"/></svg>
          <input type="text" id="searchPerso" class="form-control search-input" placeholder="Rechercher dans mon planning..." aria-label="Rechercher dans mon planning" />
        </div>
        <div class="table-responsive" id="table-responsive-perso">
          @include('planning.partials.perso')
        </div>
      </div>

      @include('planning.partials.dossiers')
      @include('planning.partials.calendrier')

      @if(in_array(strtolower($user_role), ['chef', 'associe']))
        <div class="tab-pane fade" id="view-echeances" role="tabpanel" aria-labelledby="echeances-tab" tabindex="0">
          <div class="table-responsive mt-3" id="echeances-content">
            @include('planning.partials.echeances')
          </div>
        </div>

        @include('planning.partials.repartition')
      @endif
    </div>
  </main>

  @include('planning.partials.modals')
@endsection

@push('scripts')
  <script>
    window.PlanningConfig = @json([
      'today' => $today,
      'initialEvents' => $events_json ?? [],
    ]);
  </script>
  <script src="{{ asset('assets/js/planning.js') }}"></script>
@endpush
