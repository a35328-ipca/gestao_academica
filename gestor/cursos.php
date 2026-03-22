<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
requireRole('gestor');

$db  = getDB();
$msg = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $nome    = trim($_POST['nome'] ?? '');
    $descr   = trim($_POST['descricao'] ?? '');
    $id      = (int)($_POST['id'] ?? 0);

    if ($action === 'criar') {
        if (!$nome) { $erro = 'Nome obrigatório.'; }
        else {
            $db->prepare("INSERT INTO cursos (nome, descricao) VALUES (?,?)")->execute([$nome, $descr]);
            $msg = 'Curso criado com sucesso.';
        }
    } elseif ($action === 'editar' && $id) {
        if (!$nome) { $erro = 'Nome obrigatório.'; }
        else {
            $db->prepare("UPDATE cursos SET nome=?, descricao=? WHERE id=?")->execute([$nome, $descr, $id]);
            $msg = 'Curso actualizado.';
        }
    } elseif ($action === 'desativar' && $id) {
        $db->prepare("UPDATE cursos SET ativo=0 WHERE id=?")->execute([$id]);
        $msg = 'Curso desativado.';
    } elseif ($action === 'ativar' && $id) {
        $db->prepare("UPDATE cursos SET ativo=1 WHERE id=?")->execute([$id]);
        $msg = 'Curso reativado.';
    }
}

// Plano de estudos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'assoc_uc') {
    $cursoId  = (int)($_POST['curso_id'] ?? 0);
    $ucId     = (int)($_POST['uc_id'] ?? 0);
    $ano      = (int)($_POST['ano'] ?? 1);
    $semestre = (int)($_POST['semestre'] ?? 1);
    if ($cursoId && $ucId && $ano > 0 && in_array($semestre, [1,2])) {
        try {
            $db->prepare("INSERT INTO plano_estudos (curso_id, uc_id, ano, semestre) VALUES (?,?,?,?)")->execute([$cursoId, $ucId, $ano, $semestre]);
            $msg = 'UC associada ao plano de estudos.';
        } catch (PDOException $e) {
            $erro = 'Esta UC já existe neste curso/semestre.';
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'remover_uc') {
    $db->prepare("DELETE FROM plano_estudos WHERE id=?")->execute([(int)$_POST['pe_id']]);
    $msg = 'UC removida do plano.';
}

$cursos = $db->query("SELECT * FROM cursos ORDER BY ativo DESC, nome")->fetchAll();
$ucs    = $db->query("SELECT id, nome, codigo FROM unidades_curriculares WHERE ativo=1 ORDER BY nome")->fetchAll();

// Plano do curso selecionado
$cursoSel = isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : 0;
$plano = [];
if ($cursoSel) {
    $q = $db->prepare("SELECT pe.*, u.nome AS uc_nome, u.codigo FROM plano_estudos pe JOIN unidades_curriculares u ON u.id=pe.uc_id WHERE pe.curso_id=? ORDER BY pe.ano, pe.semestre");
    $q->execute([$cursoSel]);
    $plano = $q->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
  <h2>Criar Novo Curso</h2>
  <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($erro): ?><div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
  <form method="POST">
    <input type="hidden" name="action" value="criar">
    <label>Nome do Curso *</label>
    <input type="text" name="nome" required maxlength="200">
    <label>Descrição</label>
    <textarea name="descricao"></textarea>
    <div style="margin-top:1rem;"><button type="submit" class="btn btn-primary">Criar Curso</button></div>
  </form>
</div>

<div class="card">
  <h2>Lista de Cursos</h2>
  <table>
    <thead><tr><th>Nome</th><th>Estado</th><th>Acções</th></tr></thead>
    <tbody>
    <?php foreach ($cursos as $c): ?>
      <tr>
        <td><?= htmlspecialchars($c['nome']) ?></td>
        <td><span class="badge <?= $c['ativo'] ? 'badge-aprovada' : 'badge-rejeitada' ?>"><?= $c['ativo'] ? 'Ativo' : 'Inativo' ?></span></td>
        <td style="display:flex;gap:.4rem;flex-wrap:wrap;">
          <a href="?curso_id=<?= $c['id'] ?>" class="btn btn-secondary">Plano de Estudos</a>
          <form method="POST" style="display:inline">
            <input type="hidden" name="action" value="<?= $c['ativo'] ? 'desativar' : 'ativar' ?>">
            <input type="hidden" name="id" value="<?= $c['id'] ?>">
            <button type="submit" class="btn <?= $c['ativo'] ? 'btn-danger' : 'btn-success' ?>"><?= $c['ativo'] ? 'Desativar' : 'Ativar' ?></button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php if ($cursoSel): ?>
<?php $cursoNome = ''; foreach ($cursos as $c) { if ($c['id'] == $cursoSel) $cursoNome = $c['nome']; } ?>
<div class="card">
  <h2>Plano de Estudos — <?= htmlspecialchars($cursoNome) ?></h2>
  <form method="POST" style="display:flex;flex-wrap:wrap;gap:.5rem;align-items:flex-end;margin-bottom:1rem;">
    <input type="hidden" name="action" value="assoc_uc">
    <input type="hidden" name="curso_id" value="<?= $cursoSel ?>">
    <div>
      <label style="font-size:.8rem;">UC</label>
      <select name="uc_id" required style="min-width:200px;">
        <?php foreach ($ucs as $u): ?>
          <option value="<?= $u['id'] ?>">[<?= htmlspecialchars($u['codigo']) ?>] <?= htmlspecialchars($u['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div><label style="font-size:.8rem;">Ano</label><input type="number" name="ano" min="1" max="5" value="1" style="width:60px;"></div>
    <div><label style="font-size:.8rem;">Semestre</label><select name="semestre"><option value="1">1º</option><option value="2">2º</option></select></div>
    <button type="submit" class="btn btn-primary">Associar UC</button>
  </form>

  <?php if (!$plano): ?><p>Sem UCs no plano.</p>
  <?php else: ?>
  <table>
    <thead><tr><th>Código</th><th>UC</th><th>Ano</th><th>Semestre</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($plano as $pe): ?>
      <tr>
        <td><?= htmlspecialchars($pe['codigo']) ?></td>
        <td><?= htmlspecialchars($pe['uc_nome']) ?></td>
        <td><?= $pe['ano'] ?>º</td>
        <td><?= $pe['semestre'] ?>º</td>
        <td>
          <form method="POST" style="display:inline">
            <input type="hidden" name="action" value="remover_uc">
            <input type="hidden" name="pe_id" value="<?= $pe['id'] ?>">
            <button type="submit" class="btn btn-danger" onclick="return confirm('Remover UC do plano?')">Remover</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
