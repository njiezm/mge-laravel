@php
  $mesDossiers = $mesDossiers ?? ($mes_dossiers ?? []);
  $pdo = $pdo ?? null;
@endphp

<div class="tab-pane fade" id="view-dossiers" role="tabpanel" aria-labelledby="dossiers-tab" tabindex="0">
  <div class="search-wrapper">
    <svg viewBox="0 0 24 24"><path d="M21.71 20.29l-3.388-3.388A7.936 7.936 0 0 0 18 10a8 8 0 1 0-8 8 7.936 7.936 0 0 0 6.902-3.678l3.388 3.388a1 1 0 0 0 1.414-1.414zM4 10a6 6 0 1 1 6 6 6.007 6.007 0 0 1-6-6z"/></svg>
    <input type="text" id="searchDossiers" class="form-control search-input" placeholder="Rechercher dans mes dossiers..." aria-label="Rechercher dans mes dossiers" />
  </div>

  <div class="row row-cols-1 row-cols-md-2 g-4 mt-2" id="dossiersContainer">
    @foreach($mesDossiers as $row)
      @php
        $encadrants = [];
        if ($pdo) {
          $stmtEnc = $pdo->prepare("SELECT u.nom, u.role FROM affectation a JOIN utilisateurs u ON u.id = a.utilisateur_id WHERE a.dossier_id = ?");
          $stmtEnc->execute([$row['id']]);
          $encadrants = $stmtEnc->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
        $chefs = array_filter($encadrants, fn($u) => strtolower(trim((string)($u['role'] ?? ''))) === 'chef');
        $collabs = array_filter($encadrants, fn($u) => strtolower(trim((string)($u['role'] ?? ''))) === 'collaborateur');
      @endphp
      <div class="col">
        <div class="card card-dossier shadow-sm dossier-clickable" tabindex="0" role="article" aria-label="Dossier {{ $row['code'] }}" data-dossier-id="{{ $row['id'] }}">
          <div class="card-body">
            <h5 class="card-title">{{ $row['code'] }}</h5>
            <p><strong>Client :</strong> {{ extractClientName($row['nom']) }}</p>
            <p><strong>Début :</strong> {{ formatDateLocale($row['date_debut']) }}</p>
            <p><strong>Fin :</strong> {{ formatDateLocale($row['date_fin']) }}</p>
            <p><strong>Statut :</strong> {{ $row['statut'] }}</p>
            <p><strong>Chefs :</strong> {{ implode(', ', array_column($chefs, 'nom')) ?: 'Aucun' }}</p>
            <p><strong>Collaborateurs :</strong> {{ implode(', ', array_column($collabs, 'nom')) ?: 'Aucun' }}</p>
          </div>
        </div>
      </div>
    @endforeach
  </div>
</div>
