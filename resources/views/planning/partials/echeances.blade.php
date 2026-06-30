@php
  $dossiersEcheances = $dossiersEcheances ?? ($dossiers_echeances ?? []);
  $statutsOptions = $statutsOptions ?? ($statuts_options ?? []);
  $filterStatut = $filterStatut ?? '';
  $filterUserEcheancesCheckbox = $filterUserEcheancesCheckbox ?? false;
  $currentPageEcheances = $currentPageEcheances ?? ($current_page_echeances ?? 1);
  $recordsPerPageEcheances = $recordsPerPageEcheances ?? ($records_per_page_echeances ?? 10);
  $totalPagesEcheances = $totalPagesEcheances ?? ($total_pages_echeances ?? 1);
@endphp
<form method="GET" id="formFilterEcheances" class="mb-3">
  <label for="filterStatut" class="form-label">Filtrer par statut :</label>
  <select id="filterStatut" name="filterStatut" class="form-select">
    <option value="">Tous les statuts</option>
    @foreach($statutsOptions as $key => $label)
      <option value="{{ $key }}" {{ ($filterStatut ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
    @endforeach
  </select>

  <div class="form-check mt-3">
    <input class="form-check-input" type="checkbox" id="filterUserEcheancesCheckbox" name="filterUserEcheancesCheckbox" {{ ($filterUserEcheancesCheckbox ?? false) ? 'checked' : '' }}>
    <label class="form-check-label" for="filterUserEcheancesCheckbox">Afficher seulement mes dossiers</label>
  </div>
</form>

<div class="table-responsive-content">
  <table class="table table-bordered table-hover align-middle text-center">
    <thead>
      <tr>
        <th>Code</th><th>Client</th><th>Prevues (h)</th><th>Reelles (h)</th><th>Ratio (%)</th>
        @foreach($statutsOptions as $label)
          <th>{{ $label }}</th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      @php $statutKeys = array_keys($statutsOptions); @endphp
      @foreach($dossiersEcheances as $d)
        @php
          $currentIndex = array_search($d['statut'], $statutKeys, true);
          $heurePrevues = (float) ($d['heure_prevues'] ?? 0);
        @endphp
        <tr data-dossier-id="{{ $d['id'] }}" data-code-dossier="{{ $d['code_dossier'] }}" data-heure-prevues="{{ $heurePrevues }}">
          <td>{{ $d['code_dossier'] }}</td>
          <td>{{ $d['nom_client'] }}</td>
          <td>{{ number_format($heurePrevues, 2) }}</td>
          <td class="heure-reelles-cell">Chargement...</td>
          <td class="ratio-cell">Chargement...</td>
          @foreach($statutKeys as $i => $cle)
            <td>
              @if($currentIndex !== false && $i <= $currentIndex)
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-check-circle-fill text-success" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM6.97 10.03a.75.75 0 0 0 1.07 0l3.992-3.992a.75.75 0 0 0-1.06-1.06L7.5 8.439 6.03 6.97a.75.75 0 0 0-1.06 1.06l1.999 2z"/></svg>
              @else
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-x-circle-fill text-danger" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1 0-.708z"/></svg>
              @endif
            </td>
          @endforeach
        </tr>
      @endforeach
    </tbody>
  </table>
</div>

<nav aria-label="Pagination des echeances" id="pagination-echeances">
  <ul class="pagination justify-content-center">
    <li class="page-item {{ ($currentPageEcheances <= 1) ? 'disabled' : '' }}">
      <a class="page-link" href="#" data-page="{{ $currentPageEcheances - 1 }}" data-rows="{{ $recordsPerPageEcheances }}" data-tab="echeances" aria-label="Precedent"><span aria-hidden="true">&laquo;</span></a>
    </li>
    @php
      $numLinks = 5;
      $start = max(1, $currentPageEcheances - floor($numLinks / 2));
      $end = min($totalPagesEcheances, $currentPageEcheances + floor($numLinks / 2));
    @endphp
    @for($i = $start; $i <= $end; $i++)
      <li class="page-item {{ ($i == $currentPageEcheances) ? 'active' : '' }}"><a class="page-link" href="#" data-page="{{ $i }}" data-rows="{{ $recordsPerPageEcheances }}" data-tab="echeances">{{ $i }}</a></li>
    @endfor
    <li class="page-item {{ ($currentPageEcheances >= $totalPagesEcheances) ? 'disabled' : '' }}">
      <a class="page-link" href="#" data-page="{{ $currentPageEcheances + 1 }}" data-rows="{{ $recordsPerPageEcheances }}" data-tab="echeances" aria-label="Suivant"><span aria-hidden="true">&raquo;</span></a>
    </li>
  </ul>
</nav>

<div class="d-flex justify-content-center mt-2">
  <label for="rows_echeances_select" class="me-2">Lignes par page :</label>
  <select id="rows_echeances_select" class="form-select form-select-sm w-auto" onchange="loadTabContent('echeances', 1, this.value, document.getElementById('filterStatut').value, document.getElementById('filterUserEcheancesCheckbox').checked)">
    @foreach([10, 20, 30, 50, 100] as $num)
      <option value="{{ $num }}" {{ ($recordsPerPageEcheances == $num) ? 'selected' : '' }}>{{ $num }}</option>
    @endforeach
  </select>
</div>
