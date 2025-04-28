<?php
session_start();
require_once __DIR__ . '/../includes/config.php';


$mensagem = '';
$nome = '';
$localizacao = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
        $localizacao = filter_input(INPUT_POST, 'localizacao', FILTER_SANITIZE_STRING);

        // Validação
        if (empty($nome)) {
            throw new Exception("O nome da sala é obrigatório");
        }

        // Inserir no banco
        $stmt = $pdo->prepare("INSERT INTO salas (nome, localizacao) VALUES (?, ?)");
        $stmt->execute([$nome, $localizacao]);
        
        $_SESSION['mensagem'] = 'Sala cadastrada com sucesso!';
        header('Location: reserva_salas.php');
        exit();

    } catch (PDOException $e) {
        $mensagem = "Erro no banco de dados: " . $e->getMessage();
    } catch (Exception $e) {
        $mensagem = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Nova Sala - IFCE</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="style2.css">
    <script src="https://kit.fontawesome.com/890bf7e297.js" crossorigin="anonymous"></script>
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="reserva-container">
            <div class="card">
                <h1 class="mb-4"><i class="fas fa-plus-circle"></i> Cadastrar Nova Sala</h1>

                <?php if ($mensagem): ?>
                    <div class="alert alert-error">
                        <?= htmlspecialchars($mensagem) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="form-sala">
                    <div class="form-group">
                        <label for="nome"><i class="fas fa-door-open"></i> Nome da Sala:</label>
                        <input type="text" id="nome" name="nome" 
                               value="<?= htmlspecialchars($nome) ?>" 
                               required
                               placeholder="Ex: Laboratório de Informática 01">
                    </div>

                    <div class="form-group">
                        <label for="localizacao"><i class="fas fa-map-marker-alt"></i> Localização:</label>
                        <input type="text" id="localizacao" name="localizacao"
                               value="<?= htmlspecialchars($localizacao) ?>"
                               placeholder="Ex: Bloco B, 2º Andar">
                    </div>

                    <div class="form-botoes">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Salvar Sala
                        </button>
                        <a href="reserva_salas.php" class="btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>