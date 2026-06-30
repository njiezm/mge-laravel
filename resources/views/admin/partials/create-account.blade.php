<section class="tab-pane fade show active p-4 bg-white rounded shadow-sm" id="create-account">
  <h2 class="h5 mb-4">Créer un nouvel utilisateur</h2>
  <form action="{{ url('admin_create_user.php') }}" method="POST" class="js-async-form" data-refresh-users="1">
    <div class="row g-3 mb-3">
      <div class="col-md"><label class="form-label">Code utilisateur</label><input name="code_utilisateur" type="text" class="form-control" required /></div>
      <div class="col-md"><label class="form-label">Nom</label><input name="nom" type="text" class="form-control" required /></div>
      <div class="col-md"><label class="form-label">Prénom</label><input name="prenom" type="text" class="form-control" required /></div>
    </div>
    <div class="row g-3 mb-3">
      <div class="col-md"><label class="form-label">Email</label><input name="email" type="email" class="form-control" required /></div>
      <div class="col-md"><label class="form-label">Mot de passe</label><input name="mot_de_passe" type="password" class="form-control" required /></div>
    </div>
    <div class="mb-3">
      <label class="form-label">Rôle</label>
      <select name="role" class="form-select" required>
        <option value="collaborateur">Collaborateur</option>
        <option value="chef">Chef</option>
        <option value="associe">Associé</option>
        <option value="admin">Admin</option>
      </select>
    </div>
    <button type="submit" class="btn btn-primary">Créer l'utilisateur</button>
  </form>
  <div class="alert alert-info mt-3 mb-0">Les comptes créés utilisent un mot de passe temporaire. Un email de connexion est envoyé et le changement de mot de passe est obligatoire à la première connexion.</div>
</section>
