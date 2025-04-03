<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';

// Verifica se é administrador ou recepcionista
if ($_SESSION['usuario_tipo'] !== 'administrador' && $_SESSION['usuario_tipo'] !== 'recepcionista') {
    header('Location: index.php');
    exit;
}

// Processar novo empréstimo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['novo_emprestimo'])) {
    $chave_id = filter_input(INPUT_POST, 'chave_id', FILTER_VALIDATE_INT);
    $pessoa_nome = trim(filter_input(INPUT_POST, 'pessoa_nome', FILTER_SANITIZE_STRING));
    $usuario_id = $_SESSION['usuario_id'];

    if ($chave_id && !empty($pessoa_nome)) {
        try {
            $pdo->beginTransaction();

            // 1. Registrar empréstimo
            $stmt = $pdo->prepare("
                INSERT INTO emprestimos 
                (chave_id, pessoa_nome, usuario_id) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$chave_id, $pessoa_nome, $usuario_id]);

            // 2. Atualizar status da chave
            $stmt = $pdo->prepare("UPDATE chaves SET disponivel = FALSE WHERE id = ?");
            $stmt->execute([$chave_id]);

            $pdo->commit();
            $sucesso = "Empréstimo registrado para: <strong>" . htmlspecialchars($pessoa_nome) . "</strong>";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $erro = "Erro ao registrar: " . $e->getMessage();
        }
    } else {
        $erro = "Preencha todos os campos corretamente!";
    }
}

// Processar devolução
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['devolucao'])) {
    $emprestimo_id = filter_input(INPUT_POST, 'emprestimo_id', FILTER_VALIDATE_INT);

    if ($emprestimo_id) {
        try {
            // Obter chave_id para atualização
            $stmt = $pdo->prepare("SELECT chave_id FROM emprestimos WHERE id = ?");
            $stmt->execute([$emprestimo_id]);
            $chave_id = $stmt->fetchColumn();

            // Registrar devolução
            $stmt = $pdo->prepare("UPDATE emprestimos SET data_devolucao = NOW() WHERE id = ?");
            $stmt->execute([$emprestimo_id]);

            // Atualizar status da chave
            $stmt = $pdo->prepare("UPDATE chaves SET disponivel = TRUE WHERE id = ?");
            $stmt->execute([$chave_id]);

            $sucesso = "Devolução registrada com sucesso!";
        } catch (PDOException $e) {
            $erro = "Erro ao registrar devolução: " . $e->getMessage();
        }
    }
}

$chaves = $pdo->query("SELECT id, codigo, local FROM chaves WHERE disponivel = TRUE")->fetchAll();

// Buscar empréstimos ativos (atualizado para Passo 4)
$emprestimos = $pdo->query("
    SELECT 
        e.id, 
        c.codigo AS chave, 
        e.pessoa_nome AS pessoa, 
        e.data_emprestimo 
    FROM emprestimos e
    LEFT JOIN chaves c ON e.chave_id = c.id
    WHERE e.data_devolucao IS NULL
    ORDER BY e.data_emprestimo DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empréstimos - IFCE</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="style2.css">
    <script src="https://kit.fontawesome.com/890bf7e297.js" crossorigin="anonymous"></script>
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="header">
            <h1><i class="fas fa-key"></i> Gerenciar Empréstimos</h1>
        </header>
        <?php if (isset($sucesso)): ?>
            <div class="alert alert-success"><?= $sucesso ?></div>
        <?php endif; ?>
        <?php if (isset($erro)): ?>
            <div class="alert alert-danger"><?= $erro ?></div>
        <?php endif; ?>

        <section class="card">
            <h2><i class="fas fa-plus-circle"></i> Novo Empréstimo</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Chave:</label>
                    <select name="chave_id" class="form-control" required>
                        <?php foreach ($chaves as $chave): ?>
                            <option value="<?= $chave['id'] ?>"><?= $chave['codigo'] ?> - <?= $chave['local'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Nome da Pessoa:</label>
                    <input 
                        type="text" 
                        name="pessoa_nome" 
                        class="form-control" 
                        placeholder="Ex: João da Silva"
                        required
                    >
                </div>

                <button type="submit" name="novo_emprestimo" class="btn btn-primary">
                    <i class="fas fa-save"></i> Registrar
                </button>
            </form>
        </section>

        <section class="card">
            <h2><i class="fas fa-list"></i> Empréstimos Ativos</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Chave</th>
                            <th>Pessoa</th>
                            <th>Data</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($emprestimos as $emprestimo): ?>
                            <tr>
                                <td><?= htmlspecialchars($emprestimo['chave']) ?></td>
                                <td><?= htmlspecialchars($emprestimo['pessoa']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($emprestimo['data_emprestimo'])) ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="emprestimo_id" value="<?= $emprestimo['id'] ?>">
                                        <button type="submit" name="devolucao" class="btn btn-danger btn-sm">
                                            <i class="fas fa-undo"></i> Devolver
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>