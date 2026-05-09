<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>AbsensiKu – Login</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
  <style>
    *{margin:0;padding:0;box-sizing:border-box}
    body{font-family:'Inter',system-ui,sans-serif;background:#F5F5F5;min-height:100vh;display:flex;align-items:center;justify-content:center}
    .login-card{background:#fff;border-radius:16px;box-shadow:0 10px 40px rgba(0,0,0,.08);padding:2.5rem 2rem;width:100%;max-width:380px;text-align:center}
    .login-card h2{font-size:1.8rem;color:#4A90E2;margin-bottom:1.5rem}
    .form-group{margin-bottom:1.2rem;text-align:left}
    .form-group label{font-weight:500;margin-bottom:.3rem;display:block;color:#555}
    .form-group input{width:100%;padding:.7rem;border:1px solid #DDD;border-radius:8px;font-size:1rem}
    .form-group input:focus{outline:none;border-color:#4A90E2;box-shadow:0 0 0 3px rgba(74,144,226,.15)}
    .btn-login{width:100%;padding:.75rem;background:#4A90E2;color:#fff;border:none;border-radius:8px;font-weight:600;font-size:1rem;cursor:pointer;margin-top:.5rem;display:flex;align-items:center;justify-content:center;gap:.5rem}
    .btn-login:hover{background:#357ABD}
    .error-msg{background:#FEF2F2;border:1px solid #FECACA;color:#E74C3C;font-size:.85rem;padding:.6rem .8rem;border-radius:6px;margin-bottom:.8rem;text-align:left;display:flex;align-items:center;gap:.4rem}
    .hint{font-size:.8rem;color:#AAA;margin-top:1.2rem}
  </style>
</head>
<body>
  <div class="login-card">
    <h2><i class="fas fa-user-lock"></i> AbsensiKu</h2>

    @if($errors->any())
      <div class="error-msg"><i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login.post') }}">
      @csrf
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username"
               value="{{ old('username') }}"
               placeholder="admin / siswa" autocomplete="username" autofocus>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password"
               placeholder="Masukkan password" autocomplete="current-password">
      </div>
      <button type="submit" class="btn-login">
        <i class="fas fa-sign-in-alt"></i> Masuk
      </button>
    </form>

    <p class="hint">Demo: <strong>admin / admin</strong> &nbsp;|&nbsp; <strong>siswa / siswa</strong></p>
  </div>
</body>
</html>
