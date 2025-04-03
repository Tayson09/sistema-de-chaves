<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';

if ($_SESSION['usuario_tipo'] !== 'administrador') {
    header('Location: ../home/index.php');
    exit;
}

$usuario = [];
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id) {
    $stmt = $pdo->prepare("
        SELECT id, nome, email, tipo, ativo 
        FROM usuarios 
        WHERE id = ?
    ");
    $stmt->execute([$id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$usuario) {
    header('Location: usuarios.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_usuario'])) {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $tipo = $_POST['tipo'];
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    try {
        $stmt = $pdo->prepare("
            SELECT id 
            FROM usuarios 
            WHERE email = ? AND id != ?
        ");
        $stmt->execute([$email, $id]);
        
        if ($stmt->rowCount() > 0) {
            $erro = "Este e-mail já está sendo usado por outro usuário!";
        } else {
            $params = [$nome, $email, $tipo, $ativo, $id];
            $query = "UPDATE usuarios SET
                nome = ?,
                email = ?,
                tipo = ?,
                ativo = ?
            ";

            if (!empty($senha)) {
                $query .= ", senha = ?";
                $params[] = password_hash($senha, PASSWORD_DEFAULT);
            }

            $query .= " WHERE id = ?";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            
            $sucesso = "Dados do usuário atualizados com sucesso!";

            $usuario = array_merge($usuario, [
                'nome' => $nome,
                'email' => $email,
                'tipo' => $tipo,
                'ativo' => $ativo
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
    <title>Editar Usuário - IFCE</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="style2.css">
    <script src="https://kit.fontawesome.com/890bf7e297.js" crossorigin="anonymous"></script>
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="header">
            <h1><i class="fas fa-user-edit"></i> Editar Usuário</h1>
            <a href="usuarios.php" class="btn btn-secondary">
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
                    <label>Nome Completo:</label>
                    <input 
                        type="text" 
                        name="nome" 
                        class="form-control" 
                        value="<?= htmlspecialchars($usuario['nome']) ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>E-mail:</label>
                    <input 
                        type="email" 
                        name="email" 
                        class="form-control" 
                        value="<?= htmlspecialchars($usuario['email']) ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Nova Senha (deixe em branco para manter):</label>
                    <input 
                        type="password" 
                        name="senha" 
                        class="form-control" 
                        minlength="6"
                        placeholder="••••••"
                    >
                </div>

                <div class="form-group">
                    <label>Tipo:</label>
                    <select name="tipo" class="form-control" required>
                        <option value="recepcionista" <?= $usuario['tipo'] === 'recepcionista' ? 'selected' : '' ?>>Recepcionista</option>
                        <option value="administrador" <?= $usuario['tipo'] === 'administrador' ? 'selected' : '' ?>>Administrador</option>
                    </select>
                </div>

                <div class="form-check">
                    <input 
                        type="checkbox" 
                        name="ativo" 
                        id="ativo" 
                        class="form-check-input"
                        <?= $usuario['ativo'] ? 'checked' : '' ?>
                    >
                    <label class="form-check-label" for="ativo">Usuário Ativo</label>
                </div>

                <div class="mt-4">
                    <button 
                        type="submit" 
                        name="atualizar_usuario" 
                        class="btn btn-primary"
                    >
                        <i class="fas fa-save"></i> Salvar Alterações
                    </button>
                </div>
            </form>
        </section>
    </main>

    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            if (!confirm('Tem certeza que deseja atualizar os dados deste usuário?')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>