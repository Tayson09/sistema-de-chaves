<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';

// Permitir apenas administradores e recepcionistas
if (! in_array($_SESSION['usuario_tipo'], ['administrador', 'recepcionista'], true)) {
    header('Location: /sistema/home/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_chave'])) {
    $codigo    = trim($_POST['codigo']);
    $descricao = trim($_POST['descricao']);
    $local     = trim($_POST['local']);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO chaves 
            (codigo, descricao, local) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$codigo, $descricao, $local]);
        $sucesso = "Chave cadastrada com sucesso!";
    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1062) {
            $erro = "Erro: O código da chave já existe!";
        } else {
            $erro = "Erro ao cadastrar: " . $e->getMessage();
        }
    }
}

if (isset($_GET['excluir'])) {
    $id = filter_input(INPUT_GET, 'excluir', FILTER_VALIDATE_INT);
    
    if ($id) {
        try {
            $pdo->beginTransaction();
            
            $emprestimo = $pdo->prepare("
                SELECT id 
                FROM emprestimos 
                WHERE chave_id = ? 
                  AND data_devolucao IS NULL
            ");
            $emprestimo->execute([$id]);
            
            if ($emprestimo->rowCount() > 0) {
                $erro = "Não é possível excluir: Chave está emprestada!";
            } else {
                $pdo->prepare("DELETE FROM chaves WHERE id = ?")
                    ->execute([$id]);
                $sucesso = "Chave excluída com sucesso!";
            }
            
            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $erro = "Erro ao excluir: " . $e->getMessage();
        }
    }
}

$chaves = $pdo->query("
    SELECT 
        id,
        codigo,
        descricao,
        local,
        disponivel,
        data_cadastro,
        data_atualizacao
    FROM chaves
    ORDER BY data_cadastro DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Chaves - IFCE</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="style2.css">
    <script src="https://kit.fontawesome.com/890bf7e297.js" crossorigin="anonymous"></script>
    <script>
    function confirmarExclusao(id) {
        if (confirm('Tem certeza que deseja excluir esta chave?')) {
            window.location.href = 'chaves.php?excluir=' + id;
        }
    }
    </script>
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="header">
            <h1><i class="fas fa-key"></i> Gerenciar Chaves</h1>
        </header>

        <?php if (isset($sucesso)): ?>
            <div class="alert alert-success"><?= $sucesso ?></div>
        <?php endif; ?>
        <?php if (isset($erro)): ?>
            <div class="alert alert-danger"><?= $erro ?></div>
        <?php endif; ?>

        <section class="card">
            <h2><i class="fas fa-plus-circle"></i> Cadastrar Nova Chave</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Código:</label>
                    <input 
                        type="text" 
                        name="codigo" 
                        class="form-control" 
                        placeholder="Ex: SALA-101"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Descrição:</label>
                    <input 
                        type="text" 
                        name="descricao" 
                        class="form-control" 
                        placeholder="Ex: Chave da Sala de Reuniões"
                    >
                </div>

                <div class="form-group">
                    <label>Local:</label>
                    <input 
                        type="text" 
                        name="local" 
                        class="form-control" 
                        placeholder="Ex: Bloco A, 1º Andar"
                        required
                    >
                </div>

                <button type="submit" name="cadastrar_chave" class="btn btn-primary">
                    <i class="fas fa-save"></i> Cadastrar
                </button>
            </form>
        </section>

        <section class="card">
            <h2><i class="fas fa-list"></i> Chaves Cadastradas</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Descrição</th>
                            <th>Local</th>
                            <th>Status</th>
                            <th>Cadastro</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($chaves as $chave): ?>
                            <tr>
                                <td><?= htmlspecialchars($chave['codigo']) ?></td>
                                <td><?= htmlspecialchars($chave['descricao']) ?></td>
                                <td><?= htmlspecialchars($chave['local']) ?></td>
                                <td>
                                    <?= $chave['disponivel']
                                        ? '<span class="status-disponivel">Disponível</span>'
                                        : '<span class="status-indisponivel">Emprestada</span>' 
                                    ?>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($chave['data_cadastro'])) ?></td>
                                <td>
                                    <a 
                                        href="editar_chave.php?id=<?= $chave['id'] ?>" 
                                        class="btn btn-sm btn-warning"
                                    >
                                        <i class="fas fa-edit"></i>
                                        <span>Editar</span>
                                    </a>
                                    <button 
                                        onclick="confirmarExclusao(<?= $chave['id'] ?>)" 
                                        class="btn btn-sm btn-danger"
                                    >
                                        <i class="fas fa-trash"></i>
                                        <span>Excluir</span>
                                    </button>
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