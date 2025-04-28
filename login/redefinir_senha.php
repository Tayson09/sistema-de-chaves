<?php
require_once __DIR__ . '/../includes/config.php';

$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);
$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha = $_POST['senha'];
    $confirmacao = $_POST['confirmacao'];
    
    if ($senha !== $confirmacao) {
        $erro = "As senhas não coincidem!";
    } elseif (strlen($senha) < 8) {
        $erro = "Senha deve ter no mínimo 8 caracteres!";
    } else {
        try {
            // Buscar usuário válido
            $stmt = $pdo->prepare("
                SELECT id 
                FROM usuarios 
                WHERE reset_token = ? 
                AND reset_expira > NOW()
            ");
            $stmt->execute([$token]);
            $usuario = $stmt->fetch();
            
            if ($usuario) {
                // Atualizar senha
                $hash = password_hash($senha, PASSWORD_DEFAULT);
                $pdo->prepare("
                    UPDATE usuarios 
                    SET senha = ?, reset_token = NULL, reset_expira = NULL 
                    WHERE id = ?
                ")->execute([$hash, $usuario['id']]);
                
                $sucesso = "Senha redefinida com sucesso!";
                header("Refresh: 3; url=login.php");
            } else {
                $erro = "Link inválido ou expirado!";
            }
            
        } catch (PDOException $e) {
            $erro = "Erro: " . $e->getMessage();
        }
    }
} else {
    // Verificar token inicial
    $stmt = $pdo->prepare("
        SELECT id 
        FROM usuarios 
        WHERE reset_token = ? 
        AND reset_expira > NOW()
    ");
    $stmt->execute([$token]);
    
    if (!$stmt->fetch()) {
        $erro = "Link inválido ou expirado!";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Senha - IFCE</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <img src="../assets/img/IFCE-logo.png" alt="IFCE Cedro">
        </div>
        
        <div class="header">
            <h1 class="system-title">Criar Nova Senha</h1>
        </div>

        <?php if ($erro): ?>
            <div class="mensagem-erro"><?= $erro ?></div>
            <div class="login-links">
                <a href="recuperar-senha.php">Solicitar novo link</a>
            </div>
        <?php else: ?>
            <?php if ($sucesso): ?>
                <div class="alert alert-success"><?= $sucesso ?></div>
            <?php else: ?>
                <form method="POST">
                    <div class="form-group">
                        <label>Nova Senha</label>
                        <input 
                            type="password" 
                            name="senha" 
                            required
                            minlength="8"
                            placeholder="Mínimo 8 caracteres"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label>Confirmar Senha</label>
                        <input 
                            type="password" 
                            name="confirmacao" 
                            required
                            minlength="8"
                        >
                    </div>
                    
                    <button type="submit">Redefinir Senha</button>
                    
                    <div class="login-links">
                        <a href="login.php">← Voltar ao Login</a>
                    </div>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>