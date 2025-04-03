<?php
$host = '127.0.0.1';
$db = 'controle_chaves';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false
];

function contarChavesDisponiveis($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM chaves WHERE disponivel = TRUE");
    return $stmt->fetchColumn();
}

function contarEmprestimosAtivos($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM emprestimos WHERE data_devolucao IS NULL");
    return $stmt->fetchColumn();
}

function contarUsuarios($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE ativo = TRUE");
    return $stmt->fetchColumn();
}

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    error_log("Erro DB: " . $e->getMessage());
    die("Erro de conexão. Contate o administrador.");
}