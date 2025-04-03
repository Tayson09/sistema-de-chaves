<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';

if ($_SESSION['usuario_tipo'] !== 'administrador') {
    header('Location: ../home/index.php');
    exit;
}

$chave = [];
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id) {
    $stmt = $pdo->prepare("
        SELECT id, codigo, descricao, local, disponivel 
        FROM chaves 
        WHERE id = ?
    ");
    $stmt->execute([$id]);
    $chave = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$chave) {
    header('Location: chaves.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_chave'])) {
    $codigo = trim($_POST['codigo']);
    $descricao = trim($_POST['descricao']);
    $local = trim($_POST['local']);
    $disponivel = isset($_POST['disponivel']) ? 1 : 0;

    try {
        $stmt = $pdo->prepare("
            SELECT id 
            FROM chaves 
            WHERE codigo = ? AND id != ?
        ");
        $stmt->execute([$codigo, $id]);
        
        if ($stmt->rowCount() > 0) {
            $erro = "Este código já está sendo usado por outra chave!";
        } else {
            $stmt = $pdo->prepare("
                UPDATE chaves SET
                    codigo = ?,
                    descricao = ?,
                    local = ?,
                    disponivel = ?,
                    data_atualizacao = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$codigo, $descricao, $local, $disponivel, $id]);
            
            $sucesso = "Chave atualizada com sucesso!";

            $chave = array_merge($chave, [
                'codigo' => $codigo,
                'descricao' => $descricao,
                'local' => $local,
                'disponivel' => $disponivel
            ]);
        }
    } catch (PDOException $e) {
        $erro = "Erro ao atualizar: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Chave - IFCE</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="style2.css">
    <script src="https://kit.fontawesome.com/890bf7e297.js" crossorigin="anonymous"></script>
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="header">
            <h1><i class="fas fa-key"></i> Editar Chave</h1>
            <a href="chaves.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </header>

        <?php if (isset($sucesso)): ?>
            <div class="alert alert-success"><?= $sucesso ?></div>
        <?php endif; ?>
        <?php if (isset($erro)): ?>
            <div class="alert alert-danger"><?= $erro ?></div>
        <?php endif; ?>

        <section class="card">
            <form method="POST">
                <div class="form-group">
                    <label>Código Único:</label>
                    <input 
                        type="text" 
                        name="codigo" 
                        class="form-control" 
                        value="<?= htmlspecialchars($chave['codigo']) ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Descrição:</label>
                    <input 
                        type="text" 
                        name="descricao" 
                        class="form-control" 
                        value="<?= htmlspecialchars($chave['descricao']) ?>"
                        placeholder="Opcional"
                    >
                </div>

                <div class="form-group">
                    <label>Localização:</label>
                    <input 
                        type="text" 
                        name="local" 
                        class="form-control" 
                        value="<?= htmlspecialchars($chave['local']) ?>"
                        required
                    >
                </div>

                <div class="form-check">
                    <input 
                        type="checkbox" 
                        name="disponivel" 
                        id="disponivel" 
                        class="form-check-input"
                        <?= $chave['disponivel'] ? 'checked' : '' ?>
                    >
                    <label class="form-check-label" for="disponivel">
                        Disponível para empréstimo
                    </label>
                </div>

                <div class="mt-4">
                    <button 
                        type="submit" 
                        name="atualizar_chave" 
                        class="btn btn-primary"
                    >
                        <i class="fas fa-save"></i> Salvar Alterações
                    </button>
                </div>
            </form>
        </section>
    </main>

    <script>
        window.addEventListener('beforeunload', function(e) {
            if(document.querySelector('form').checkValidity()) return;
            e.preventDefault();
            e.returnValue = '';
        });

        document.querySelector('form').addEventListener('submit', function(e) {
            if (!confirm('Tem certeza que deseja atualizar os dados desta chave?')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>