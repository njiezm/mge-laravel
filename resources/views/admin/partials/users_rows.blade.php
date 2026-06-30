@foreach ($users as $u)
  <tr>
    <td>{{ $u['code_utilisateur'] }}</td>
    <td>{{ $u['prenom'] }}</td>
    <td>{{ $u['nom'] }}</td>
    <td>{{ $u['email'] }}</td>
    <td>{{ ucfirst($u['role']) }}</td>
    <td>{!! $u['actif'] ? '<span class="text-success">Actif</span>' : '<span class="text-danger">Inactif</span>' !!}</td>
    <td class="d-flex gap-2 flex-wrap">
      <form action="{{ url('admin_toggle_user.php') }}" method="POST" class="js-async-form" data-refresh-users="1">
        <input type="hidden" name="id" value="{{ $u['id'] }}">
        <button class="btn btn-sm {{ $u['actif'] ? 'btn-danger' : 'btn-success' }}">{{ $u['actif'] ? 'Désactiver' : 'Activer' }}</button>
      </form>
      <button
        type="button"
        class="btn btn-sm btn-warning editUserBtn"
        data-id="{{ $u['id'] }}"
        data-code="{{ $u['code_utilisateur'] }}"
        data-prenom="{{ $u['prenom'] }}"
        data-nom="{{ $u['nom'] }}"
        data-email="{{ $u['email'] }}"
        data-role="{{ $u['role'] }}"
        data-bs-toggle="modal"
        data-bs-target="#editUserModal"
      >Modifier</button>
      <form action="{{ url('admin_delete_user.php') }}" method="POST" class="js-async-form" data-confirm="Confirmer la suppression ?" data-refresh-users="1">
        <input type="hidden" name="id" value="{{ $u['id'] }}">
        <button class="btn btn-sm btn-outline-danger">Supprimer</button>
      </form>
    </td>
  </tr>
@endforeach
