<section class="tab-pane fade p-4 bg-white rounded shadow-sm" id="stats">
  <h2 class="h5 mb-3">KPI et charge</h2>
  <div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card card-kpi p-3"><small>Utilisateurs</small><div class="display-6">{{ $total_users }}</div></div></div>
    <div class="col-md-3"><div class="card card-kpi p-3"><small>Dossiers</small><div class="display-6">{{ $dossiers_annee }}</div></div></div>
    <div class="col-md-3"><div class="card card-kpi p-3"><small>En retard</small><div class="display-6 text-danger">{{ $kpi_retards }}</div></div></div>
    <div class="col-md-3"><div class="card card-kpi p-3"><small>Bloqués</small><div class="display-6 text-warning">{{ $kpi_bloques }}</div></div></div>
  </div>
  <h3 class="h6">Charge par collaborateur</h3>
  <div class="table-responsive">
    <table class="table table-sm table-striped"><thead><tr><th>Collaborateur</th><th>Nb dossiers</th><th>Heures prévues</th></tr></thead><tbody>
      @foreach($kpi_charge as $c)
        <tr><td>{{ $c['collaborateur'] }}</td><td>{{ $c['nb_dossiers'] }}</td><td>{{ number_format((float)$c['heures_prevues'], 2, ',', ' ') }}</td></tr>
      @endforeach
    </tbody></table>
  </div>
</section>
