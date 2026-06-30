@php
  $usersList = $usersList ?? ($users_list ?? []);
@endphp

<div class="tab-pane fade" id="view-calendrier" role="tabpanel" aria-labelledby="calendrier-tab" tabindex="0">
  <div id="filters" style="margin-bottom: 1em;">
    <div class="calendar-wrapper">
      <aside class="calendar-filters">
        <fieldset>
          <legend>Statuts des dossiers</legend>
          <label><input type="checkbox" class="status-filter" value="non_traite" checked> Reçu non traité</label><br>
          <label><input type="checkbox" class="status-filter" value="en_cours_traitement" checked> En cours de traitement</label><br>
          <label><input type="checkbox" class="status-filter" value="revu_en_cours" checked> Revu en cours</label><br>
          <label><input type="checkbox" class="status-filter" value="revu_associe" checked> Revu associé</label><br>
          <label><input type="checkbox" class="status-filter" value="liasse_envoyee" checked> Liasse envoyée</label><br>
          <label><input type="checkbox" class="status-filter" value="declarer_en_retard" checked> En retard</label>
        </fieldset>

        <label for="user-filter" style="margin-top: 1rem; display: block;">Utilisateurs</label>
        <select id="user-filter" class="form-select">
          <option value="moi" selected>Moi</option>
          <option value="tous">Tout le monde</option>
          @foreach($usersList as $user)
            <option value="{{ $user['id'] }}">{{ trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')) }}</option>
          @endforeach
        </select>

        <button id="apply-filters" class="btn btn-primary mt-3">Appliquer</button>
      </aside>

      <div id="calendar"></div>
    </div>
  </div>
</div>
