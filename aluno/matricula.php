<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
requireRole('aluno');

$db     = getDB();
$userId = $_SESSION['user_id'];
$msg    = '';
$erro   = '';

// Verificar se ficha está aprovada
$ficha = $db->prepare("SELECT * FROM fichas_aluno WHERE aluno_id = ? AND estado = 'aprovada'");
$ficha->execute([$userId]);
$fichaAprovada = $ficha->fetch();

// Pedidos existentes
$pedidos = $db->prepare("SELECT pm.*, c.nome AS curso_nome FROM pedidos_matricula pm JOIN cursos c ON c.id = pm.curso_id WHERE pm.aluno_id = ? ORDER BY pm.criado_em DESC");
$pedidos->execute([$userId]);
$pedidos = $pedidos->fetchAll();

$cursos = $db->query("SELECT id, nome FROM cursos WHERE ativo = 1")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $fichaAprovada) {
    $cursoId = (int)($_POST['curso_id'] ?? 0);
    if (!$cursoId) {
        $erro = 'Selecione um curso.';
    } else {
        // Verificar pedido duplicado pendente
        $dup = $db->prepare("SELECT id FROM pedidos_matricula WHERE aluno_id=? AND curso_id=? AND estado='pendente'");
        $dup->execute([$userId, $cursoId]);
        if ($dup->fetch()) {
            $erro = 'Já tem um pedido pendente para este curso.';
        } else {
            $db->prepare("INSERT INTO pedidos_matricula (aluno_id, curso_id) VALUES (?,?)")->execute([$userId, $cursoId]);
            $msg = 'Pedido de matrícula submetido com sucesso!';
            // Recarregar
            $pedidos = $db->prepare("SELECT pm.*, c.nome AS curso_nome FROM pedidos_matricula pm JOIN cursos c ON c.id = pm.curso_id WHERE pm.aluno_id = ? ORDER BY pm.criado_em DESC");
            $pedidos->execute([$userId]);
            $pedidos = $pedidos->fetchAll();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
  <h2>Pedido de Matrícula / Inscrição</h2>

  <?php if (!$fichaAprovada): ?>
    <div class="alert alert-danger">A sua ficha de aluno ainda não foi aprovada. Só pode solicitar matrícula após aprovação da ficha.</div>
  <?php else: ?>
    <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($erro): ?><div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
    <form method="POST">
      <label>Curso *</label>
      <select name="curso_id" required>
        <option value="">-- Selecione --</option>
        <?php foreach ($cursos as $c): ?>
          <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
        <?php endforeach; ?>
      </select>
      <div style="margin-top:1rem;">
        <button type="submit" class="btn btn-primary">Submeter Pedido</button>
      </div>
    </form>
  <?php endif; ?>
</div>

<?php if ($pedidos): ?>
<div class="card">
  <h2>Histórico de Pedidos</h2>
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
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
