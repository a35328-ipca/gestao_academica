<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
requireRole('aluno');

$db     = getDB();
$userId = $_SESSION['user_id'];

$ficha = $db->prepare("SELECT f.*, c.nome AS curso_nome FROM fichas_aluno f JOIN cursos c ON c.id=f.curso_id WHERE f.aluno_id=?");
$ficha->execute([$userId]);
$ficha = $ficha->fetch();

$pedidos = $db->prepare("SELECT pm.*, c.nome AS curso_nome FROM pedidos_matricula pm JOIN cursos c ON c.id=pm.curso_id WHERE pm.aluno_id=? ORDER BY pm.criado_em DESC");
$pedidos->execute([$userId]);
$pedidos = $pedidos->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
  <h2>Estado da Ficha de Aluno</h2>
  <?php if (!$ficha): ?>
    <p>Ainda não preencheu a sua ficha. <a href="/aluno/ficha.php" class="btn btn-primary" style="margin-left:.5rem;">Preencher Ficha</a></p>
  <?php else: ?>
    <table>
      <tr><th>Curso</th><td><?= htmlspecialchars($ficha['curso_nome']) ?></td></tr>
      <tr><th>Estado</th><td><span class="badge badge-<?= $ficha['estado'] ?>"><?= ucfirst($ficha['estado']) ?></span></td></tr>
      <tr><th>Submetida em</th><td><?= $ficha['submetida_em'] ? date('d/m/Y H:i', strtotime($ficha['submetida_em'])) : '—' ?></td></tr>
      <tr><th>Validada em</th><td><?= $ficha['validada_em'] ? date('d/m/Y H:i', strtotime($ficha['validada_em'])) : '—' ?></td></tr>
      <tr><th>Observações</th><td><?= htmlspecialchars($ficha['observacoes'] ?? '—') ?></td></tr>
    </table>
  <?php endif; ?>
</div>

<div class="card">
  <h2>Estado das Matrículas</h2>
  <?php if (!$pedidos): ?>
    <p>Sem pedidos de matrícula.</p>
  <?php else: ?>
    <table>
      <thead><tr><th>Curso</th><th>Estado</th><th>Data</th><th>Observações</th></tr></thead>
      <tbody>
      <?php foreach ($pedidos as $p): ?>
        <tr>
          <td><?= htmlspecialchars($p['curso_nome']) ?></td>
          <td><span class="badge badge-<?= $p['estado'] ?>"><?= ucfirst($p['estado']) ?></span></td>
          <td><?= date('d/m/Y H:i', strtotime($p['criado_em'])) ?></td>
          <td><?= htmlspecialchars($p['observacoes'] ?? '—') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
