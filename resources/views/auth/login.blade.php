<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion - MG EXPERTISE</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(to right, #003366, #005599);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
        }
        .login-title {
            font-size: 1.8rem;
            color: #003366;
            font-weight: 700;
            text-align: center;
            margin-bottom: 10px;
        }
        .login-subtitle {
            font-size: 0.95rem;
            color: #666;
            text-align: center;
            margin-bottom: 20px;
        }
        .form-label { font-weight: 600; color: #003366; }
        .btn-primary { background-color: #003366; border: none; font-weight: 600; }
        .btn-primary:hover { background-color: #005599; }
        .footer { text-align: center; font-size: 0.8rem; margin-top: 20px; color: #888; }
    </style>
</head>
<body>
<div class="login-container">
    <img src="{{ asset('assets/images/logo-MGB.png') }}" alt="Logo MG EXPERTISE" style="display:block;margin:0 auto;height:50px;width:auto;"/>
    <div class="login-title">MG PLANNER</div>
    <div class="login-subtitle">Connexion a l'espace securise</div>

    @if ($errors->any())
        <div class="alert alert-danger text-center">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login.submit') }}" autocomplete="off">
        @csrf
        <div class="mb-3">
            <label for="email" class="form-label">Adresse e-mail</label>
            <input type="email" class="form-control" id="email" name="email" required value="{{ old('email') }}" autofocus>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Mot de passe</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Se connecter</button>
    </form>

    <div class="footer">&copy; {{ now()->year }} MG EXPERTISE - Tous droits reserves.</div>
</div>
</body>
</html>
