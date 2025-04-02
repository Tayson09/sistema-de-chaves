<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400,
        'cookie_secure' => true,
        'cookie_httponly' => true
    ]);
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: /sistema/login/login.php");
    exit();
}

require_once __DIR__ . '/config.php';

try {
    $stmt = $pdo->prepare("SELECT ativo FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuario = $stmt->fetch();

    if (!$usuario || $usuario['ativo'] != 1) {
        session_destroy();
        header("Location: /sistema/login/login.php?erro=inativo");
        exit();
    }
} catch (PDOException $e) {
    error_log("Erro de verificação: " . $e->getMessage());
    die("Erro no sistema");
}