<?php
require_once __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h2>Bem-vindo, <?= $nome ?>!</h2>
  <p style="margin-bottom:1rem;">Perfil: <strong><?= ucfirst($perfil) ?></strong></p>
  <p>Utilize o menu de navegação para aceder às funcionalidades disponíveis para o seu perfil.</p>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
