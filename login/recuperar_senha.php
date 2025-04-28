<?php
require_once __DIR__ . '/../includes/config.php';

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        try {
            // Gerar token
            $token = bin2hex(random_bytes(32));
            $expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Salvar no banco
            $stmt = $pdo->prepare("
                UPDATE usuarios 
                SET reset_token = ?, reset_expira = ? 
                WHERE email = ?
            ");
            $stmt->execute([$token, $expiracao, $email]);
            
            // Chamar Google Script
            $scriptUrl = 'https://script.google.com/macros/s/AKfycbxAaiFLkyQfO0c1k_hNgdKokQ_AM-oq4JSfrjkmDNER4xlIjRJLOY9PxbBqyCKG00Pc_Q/exec';
            $link = "http://localhost/sistema/login/redefinir_senha.php?token=$token";
            
            $data = [
                'email' => $email,
                'link' => $link
            ];
            
            $options = [
                'http' => [
                    'header'  => "Content-type: application/json\r\n",
                    'method'  => 'POST',
                    'content' => json_encode($data)
                ]
            ];
            
            $context = stream_context_create($options);
            $result = file_get_contents($scriptUrl, false, $context);
            
            if ($result === false) {
                throw new Exception("Erro de conexão com o serviço de e-mail");
            }
            
            $response = json_decode($result, true);
            if ($response['success']) {
                $sucesso = "Instruções enviadas para seu e-mail!";
            } else {
                $erro = "Erro no envio: " . ($response['error'] ?? 'Erro desconhecido');
            }
            
        } catch (Exception $e) {
            $erro = "Erro: " . $e->getMessage();
        }
    } else {
        $erro = "E-mail inválido!";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - IFCE</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <img src="../assets/img/IFCE-logo.png" alt="IFCE Cedro">
        </div>
        
        <div class="header">
            <h1 class="system-title">Recuperação de Senha</h1>
        </div>

        <?php if ($erro): ?>
            <div class="mensagem-erro"><?= $erro ?></div>
        <?php endif; ?>
        
        <?php if ($sucesso): ?>
            <div class="alert alert-success"><?= $sucesso ?></div>
            <div class="login-links">
                <a href="login.php">← Voltar ao Login</a>
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label>E-mail cadastrado</label>
                    <input 
                        type="email" 
                        name="email" 
                        required
                        placeholder="exemplo@ifce.edu.br"
                    >
                </div>
                
                <button type="submit">Enviar Instruções</button>
                
                <div class="login-links">
                    <a href="login.php">Lembrou sua senha? Faça login</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>