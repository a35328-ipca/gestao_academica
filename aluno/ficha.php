<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
requireRole('aluno');

$db     = getDB();
$userId = $_SESSION['user_id'];
$msg    = '';
$erro   = '';

// Buscar ficha existente
$ficha = $db->prepare("SELECT f.*, c.nome AS curso_nome FROM fichas_aluno f JOIN cursos c ON c.id = f.curso_id WHERE f.aluno_id = ?");
$ficha->execute([$userId]);
$ficha = $ficha->fetch();

// Cursos disponíveis
$cursos = $db->query("SELECT id, nome FROM cursos WHERE ativo = 1")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $cursoId = (int)($_POST['curso_id'] ?? 0);
    $dataNasc = $_POST['data_nascimento'] ?? '';
    $tel      = trim($_POST['telefone'] ?? '');
    $morada   = trim($_POST['morada'] ?? '');
    $cp       = trim($_POST['codigo_postal'] ?? '');
    $nif      = trim($_POST['nif'] ?? '');

    // Validações
    if (!$cursoId) { $erro = 'Selecione um curso.'; }
    elseif (!$dataNasc) { $erro = 'Data de nascimento obrigatória.'; }
    elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataNasc)) { $erro = 'Data de nascimento inválida.'; }
    elseif ($nif && !preg_match('/^\d{9}$/', $nif)) { $erro = 'NIF deve ter 9 dígitos.'; }
    else {
        // Upload de foto
        $fotoPath = $ficha['foto_path'] ?? null;
        if (!empty($_FILES['foto']['tmp_name'])) {
            $allowed = ['image/jpeg', 'image/png'];
            $maxSize = 2 * 1024 * 1024; // 2MB
            $finfo   = finfo_open(FILEINFO_MIME_TYPE);
            $mime    = finfo_file($finfo, $_FILES['foto']['tmp_name']);
            finfo_close($finfo);
            if (!in_array($mime, $allowed)) {
                $erro = 'Foto: apenas JPG ou PNG são aceites.';
            } elseif ($_FILES['foto']['size'] > $maxSize) {
                $erro = 'Foto: tamanho máximo é 2MB.';
            } else {
                $ext      = ($mime === 'image/png') ? 'png' : 'jpg';
                $filename = 'foto_' . $userId . '_' . time() . '.' . $ext;
                $dest     = __DIR__ . '/../uploads/' . $filename;
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $dest)) {
                    $fotoPath = $filename;
                }
            }
        }

        if (!$erro) {
            $estado = ($action === 'submeter' && (!$ficha || $ficha['estado'] === 'rascunho')) ? 'submetida' : 'rascunho';
            $submetidaEm = ($estado === 'submetida') ? date('Y-m-d H:i:s') : null;

            if (!$ficha) {
                $stmt = $db->prepare("INSERT INTO fichas_aluno (aluno_id, curso_id, data_nascimento, telefone, morada, codigo_postal, nif, foto_path, estado, submetida_em) VALUES (?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([$userId, $cursoId, $dataNasc, $tel, $morada, $cp, $nif, $fotoPath, $estado, $submetidaEm]);
            } else {
                $stmt = $db->prepare("UPDATE fichas_aluno SET curso_id=?, data_nascimento=?, telefone=?, morada=?, codigo_postal=?, nif=?, foto_path=?, estado=?, submetida_em=COALESCE(submetida_em,?) WHERE aluno_id=? AND estado='rascunho'");
                $stmt->execute([$cursoId, $dataNasc, $tel, $morada, $cp, $nif, $fotoPath, $estado, $submetidaEm, $userId]);
            }

            $msg = ($estado === 'submetida') ? 'Ficha submetida com sucesso!' : 'Ficha guardada como rascunho.';

            // Recarregar
            $q = $db->prepare("SELECT f.*, c.nome AS curso_nome FROM fichas_aluno f JOIN cursos c ON c.id = f.curso_id WHERE f.aluno_id = ?");
            $q->execute([$userId]);
            $ficha = $q->fetch();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
  <h2>Ficha de Aluno</h2>

  <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($erro): ?><div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

  <?php if ($ficha && $ficha['estado'] !== 'rascunho'): ?>
    <p>Estado actual: <span class="badge badge-<?= $ficha['estado'] ?>"><?= ucfirst($ficha['estado']) ?></span></p>
    <?php if ($ficha['observacoes']): ?>
      <p style="margin-top:.5rem;"><strong>Observações do gestor:</strong> <?= htmlspecialchars($ficha['observacoes']) ?></p>
    <?php endif; ?>
    <?php if ($ficha['estado'] === 'rejeitada'): ?>
      <p style="margin-top:1rem;">A sua ficha foi rejeitada. Pode corrigir e resubmeter:</p>
      <?php
        $db->prepare("UPDATE fichas_aluno SET estado='rascunho', validado_por=NULL, validada_em=NULL, observacoes=NULL WHERE aluno_id=?")->execute([$userId]);
        header('Refresh: 0');
      ?>
    <?php endif; ?>
  <?php else: ?>
  <form method="POST" enctype="multipart/form-data">
    <label>Curso pretendido *</label>
    <select name="curso_id" required>
      <option value="">-- Selecione --</option>
      <?php foreach ($cursos as $c): ?>
        <option value="<?= $c['id'] ?>" <?= ($ficha['curso_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($c['nome']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <label>Data de nascimento *</label>
    <input type="date" name="data_nascimento" value="<?= htmlspecialchars($ficha['data_nascimento'] ?? '') ?>" required>

    <label>Telefone</label>
    <input type="text" name="telefone" maxlength="20" value="<?= htmlspecialchars($ficha['telefone'] ?? '') ?>">

    <label>Morada</label>
    <input type="text" name="morada" maxlength="255" value="<?= htmlspecialchars($ficha['morada'] ?? '') ?>">

    <label>Código Postal</label>
    <input type="text" name="codigo_postal" maxlength="10" placeholder="1234-567" value="<?= htmlspecialchars($ficha['codigo_postal'] ?? '') ?>">

    <label>NIF</label>
    <input type="text" name="nif" maxlength="9" placeholder="9 dígitos" value="<?= htmlspecialchars($ficha['nif'] ?? '') ?>">

    <label>Fotografia (JPG/PNG, máx. 2MB)</label>
    <input type="file" name="foto" accept=".jpg,.jpeg,.png">
    <?php if (!empty($ficha['foto_path'])): ?>
      <p style="font-size:.8rem;margin-top:.3rem;">Foto actual: <?= htmlspecialchars($ficha['foto_path']) ?></p>
    <?php endif; ?>

    <div style="margin-top:1.2rem;display:flex;gap:.75rem;">
      <button type="submit" name="action" value="guardar" class="btn btn-secondary">Guardar Rascunho</button>
      <button type="submit" name="action" value="submeter" class="btn btn-primary">Submeter Ficha</button>
    </div>
  </form>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
