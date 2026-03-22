<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if ($email && $pass) {
        $stmt = getDB()->prepare("SELECT * FROM utilizadores WHERE email = ? AND ativo = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($pass, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']       = $user['id'];
            $_SESSION['nome']          = $user['nome'];
            $_SESSION['perfil']        = $user['perfil'];
            $_SESSION['last_activity'] = time();
            header('Location: /gestao_academica/index.php');
            exit;
        }
    }
    $erro = 'Email ou palavra-passe incorretos.';
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login — IPCA</title>
  <link rel="stylesheet" href="/gestao_academica/assets/style.css">
</head>
<body class="login-page">
<div class="login-box">
  <h1>🎓 IPCA — Gestão Académica</h1>
  <?php if ($erro): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
  <?php endif; ?>
  <?php if (isset($_GET['timeout'])): ?>
    <div class="alert alert-warning">Sessão expirada. Por favor inicie sessão novamente.</div>
  <?php endif; ?>
  <form method="POST">
    <label for="email">Email</label>
    <input type="email" id="email" name="email" required autofocus>
    <label for="password">Palavra-passe</label>
    <input type="password" id="password" name="password" required>
    <button type="submit">Entrar</button>
  </form>
  <p class="sub">Instituto Politécnico do Cávado e do Ave</p>
</div>
</body>
</html>
