<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
requireRole('funcionario');

$db     = getDB();
$userId = $_SESSION['user_id'];
$msg    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pedidoId = (int)($_POST['pedido_id'] ?? 0);
    $acao     = $_POST['acao'] ?? '';
    $obs      = trim($_POST['observacoes'] ?? '');
    if ($pedidoId && in_array($acao, ['aprovado', 'rejeitado'])) {
        $db->prepare("UPDATE pedidos_matricula SET estado=?, decidido_por=?, decidido_em=NOW(), observacoes=? WHERE id=? AND estado='pendente'")
           ->execute([$acao, $userId, $obs ?: null, $pedidoId]);
        $msg = 'Pedido ' . ($acao === 'aprovado' ? 'aprovado' : 'rejeitado') . ' com sucesso.';
    }
}

$pendentes = $db->query("SELECT pm.*, u.nome AS aluno_nome, c.nome AS curso_nome FROM pedidos_matricula pm JOIN utilizadores u ON u.id=pm.aluno_id JOIN cursos c ON c.id=pm.curso_id WHERE pm.estado='pendente' ORDER BY pm.criado_em")->fetchAll();

$historico = $db->query("SELECT pm.*, u.nome AS aluno_nome, c.nome AS curso_nome, d.nome AS decidido_nome FROM pedidos_matricula pm JOIN utilizadores u ON u.id=pm.aluno_id JOIN cursos c ON c.id=pm.curso_id LEFT JOIN utilizadores d ON d.id=pm.decidido_por WHERE pm.estado<>'pendente' ORDER BY pm.decidido_em DESC LIMIT 30")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
  <h2>Pedidos de Matrícula Pendentes</h2>
  <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if (!$pendentes): ?>
    <p>Não há pedidos pendentes.</p>
  <?php else: ?>
    <?php foreach ($pendentes as $p): ?>
    <div style="border:1px solid #e0e4ea;border-radius:6px;padding:1rem;margin-bottom:1rem;">
      <p><strong><?= htmlspecialchars($p['aluno_nome']) ?></strong> — <?= htmlspecialchars($p['curso_nome']) ?></p>
      <p style="font-size:.85rem;color:#666;margin:.3rem 0;">Submetido em <?= date('d/m/Y H:i', strtotime($p['criado_em'])) ?></p>
      <form method="POST" style="display:flex;flex-wrap:wrap;gap:.5rem;margin-top:.6rem;align-items:flex-end;">
        <input type="hidden" name="pedido_id" value="<?= $p['id'] ?>">
        <div style="flex:1;min-width:200px;">
          <label style="font-size:.8rem;">Observações</label>
          <input type="text" name="observacoes" placeholder="Opcional">
        </div>
        <button type="submit" name="acao" value="aprovado" class="btn btn-success">Aprovar</button>
        <button type="submit" name="acao" value="rejeitado" class="btn btn-danger">Rejeitar</button>
      </form>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<div class="card">
  <h2>Histórico de Decisões</h2>
  <?php if (!$historico): ?>
    <p>Sem histórico ainda.</p>
  <?php else: ?>
  <table>
    <thead><tr><th>Aluno</th><th>Curso</th><th>Estado</th><th>Decidido por</th><th>Data</th><th>Observações</th></tr></thead>
    <tbody>
    <?php foreach ($historico as $h): ?>
      <tr>
        <td><?= htmlspecialchars($h['aluno_nome']) ?></td>
        <td><?= htmlspecialchars($h['curso_nome']) ?></td>
        <td><span class="badge badge-<?= $h['estado'] ?>"><?= ucfirst($h['estado']) ?></span></td>
        <td><?= htmlspecialchars($h['decidido_nome'] ?? '—') ?></td>
        <td><?= $h['decidido_em'] ? date('d/m/Y H:i', strtotime($h['decidido_em'])) : '—' ?></td>
        <td><?= htmlspecialchars($h['observacoes'] ?? '—') ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
