<?php
// seed.php — Gerar hashes reais e atualizar utilizadores de teste
// APAGAR ESTE FICHEIRO após usar!
require_once __DIR__ . '/config/db.php';

$users = [
    ['gestor@ipca.pt',       'password123'],
    ['funcionario@ipca.pt',  'password123'],
    ['aluno1@ipca.pt',       'password123'],
    ['aluno2@ipca.pt',       'password123'],
];

$stmt = getDB()->prepare("UPDATE utilizadores SET password_hash = ? WHERE email = ?");
$ok = 0;
foreach ($users as [$email, $pass]) {
    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $stmt->execute([$hash, $email]);
    if ($stmt->rowCount()) $ok++;
}
?>
<!DOCTYPE html>
<html lang="pt">
<head><meta charset="UTF-8"><title>Seed</title>
<style>body{font-family:Arial,sans-serif;max-width:500px;margin:3rem auto;padding:1rem}
.ok{background:#d1f2e8;color:#0b6e4f;padding:1rem;border-radius:6px;margin-bottom:1rem}
.warn{background:#fff3cd;color:#856404;padding:.75rem;border-radius:6px;font-size:.88rem}</style>
</head>
<body>
<div class="ok">✅ <?= $ok ?> utilizador(es) atualizados com hashes bcrypt reais.</div>
<div class="warn">⚠️ Apague este ficheiro agora! <code>C:\xampp\htdocs\gestao_academica\seed.php</code></div>
<p style="margin-top:1rem"><a href="/gestao_academica/auth/login.php">→ Ir para o Login</a></p>
</body>
</html>
