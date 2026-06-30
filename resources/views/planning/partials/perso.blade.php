@php
  $planningUser = $planningUser ?? ($planning_user ?? []);
  $statutsOptions = $statutsOptions ?? ($statuts_options ?? []);
  $currentPagePerso = $currentPagePerso ?? ($current_page_perso ?? 1);
  $recordsPerPagePerso = $recordsPerPagePerso ?? ($records_per_page_perso ?? 10);
  $totalPagesPerso = $totalPagesPerso ?? ($total_pages_perso ?? 1);
@endphp
<div class="table-responsive-content">
  <table class="table table-striped" id="tablePerso" role="table">
    <thead>
      <tr><th>Code</th><th>Client</th><th>Debut</th><th>Fin</th><th>Commentaires</th><th>Actions</th></tr>
    </thead>
    <tbody>
    @foreach($planningUser as $row)
      @php
        $status = $row['statut'] ?? 'non_traite';
      @endphp
      <tr data-temps-id="{{ $row['temps_id'] }}" data-dossier-id="{{ $row['dossier_id'] }}" class="{{ $status === 'declarer_en_retard' ? 'table-danger' : ($status === 'liasse_envoyee' ? 'table-success' : '') }}">
        <td>{{ $row['code'] }}</td>
        <td>{{ $row['dossier_nom'] }}</td>
        <td>{{ $row['date_debut'] }}</td>
        <td>{{ $row['date_fin'] }}</td>
        <td><textarea class="form-control" data-id="{{ $row['temps_id'] }}">{{ $row['commentaires'] }}</textarea></td>
        <td>
          <select class="form-select form-select-sm mb-1" id="select-statut-{{ $row['dossier_id'] }}">
            @php
              $keys = array_keys($statutsOptions);
              $idxCurrent = array_search($status, $keys, true);
            @endphp
            @foreach($statutsOptions as $key => $label)
              @php
                $idx = array_search($key, $keys, true);
                $disabled = $idxCurrent !== false ? ($idx > $idxCurrent + 1) : false;
              @endphp
              <option value="{{ $key }}" {{ $status === $key ? 'selected' : '' }} {{ $disabled ? 'disabled' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
          <button type="button" class="btn btn-sm btn-primary" onclick="validerStatut({{ $row['dossier_id'] }})">Valider statut</button>
          @if($status === 'declarer_en_retard')
            <button type="button" class="btn btn-sm btn-warning" onclick="sendAction({{ $row['temps_id'] }}, 'toggleRetard')" title="Relancer">Relancer</button>
          @else
            <button type="button" class="btn btn-sm btn-outline-warning" onclick="marquerRetard({{ $row['dossier_id'] }})" title="Marquer retard">Marquer retard</button>
          @endif
        </td>
      </tr>
    @endforeach
    </tbody>
  </table>

  <nav aria-label="Pagination du planning perso" id="pagination-perso">
    <ul class="pagination justify-content-center">
      <li class="page-item {{ ($currentPagePerso <= 1) ? 'disabled' : '' }}">
        <a class="page-link" href="#" data-page="{{ $currentPagePerso - 1 }}" data-rows="{{ $recordsPerPagePerso }}" data-tab="perso" aria-label="Precedent"><span aria-hidden="true">&laquo;</span></a>
      </li>
      @php
        $numLinks = 5;
        $start = max(1, $currentPagePerso - floor($numLinks / 2));
        $end = min($totalPagesPerso, $currentPagePerso + floor($numLinks / 2));
      @endphp
      @for($i = $start; $i <= $end; $i++)
        <li class="page-item {{ ($i == $currentPagePerso) ? 'active' : '' }}"><a class="page-link" href="#" data-page="{{ $i }}" data-rows="{{ $recordsPerPagePerso }}" data-tab="perso">{{ $i }}</a></li>
      @endfor
      <li class="page-item {{ ($currentPagePerso >= $totalPagesPerso) ? 'disabled' : '' }}">
        <a class="page-link" href="#" data-page="{{ $currentPagePerso + 1 }}" data-rows="{{ $recordsPerPagePerso }}" data-tab="perso" aria-label="Suivant"><span aria-hidden="true">&raquo;</span></a>
      </li>
    </ul>
  </nav>

  <div class="d-flex justify-content-center mt-2">
    <label for="rows_perso_select" class="me-2">Lignes par page :</label>
    <select id="rows_perso_select" class="form-select form-select-sm w-auto" onchange="loadTabContent('perso', 1, this.value)">
      @foreach([10, 20, 30, 50, 100] as $num)
        <option value="{{ $num }}" {{ ($recordsPerPagePerso == $num) ? 'selected' : '' }}>{{ $num }}</option>
      @endforeach
    </select>
  </div>
</div>
