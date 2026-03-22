<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
requireRole('gestor');

$db   = getDB();
$msg  = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action'] ?? '';
    $nome     = trim($_POST['nome'] ?? '');
    $codigo   = strtoupper(trim($_POST['codigo'] ?? ''));
    $creditos = (int)($_POST['creditos'] ?? 6);
    $id       = (int)($_POST['id'] ?? 0);

    if (in_array($action, ['criar','editar'])) {
        if (!$nome || !$codigo) { $erro = 'Nome e código são obrigatórios.'; }
        elseif ($creditos < 1 || $creditos > 30) { $erro = 'Créditos inválidos (1-30).'; }
        else {
            if ($action === 'criar') {
                try {
                    $db->prepare("INSERT INTO unidades_curriculares (nome, codigo, creditos) VALUES (?,?,?)")->execute([$nome, $codigo, $creditos]);
                    $msg = 'UC criada com sucesso.';
                } catch (PDOException $e) {
                    $erro = 'Código de UC já existe.';
                }
            } else {
                $db->prepare("UPDATE unidades_curriculares SET nome=?, codigo=?, creditos=? WHERE id=?")->execute([$nome, $codigo, $creditos, $id]);
                $msg = 'UC actualizada.';
            }
        }
    } elseif ($action === 'desativar' && $id) {
        $db->prepare("UPDATE unidades_curriculares SET ativo=0 WHERE id=?")->execute([$id]);
        $msg = 'UC desativada.';
    } elseif ($action === 'ativar' && $id) {
        $db->prepare("UPDATE unidades_curriculares SET ativo=1 WHERE id=?")->execute([$id]);
        $msg = 'UC reativada.';
    }
}

$ucs = $db->query("SELECT * FROM unidades_curriculares ORDER BY ativo DESC, nome")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
  <h2>Criar Nova Unidade Curricular</h2>
  <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($erro): ?><div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
  <form method="POST">
    <input type="hidden" name="action" value="criar">
    <label>Nome da UC *</label>
    <input type="text" name="nome" required maxlength="200">
    <label>Código * (ex: PW2)</label>
    <input type="text" name="codigo" required maxlength="20" style="width:120px;">
    <label>Créditos ECTS</label>
    <input type="number" name="creditos" min="1" max="30" value="6" style="width:80px;">
    <div style="margin-top:1rem;"><button type="submit" class="btn btn-primary">Criar UC</button></div>
  </form>
</div>

<div class="card">
  <h2>Lista de Unidades Curriculares</h2>
  <table>
    <thead><tr><th>Código</th><th>Nome</th><th>ECTS</th><th>Estado</th><th>Acções</th></tr></thead>
    <tbody>
    <?php foreach ($ucs as $u): ?>
      <tr>
        <td><?= htmlspecialchars($u['codigo']) ?></td>
        <td><?= htmlspecialchars($u['nome']) ?></td>
        <td><?= $u['creditos'] ?></td>
        <td><span class="badge <?= $u['ativo'] ? 'badge-aprovada' : 'badge-rejeitada' ?>"><?= $u['ativo'] ? 'Ativa' : 'Inativa' ?></span></td>
        <td>
          <form method="POST" style="display:inline">
            <input type="hidden" name="action" value="<?= $u['ativo'] ? 'desativar' : 'ativar' ?>">
            <input type="hidden" name="id" value="<?= $u['id'] ?>">
            <button type="submit" class="btn <?= $u['ativo'] ? 'btn-danger' : 'btn-success' ?>"><?= $u['ativo'] ? 'Desativar' : 'Ativar' ?></button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
