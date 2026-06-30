<section class="tab-pane fade p-4 bg-white rounded shadow-sm" id="data">
  <h2 class="h5 mb-3">Importer les données annuelles</h2>
  <div id="dropzone" class="dropzone">
    <p class="lead">Glissez-déposez un fichier ici ou cliquez pour sélectionner</p>
    <input type="file" id="fileInput" accept=".xlsx,.csv" hidden>
  </div>
  <div id="filePreview" class="mt-3 text-secondary" style="display:none;"></div>
  <button class="btn btn-success mt-3" id="openValidationBtn" style="display:none;">Afficher l'aperçu et valider</button>
</section>
