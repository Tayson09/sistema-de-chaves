<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';

if ($_SESSION['usuario_tipo'] !== 'administrador') {
    header('Location: ../home/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['novo_usuario'])) {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $tipo = $_POST['tipo'];

    try {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $erro = "Este e-mail já está cadastrado!";
        } else {
            $hash_senha = password_hash($senha, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("
                INSERT INTO usuarios 
                (nome, email, senha, tipo) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$nome, $email, $hash_senha, $tipo]);
            $sucesso = "Usuário cadastrado com sucesso!";
        }
    } catch (PDOException $e) {
        $erro = "Erro ao cadastrar: " . $e->getMessage();
    }
}

if (isset($_GET['excluir'])) {
    $id = filter_input(INPUT_GET, 'excluir', FILTER_VALIDATE_INT);
    
    if ($id && $id != $_SESSION['usuario_id']) { 
        try {
            $pdo->beginTransaction();
        
            $stmt = $pdo->prepare("SELECT id FROM emprestimos WHERE usuario_id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() > 0) {
                $erro = "Não é possível excluir: Usuário possui empréstimos registrados!";
            } else {
                $pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$id]);
                $sucesso = "Usuário excluído com sucesso!";
            }
            
            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $erro = "Erro ao excluir: " . $e->getMessage();
        }
    } else {
        $erro = "Você não pode excluir seu próprio usuário!";
    }
}

if (isset($_GET['toggle_status'])) {
    $id = filter_input(INPUT_GET, 'toggle_status', FILTER_VALIDATE_INT);
    
    if ($id) {
        try {
            $stmt = $pdo->prepare("UPDATE usuarios SET ativo = NOT ativo WHERE id = ?");
            $stmt->execute([$id]);
            $sucesso = "Status do usuário atualizado!";
        } catch (PDOException $e) {
            $erro = "Erro ao atualizar status: " . $e->getMessage();
        }
    }
}

$usuarios = $pdo->query("
    SELECT 
        id,
        nome,
        email,
        tipo,
        ativo,
        data_cadastro
    FROM usuarios
    ORDER BY data_cadastro DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - IFCE</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="style2.css">
    <script src="https://kit.fontawesome.com/890bf7e297.js" crossorigin="anonymous"></script>
    <script>
    function confirmarExclusao(id) {
        if (confirm('Tem certeza que deseja excluir este usuário?\nEsta ação é irreversível!')) {
            window.location.href = 'usuarios.php?excluir=' + id;
        }
    }
    </script>
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="header">
            <h1><i class="fas fa-users-cog"></i> Gerenciar Usuários</h1>
        </header>

        <?php if (isset($sucesso)): ?>
            <div class="alert alert-success"><?= $sucesso ?></div>
        <?php endif; ?>
        <?php if (isset($erro)): ?>
            <div class="alert alert-danger"><?= $erro ?></div>
        <?php endif; ?>

        <section class="card">
            <h2><i class="fas fa-user-plus"></i> Novo Usuário</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Nome Completo:</label>
                    <input 
                        type="text" 
                        name="nome" 
                        class="form-control" 
                        required
                    >
                </div>

                <div class="form-group">
                    <label>E-mail:</label>
                    <input 
                        type="email" 
                        name="email" 
                        class="form-control" 
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Senha:</label>
                    <input 
                        type="password" 
                        name="senha" 
                        class="form-control" 
                        minlength="6" 
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Tipo:</label>
                    <select name="tipo" class="form-control" required>
                        <option value="recepcionista">Recepcionista</option>
                        <option value="administrador">Administrador</option>
                    </select>
                </div>

                <button type="submit" name="novo_usuario" class="btn btn-primary">
                    <i class="fas fa-save"></i> Cadastrar
                </button>
            </form>
        </section>

        <section class="card">
            <h2><i class="fas fa-users"></i> Usuários Cadastrados</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th>Cadastro</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><?= htmlspecialchars($usuario['nome']) ?></td>
                                <td><?= htmlspecialchars($usuario['email']) ?></td>
                                <td><?= ucfirst($usuario['tipo']) ?></td>
                                <td>
                                    <?php if ($usuario['ativo']): ?>
                                        <a 
                                            href="usuarios.php?toggle_status=<?= $usuario['id'] ?>" 
                                            class="status-ativo"
                                        >
                                            Ativo
                                        </a>
                                    <?php else: ?>
                                        <a 
                                            href="usuarios.php?toggle_status=<?= $usuario['id'] ?>" 
                                            class="status-inativo"
                                        >
                                            Inativo
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d/m/Y', strtotime($usuario['data_cadastro'])) ?></td>
                                <td>
                                    <a 
                                        href="editar_usuario.php?id=<?= $usuario['id'] ?>" 
                                        class="btn btn-sm btn-warning"
                                    >
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($usuario['id'] != $_SESSION['usuario_id']): ?>
                                        <button 
                                            onclick="confirmarExclusao(<?= $usuario['id'] ?>)" 
                                            class="btn btn-sm btn-danger"
                                        >
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
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