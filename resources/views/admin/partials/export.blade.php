<section class="tab-pane fade p-4 bg-white rounded shadow-sm" id="export">
  <h2 class="h5 mb-4">Export des données</h2>
  <div class="row g-4">
    <div class="col-md-6">
      <form id="exportForm" action="{{ url('admin_export.php') }}" method="POST" target="_blank">
        <div class="mb-3"><label class="form-label">Type d'export</label>
          <select id="export_type" name="export_type" class="form-select" required>
            <option value="users">Utilisateurs</option>
            <option value="planning_global">Planning global</option>
            <option value="planning_par_personne">Planning par personne</option>
            <option value="stats">Statistiques</option>
            <option value="dossiers">Dossiers</option>
          </select>
        </div>
        <div class="mb-3" id="userSelectContainer" style="display:none;">
          <label class="form-label">Sélectionner l'utilisateur</label>
          <select id="user_filter" name="user_id" class="form-select">@include('admin.partials.user_options', ['users' => $users])</select>
        </div>
        <div class="mb-3"><label class="form-label">Format</label>
          <select id="export_format" name="export_format" class="form-select"><option value="csv">CSV</option><option value="xlsx">Excel (.xlsx)</option><option value="pdf">PDF</option></select>
        </div>
        <button type="submit" class="btn btn-primary">Exporter</button>
      </form>
    </div>
  </div>
</section>
