@php
  $planningAll = $planningAll ?? ($planning_all ?? []);
  $currentPageGlobal = $currentPageGlobal ?? ($current_page_global ?? 1);
  $recordsPerPageGlobal = $recordsPerPageGlobal ?? ($records_per_page_global ?? 10);
  $totalPagesGlobal = $totalPagesGlobal ?? ($total_pages_global ?? 1);
@endphp
<div class="table-responsive-content">
  <table class="table table-striped" id="tableGlobal" role="table">
    <thead><tr><th>Code</th><th>Client</th><th>Collaborateur</th><th>Role</th><th>Debut</th><th>Fin</th></tr></thead>
    <tbody>
    @foreach($planningAll as $row)
      <tr>
        <td>{{ $row['code'] }}</td>
        <td>{{ $row['dossier_nom'] }}</td>
        <td>{{ $row['collaborateur'] }}</td>
        <td>{{ $row['role'] }}</td>
        <td>{{ $row['date_debut'] }}</td>
        <td>{{ $row['date_fin'] }}</td>
      </tr>
    @endforeach
    </tbody>
  </table>
  <nav aria-label="Pagination du planning global" id="pagination-global">
    <ul class="pagination justify-content-center">
      <li class="page-item {{ $currentPageGlobal <= 1 ? 'disabled' : '' }}">
        <a class="page-link" href="#" data-page="{{ $currentPageGlobal - 1 }}" data-rows="{{ $recordsPerPageGlobal }}" data-tab="global" aria-label="Précédent">&laquo;</a>
      </li>

      @php
        $numLinks = 5;
        $startPage = max(1, $currentPageGlobal - (int) floor($numLinks / 2));
        $endPage = min($totalPagesGlobal, $currentPageGlobal + (int) floor($numLinks / 2));

        if ($endPage - $startPage + 1 < $numLinks) {
            if ($startPage > 1) {
                $startPage = max(1, $totalPagesGlobal - $numLinks + 1);
            }
            if ($endPage < $totalPagesGlobal) {
                $endPage = min($totalPagesGlobal, $numLinks);
            }
        }
      @endphp

      @if($startPage > 1)
        <li class="page-item"><a class="page-link" href="#" data-page="1" data-rows="{{ $recordsPerPageGlobal }}" data-tab="global">1</a></li>
        @if($startPage > 2)
          <li class="page-item disabled"><span class="page-link">...</span></li>
        @endif
      @endif

      @for($i = $startPage; $i <= $endPage; $i++)
        <li class="page-item {{ $i === $currentPageGlobal ? 'active' : '' }}">
          <a class="page-link" href="#" data-page="{{ $i }}" data-rows="{{ $recordsPerPageGlobal }}" data-tab="global">{{ $i }}</a>
        </li>
      @endfor

      @if($endPage < $totalPagesGlobal)
        @if($endPage < $totalPagesGlobal - 1)
          <li class="page-item disabled"><span class="page-link">...</span></li>
        @endif
        <li class="page-item"><a class="page-link" href="#" data-page="{{ $totalPagesGlobal }}" data-rows="{{ $recordsPerPageGlobal }}" data-tab="global">{{ $totalPagesGlobal }}</a></li>
      @endif

      <li class="page-item {{ $currentPageGlobal >= $totalPagesGlobal ? 'disabled' : '' }}">
        <a class="page-link" href="#" data-page="{{ $currentPageGlobal + 1 }}" data-rows="{{ $recordsPerPageGlobal }}" data-tab="global" aria-label="Suivant">&raquo;</a>
      </li>
    </ul>
  </nav>

  <div class="d-flex justify-content-center mt-2">
    <label for="rows_global_select" class="me-2">Lignes par page :</label>
    <select id="rows_global_select" class="form-select form-select-sm w-auto" onchange="loadTabContent('global', 1, this.value)">
      @foreach([10, 20, 30, 50, 100] as $num)
        <option value="{{ $num }}" {{ (int) $recordsPerPageGlobal === $num ? 'selected' : '' }}>{{ $num }}</option>
      @endforeach
    </select>
  </div>
</div>
