<?php
require_once __DIR__ . '/../config/session.php';
checkSession();
$perfil = $_SESSION['perfil'];
$nome   = htmlspecialchars($_SESSION['nome']);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sistema Académico — IPCA</title>
  <link rel="stylesheet" href="/gestao_academica/assets/style.css">
</head>
<body>
<nav>
  <span class="brand">🎓 IPCA — Gestão Académica</span>
  <a href="/gestao_academica/index.php">Início</a>
  <?php if ($perfil === 'aluno'): ?>
    <a href="/gestao_academica/aluno/ficha.php">Ficha</a>
    <a href="/gestao_academica/aluno/matricula.php">Matrícula</a>
    <a href="/gestao_academica/aluno/estado.php">Estado</a>
  <?php elseif ($perfil === 'funcionario'): ?>
    <a href="/gestao_academica/funcionario/pedidos.php">Pedidos</a>
    <a href="/gestao_academica/funcionario/pautas.php">Pautas</a>
  <?php elseif ($perfil === 'gestor'): ?>
    <a href="/gestao_academica/gestor/cursos.php">Cursos</a>
    <a href="/gestao_academica/gestor/ucs.php">UCs</a>
    <a href="/gestao_academica/gestor/fichas.php">Fichas</a>
  <?php endif; ?>
  <a href="/gestao_academica/auth/logout.php">Sair (<?= $nome ?>)</a>
</nav>
<div class="container">
