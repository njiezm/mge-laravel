<div class="modal fade" id="validationModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Validation de l'importation</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <h6>Dossiers à importer</h6>
      <div class="table-responsive mb-4"><table class="table table-bordered table-sm"><thead class="table-light"><tr><th>Code</th><th>Société</th><th>Groupe</th><th>Collaborateur</th><th>CDM</th><th>Associé</th><th>Réception</th><th>Heures</th><th>Début simulé</th><th>Fin simulée</th><th>Ventilation simulée</th></tr></thead><tbody id="validationTableBody"></tbody></table></div>
      <h6>Utilisateurs manquants</h6>
      <ul id="missingUsers" class="list-group mb-3"></ul>
      <button class="btn btn-outline-primary mb-3" id="createMissingBtn">Créer tous les comptes manquants</button>
      <div id="overwriteWarning" class="alert alert-danger import-warning mb-3">
        Des dossiers existent déjà en base. Cette importation va écraser les données actuelles.
        <div class="mt-2 d-flex gap-2 flex-wrap">
          <button class="btn btn-sm btn-outline-light" id="exportBeforeOverwriteBtn">Exporter l'ancien dataset</button>
          <button class="btn btn-sm btn-light" id="confirmOverwriteBtn">Confirmer l'écrasement</button>
        </div>
      </div>
      <div class="alert alert-warning">Merci de vérifier les informations ci-dessus avant d'importer.</div>
    </div>
    <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button><button class="btn btn-primary" id="confirmImportBtn">Valider l'importation</button></div>
  </div></div>
</div>

<div class="modal fade" id="fiscalYearModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Choix année fiscale</h5>
      </div>
      <div class="modal-body">
        <p class="mb-0">Calculer les dates pour quelle année ?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-primary" id="chooseCurrentYearBtn"></button>
        <button type="button" class="btn btn-primary" id="chooseNextYearBtn"></button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="overwriteConfirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirmation d'écrasement</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Des dossiers existent déjà en base. Veux-tu écraser les données actuelles avec ce nouveau dataset ?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
        <button type="button" class="btn btn-danger" id="confirmOverwriteFromPopupBtn">Confirmer l'écrasement</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content js-async-form" method="POST" action="{{ url('admin_edit_user.php') }}" data-refresh-users="1">
      <div class="modal-header"><h5 class="modal-title">Modifier l'utilisateur</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" name="id" id="edit-id">
        <div class="mb-2"><label class="form-label">Code</label><input type="text" name="code_utilisateur" id="edit-code" class="form-control" required></div>
        <div class="mb-2"><label class="form-label">Prénom</label><input type="text" name="prenom" id="edit-prenom" class="form-control" required></div>
        <div class="mb-2"><label class="form-label">Nom</label><input type="text" name="nom" id="edit-nom" class="form-control" required></div>
        <div class="mb-2"><label class="form-label">Email</label><input type="email" name="email" id="edit-email" class="form-control" required></div>
        <div class="mb-2"><label class="form-label">Rôle</label><select name="role" id="edit-role" class="form-select" required><option value="collaborateur">Collaborateur</option><option value="chef">Chef</option><option value="associe">Associé</option><option value="admin">Admin</option></select></div>
        <div class="mb-2"><label class="form-label">Nouveau mot de passe (optionnel)</label><input type="password" name="mot_de_passe" id="edit-password" class="form-control" placeholder="Laisser vide pour ne pas changer"></div>
      </div>
      <div class="modal-footer"><button type="submit" class="btn btn-primary">Enregistrer</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button></div>
    </form>
  </div>
</div>
