<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
requireRole('funcionario');

$db     = getDB();
$userId = $_SESSION['user_id'];
$msg    = '';
$erro   = '';

// Criar pauta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'criar_pauta') {
    $ucId      = (int)($_POST['uc_id'] ?? 0);
    $anoLetivo = trim($_POST['ano_letivo'] ?? '');
    $epoca     = $_POST['epoca'] ?? '';
    if (!$ucId || !$anoLetivo || !in_array($epoca, ['normal','recurso','especial'])) {
        $erro = 'Preencha todos os campos da pauta.';
    } elseif (!preg_match('/^\d{4}\/\d{4}$/', $anoLetivo)) {
        $erro = 'Ano letivo inválido (ex: 2024/2025).';
    } else {
        try {
            $db->prepare("INSERT INTO pautas (uc_id, criado_por, ano_letivo, epoca) VALUES (?,?,?,?)")
               ->execute([$ucId, $userId, $anoLetivo, $epoca]);
            $pautaId = $db->lastInsertId();
            // Inserir alunos elegíveis (matrícula aprovada para o curso da UC)
            $alunos = $db->prepare("
                SELECT DISTINCT pm.aluno_id FROM pedidos_matricula pm
                JOIN plano_estudos pe ON pe.curso_id = pm.curso_id
                WHERE pe.uc_id = ? AND pm.estado = 'aprovado'");
            $alunos->execute([$ucId]);
            $ins = $db->prepare("INSERT IGNORE INTO notas (pauta_id, aluno_id) VALUES (?,?)");
            foreach ($alunos->fetchAll() as $a) {
                $ins->execute([$pautaId, $a['aluno_id']]);
            }
            $msg = 'Pauta criada com sucesso!';
        } catch (PDOException $e) {
            $erro = 'Já existe uma pauta para esta UC / ano / época.';
        }
    }
}

// Lançar/editar nota
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'lancar_nota') {
    $notaId = (int)($_POST['nota_id'] ?? 0);
    $nota   = $_POST['nota_final'];
    if ($nota !== '' && ($nota < 0 || $nota > 20)) {
        $erro = 'Nota deve estar entre 0 e 20.';
    } else {
        $db->prepare("UPDATE notas SET nota_final=? WHERE id=?")
           ->execute([$nota === '' ? null : $nota, $notaId]);
        $msg = 'Nota guardada.';
    }
}

$ucs    = $db->query("SELECT id, nome, codigo FROM unidades_curriculares WHERE ativo=1 ORDER BY nome")->fetchAll();
$pautas = $db->query("SELECT p.*, u.nome AS uc_nome, ut.nome AS criador_nome FROM pautas p JOIN unidades_curriculares u ON u.id=p.uc_id JOIN utilizadores ut ON ut.id=p.criado_por ORDER BY p.criada_em DESC")->fetchAll();

$pautaSel = null;
$notas    = [];
if (isset($_GET['pauta_id'])) {
    $pid = (int)$_GET['pauta_id'];
    $q   = $db->prepare("SELECT p.*, u.nome AS uc_nome FROM pautas p JOIN unidades_curriculares u ON u.id=p.uc_id WHERE p.id=?");
    $q->execute([$pid]);
    $pautaSel = $q->fetch();
    if ($pautaSel) {
        $nq = $db->prepare("SELECT n.*, ut.nome AS aluno_nome FROM notas n JOIN utilizadores ut ON ut.id=n.aluno_id WHERE n.pauta_id=? ORDER BY ut.nome");
        $nq->execute([$pid]);
        $notas = $nq->fetchAll();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
  <h2>Criar Nova Pauta</h2>
  <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($erro): ?><div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
  <form method="POST">
    <input type="hidden" name="action" value="criar_pauta">
    <label>Unidade Curricular *</label>
    <select name="uc_id" required>
      <option value="">-- Selecione --</option>
      <?php foreach ($ucs as $u): ?>
        <option value="<?= $u['id'] ?>">[<?= htmlspecialchars($u['codigo']) ?>] <?= htmlspecialchars($u['nome']) ?></option>
      <?php endforeach; ?>
    </select>
    <label>Ano Letivo * (ex: 2024/2025)</label>
    <input type="text" name="ano_letivo" placeholder="2024/2025" pattern="\d{4}\/\d{4}" required>
    <label>Época *</label>
    <select name="epoca" required>
      <option value="normal">Normal</option>
      <option value="recurso">Recurso</option>
      <option value="especial">Especial</option>
    </select>
    <div style="margin-top:1rem;">
      <button type="submit" class="btn btn-primary">Criar Pauta</button>
    </div>
  </form>
</div>

<div class="card">
  <h2>Pautas Existentes</h2>
  <?php if (!$pautas): ?><p>Sem pautas criadas.</p>
  <?php else: ?>
  <table>
    <thead><tr><th>UC</th><th>Ano Letivo</th><th>Época</th><th>Criada por</th><th>Data</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($pautas as $p): ?>
      <tr>
        <td><?= htmlspecialchars($p['uc_nome']) ?></td>
        <td><?= htmlspecialchars($p['ano_letivo']) ?></td>
        <td><?= ucfirst($p['epoca']) ?></td>
        <td><?= htmlspecialchars($p['criador_nome']) ?></td>
        <td><?= date('d/m/Y', strtotime($p['criada_em'])) ?></td>
        <td><a href="?pauta_id=<?= $p['id'] ?>" class="btn btn-secondary">Ver / Lançar Notas</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<?php if ($pautaSel): ?>
<div class="card">
  <h2>Pauta: <?= htmlspecialchars($pautaSel['uc_nome']) ?> — <?= htmlspecialchars($pautaSel['ano_letivo']) ?> (<?= ucfirst($pautaSel['epoca']) ?>)</h2>
  <?php if (!$notas): ?>
    <p>Sem alunos elegíveis nesta pauta.</p>
  <?php else: ?>
  <form method="POST">
    <input type="hidden" name="action" value="lancar_nota">
    <table>
      <thead><tr><th>Aluno</th><th>Nota Final (0-20)</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($notas as $n): ?>
        <tr>
          <td><?= htmlspecialchars($n['aluno_nome']) ?></td>
          <td>
            <input type="hidden" name="nota_id" value="<?= $n['id'] ?>">
            <input type="number" name="nota_final" min="0" max="20" step="0.1"
                   value="<?= $n['nota_final'] ?? '' ?>" style="width:80px;"
                   onchange="this.form.querySelector('[name=nota_id]').value='<?= $n['id'] ?>';this.form.submit();">
          </td>
          <td><span style="font-size:.8rem;color:#888;"><?= $n['registada_em'] ? date('d/m/Y H:i', strtotime($n['registada_em'])) : '' ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </form>
  <?php endif; ?>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
