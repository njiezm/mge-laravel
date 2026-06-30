<section class="tab-pane fade p-4 bg-white rounded shadow-sm" id="manage-account">
  <h2 class="h5 mb-4">Gestion des utilisateurs</h2>
  <div class="table-responsive">
    <table class="table align-middle">
      <thead class="table-primary"><tr><th>Code</th><th>Prénom</th><th>Nom</th><th>Email</th><th>Rôle</th><th>Statut</th><th>Actions</th></tr></thead>
      <tbody id="usersTableBody">@include('admin.partials.users_rows', ['users' => $users])</tbody>
    </table>
  </div>
</section>
