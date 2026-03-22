<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
requireRole('gestor');

$db     = getDB();
$userId = $_SESSION['user_id'];
$msg    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fichaId = (int)($_POST['ficha_id'] ?? 0);
    $acao    = $_POST['acao'] ?? '';
    $obs     = trim($_POST['observacoes'] ?? '');
    if ($fichaId && in_array($acao, ['aprovada','rejeitada'])) {
        $db->prepare("UPDATE fichas_aluno SET estado=?, validado_por=?, validada_em=NOW(), observacoes=? WHERE id=? AND estado='submetida'")
           ->execute([$acao, $userId, $obs ?: null, $fichaId]);
        $msg = 'Ficha ' . ($acao === 'aprovada' ? 'aprovada' : 'rejeitada') . ' com sucesso.';
    }
}

$submetidas = $db->query("
    SELECT f.*, u.nome AS aluno_nome, u.email, c.nome AS curso_nome
    FROM fichas_aluno f
    JOIN utilizadores u ON u.id = f.aluno_id
    JOIN cursos c ON c.id = f.curso_id
    WHERE f.estado = 'submetida'
    ORDER BY f.submetida_em")->fetchAll();

$historico = $db->query("
    SELECT f.*, u.nome AS aluno_nome, c.nome AS curso_nome, g.nome AS gestor_nome
    FROM fichas_aluno f
    JOIN utilizadores u ON u.id = f.aluno_id
    JOIN cursos c ON c.id = f.curso_id
    LEFT JOIN utilizadores g ON g.id = f.validado_por
    WHERE f.estado IN ('aprovada','rejeitada')
    ORDER BY f.validada_em DESC LIMIT 30")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
  <h2>Fichas de Aluno — Pendentes de Validação</h2>
  <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if (!$submetidas): ?>
    <p>Não há fichas pendentes.</p>
  <?php else: ?>
    <?php foreach ($submetidas as $f): ?>
    <div style="border:1px solid #e0e4ea;border-radius:6px;padding:1rem;margin-bottom:1rem;">
      <p><strong><?= htmlspecialchars($f['aluno_nome']) ?></strong> — <?= htmlspecialchars($f['email']) ?></p>
      <p style="font-size:.85rem;color:#555;margin:.2rem 0;">Curso: <?= htmlspecialchars($f['curso_nome']) ?></p>
      <p style="font-size:.85rem;color:#555;margin:.2rem 0;">Submetida em: <?= date('d/m/Y H:i', strtotime($f['submetida_em'])) ?></p>
      <?php if ($f['foto_path']): ?>
        <p style="font-size:.85rem;margin:.2rem 0;">
          <a href="/gestao_academica/uploads/<?= htmlspecialchars($f['foto_path']) ?>" target="_blank">Ver fotografia</a>
        </p>
      <?php endif; ?>
      <form method="POST" style="display:flex;flex-wrap:wrap;gap:.5rem;margin-top:.75rem;align-items:flex-end;">
        <input type="hidden" name="ficha_id" value="<?= $f['id'] ?>">
        <div style="flex:1;min-width:220px;">
          <label style="font-size:.8rem;">Observações / Justificação</label>
          <input type="text" name="observacoes" placeholder="Opcional">
        </div>
        <button type="submit" name="acao" value="aprovada" class="btn btn-success">Aprovar</button>
        <button type="submit" name="acao" value="rejeitada" class="btn btn-danger">Rejeitar</button>
      </form>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<div class="card">
  <h2>Histórico de Validações</h2>
  <?php if (!$historico): ?><p>Sem histórico.</p>
  <?php else: ?>
  <table>
    <thead><tr><th>Aluno</th><th>Curso</th><th>Estado</th><th>Validado por</th><th>Data</th><th>Observações</th></tr></thead>
    <tbody>
    <?php foreach ($historico as $h): ?>
      <tr>
        <td><?= htmlspecialchars($h['aluno_nome']) ?></td>
        <td><?= htmlspecialchars($h['curso_nome']) ?></td>
        <td><span class="badge badge-<?= $h['estado'] ?>"><?= ucfirst($h['estado']) ?></span></td>
        <td><?= htmlspecialchars($h['gestor_nome'] ?? '—') ?></td>
        <td><?= $h['validada_em'] ? date('d/m/Y H:i', strtotime($h['validada_em'])) : '—' ?></td>
        <td><?= htmlspecialchars($h['observacoes'] ?? '—') ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
