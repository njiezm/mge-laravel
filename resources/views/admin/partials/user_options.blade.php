<option value="">-- Tous --</option>
@foreach($users as $u)
  <option value="{{ $u['id'] }}">{{ $u['prenom'] }} {{ $u['nom'] }} ({{ $u['code_utilisateur'] }})</option>
@endforeach
