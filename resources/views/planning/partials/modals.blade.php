<div class="modal fade" id="editDateModal" tabindex="-1" aria-labelledby="editDateModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="editDateForm" novalidate>
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editDateModalLabel">Modifier date de fin</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="tempsId" name="dossier_id" />
          <p>Dossier : <span id="editModalDossierCode" class="fw-bold"></span></p>
          <label for="newDateFin" class="form-label">Nouvelle date de fin :</label>
          <input type="date" id="newDateFin" name="new_date_fin" class="form-control" required />
          <div class="invalid-feedback">Veuillez choisir une date valide.</div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Enregistrer</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
        </div>
      </div>
    </form>
  </div>
</div>

<div id="dossierDetailsModal" role="dialog" aria-modal="true" aria-labelledby="dossierDetailsModalLabel" aria-describedby="modalDescription" hidden>
  <div class="modal-overlay" tabindex="-1"></div>
  <div class="modal-content" tabindex="0">
    <div class="modal-header">
      <h2 id="dossierDetailsModalLabel">Details du dossier</h2>
      <button id="modalCloseBtn" aria-label="Fermer la modale">&times;</button>
    </div>
    <main id="modalDescription" class="modal-body">
      <div id="modalContentLoading" class="modal-loading">
        <div class="spinner-border" role="status"><span class="visually-hidden">Chargement...</span></div>
      </div>
      <div id="modalContent" style="display:none;">
        <h4 id="modalDossierCode" class="mb-3 text-primary"></h4>
        <p><strong>Client :</strong> <span id="modalDossierClient"></span></p>
        <div class="row g-2 mb-2">
          <div class="col-md-6">
            <p><strong>Statut :</strong>
              <select id="modalSelectStatut" class="form-select form-select-sm w-auto d-inline-block"></select>
              <button id="btnSaveStatut" class="btn btn-sm btn-outline-success ms-2">Enregistrer</button>
            </p>
          </div>
          <div class="col-md-6">
            <p><strong>Workflow fiscal :</strong>
              <select id="modalWorkflowStep" class="form-select form-select-sm w-auto d-inline-block">
                <option value="reception">Reception</option>
                <option value="revision">Revision</option>
                <option value="validation_associe">Validation associe</option>
                <option value="envoi">Envoi</option>
                <option value="depot">Depot</option>
              </select>
              <label class="ms-2"><input type="checkbox" id="modalPiecesCritiquesOk"> Pieces critiques OK</label>
              <button id="btnSaveWorkflow" class="btn btn-sm btn-outline-primary ms-2">Enregistrer</button>
            </p>
          </div>
        </div>
        <p><strong>Collaborateurs affectes :</strong></p>
        <ul id="modalCollaborateursList" class="list-unstyled ps-3 mb-3"></ul>
        <div id="collabEditSection" class="mb-3">
          <p><strong>Modifier collaborateur :</strong></p>
          <select id="modalSelectCollaborateur" class="form-select form-select-sm w-50 d-inline-block"></select>
          <button id="btnChangeCollaborateur" class="btn btn-sm btn-outline-primary ms-2">Modifier</button>
        </div>
        <p><strong>Dates de traitement :</strong></p>
        <div class="d-flex align-items-center gap-3 mb-3">
          <label>Debut : <input type="date" id="modalDateDebut" class="form-control form-control-sm w-auto d-inline-block"></label>
          <label>Fin : <input type="date" id="modalDateFin" class="form-control form-control-sm w-auto d-inline-block"></label>
          <button id="btnSaveDates" class="btn btn-sm btn-outline-warning ms-2">Enregistrer dates</button>
        </div>
        <p><strong>Temps saisis des collaborateurs (en cours)</strong></p>
        <ul id="modalTempsCollaborateurs" class="list-unstyled ps-3 mb-3"></ul>
        <h5>Checklist legale & conformite</h5>
        <div id="checklistContainer" class="mb-2"></div>
        <button id="btnSaveChecklist" class="btn btn-sm btn-outline-success mb-3">Enregistrer checklist</button>
        <h5>Alertes internes (SLA)</h5>
        <div class="d-flex flex-wrap gap-2 mb-2">
          <input id="alertTitle" type="text" class="form-control form-control-sm" style="max-width: 260px;" placeholder="Titre alerte">
          <select id="alertLevel" class="form-select form-select-sm" style="max-width: 140px;">
            <option value="info">Info</option><option value="warning">Warning</option><option value="danger">Critique</option>
          </select>
          <select id="alertTargetRole" class="form-select form-select-sm" style="max-width: 180px;">
            <option value="all">Tous</option><option value="collaborateur">Collaborateurs</option><option value="chef">Chefs</option><option value="associe">Associes</option>
          </select>
          <button id="btnCreateInternalAlert" class="btn btn-sm btn-outline-danger">Creer alerte</button>
        </div>
        <textarea id="alertMessage" class="form-control form-control-sm mb-2" rows="2" placeholder="Message interne"></textarea>
        <ul id="alertsContainer" class="list-unstyled ps-3 mb-3"></ul>
        <h5>Ventilation des heures et repartition par role</h5>
        <div class="d-flex flex-wrap gap-2 mb-2">
          <input type="month" id="ventilationPeriod" class="form-control form-control-sm" style="max-width: 170px;">
          <select id="ventilationCompetence" class="form-select form-select-sm" style="max-width: 200px;">
            <option value="general">General</option><option value="tenue">Tenue</option><option value="revision">Revision</option><option value="fiscal">Fiscal</option><option value="conseil">Conseil</option>
          </select>
        </div>
        <div id="ventilationContainer" class="mb-2"></div>
        <textarea id="ventilationJustification" class="form-control form-control-sm mb-2" rows="2" placeholder="Justification (obligatoire si ecart >= 20h)"></textarea>
        <button id="saveVentilation" class="btn btn-sm btn-outline-primary">Enregistrer ventilation</button>
        <h6 class="mt-3">Historique ventilation</h6>
        <ul id="ventilationHistory" class="list-unstyled ps-3 mb-3"></ul>
        <hr>
        <div class="text-end"><button id="btnLeverAlerte" class="btn btn-sm btn-danger">Lever une alerte</button></div>
      </div>
    </main>
  </div>
</div>

<div id="toast-container" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 9999;"></div>
