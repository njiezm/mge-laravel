(function () {
  const config = window.AdminDashboardConfig || {};
  const routes = config.routes || {};
  const csrfToken = config.csrfToken || '';
  const toastContainer = document.getElementById('toastContainer');
  const usersTableBody = document.getElementById('usersTableBody');
  const userFilter = document.getElementById('user_filter');
  const showToast = (message, type = 'success') => {
    if (!toastContainer) return;
    const bg = type === 'error' ? 'danger' : (type === 'warning' ? 'warning' : 'success');
    const el = document.createElement('div');
    el.className = `toast align-items-center text-bg-${bg} border-0 mb-2`;
    el.role = 'alert';
    el.innerHTML = `<div class="d-flex"><div class="toast-body">${message}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
    toastContainer.appendChild(el);
    const t = new bootstrap.Toast(el, { delay: 4500 });
    t.show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
  };

  const refreshUsersFragments = async (payload = null) => {
    let data = payload;
    if (!data || !data.users_rows_html) {
      const res = await fetch(routes.usersFragment, { headers: { 'Accept': 'application/json' } });
      data = await res.json();
    }
    if (data.users_rows_html && usersTableBody) usersTableBody.innerHTML = data.users_rows_html;
    if (data.users_select_html && userFilter) userFilter.innerHTML = data.users_select_html;
    bindEditButtons();
  };

  const bindEditButtons = () => {
    const editId = document.getElementById('edit-id');
    const editCode = document.getElementById('edit-code');
    const editPrenom = document.getElementById('edit-prenom');
    const editNom = document.getElementById('edit-nom');
    const editEmail = document.getElementById('edit-email');
    const editRole = document.getElementById('edit-role');
    const editPassword = document.getElementById('edit-password');

    document.querySelectorAll('.editUserBtn').forEach(btn => {
      btn.onclick = () => {
        editId.value = btn.dataset.id;
        editCode.value = btn.dataset.code;
        editPrenom.value = btn.dataset.prenom;
        editNom.value = btn.dataset.nom;
        editEmail.value = btn.dataset.email;
        editRole.value = btn.dataset.role;
        editPassword.value = '';
      };
    });
  };

  bindEditButtons();

  document.addEventListener('submit', async (e) => {
    const form = e.target;
    if (!form.classList.contains('js-async-form')) return;
    e.preventDefault();

    const confirmMsg = form.dataset.confirm;
    if (confirmMsg && !window.confirm(confirmMsg)) return;

    const formData = new FormData(form);
    formData.append('_token', csrfToken);

    const res = await fetch(form.action, {
      method: 'POST',
      headers: { 'Accept': 'application/json' },
      body: formData,
    });
    const json = await res.json();

    if (!res.ok || !json.success) {
      showToast(json.message || json.error || 'Action impossible.', 'error');
      return;
    }

    showToast(json.message || 'Action enregistrÃ©e.');
    if (json.notice) showToast(json.notice, 'warning');

    if (form.dataset.refreshUsers === '1') {
      await refreshUsersFragments(json);
      if (form.closest('#editUserModal')) bootstrap.Modal.getInstance(document.getElementById('editUserModal'))?.hide();
      if (form.closest('#create-account')) form.reset();
    }
  });

  const exportTypeSelect = document.getElementById('export_type');
  const userSelectContainer = document.getElementById('userSelectContainer');
  exportTypeSelect?.addEventListener('change', () => {
    const show = exportTypeSelect.value === 'planning_par_personne';
    if (userSelectContainer) {
      userSelectContainer.style.display = show ? 'block' : 'none';
      const select = userSelectContainer.querySelector('select');
      if (select) select.required = show;
    }
  });

  const dropzone = document.getElementById('dropzone');
  const fileInput = document.getElementById('fileInput');
  const filePreview = document.getElementById('filePreview');
  const openValidationBtn = document.getElementById('openValidationBtn');
  const validationModalEl = document.getElementById('validationModal');
  const validationModal = validationModalEl ? new bootstrap.Modal(validationModalEl) : null;
  const overwriteConfirmModalEl = document.getElementById('overwriteConfirmModal');
  const overwriteConfirmModal = overwriteConfirmModalEl ? new bootstrap.Modal(overwriteConfirmModalEl, { backdrop: 'static', keyboard: false }) : null;
  const fiscalYearModalEl = document.getElementById('fiscalYearModal');
  const chooseCurrentYearBtn = document.getElementById('chooseCurrentYearBtn');
  const chooseNextYearBtn = document.getElementById('chooseNextYearBtn');
  const fiscalYearModal = fiscalYearModalEl ? new bootstrap.Modal(fiscalYearModalEl, { backdrop: 'static', keyboard: false }) : null;
  const validationTableBody = document.getElementById('validationTableBody');
  const missingUsersList = document.getElementById('missingUsers');
  const createMissingBtn = document.getElementById('createMissingBtn');
  const confirmImportBtn = document.getElementById('confirmImportBtn');
  const overwriteWarning = document.getElementById('overwriteWarning');
  const exportBeforeOverwriteBtn = document.getElementById('exportBeforeOverwriteBtn');
  const confirmOverwriteFromPopupBtn = document.getElementById('confirmOverwriteFromPopupBtn');

  let dossiersGlobaux = [];
  let utilisateursExistants = new Set();
  let selectedTargetYear = null;
  let pendingFiscalYearResolver = null;
  let existingCount = 0;

  const setImportLoading = (isLoading) => {
    const buttonMap = [
      [confirmImportBtn, 'Valider l\'importation'],
      [confirmOverwriteFromPopupBtn, 'Confirmer l\'Ã©crasement'],
    ];

    buttonMap.forEach(([btn, label]) => {
      if (!btn) return;
      if (!btn.dataset.originalHtml) btn.dataset.originalHtml = btn.innerHTML;
      btn.disabled = isLoading;
      btn.innerHTML = isLoading
        ? `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>${label}...`
        : btn.dataset.originalHtml;
    });

    if (exportBeforeOverwriteBtn) exportBeforeOverwriteBtn.disabled = isLoading;
    if (openValidationBtn) openValidationBtn.disabled = isLoading;
    if (createMissingBtn) createMissingBtn.disabled = isLoading;
  };

  dropzone?.addEventListener('click', () => fileInput.click());
  fileInput?.addEventListener('change', () => handleFile(fileInput.files[0]));
  dropzone?.addEventListener('dragover', e => { e.preventDefault(); });
  dropzone?.addEventListener('drop', e => { e.preventDefault(); handleFile(e.dataTransfer.files[0]); });

  function parseExcelDate(value) {
    if (!value) return null;
    if (Object.prototype.toString.call(value) === '[object Date]' && !isNaN(value)) return value.toISOString().split('T')[0];
    if (typeof value === 'number' && value > 40000 && value < 60000) {
      const date = new Date(Date.UTC(1899, 11, 30)); date.setDate(date.getDate() + value); return date.toISOString().split('T')[0];
    }
    if (typeof value === 'string') { const d = new Date(value); if (!isNaN(d)) return d.toISOString().split('T')[0]; }
    return null;
  }

  function askTargetFiscalYear() {
    const currentYear = new Date().getFullYear();
    const nextYear = currentYear + 1;
    if (!fiscalYearModal || !chooseCurrentYearBtn || !chooseNextYearBtn) return Promise.resolve(currentYear);
    chooseCurrentYearBtn.textContent = `Calcul ${currentYear}`;
    chooseNextYearBtn.textContent = `Calcul ${nextYear}`;
    return new Promise(resolve => {
      pendingFiscalYearResolver = resolve;
      fiscalYearModal.show();
    });
  }

  chooseCurrentYearBtn?.addEventListener('click', () => {
    const year = new Date().getFullYear();
    selectedTargetYear = year;
    fiscalYearModal?.hide();
    if (pendingFiscalYearResolver) pendingFiscalYearResolver(year);
    pendingFiscalYearResolver = null;
  });

  chooseNextYearBtn?.addEventListener('click', () => {
    const year = new Date().getFullYear() + 1;
    selectedTargetYear = year;
    fiscalYearModal?.hide();
    if (pendingFiscalYearResolver) pendingFiscalYearResolver(year);
    pendingFiscalYearResolver = null;
  });

  async function handleFile(file) {
    if (!file) return;
    const ext = file.name.slice(file.name.lastIndexOf('.')).toLowerCase();
    if (!['.csv', '.xlsx'].includes(ext)) { showToast('Seuls les fichiers .csv et .xlsx sont acceptÃ©s.', 'error'); return; }

    if (filePreview) {
      filePreview.style.display = 'block';
      filePreview.innerHTML = `<strong>Fichier sÃ©lectionnÃ© :</strong> ${file.name} (${(file.size / 1024).toFixed(1)} Ko)`;
    }

    const reader = new FileReader();
    reader.onload = async e => {
      const data = new Uint8Array(e.target.result);
      const workbook = XLSX.read(data, { type: 'array' });
      const sheet = workbook.Sheets[workbook.SheetNames[1] || workbook.SheetNames[0]];
      const rows = XLSX.utils.sheet_to_json(sheet, { defval: '' });

      dossiersGlobaux = rows.map(row => ({
        code_dossier: row['NÂ°Dossier'] || row['NoDossier'] || '',
        societe: row['Nom SociÃ©tÃ©'] || row['Nom Societe'] || '',
        groupe: row['Groupe'] || '',
        collab: (row['Collab 2025'] || '').trim() || null,
        cdm: (row['CDM 2025'] || '').trim() || null,
        associe: (row['AssociÃ©'] || row['Associe'] || '').trim() || null,
        heure_prevues: parseFloat(row[' Temps prÃ©vi collab hors conso 2025 '] || row['Heures'] || 0) || 0,
        date_reception: parseExcelDate(row['Date rÃ©ception'] || row['Date reception']),
        critere: row['CritÃ¨re'] || row['Critere'] || 0,
      })).filter(d => d.code_dossier && d.code_dossier.trim() !== '');

      selectedTargetYear = await askTargetFiscalYear();
      const response = await fetch(routes.importDossiers, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ dossiers: dossiersGlobaux, action: 'preview', target_year: selectedTargetYear }),
      });
      const responseData = await response.json();
      if (!responseData.success && responseData.error) { showToast('Erreur: ' + responseData.error, 'error'); return; }
      dossiersGlobaux = responseData.dossiers || dossiersGlobaux;
      existingCount = responseData.existing_count || 0;

      await fetchExistingUsers();
      fillValidationTable(dossiersGlobaux);
      detectMissingUsers(dossiersGlobaux);
      if (openValidationBtn) openValidationBtn.style.display = 'inline-block';
      if (overwriteWarning) overwriteWarning.style.display = existingCount > 0 ? 'block' : 'none';
      if (existingCount > 0) showToast(`Attention: ${existingCount} dossiers existants seront Ã©crasÃ©s aprÃ¨s confirmation.`, 'warning');
    };
    reader.readAsArrayBuffer(file);
  }

  async function fetchExistingUsers() {
    const res = await fetch(routes.activeUsers);
    const json = await res.json();
    utilisateursExistants = new Set(json.users || []);
  }

  function fillValidationTable(dossiers) {
    if (!validationTableBody) return;
    validationTableBody.innerHTML = dossiers.map(d => `<tr><td>${d.code_dossier}</td><td>${d.societe || ''}</td><td>${d.groupe || ''}</td><td>${d.collab || ''}</td><td>${d.cdm || ''}</td><td>${d.associe || ''}</td><td>${d.date_reception || ''}</td><td>${d.heure_prevues || 0}</td><td>${d.date_debut || ''}</td><td>${d.date_fin || ''}</td><td>${d.ventilation_resume || ''}</td></tr>`).join('');
  }

  function detectMissingUsers(dossiers) {
    const missing = new Set();
    dossiers.forEach(d => ['collab', 'cdm', 'associe'].forEach(r => { const code = d[r]; if (code && !utilisateursExistants.has(code)) missing.add(code); }));

    if (!missingUsersList) return;
    missingUsersList.innerHTML = '';
    if (missing.size === 0) {
      missingUsersList.innerHTML = `<li class="list-group-item list-group-item-success">Aucun utilisateur manquant dÃ©tectÃ©.</li>`;
      if (createMissingBtn) createMissingBtn.style.display = 'none';
      return;
    }

    missing.forEach(code => { const li = document.createElement('li'); li.textContent = code; li.classList.add('list-group-item'); missingUsersList.appendChild(li); });
    if (createMissingBtn) createMissingBtn.style.display = 'inline-block';
  }

  function rolePriority(role) {
    if (role === 'associe') return 3;
    if (role === 'chef') return 2;
    return 1;
  }

  function buildRolesByUserFromDossiers(dossiers) {
    const map = {};
    const register = (code, role) => {
      const c = (code || '').trim();
      if (!c) return;
      if (!map[c] || rolePriority(role) > rolePriority(map[c])) map[c] = role;
    };
    dossiers.forEach(d => {
      register(d.collab, 'collaborateur');
      register(d.cdm, 'chef');
      register(d.associe, 'associe');
    });
    return map;
  }

  createMissingBtn?.addEventListener('click', async () => {
    const usersToCreate = Array.from(missingUsersList.querySelectorAll('li')).map(li => li.textContent).filter(v => !v.includes('Aucun utilisateur'));
    if (usersToCreate.length === 0) return showToast('Aucun utilisateur Ã  crÃ©er.', 'warning');
    if (!window.confirm(`CrÃ©er ${usersToCreate.length} utilisateurs manquants ?`)) return;

    const res = await fetch(routes.createMissingUsers, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ users: usersToCreate, roles_by_user: buildRolesByUserFromDossiers(dossiersGlobaux) }),
    });
    const json = await res.json();
    if (!json.success) return showToast(json.error || 'Erreur de crÃ©ation des utilisateurs.', 'error');
    showToast(json.message || 'OpÃ©ration terminÃ©e.');
    showToast("Mots de passe temporaires crÃ©Ã©s. Les utilisateurs recevront les informations de connexion par email.", 'warning');

    usersToCreate.forEach(code => utilisateursExistants.add(code));
    detectMissingUsers(dossiersGlobaux);
    await refreshUsersFragments(json);
  });

  openValidationBtn?.addEventListener('click', () => validationModal?.show());

  const runImport = async (overwrite = false) => {
    const targetYear = selectedTargetYear || new Date().getFullYear();
    setImportLoading(true);
    try {
      const res = await fetch(routes.importDossiers, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ dossiers: dossiersGlobaux, action: 'import', overwrite, target_year: targetYear }),
      });
      const json = await res.json();

      if (res.status === 409 && json.requires_overwrite_confirmation) {
        if (overwriteWarning) overwriteWarning.style.display = 'block';
        showToast(json.message || 'Confirmation d\'écrasement requise.', 'warning');
        return;
      }

      if (!json.success) {
        showToast(json.error || json.message || 'Import impossible.', 'error');
        return;
      }

      if (overwriteWarning) overwriteWarning.style.display = 'none';
      showToast(json.message || 'Import terminé.');
      validationModal?.hide();
      overwriteConfirmModal?.hide();
    } finally {
      setImportLoading(false);
    }
  };

  confirmImportBtn?.addEventListener('click', async () => {
    if (existingCount > 0) {
      overwriteConfirmModal?.show();
      return;
    }
    await runImport(false);
  });

  confirmOverwriteFromPopupBtn?.addEventListener('click', async () => {
    await runImport(true);
  });

  exportBeforeOverwriteBtn?.addEventListener('click', () => {
    document.getElementById('exportBackupForm')?.submit();
    showToast("Export de sauvegarde lancÃ© dans un nouvel onglet.", 'warning');
  });

  document.addEventListener('submit', async (e) => {
    const form = e.target;
    if (!form.classList.contains('js-async-form')) return;
    e.preventDefault();

    const confirmMsg = form.dataset.confirm;
    if (confirmMsg && !window.confirm(confirmMsg)) return;

    const formData = new FormData(form);
    formData.append('_token', csrfToken);

    const res = await fetch(form.action, {
      method: 'POST',
      headers: { 'Accept': 'application/json' },
      body: formData,
    });
    const json = await res.json();

    if (!res.ok || !json.success) {
      showToast(json.message || json.error || 'Action impossible.', 'error');
      return;
    }

    showToast(json.message || 'Action enregistrÃ©e.');
    if (json.notice) showToast(json.notice, 'warning');

    if (form.dataset.refreshUsers === '1') {
      await refreshUsersFragments(json);
      if (form.closest('#editUserModal')) bootstrap.Modal.getInstance(document.getElementById('editUserModal'))?.hide();
      if (form.closest('#create-account')) form.reset();
    }
  });

})();

