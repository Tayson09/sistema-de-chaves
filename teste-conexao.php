// teste-conexao.php
<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'controle_chaves';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    echo "✅ Conexão bem-sucedida!";
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage();
}
?>