
// Définition des statuts pour la logique JavaScript
const statuts_js = {
  'non_traite': 'Re\u00E7u Non Trait\u00E9',
  'en_cours_traitement': 'Re\u00E7u En Cours de Traitement',
  'revu_en_cours': 'Revu En Cours',
  'revu_associe': 'Revu Associ\u00E9',
  'liasse_envoyee': 'Liasse Envoy\u00E9e',
  'declarer_en_retard': 'D\u00E9clar\u00E9 En Retard'
};

/**
 * Affiche un message toast à l'utilisateur.
 * @param {string} message Le message à afficher.
 * @param {string} type Le type de toast (ex: "info", "success", "danger", "warning").
 */
function showToast(message, type = "info") {
  const toastId = "toast-" + Date.now();
  const toastContainer = document.getElementById("toast-container");
  if (!toastContainer) {
    console.error("Le conteneur de toast n'existe pas.");
    return;
  }

  const toast = document.createElement("div");
  toast.id = toastId;
  toast.className = `toast align-items-center text-white bg-${type} border-0 show`;
  toast.setAttribute("role", "alert");
  toast.setAttribute("aria-live", "assertive");
  toast.setAttribute("aria-atomic", "true");

  toast.innerHTML = `
    <div class="d-flex">
      <div class="toast-body">${message}</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Fermer"></button>
    </div>
  `;
  toastContainer.appendChild(toast);
  setTimeout(() => toast.remove(), 5000);
}

/**
 * Safely retrieves filter values for the 'echeances' tab.
 * Returns default values if elements are not found.
 * @returns {object} An object containing filterStatut, filterUserEcheancesCheckbox, and rows.
 */
function getEcheancesFilterValues() {
    const filterStatutSelect = document.getElementById('filterStatut');
    const filterUserCheckbox = document.getElementById('filterUserEcheancesCheckbox');
    const rowsSelect = document.getElementById('rows_echeances_select');

    const filterStatut = filterStatutSelect ? filterStatutSelect.value : '';
    const filterUserEcheancesCheckbox = filterUserCheckbox ? filterUserCheckbox.checked : false;
    const rows = rowsSelect ? parseInt(rowsSelect.value) : 10; // Default to 10 if not found

    return { filterStatut, filterUserEcheancesCheckbox, rows };
}

/**
 * Safely retrieves the number of rows per page for a given tab.
 * Returns a default of 10 if the select element is not found.
 * @param {string} tabId The ID of the tab (e.g., 'global', 'perso').
 * @returns {number} The number of rows per page.
 */
function getRowsPerPage(tabId) {
    const selectElement = document.getElementById(`rows_${tabId}_select`);
    return selectElement ? parseInt(selectElement.value) : 10; // Default to 10
}


/**
 * Charge le contenu d'un onglet via AJAX.
 * @param {string} tabId L'identifiant de l'onglet ('global', 'perso', 'echeances').
 * @param {number} page Le numéro de page à charger.
 * @param {number} rows Le nombre de lignes par page.
 * @param {string} filterStatut (Pour échéances) Le statut à filtrer.
 * @param {boolean} filterUserEcheancesCheckbox (Pour échéances) Si la checkbox "Mes dossiers" est cochée.
 */
async function loadTabContent(tabId, page, rows, filterStatut = '', filterUserEcheancesCheckbox = false) {
  console.log(`loadTabContent: Chargement de l'onglet ${tabId}, page ${page}, ${rows} lignes.`);
  const url = new URL(window.location.origin + window.location.pathname);
  url.searchParams.set('ajax', 'true');
  url.searchParams.set('tab', tabId);

  if (tabId === 'global') {
    url.searchParams.set('page_global', page);
    url.searchParams.set('rows_global', rows);
  } else if (tabId === 'perso') {
    url.searchParams.set('page_perso', page);
    url.searchParams.set('rows_perso', rows);
  } else if (tabId === 'echeances') {
    url.searchParams.set('page_echeances', page);
    url.searchParams.set('rows_echeances', rows);
  } else {
    url.searchParams.set('page', page);
    url.searchParams.set('rows', rows);
  }

  if (tabId === 'echeances') {
    if (filterStatut) {
      url.searchParams.set('filterStatut', filterStatut);
    }
    if (filterUserEcheancesCheckbox) {
      url.searchParams.set('filterUserEcheancesCheckbox', 'on');
    } else {
      url.searchParams.delete('filterUserEcheancesCheckbox'); // Ensure it's not present if unchecked
    }
  }

  try {
    console.log(`loadTabContent: Requête AJAX vers ${url.toString()}`);
    const response = await fetch(url.toString());
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    const htmlContent = await response.text();
    console.log(`loadTabContent: Réponse AJAX reçue pour ${tabId}.`);

    if (tabId === 'echeances') {
      const contentContainer = document.getElementById('echeances-content');
      if (contentContainer) {
        contentContainer.innerHTML = htmlContent;
        console.log(`loadTabContent: Contenu de l'onglet '${tabId}' (echeances) mis \u00E0 jour.`);
        attachFilterListeners(); // Re-attach filter listeners for echeances
        attachPaginationListeners(tabId); // Re-attach pagination listeners for echeances
        loadEcheancesRationData(); // Re-load ratio data for echeances
      } else {
        console.error(`loadTabContent: Élément cible pour l'onglet '${tabId}' non trouvé.`);
      }
    } else { // For 'global' and 'perso' tabs
      const parser = new DOMParser();
      const doc = parser.parseFromString(htmlContent, 'text/html');

      // Get the entire table-responsive div from the AJAX response
      const newTableResponsiveContent = doc.querySelector('.table-responsive-content');
      // Get the current table-responsive div in the DOM
      const oldTableResponsiveContainer = document.getElementById(`table-responsive-${tabId}`);


      if (newTableResponsiveContent && oldTableResponsiveContainer) {
        // Replace the entire innerHTML of the old container with the new content
        oldTableResponsiveContainer.innerHTML = newTableResponsiveContent.innerHTML;
        console.log(`loadTabContent: Contenu du div 'table-responsive-${tabId}' remplacé.`);
      } else {
        console.error(`loadTabContent: Impossible de trouver les éléments 'table-responsive-content' ou 'table-responsive-${tabId}'.`);
      }

      attachPaginationListeners(tabId); // Re-attach listeners for new pagination
      if (tabId === 'perso') {
        document.querySelectorAll('#tablePerso textarea[data-id]').forEach(textarea => {
          textarea.removeEventListener('change', handleCommentChange); // Avoid duplicates
          textarea.addEventListener('change', handleCommentChange);
        });
        console.log(`loadTabContent: Écouteurs de commentaires ré-attachés pour l'onglet perso.`);
      }
    }



  } catch (error) {
    console.error('Erreur lors du chargement du contenu de l\'onglet :', error);
    showToast(`Erreur lors du chargement du contenu de l'onglet "${tabId}".`, 'danger');
  }
}

/**
 * Attaches event listeners to pagination links.
 * @param {string} tabId The ID of the tab concerned.
 */
function attachPaginationListeners(tabId) {
  console.log(`attachPaginationListeners: Tentative d'attacher les écouteurs pour l'onglet ${tabId}.`);
  const paginationContainer = document.getElementById(`pagination-${tabId}`); // This is now the NAV element itself

  if (paginationContainer) {
    paginationContainer.querySelectorAll('.page-link').forEach(link => {
      link.removeEventListener('click', handlePaginationClick); // Avoid duplicates
      link.addEventListener('click', handlePaginationClick);
    });
    console.log(`attachPaginationListeners: Écouteurs attachés pour la pagination de l'onglet ${tabId}.`);
  } else {
    console.warn(`attachPaginationListeners: Conteneur de pagination (nav) non trouvé pour l'onglet ${tabId}.`);
  }
}

/**
 * Handles click on a pagination link.
 * @param {Event} event The click event.
 */
function handlePaginationClick(event) {
  event.preventDefault(); // Prevents full page reload
  const link = event.currentTarget;
  const page = parseInt(link.dataset.page);
  const rows = parseInt(link.dataset.rows);
  const tab = link.dataset.tab;

  console.log(`handlePaginationClick: Clic sur la page ${page}, ${rows} lignes, onglet ${tab}.`);

  let filterStatut = '';
  let filterUserEcheancesCheckbox = false;

  // Only attempt to get filter values if the tab is 'echeances'
  if (tab === 'echeances') {
    const { filterStatut: fStatut, filterUserEcheancesCheckbox: fUserCheckbox } = getEcheancesFilterValues();
    filterStatut = fStatut;
    filterUserEcheancesCheckbox = fUserCheckbox;
    console.log(`handlePaginationClick: Filtres pour échéances - Statut: ${filterStatut}, Mes dossiers: ${filterUserEcheancesCheckbox}`);
  }

  loadTabContent(tab, page, rows, filterStatut, filterUserEcheancesCheckbox);
}

/**
 * Handles comment change in a textarea.
 * @param {Event} event The change event.
 */
function handleCommentChange(event) {
  const textarea = event.currentTarget;
  const id = textarea.dataset.id;
  const commentaires = textarea.value;
  console.log(`handleCommentChange: Commentaire mis \u00E0 jour pour ID ${id}.`);
  sendAction(id, 'updateComment', commentaires);
}

/**
 * Sends an action to the server via AJAX.
 * @param {string} id The ID of the concerned element (temps_id or dossier_id).
 * @param {string} action The action to perform ('updateComment', 'toggleRetard', 'updateStatut').
 * @param {string} [value=''] The value associated with the action (e.g., comments, new status).
 */
async function sendAction(id, action, value = '') {
  console.log(`sendAction: Envoi de l'action '${action}' pour l'ID ${id} avec la valeur '${value}'.`);
  const data = new URLSearchParams();
  data.append('id', id);
  data.append('id_temps', id);
  data.append('action', action);
  if (value !== '') {
    data.append('value', value);
    data.append('commentaires', value);
  }

  try {
    const response = await fetch('actions.php', {
      method: 'POST',
      body: data
    });
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    const res = await response.json();

    if (!res.success) {
      showToast('Erreur : ' + res.message, 'danger');
      console.error(`sendAction: Erreur lors de l'action '${action}': ${res.message}`);
    } else {
      showToast('Action effectuee avec succes.', 'success');
      console.log(`sendAction: Action '${action}' reussie.`);
      if (action === 'toggleRetard' || action === 'updateStatut') {
         loadTabContent('perso', 1, getRowsPerPage('perso'));
      }
    }
  } catch (error) {
    showToast('Erreur reseau ou serveur : ' + error.message, 'danger');
    console.error('Erreur fetch:', error);
  }
}

/**
 * Updates the status of a dossier.
 * @param {string} dossierId The dossier ID.
 */
async function validerStatut(dossierId) {
  const select = document.getElementById(`select-statut-${dossierId}`);
  const statut = select.value;
  console.log(`validerStatut: Validation du statut '${statut}' pour le dossier ${dossierId}.`);

  try {
    const response = await fetch('update_statut.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ dossier_id: dossierId, id_dossier: dossierId, statut: statut })
    });
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    const data = await response.json();

    if (data.success) {
      showToast('Statut mis a jour avec succes', 'success');
      console.log(`validerStatut: Statut mis a jour avec succes pour le dossier ${dossierId}.`);
      loadTabContent('perso', 1, getRowsPerPage('perso'));
    } else {
      showToast('Erreur : ' + data.message, 'danger');
      console.error(`validerStatut: Erreur lors de la mise a jour du statut: ${data.message}`);
    }
  } catch (error) {
    showToast('Erreur reseau ou serveur : ' + error.message, 'danger');
    console.error('Erreur fetch:', error);
  }
}

/**
 * Marks a dossier as delayed.
 * @param {string} id_dossier The dossier ID.
 */
async function marquerRetard(id_dossier) {
  console.log(`marquerRetard: Marquage en retard pour le dossier ${id_dossier}.`);
  try {
    const response = await fetch('update_statut.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `dossier_id=${encodeURIComponent(id_dossier)}&id_dossier=${encodeURIComponent(id_dossier)}&statut=declarer_en_retard`
    });
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    const text = await response.text();
    console.log(`marquerRetard: Reponse serveur brute: ${text}`);

    if (text.trim().toUpperCase() === 'OK' || (text.startsWith('{') && JSON.parse(text).success)) {
      showToast('Dossier marque en retard.', 'warning');
      console.log(`marquerRetard: Dossier ${id_dossier} marque en retard.`);
      loadTabContent('perso', 1, getRowsPerPage('perso'));
    } else {
      showToast('Erreur serveur : ' + text, 'danger');
      console.error(`marquerRetard: Erreur serveur: ${text}`);
    }
  } catch (err) {
    showToast('Erreur reseau : ' + err.message, 'danger');
    console.error('Erreur reseau ou serveur :', err);
  }
}

// Filtres echeances - Moved outside DOMContentLoaded
const formFilterEcheances = document.getElementById('formFilterEcheances');
function attachFilterListeners() {
    if (formFilterEcheances) {
        formFilterEcheances.removeEventListener('change', handleEcheancesFilterChange);
        formFilterEcheances.addEventListener('change', handleEcheancesFilterChange);
        console.log(`attachFilterListeners: Écouteurs de filtre pour échéances attachés.`);
    } else {
      console.warn(`attachFilterListeners: Formulaire de filtre d'échéances non trouvé.`);
    }
}

function handleEcheancesFilterChange() {
    console.log(`handleEcheancesFilterChange: Changement de filtre pour les échéances détecté.`);
    const { filterStatut, filterUserEcheancesCheckbox, rows } = getEcheancesFilterValues();
    loadTabContent('echeances', 1, rows, filterStatut, filterUserEcheancesCheckbox);
}


document.addEventListener('DOMContentLoaded', function () {
  console.log("DOMContentLoaded: Le DOM est entièrement chargé.");

  // Attach search listeners
  document.getElementById('searchGlobal')?.addEventListener('input', () => filterTable('searchGlobal', 'tableGlobal'));
  document.getElementById('searchPerso')?.addEventListener('input', () => filterTable('searchPerso', 'tablePerso'));
  document.getElementById('searchDossiers')?.addEventListener('input', () => filterCards('searchDossiers', 'dossiersContainer'));
  console.log("DOMContentLoaded: Écouteurs de recherche attachés.");

  // Attach listeners for comments on initial page load
  document.querySelectorAll('textarea[data-id]').forEach(textarea => {
    textarea.addEventListener('change', handleCommentChange);
  });
  console.log("DOMContentLoaded: Écouteurs de commentaires initiaux attachés.");
  if (ventilationPeriod) {
    ventilationPeriod.value = new Date().toISOString().slice(0, 7);
  }

  document.getElementById('btnGenerateSlaAlerts')?.addEventListener('click', async () => {
    try {
      const res = await fetch('ajax_generate_sla_alerts.php');
      const data = await res.json();
      if (data.success) {
        showToast(`Alertes SLA creees: ${data.created}`, 'success');
      } else {
        showToast('Erreur generation alertes SLA', 'danger');
      }
    } catch (e) {
      showToast('Erreur reseau generation alertes SLA', 'danger');
    }
  });

  document.getElementById('btnRunProjection')?.addEventListener('click', runCampaignProjection);
  runCampaignProjection();

    // FullCalendar
  let calendarEl = document.getElementById('calendar');
  let calendar;
  const planningConfig = window.PlanningConfig || {};
  const planningToday = planningConfig.today || new Date().toISOString().slice(0, 10);
  let calendarInitialised = false;

  function loadCalendar(events) {
    if (!calendarEl || typeof FullCalendar === 'undefined') {
      console.warn('FullCalendar unavailable or calendar container missing.');
      return;
    }

    if (calendar) calendar.destroy();
    calendar = new FullCalendar.Calendar(calendarEl, {
      locale: 'fr',
      initialView: 'dayGridMonth',
      initialDate: planningToday,
      events: events,
      height: 650,
      themeSystem: 'bootstrap5',
      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay'
      },
      buttonText: {
        today: 'Aujourd\'hui',
        month: 'Mois',
        week: 'Semaine',
        day: 'Jour'
      },
      hiddenDays: [0, 6]
    });

    calendar.render();
    calendarInitialised = true;
  }

  function ensureCalendarReady() {
    if (!calendarInitialised) {
      loadCalendar(planningConfig.initialEvents || []);
    }

    if (calendar) {
      requestAnimationFrame(() => {
        calendar.updateSize();
        setTimeout(() => calendar.updateSize(), 50);
      });
    }
  }



  /**
   * Retrieves current filters for the calendar.
   * @returns {object} An object containing checked statuses and selected user.
   */
  function getCalendarFilters() {
    let checkedStatuses = Array.from(document.querySelectorAll('.status-filter:checked')).map(el => el.value);
    let userFilter = document.getElementById('user-filter')?.value || 'moi';
    return { statuses: checkedStatuses, user: userFilter };
  }

  /**
   * Fetches filtered events for the calendar via AJAX.
   */
    async function fetchFilteredEvents(showSuccessToast = false) {
    let filters = getCalendarFilters();
    console.log('fetchFilteredEvents: Recuperation des evenements filtres avec:', filters);

    try {
      const response = await fetch('/calendrier_dossiers_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(filters)
      });
      if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
      const data = await response.json();

      if (data.success) {
        loadCalendar(data.events);
        if (showSuccessToast) {
          showToast('Calendrier mis a jour avec les filtres.', 'success');
        }
      } else {
        showToast('Erreur lors du chargement des dossiers du calendrier.', 'danger');
        console.error(`fetchFilteredEvents: Erreur: ${data.message}`);
      }
    } catch (err) {
      console.error(err);
      showToast('Erreur reseau ou technique lors du chargement du calendrier.', 'danger');
    }
  }

  document.getElementById('apply-filters')?.addEventListener('click', () => fetchFilteredEvents(true));
  document.querySelectorAll('.status-filter').forEach((checkbox) => {
    checkbox.addEventListener('change', () => fetchFilteredEvents(false));
  });
  document.getElementById('user-filter')?.addEventListener('change', () => fetchFilteredEvents(false));
  document.getElementById('calendrier-tab')?.addEventListener('shown.bs.tab', () => {
    ensureCalendarReady();
    fetchFilteredEvents(false);
    setTimeout(() => {
      if (calendar) calendar.updateSize();
    }, 100);
  });

  if (calendarEl && document.getElementById('view-calendrier')?.classList.contains('show')) {
    loadCalendar(planningConfig.initialEvents || []);
    fetchFilteredEvents(false);
  }

  // Modal for changing end date
  const editModalEl = document.getElementById('editDateModal');
  const editModal = editModalEl ? new bootstrap.Modal(editModalEl) : null;

  window.openEditModal = function (id, code, currentFin) {
    if (!editModal) {
      console.error("openEditModal: Modal d'édition non initialisée.");
      return;
    }
    document.getElementById('tempsId').value = id;
    document.getElementById('editModalDossierCode').textContent = code;
    document.getElementById('newDateFin').value = currentFin;
    document.getElementById('newDateFin').classList.remove('is-invalid');
    editModal.show();
    console.log(`openEditModal: Ouverture de la modale d'édition pour le dossier ${code}.`);
  };

  document.getElementById('editDateForm')?.addEventListener('submit', async e => {
    e.preventDefault();
    const inputDate = document.getElementById('newDateFin');
    if (!inputDate.value) {
      inputDate.classList.add('is-invalid');
      console.warn("editDateForm: Date de fin invalide.");
      return;
    } else {
      inputDate.classList.remove('is-invalid');
    }

    const formData = new FormData(e.target);
    try {
      const response = await fetch('update_fin_dossier.php', {
        method: 'POST',
        body: formData
      });
      if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
      const data = await response.json();

      if (data.success) {
        showToast("Date de fin mise à jour.", "success");
        editModal?.hide();
        console.log("editDateForm: Date de fin mise à jour avec succès.");
        loadTabContent('perso', 1, getRowsPerPage('perso'));
      } else {
        showToast('Erreur : ' + data.message, "danger");
        console.error(`editDateForm: Erreur lors de la mise à jour de la date de fin: ${data.message}`);
      }
    } catch (error) {
      showToast('Erreur réseau ou technique.', "danger");
      console.error('Erreur fetch:', error);
    }
  });


  // Tab persistence and content loading
  const myTabEl = document.querySelector('#myTab');
  const storedActiveTabId = localStorage.getItem('activeTab');
  let tabToActivateElement = null;

  if (storedActiveTabId) {
    tabToActivateElement = document.getElementById(storedActiveTabId);
  }

  // Listener for tab changes (fires when a tab is *shown*, including programmatic ones)
  myTabEl?.addEventListener('shown.bs.tab', event => {
    localStorage.setItem('activeTab', event.target.id);
    const activeTabId = event.target.id.replace('-tab', '');
    console.log(`shown.bs.tab: Onglet '${activeTabId}' affiché.`);

    // Load content for the newly shown tab
    const rows = getRowsPerPage(activeTabId);
    if (activeTabId === 'global') {
      loadTabContent('global', 1, rows);
    } else if (activeTabId === 'perso') {
      loadTabContent('perso', 1, rows);
    } else if (activeTabId === 'echeances') {
      const { filterStatut, filterUserEcheancesCheckbox } = getEcheancesFilterValues();
      loadTabContent('echeances', 1, rows, filterStatut, filterUserEcheancesCheckbox);
    }
  });

  // Activate the stored tab or the default active tab on initial load
  if (tabToActivateElement) {
    const tabInstance = bootstrap.Tab.getInstance(tabToActivateElement) || new bootstrap.Tab(tabToActivateElement);
    tabInstance.show();
    console.log(`DOMContentLoaded: Activation de l'onglet stocké: ${tabToActivateElement.id}`);
  } else {
    // If no stored tab, ensure the default active tab's content is loaded
    const defaultActiveTabButton = document.querySelector('#myTab .nav-link.active');
    if (defaultActiveTabButton) {
      const activeTabId = defaultActiveTabButton.id.replace('-tab', '');
      const rows = getRowsPerPage(activeTabId);
      console.log(`DOMContentLoaded: Chargement initial pour l'onglet par défaut: ${activeTabId}`);
      if (activeTabId === 'global') {
        loadTabContent('global', 1, rows);
      } else if (activeTabId === 'perso') {
        loadTabContent('perso', 1, rows);
      } else if (activeTabId === 'echeances') {
        const { filterStatut, filterUserEcheancesCheckbox } = getEcheancesFilterValues();
        loadTabContent('echeances', 1, rows, filterStatut, filterUserEcheancesCheckbox);
      }
    }
  }

  // Initial attachment of pagination listeners for global and perso tabs.
  attachPaginationListeners('global');
  attachPaginationListeners('perso');
  attachFilterListeners();

});

// Functions for searching in tables and cards
function filterTable(inputId, tableId) {
  const input = document.getElementById(inputId);
  if (!input) {
    console.warn(`filterTable: Input avec l'ID ${inputId} non trouvé.`);
    return;
  }
  const filter = input.value.toLowerCase();
  const table = document.getElementById(tableId);
  if (!table) {
    console.warn(`filterTable: Table avec l'ID ${tableId} non trouvée.`);
    return;
  }
  const tbody = table.getElementsByTagName("tbody")[0];
  if (!tbody) {
    console.warn(`filterTable: tbody non trouvé pour la table ${tableId}.`);
    return;
  }
  const rows = tbody.getElementsByTagName("tr");
  for (let i = 0; i < rows.length; i++) {
    const text = rows[i].textContent.toLowerCase();
    rows[i].style.display = text.includes(filter) ? "" : "none";
  }
  console.log(`filterTable: Table ${tableId} filtrée par '${filter}'.`);
}

function filterCards(inputId, containerId) {
  const input = document.getElementById(inputId);
  if (!input) {
    console.warn(`filterCards: Input avec l'ID ${inputId} non trouvé.`);
    return;
  }
  const filter = input.value.toLowerCase();
  const container = document.getElementById(containerId);
  if (!container) {
    console.warn(`filterCards: Conteneur avec l'ID ${containerId} non trouvé.`);
    return;
  }
  const cards = container.getElementsByClassName("card-dossier");
  for (let card of cards) {
    const text = card.textContent.toLowerCase();
    card.parentElement.style.display = text.includes(filter) ? "" : "none";
  }
  console.log(`filterCards: Cartes dans ${containerId} filtrées par '${filter}'.`);
}

// Logic for dossier details modal
const modal = document.getElementById('dossierDetailsModal');
const modalCloseBtn = document.getElementById('modalCloseBtn');
const mainContent = document.querySelector('main');
const modalContent = document.getElementById('modalContent');
const modalLoading = document.getElementById('modalContentLoading');

// Modal elements
const modalDossierCode = document.getElementById('modalDossierCode');
const modalDossierClient = document.getElementById('modalDossierClient');
const modalSelectStatut = document.getElementById('modalSelectStatut');
const btnSaveStatut = document.getElementById('btnSaveStatut');
const modalCollaborateursList = document.getElementById('modalCollaborateursList');
const modalSelectCollaborateur = document.getElementById('modalSelectCollaborateur');
const btnChangeCollaborateur = document.getElementById('btnChangeCollaborateur');
const modalDateDebut = document.getElementById('modalDateDebut');
const modalDateFin = document.getElementById('modalDateFin');
const btnSaveDates = document.getElementById('btnSaveDates');
const btnLeverAlerte = document.getElementById('btnLeverAlerte');
const modalWorkflowStep = document.getElementById('modalWorkflowStep');
const modalPiecesCritiquesOk = document.getElementById('modalPiecesCritiquesOk');
const btnSaveWorkflow = document.getElementById('btnSaveWorkflow');
const checklistContainer = document.getElementById('checklistContainer');
const btnSaveChecklist = document.getElementById('btnSaveChecklist');
const alertsContainer = document.getElementById('alertsContainer');
const btnCreateInternalAlert = document.getElementById('btnCreateInternalAlert');
const alertTitle = document.getElementById('alertTitle');
const alertMessage = document.getElementById('alertMessage');
const alertLevel = document.getElementById('alertLevel');
const alertTargetRole = document.getElementById('alertTargetRole');
const ventilationHistory = document.getElementById('ventilationHistory');
const ventilationPeriod = document.getElementById('ventilationPeriod');
const ventilationCompetence = document.getElementById('ventilationCompetence');
const ventilationJustification = document.getElementById('ventilationJustification');

let lastFocusedElement = null;

/**
 * Loads time entries by collaborators for a given dossier.
 */
async function chargerTempsCollaborateurs() {
  const codeDossier = modalDossierCode.textContent.trim();
  const ulTemps = document.getElementById('modalTempsCollaborateurs');
  if (!ulTemps) {
    console.warn("chargerTempsCollaborateurs:  'modalTempsCollaborateurs' non trouvé.");
    return;
  }
  ulTemps.innerHTML = '<li>Chargement des temps en cours...</li>';
  console.log(`chargerTempsCollaborateurs: Chargement des temps pour le dossier ${codeDossier}.`);

  try {
    const response = await fetch(`ajax_get_temps_dossier.php?codeDossier=${encodeURIComponent(codeDossier)}`);
    const text = await response.text();
    const data = JSON.parse(text);

    if (!data.success) {
      ulTemps.innerHTML = `<li style="color:red;">Erreur : ${data.message}</li>`;
      console.error(`chargerTempsCollaborateurs: Erreur: ${data.message}`);
      return;
    }
    if (!data.temps || data.temps.length === 0) {
      ulTemps.innerHTML = '<li>Aucun temps saisi trouvé pour ce dossier.</li>';
      console.log(`chargerTempsCollaborateurs: Aucun temps trouvé pour le dossier ${codeDossier}.`);
      return;
    }

    ulTemps.innerHTML = '';
    data.temps.forEach(item => {
      const li = document.createElement('li');
      li.textContent = `Collaborateur : ${item.collaborateur} | Temps saisi : ${item.temps} h`;
      ulTemps.appendChild(li);
    });
    console.log(`chargerTempsCollaborateurs: Temps chargé avec succès pour le dossier ${codeDossier}.`);
  } catch(e) {
      console.error("JSON parse ou erreur fetch (temps):", e);
      ulTemps.innerHTML = '<li style="color:red;">Erreur technique lors de la récupération des temps.</li>';
  }
}

/**
 * Loads real hours and calculates the ratio for the "échéances" tab.
 */
async function loadEcheancesRationData() {
  const echeancesTable = document.querySelector('#view-echeances table tbody');
  if (!echeancesTable) {
    console.warn("loadEcheancesRationData: Tableau des échéances non trouvé.");
    return;
  }
  console.log("loadEcheancesRationData: Chargement des données de ratio pour les échéances.");

  const rows = echeancesTable.querySelectorAll('tr[data-dossier-id]');

  for (const row of rows) {
    const dossierId = row.dataset.dossierId;
    const codeDossier = row.dataset.codeDossier;
    const heurePrevues = parseFloat(row.dataset.heurePrevues);
    const heureReellesCell = row.querySelector('.heure-reelles-cell');
    const ratioCell = row.querySelector('.ratio-cell');

    if (heureReellesCell) heureReellesCell.textContent = 'Chargement...';
    if (ratioCell) ratioCell.textContent = 'Chargement...';

    try {
      const response = await fetch(`ajax_get_temps_dossier.php?codeDossier=${encodeURIComponent(codeDossier)}`);
      const data = await response.json();

      let totalHeuresReelles = 0;
      if (data.success && data.temps && Array.isArray(data.temps)) {
        totalHeuresReelles = data.temps.reduce((sum, item) => sum + parseFloat(item.temps || 0), 0);
      }

      const ratio = (heurePrevues > 0) ? ((totalHeuresReelles / heurePrevues) * 100).toFixed(2) : 0;

      if (heureReellesCell) heureReellesCell.textContent = `${totalHeuresReelles.toFixed(2)} h`;
      if (ratioCell) ratioCell.textContent = `${ratio} %`;

    } catch (error) {
      console.error(`Erreur lors du chargement des temps pour le dossier ${codeDossier}:`, error);
      if (heureReellesCell) heureReellesCell.textContent = 'N/A';
      if (ratioCell) ratioCell.textContent = 'N/A';
    }
  }
}

function renderChecklist(items = []) {
  if (!checklistContainer) return;
  if (!Array.isArray(items) || items.length === 0) {
    checklistContainer.innerHTML = '<p class="text-muted small">Aucune checklist.</p>';
    return;
  }

  checklistContainer.innerHTML = items.map((item, idx) => {
    const checked = item.is_done ? 'checked' : '';
    const required = item.is_required ? 'required' : 'optional';
    return `<label class="d-block small"><input type="checkbox" class="checklist-item" data-category="${item.category}" data-label="${item.item_label}" data-required="${item.is_required ? 1 : 0}" ${checked}> [${item.category}] ${item.item_label} <span class="text-muted">(${required})</span></label>`;
  }).join('');
}

function collectChecklistPayload() {
  return Array.from(document.querySelectorAll('.checklist-item')).map((el) => ({
    category: el.dataset.category,
    item_label: el.dataset.label,
    is_required: el.dataset.required === '1',
    is_done: el.checked,
  }));
}

function renderAlerts(alerts = []) {
  if (!alertsContainer) return;
  if (!Array.isArray(alerts) || alerts.length === 0) {
    alertsContainer.innerHTML = '<li class="text-muted small">Aucune alerte interne.</li>';
    return;
  }

  alertsContainer.innerHTML = alerts.map((a) => `<li><strong>[${a.level}]</strong> ${a.title} - ${a.message || ''} <span class="text-muted">(${a.status})</span></li>`).join('');
}

function renderVentilationHistory(items = []) {
  if (!ventilationHistory) return;
  if (!Array.isArray(items) || items.length === 0) {
    ventilationHistory.innerHTML = '<li class="text-muted small">Aucun historique.</li>';
    return;
  }

  ventilationHistory.innerHTML = items.map((h) => `<li><strong>${h.mois}</strong> ${h.old_value} -> ${h.new_value} (delta ${h.delta}) ${h.justification ? '- ' + h.justification : ''}</li>`).join('');
}

async function runCampaignProjection() {
  const extraEtp = parseInt(document.getElementById('simExtraEtp')?.value || '0', 10);
  const delayDays = parseInt(document.getElementById('simDelayDays')?.value || '0', 10);

  try {
    const response = await fetch(`ajax_campaign_projection.php?extra_etp=${encodeURIComponent(extraEtp)}&delay_days=${encodeURIComponent(delayDays)}`);
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    const data = await response.json();
    if (!data.success) throw new Error(data.message || 'projection failed');

    const teamEl = document.getElementById('projectionByTeam');
    const portEl = document.getElementById('projectionByPortfolio');

    if (teamEl) {
      teamEl.innerHTML = (data.by_team || []).map((r) => `<div>${r.label}: ${r.dossiers} dossiers, fin estimee ${r.date_estimee}</div>`).join('') || '<div class="text-muted">Aucune donnee</div>';
    }
    if (portEl) {
      portEl.innerHTML = (data.by_portfolio || []).map((r) => `<div>${r.label}: ${r.dossiers} dossiers, fin estimee ${r.date_estimee}</div>`).join('') || '<div class="text-muted">Aucune donnee</div>';
    }
  } catch (e) {
    showToast('Erreur chargement projection campagne', 'danger');
  }
}

/**
 * Opens the dossier details modal.
 * @param {string} dossierId The ID of the dossier to display.
 */
async function openDossierModal(dossierId) {
  if (!modal || !modalContent || !modalLoading) {
    showToast("Elements de la modale manquants dans le DOM.", "danger");
    console.error("openDossierModal: Elements de la modale manquants.");
    return;
  }

  lastFocusedElement = document.activeElement;
  modal.removeAttribute('hidden');
  if (mainContent) mainContent.setAttribute('inert', '');
  modal.querySelector('.modal-content')?.focus();
  modalLoading.style.display = 'block';
  modalContent.style.display = 'none';
  console.log(`openDossierModal: Ouverture de la modale pour le dossier ID ${dossierId}.`);

  try {
    const response = await fetch('ajax_get_dossier.php?id=' + dossierId);
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    const data = await response.json();

    if (data.success) {
      fillModal(data.dossier);
      renderVentilation(data.dossier.collaborateurs);
      renderChecklist(data.dossier.checklist || []);
      renderAlerts(data.dossier.alerts || []);
      renderVentilationHistory(data.dossier.ventilation_history || []);
      chargerTempsCollaborateurs();
      modalLoading.style.display = 'none';
      modalContent.style.display = 'block';
      console.log(`openDossierModal: Données du dossier ${dossierId} chargé avec succès.`);
    } else {
      showToast('Erreur de chargement : ' + data.message, "danger");
      console.error(`openDossierModal: Erreur de chargement: ${data.message}`);
      closeModal();
    }
  } catch (err) {
    console.error("Erreur ré :", err);
    showToast('Erreur récurrente ou technique.', "danger");
    closeModal();
  }
}

/**
 * Closes the dossier details modal.
 */
function closeModal() {
  if (!modal) return;
  modal.setAttribute('hidden', '');
  if (mainContent) mainContent.removeAttribute('inert');
  if (lastFocusedElement) lastFocusedElement.focus();
  console.log("closeModal: Modale fermé.");
}

// Close modal
modalCloseBtn?.addEventListener('click', closeModal);
modal.querySelector('.modal-overlay')?.addEventListener('click', closeModal);
document.addEventListener('keydown', e => {
  if (e.key === 'Escape' && !modal.hasAttribute('hidden')) closeModal();
});

/**
 * Fills the modal with dossier data.
 * @param {object} dossier The dossier data.
 */
function fillModal(dossier) {
  modalDossierCode.textContent = dossier.code_dossier;
  modalDossierClient.textContent = dossier.nom_client;

  modalSelectStatut.innerHTML = '';
  for (const [key, label] of Object.entries(statuts_js)) {
    const opt = document.createElement('option');
    opt.value = key;
    opt.textContent = label;
    if (key === dossier.statut) opt.selected = true;
    modalSelectStatut.appendChild(opt);
  }

  modalCollaborateursList.innerHTML = '';
  dossier.collaborateurs.forEach(collab => {
    const li = document.createElement('li');
    li.textContent = `${collab.nom} ${collab.prenom} (${collab.role})`;
    modalCollaborateursList.appendChild(li);
  });

  if (modalSelectCollaborateur && dossier.tous_collaborateurs) {
    modalSelectCollaborateur.innerHTML = '<option value="">-- Choisir un collaborateur --</option>';
    dossier.tous_collaborateurs.forEach(collab => {
      const opt = document.createElement('option');
      opt.value = collab.id;
      opt.textContent = `${collab.nom} ${collab.prenom} (${collab.role})`;
      modalSelectCollaborateur.appendChild(opt);
    });
  }

  modalDateDebut.value = dossier.date_debut || '';
  modalDateFin.value = dossier.date_fin || '';
  if (modalWorkflowStep) modalWorkflowStep.value = dossier.workflow_step || 'reception';
  if (modalPiecesCritiquesOk) modalPiecesCritiquesOk.checked = !!dossier.pieces_critiques_ok;
  modalContent.dataset.dossierId = dossier.id;
  console.log(`fillModal: Modale remplie pour le dossier ${dossier.code_dossier}.`);
}

// Modal actions
btnSaveStatut?.addEventListener('click', () => {
  const dossierId = modalContent.dataset.dossierId;
  const newStatut = modalSelectStatut.value;
  updateStatut(dossierId, newStatut);
});

btnChangeCollaborateur?.addEventListener('click', () => {
  const dossierId = modalContent.dataset.dossierId;
  const newUserId = modalSelectCollaborateur.value;
  if (!newUserId) return showToast('Veuillez sélectionner un collaborateur.', "warning");
  changeCollaborateur(dossierId, newUserId);
});

btnSaveDates?.addEventListener('click', () => {
  const dossierId = modalContent.dataset.dossierId;
  const debut = modalDateDebut.value;
  const fin = modalDateFin.value;
  if (!debut || !fin) return showToast('Veuillez remplir les deux dates.', "warning");
  if (debut > fin) return showToast('La date de début doit être avant la date de fin.', "warning");
  updateDates(dossierId, debut, fin);
});

btnLeverAlerte?.addEventListener('click', () => {
  const dossierId = modalContent.dataset.dossierId;
  closeModal();
  setTimeout(() => {
    showCustomConfirm(
      "Confirmez-vous la levée de l'alerte sur ce dossier ?",
      () => leverAlerte(dossierId),
      () => openDossierModal(dossierId)
    );
  }, 150);
});

btnSaveWorkflow?.addEventListener('click', async () => {
  const dossierId = modalContent.dataset.dossierId;
  try {
    const response = await fetch('ajax_update_workflow.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        dossier_id: dossierId,
        workflow_step: modalWorkflowStep?.value,
        pieces_critiques_ok: modalPiecesCritiquesOk?.checked || false,
      })
    });
    const data = await response.json();
    if (!response.ok || !data.success) throw new Error(data.message || 'Erreur workflow');
    showToast('Workflow fiscal mis a jour', 'success');
  } catch (e) {
    showToast(e.message || 'Erreur mise a jour workflow', 'danger');
  }
});

btnSaveChecklist?.addEventListener('click', async () => {
  const dossierId = modalContent.dataset.dossierId;
  const items = collectChecklistPayload();
  try {
    const response = await fetch('ajax_save_checklist.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ dossier_id: dossierId, items })
    });
    const data = await response.json();
    if (!response.ok || !data.success) throw new Error(data.message || 'Erreur checklist');
    if (modalPiecesCritiquesOk) modalPiecesCritiquesOk.checked = !!data.pieces_critiques_ok;
    showToast('Checklist enregistree', 'success');
  } catch (e) {
    showToast(e.message || 'Erreur checklist', 'danger');
  }
});

btnCreateInternalAlert?.addEventListener('click', async () => {
  const dossierId = modalContent.dataset.dossierId;
  try {
    const response = await fetch('ajax_create_internal_alert.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        dossier_id: dossierId,
        title: alertTitle?.value || '',
        message: alertMessage?.value || '',
        level: alertLevel?.value || 'info',
        target_role: alertTargetRole?.value || 'all',
      })
    });
    const data = await response.json();
    if (!response.ok || !data.success) throw new Error(data.message || 'Erreur creation alerte');
    showToast('Alerte interne creee', 'success');
    if (alertTitle) alertTitle.value = '';
    if (alertMessage) alertMessage.value = '';
    openDossierModal(dossierId);
  } catch (e) {
    showToast(e.message || 'Erreur creation alerte', 'danger');
  }
});
/**
 * Updates the status of a dossier via AJAX.
 * @param {string} dossierId The dossier ID.
 */
async function updateStatut(dossierId, statut) {
  console.log(`updateStatut: Mise à jour du statut pour le dossier ${dossierId} vers '${statut}'.`);
  try {
    const response = await fetch('ajax_update_statut.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ dossier_id: dossierId, statut: statut })
    });
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    const data = await response.json();

    if (data.success) {
      showToast('Statut mis \u00E0 jour', "success");
      closeModal();
      console.log(`updateStatut: Statut du dossier ${dossierId} mis \u00E0 jour avec succès.`);
      const { filterStatut, filterUserEcheancesCheckbox, rows } = getEcheancesFilterValues();
      loadTabContent('echeances', 1, rows, filterStatut, filterUserEcheancesCheckbox);
    } else {
      showToast('Erreur lors de la mise à jour du statut', "danger");
      console.error(`updateStatut: Erreur: ${data.message}`);
    }
  } catch (error) {
    showToast('Erreur lors de la mise à jour du statut', "danger");
    console.error('Fetch error:', error);
  }
}

/**
 * Changes the collaborator assigned to a dossier via AJAX.
 * @param {string} dossierId The dossier ID.
 * @param {string} userId The ID of the new user.
 */
async function changeCollaborateur(dossierId, userId) {
  console.log(`changeCollaborateur: Changement de collaborateur pour le dossier ${dossierId} vers l'utilisateur ${userId}.`);
  try {
    const response = await fetch('ajax_change_collaborateur.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ dossier_id: dossierId, utilisateur_id: userId })
    });
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    const data = await response.json();

    if (data.success) {
      showToast('Collaborateur modifié avec succès', "success");
      closeModal();
      console.log(`changeCollaborateur: Collaborateur du dossier ${dossierId} changé avec succès.`);
      loadTabContent('dossiers', 1, 10);
    } else {
      showToast('Erreur lors du changement de collaborateur : ' + data.message, "danger");
      console.error(`changeCollaborateur: Erreur: ${data.message}`);
    }
  } catch (error) {
      console.error('Fetch error:', error);
      showToast('Erreur réseaux ou technique.', "danger");
  }
}

/**
 * Updates the start and end dates of a dossier via AJAX.
 * @param {string} dossierId The dossier ID.
 * @param {string} debut The new start date.
 * @param {string} fin The new end date.
 */
async function updateDates(dossierId, debut, fin) {
  console.log(`updateDates: Mise à jour des dates pour le dossier ${dossierId} (Début: ${debut}, Fin: ${fin}).`);
  try {
    const response = await fetch('ajax_update_dates.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ dossier_id: dossierId, date_debut: debut, date_fin: fin })
    });
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    const data = await response.json();

    if (data.success) {
      showToast('Dates mises à jour', "success");
      closeModal();
      console.log(`updateDates: Dates du dossier ${dossierId} mises à jour avec succès.`);
      loadTabContent('perso', 1, getRowsPerPage('perso'));
    } else {
      showToast('Erreur lors de la mise à jour des dates : ' + data.message, "danger");
      console.error(`updateDates: Erreur: ${data.message}`);
    }
  } catch (error) {
    showToast('Erreur réseau ou technique.', "danger");
    console.error('Fetch error:', error);
  }
}

/**
 * Raises an alert for a dossier via AJAX.
 * @param {string} dossierId The dossier ID.
 */
async function leverAlerte(dossierId) {
  console.log(`leverAlerte: Lever d'alerte pour le dossier ${dossierId}.`);
  try {
    const response = await fetch('ajax_lever_alerte.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ dossier_id: dossierId })
    });
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    const data = await response.json();

    if (data.success) {
      showToast('Alerte levée. Le dossier est repassé en traitement.', 'success');
      console.log(`leverAlerte: Alerte du dossier ${dossierId} levée avec succès.`);
    } else {
      showToast('Impossible de lever l\'alerte : ' + (data.message || 'erreur inconnue'), 'danger');
      console.error(`leverAlerte: Erreur: ${data.message}`);
    }
  } catch (error) {
    showToast('Erreur reseau ou technique lors de la levée de l\'alerte.', 'danger');
    console.error('Fetch error:', error);
  }
}
// Click on dossier card to open modal
document.addEventListener('click', (event) => {
  const target = event.target instanceof Element ? event.target : null;
  const card = target ? target.closest('.dossier-clickable') : null;
  if (!card) return;

  const dossierId = card.dataset.dossierId;
  if (!dossierId) return;

  openDossierModal(dossierId);
});

document.addEventListener('keydown', (event) => {
  if (event.key !== 'Enter' && event.key !== ' ') return;
  const target = event.target instanceof Element ? event.target : null;
  const card = target ? target.closest('.dossier-clickable') : null;
  if (!card) return;

  event.preventDefault();
  const dossierId = card.dataset.dossierId;
  if (!dossierId) return;

  openDossierModal(dossierId);
});

/**
 * Renders the hours breakdown table.
 * @param {Array<object>} collaborateurs The list of collaborators with their breakdowns.
 */
function renderVentilation(collaborateurs) {
  const container = document.getElementById('ventilationContainer');
  if (!container) {
    console.warn("renderVentilation: Conteneur de ventilation non trouvé.");
    return;
  }
  container.innerHTML = '';
  console.log("renderVentilation: Rendu de la ventilation.");

  if (!Array.isArray(collaborateurs) || collaborateurs.length === 0) {
    container.innerHTML = '<p>Aucun collaborateur trouv&eacute;.</p>';
    return;
  }

  const rowsSource = collaborateurs.filter((c) => c && typeof c === 'object');

  const roleTotals = rowsSource.reduce((acc, c) => {
    const role = String(c?.role || '').toLowerCase();
    const roleKey = role.includes('assoc') ? 'associe' : (role.includes('chef') || role.includes('cdm') ? 'chef' : 'collaborateur');
    const total = ['janvier', 'fevrier', 'mars', 'avril', 'mai'].reduce((sum, mois) => sum + parseFloat(c?.[mois] || 0), 0);
    acc[roleKey] = (acc[roleKey] || 0) + total;
    acc.total = (acc.total || 0) + total;
    return acc;
  }, {});

  const table = document.createElement('table');
  table.className = 'table table-sm table-bordered';

  const summary = document.createElement('div');
  summary.className = 'd-flex flex-wrap gap-2 mb-2 small';
  summary.innerHTML = `
    <span class="badge bg-primary">Collaborateur: ${(roleTotals.collaborateur || 0).toFixed(2)} h</span>
    <span class="badge bg-secondary">CDM: ${(roleTotals.chef || 0).toFixed(2)} h</span>
    <span class="badge bg-dark">Associe: ${(roleTotals.associe || 0).toFixed(2)} h</span>
    <span class="badge bg-success">Total: ${(roleTotals.total || 0).toFixed(2)} h</span>
  `;
  container.appendChild(summary);

  const thead = document.createElement('thead');
  thead.innerHTML = `
    <tr>
      <th>Collaborateur</th>
      <th>R&ocirc;le</th>
      <th>Janvier</th>
      <th>F&eacute;vrier</th>
      <th>Mars</th>
      <th>Avril</th>
      <th>Mai</th>
    </tr>
  `;
  table.appendChild(thead);

  const tbody = document.createElement('tbody');

  rowsSource.forEach(c => {
    const v = {
      janvier: parseFloat(c.janvier || 0),
      fevrier: parseFloat(c.fevrier || 0),
      mars: parseFloat(c.mars || 0),
      avril: parseFloat(c.avril || 0),
      mai: parseFloat(c.mai || 0),
    };
    const row = document.createElement('tr');
    row.innerHTML = `
      <td>${c.nom} ${c.prenom}</td>
      <td>${c.role}</td>
      <td><input type="number" class="form-control form-control-sm ventilation-input" data-id="${c.id}" data-mois="janvier" value="${v.janvier || 0}" min="0"></td>
      <td><input type="number" class="form-control form-control-sm ventilation-input" data-id="${c.id}" data-mois="fevrier" value="${v.fevrier || 0}" min="0"></td>
      <td><input type="number" class="form-control form-control-sm ventilation-input" data-id="${c.id}" data-mois="mars" value="${v.mars || 0}" min="0"></td>
      <td><input type="number" class="form-control form-control-sm ventilation-input" data-id="${c.id}" data-mois="avril" value="${v.avril || 0}" min="0"></td>
      <td><input type="number" class="form-control form-control-sm ventilation-input" data-id="${c.id}" data-mois="mai" value="${v.mai || 0}" min="0"></td>
    `;
    tbody.appendChild(row);
  });

  table.appendChild(tbody);
  container.appendChild(table);
}

document.getElementById('saveVentilation')?.addEventListener('click', async () => {
  const inputs = document.querySelectorAll('.ventilation-input');
  const data = {};

  inputs.forEach(input => {
    const id = input.dataset.id;
    const mois = input.dataset.mois;
    const val = parseFloat(input.value) || 0;
    if (!data[id]) data[id] = {};
    data[id][mois] = val;
  });
  console.log("saveVentilation: Tentative d'enregistrement de la ventilation.", data);

  try {
    const response = await fetch('ajax_save_ventilation.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        dossier_id: modalContent.dataset.dossierId,
        ventilation: data,
        period: ventilationPeriod?.value || new Date().toISOString().slice(0, 7),
        competence: ventilationCompetence?.value || 'general',
        justification: ventilationJustification?.value || ''
      })
    });
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    const res = await response.json();

    if (res.success) {
      showToast('Ventilation enregistree.', "success");
      if (ventilationJustification) ventilationJustification.value = '';
      openDossierModal(modalContent.dataset.dossierId);
    } else {
      showToast('Erreur lors de l\'enregistrement de la ventilation.', "danger");
      console.error(`saveVentilation: Erreur: ${res.message}`);
    }
  } catch (err) {
    console.error('Erreur réseau :', err);
    showToast('Erreur technique lors de l\'enregistrement.', "danger");
  }
});


// Function to display a custom confirmation dialog
function showCustomConfirm(message, onConfirm, onCancel = null) {
  const confirmModal = document.createElement('div');
  confirmModal.className = 'modal fade show d-block';
  confirmModal.setAttribute('tabindex', '-1');
  confirmModal.setAttribute('aria-modal', 'true');
  confirmModal.setAttribute('role', 'dialog');
  confirmModal.style.backgroundColor = 'rgba(0,0,0,0.5)';

  confirmModal.innerHTML = `
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Confirmation</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
        </div>
        <div class="modal-body">
          <p>${message}</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="button" class="btn btn-primary" id="customConfirmBtn">Confirmer</button>
        </div>
      </div>
    </div>
  `;

  document.body.appendChild(confirmModal);

  const bsModal = new bootstrap.Modal(confirmModal);
  let confirmed = false;
  bsModal.show();

  confirmModal.querySelector('#customConfirmBtn').addEventListener('click', () => {
    confirmed = true;
    onConfirm();
    bsModal.hide();
  });

  confirmModal.addEventListener('hidden.bs.modal', () => {
    confirmModal.remove();
    if (!confirmed && typeof onCancel === 'function') {
      onCancel();
    }
  });
  console.log("showCustomConfirm: Bo de dialogue de confirmation personnalisée affichée.");
}






