<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400,
        'cookie_secure'   => true,
        'cookie_httponly' => true
    ]);
}

require_once __DIR__ . '/../includes/config.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $senha = $_POST['senha'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            $senhaSalva = $usuario['senha'];
            $idUsuario  = $usuario['id'];

            // Primeiro, tenta com password_verify (BCrypt, Argon2 etc)
            if (password_verify($senha, $senhaSalva)) {
                // Atualiza se necessário para o algoritmo/configuração atual
                if (password_needs_rehash($senhaSalva, PASSWORD_DEFAULT)) {
                    $novaHash = password_hash($senha, PASSWORD_DEFAULT);
                    $upd = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
                    $upd->execute([$novaHash, $idUsuario]);
                }
                $_SESSION['usuario_id']   = $idUsuario;
                $_SESSION['usuario_nome'] = htmlspecialchars($usuario['nome']);
                $_SESSION['usuario_tipo'] = $usuario['tipo'];
                header('Location: /sistema/home/index.php');
                exit();
            }
            // Se falhar, tenta fallback SHA-256
            elseif (hash('sha256', $senha) === $senhaSalva) {
                // Converte para Bcrypt
                $novaHash = password_hash($senha, PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
                $upd->execute([$novaHash, $idUsuario]);

                $_SESSION['usuario_id']   = $idUsuario;
                $_SESSION['usuario_nome'] = htmlspecialchars($usuario['nome']);
                $_SESSION['usuario_tipo'] = $usuario['tipo'];
                header('Location: /sistema/home/index.php');
                exit();
            }
            else {
                $erro = "Credenciais inválidas!";
                error_log("Falha no login para o email: $email");
            }
        } else {
            $erro = "Credenciais inválidas!";
            error_log("Tentativa de login com e-mail inexistente: $email");
        }
    } catch (PDOException $e) {
        error_log("Erro de login: " . $e->getMessage());
        $erro = "Erro no sistema. Tente novamente mais tarde.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <title>Sistema de Controle de Chaves - IFCE Campus Cedro</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;700&display=swap">
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <img src="/sistema/assets/img/IFCE-logo.png" alt="IFCE Cedro">
        </div>
        <div class="header">
            <h1 class="system-title">Controle de Chaves</h1>
        </div>

        <?php if ($erro): ?>
            <div class="mensagem-erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
            <div class="form-group">
                <label for="email">E-mail:</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="senha">Senha:</label>
                <input type="password" id="senha" name="senha" required>
            </div>

            <button type="submit">Acessar Sistema</button>

            <div class="login-links">
                <a href="/sistema/login/recuperar_senha.php">Recuperar Senha</a>
            </div>
        </form>
    </div>
</body>
</html>
