<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Première connexion - MG EXPERTISE</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body { font-family:'Montserrat',sans-serif; background:linear-gradient(to right,#003366,#005599); min-height:100vh; display:flex; align-items:center; justify-content:center; }
    .card-box { width:100%; max-width:460px; background:#fff; border-radius:12px; padding:28px; box-shadow:0 10px 25px rgba(0,0,0,.15); }
  </style>
</head>
<body>
  <div class="card-box">
    <div class="text-center mb-3"><img src="{{ asset('assets/images/logo-MGB.png') }}" alt="MG" style="height:52px;width:auto;"></div>
    <h1 class="h4 text-center text-primary mb-2">Première connexion</h1>
    <p class="text-center text-muted mb-4">Vous devez définir un nouveau mot de passe pour continuer.</p>

    @if ($errors->any())
      <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('password.change.submit') }}">
      @csrf
      <div class="mb-3">
        <label class="form-label">Nouveau mot de passe</label>
        <input type="password" name="password" class="form-control" required minlength="8" />
      </div>
      <div class="mb-3">
        <label class="form-label">Confirmer le mot de passe</label>
        <input type="password" name="password_confirmation" class="form-control" required minlength="8" />
      </div>
      <button type="submit" class="btn btn-primary w-100">Enregistrer et continuer</button>
    </form>
  </div>
</body>
</html>
