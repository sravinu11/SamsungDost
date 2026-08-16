<?php
require_once __DIR__ . "/db.php";
if (session_status() === PHP_SESSION_NONE) session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    if ($username === '' || $password === '') {
        $error = 'Enter both username and password.';
    } else {
        $pdo = get_pdo();
        $stmt = $pdo->prepare("SELECT * FROM dost_users WHERE UPPER(username) = UPPER(:u)");
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['display_name'] = $user['display_name'];
            header("Location: index.php");
            exit;
        }
        $error = 'Incorrect username or password.';
    }
}
if (!empty($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>OJT Pulse — Sign In</title>
<style>
  :root {
    color-scheme: light;
    --page:        #f9f9f7;
    --text-primary:#0b0b0b;
    --text-secondary:#52514e;
    --text-muted:  #898781;
    --accent:      #2a78d6;
    --accent2:     #eb6834;
    --slot3:       #1baf7a;
    --critical:    #d03b3b;
    --glass-bg:    rgba(255,255,255,0.55);
    --glass-border:rgba(255,255,255,0.45);
  }
  @media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) {
      color-scheme: dark;
      --page:        #0d0d0d;
      --text-primary:#ffffff;
      --text-secondary:#c3c2b7;
      --text-muted:  #898781;
      --accent:      #3987e5;
      --accent2:     #d95926;
      --slot3:       #199e70;
      --glass-bg:    rgba(26,26,25,0.45);
      --glass-border:rgba(255,255,255,0.12);
    }
  }
  :root[data-theme="dark"] {
    color-scheme: dark;
    --page:        #0d0d0d;
    --text-primary:#ffffff;
    --text-secondary:#c3c2b7;
    --text-muted:  #898781;
    --accent:      #3987e5;
    --accent2:     #d95926;
    --slot3:       #199e70;
    --glass-bg:    rgba(26,26,25,0.45);
    --glass-border:rgba(255,255,255,0.12);
  }
  * { box-sizing: border-box; }
  html, body { height: 100%; }
  body {
    margin: 0;
    background: var(--page);
    color: var(--text-primary);
    font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
    display: flex; align-items: center; justify-content: center;
    position: relative; overflow: hidden;
  }
  .mesh {
    position: fixed; inset: -20%; z-index: 0; filter: blur(60px); opacity: 0.55;
    background:
      radial-gradient(circle at 20% 25%, var(--accent) 0%, transparent 45%),
      radial-gradient(circle at 80% 20%, var(--accent2) 0%, transparent 45%),
      radial-gradient(circle at 50% 80%, var(--slot3) 0%, transparent 45%);
    animation: drift 22s ease-in-out infinite alternate;
  }
  @keyframes drift {
    0%   { transform: translate(0, 0) scale(1); }
    100% { transform: translate(-3%, 3%) scale(1.08); }
  }
  @media (prefers-reduced-motion: reduce) { .mesh { animation: none; } }

  .card {
    position: relative; z-index: 1; width: 420px; padding: 34px 32px 30px;
    background: var(--glass-bg); backdrop-filter: blur(22px) saturate(160%);
    -webkit-backdrop-filter: blur(22px) saturate(160%);
    border: 1px solid var(--glass-border); border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.18), inset 0 1px 0 rgba(255,255,255,0.25);
  }
  .eyebrow { font-size: 11px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: var(--accent); margin: 0 0 6px; }
  h1 { font-size: 24px; font-weight: 750; margin: 0 0 26px; letter-spacing: -0.01em; }
  label { display: block; font-size: 11px; font-weight: 650; letter-spacing: 0.05em; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px; }
  .field { margin-bottom: 16px; }
  input {
    width: 100%; font: inherit; font-size: 14px; color: var(--text-primary);
    background: rgba(255,255,255,0.35); border: 1px solid var(--glass-border); border-radius: 9px;
    padding: 10px 12px;
  }
  input::placeholder { color: var(--text-muted); }
  input:focus-visible { outline: 2px solid var(--accent); outline-offset: 1px; }
  button {
    width: 100%; margin-top: 6px; font: inherit; font-size: 14px; font-weight: 650; color: #fff;
    background: var(--accent); border: none; border-radius: 9px; padding: 11px 12px; cursor: pointer;
    box-shadow: 0 8px 20px rgba(42,120,214,0.35);
  }
  button:hover { filter: brightness(1.06); }
  .error { color: var(--critical); font-size: 12.5px; margin: -8px 0 16px; }

  .logo-row { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
  .logo-chip {
    background: #ffffff; border-radius: 11px; padding: 9px 14px; display: inline-flex; align-items: center;
    border: 1px solid rgba(0,0,0,0.06); box-shadow: 0 6px 18px rgba(0,0,0,0.12);
  }
  .logo-chip img { display: block; height: 40px; width: auto; }
  .logo-chip.dost img { height: 52px; }
</style>
</head>
<body>
  <div class="mesh"></div>
  <div class="card">
    <div class="logo-row">
      <span class="logo-chip"><img src="assets/quess-logo.png" alt="Quess"></span>
      <span class="logo-chip dost"><img src="assets/samsung-dost-logo.jpg" alt="Samsung Dost"></span>
    </div>
    <p class="eyebrow">Quess &middot; Workforce Onboarding</p>
    <h1>Samsung Dost</h1>
    <form method="post" autocomplete="off">
      <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <div class="field">
        <label for="username">Username</label>
        <input id="username" name="username" type="text" placeholder="SAMSUNGADMIN / ESSCI / TSSC" required autofocus>
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" placeholder="••••••••" required>
      </div>
      <button type="submit">Sign in</button>
    </form>
  </div>
</body>
</html>
