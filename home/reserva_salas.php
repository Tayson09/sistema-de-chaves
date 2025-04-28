<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

// Verificar autenticação
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login/login.php');
    exit();
}

$is_admin = ($_SESSION['tipo'] ?? '') === 'administrador';

// Configurar timezone
date_default_timezone_set('America/Fortaleza');

// Gerar token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Processar nova reserva
$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservar'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Token CSRF inválido!');
    }

    $sala_id = $_POST['sala_id'];
    $dia_semana = $_POST['dia_semana'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fim = $_POST['hora_fim'];
    $usuario_id = $_SESSION['usuario_id'];

    try {
        // Validar horário
        if (strtotime($hora_fim) <= strtotime($hora_inicio)) {
            throw new Exception('Horário final deve ser posterior ao inicial');
        }

        // Verificar conflitos
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservas_salas 
                             WHERE sala_id = ? 
                             AND dia_semana = ?
                             AND status = 'Ativa'
                             AND (
                                 (hora_inicio < ? AND hora_fim > ?) OR
                                 (hora_inicio BETWEEN ? AND ?) OR
                                 (hora_fim BETWEEN ? AND ?)
                             )");
        $stmt->execute([
            $sala_id, $dia_semana,
            $hora_inicio, $hora_inicio,
            $hora_inicio, $hora_fim,
            $hora_inicio, $hora_fim
        ]);

        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Conflito de horário nesta sala');
        }

        // Inserir reserva
        $stmt = $pdo->prepare("INSERT INTO reservas_salas 
                              (sala_id, usuario_id, dia_semana, hora_inicio, hora_fim) 
                              VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$sala_id, $usuario_id, $dia_semana, $hora_inicio, $hora_fim]);
        $mensagem = 'Reserva criada com sucesso!';

    } catch (Exception $e) {
        $mensagem = 'Erro: ' . $e->getMessage();
    }
}

// Processar cancelamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelar_id'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Token CSRF inválido!');
    }

    $id = $_POST['cancelar_id'];
    
    try {
        // Verificar permissão
        $stmt = $pdo->prepare("SELECT usuario_id FROM reservas_salas WHERE id = ?");
        $stmt->execute([$id]);
        $reserva = $stmt->fetch();

        if ($reserva && ($reserva['usuario_id'] === $_SESSION['usuario_id'] || $is_admin)) {
            $stmt = $pdo->prepare("UPDATE reservas_salas 
                                  SET status = 'Cancelada', canceled_at = NOW(), canceled_by = ?
                                  WHERE id = ?");
            $stmt->execute([$_SESSION['usuario_id'], $id]);
            $mensagem = 'Reserva cancelada com sucesso!';
        } else {
            throw new Exception('Permissão negada');
        }
    } catch (Exception $e) {
        $mensagem = 'Erro: ' . $e->getMessage();
    }
}

// Obter dados
$salas = $pdo->query("SELECT * FROM salas WHERE ativo = 1 ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$reservas_por_sala = [];

foreach ($salas as $sala) {
    $stmt = $pdo->prepare("SELECT rs.*, u.nome AS usuario_nome 
                          FROM reservas_salas rs
                          JOIN usuarios u ON rs.usuario_id = u.id
                          WHERE sala_id = ? AND status = 'Ativa'
                          ORDER BY dia_semana, hora_inicio");
    $stmt->execute([$sala['id']]);
    $reservas_por_sala[$sala['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Reserva de Salas - IFCE Cedro</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="reserva.css">
    <script src="https://kit.fontawesome.com/890bf7e297.js" crossorigin="anonymous"></script>
    
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="reserva-container">
            <?php if ($mensagem): ?>
                <div class="alert <?= strpos($mensagem, 'sucesso') !== false ? 'alert-success' : 'alert-error' ?>">
                    <?= htmlspecialchars($mensagem) ?>
                </div>
            <?php endif; ?>

            <div class="card mb-4">
                <h1><i class="fas fa-door-open"></i> Gerenciamento de Salas</h1>
                <a href="cadastrar_sala.php" class="btn-cadastrar">
                    <i class="fas fa-plus-circle"></i> Nova Sala
                </a>
            </div>

            <div class="sala-grid">
                <?php foreach ($salas as $sala): ?>
                <div class="sala-card card">
                    <div class="sala-header">
                        <div>
                            <h3><?= htmlspecialchars($sala['nome']) ?></h3>
                            <?php if ($sala['localizacao']): ?>
                                <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($sala['localizacao']) ?></p>
                            <?php endif; ?>
                        </div>
                        <button class="btn-expand">
                            <i class="fas fa-expand-alt"></i>
                        </button>
                    </div>

                    <div class="reservas-content">
                        <div class="reservas-list">
                            <?php foreach ($reservas_por_sala[$sala['id']] as $reserva): ?>
                            <div class="reserva-item">
                                <div class="reserva-info">
                                    <span class="dia"><?= $reserva['dia_semana'] ?></span>
                                    <span class="horario">
                                        <?= date('H:i', strtotime($reserva['hora_inicio'])) ?> - 
                                        <?= date('H:i', strtotime($reserva['hora_fim'])) ?>
                                    </span>
                                    <small><?= htmlspecialchars($reserva['usuario_nome']) ?></small>
                                </div>
                                <div class="reserva-actions">
                                    <form method="POST" onsubmit="return confirm('Cancelar esta reserva?');">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <input type="hidden" name="cancelar_id" value="<?= $reserva['id'] ?>">
                                        <button type="submit" class="btn-danger btn-sm">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <form class="reserva-form" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="sala_id" value="<?= $sala['id'] ?>">
                            
                            <div class="form-group-inline">
                                <select name="dia_semana" class="form-control" required>
                                    <option value="Segunda">Segunda</option>
                                    <option value="Terça">Terça</option>
                                    <option value="Quarta">Quarta</option>
                                    <option value="Quinta">Quinta</option>
                                    <option value="Sexta">Sexta</option>
                                </select>
                                
                                <input type="time" name="hora_inicio" class="form-control" required>
                                <input type="time" name="hora_fim" class="form-control" required>
                                
                                <button type="submit" name="reservar" class="btn-primary">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Alternar expansão dos cards
        document.querySelectorAll('.btn-expand').forEach(button => {
            button.addEventListener('click', () => {
                const card = button.closest('.sala-card');
                card.classList.toggle('expanded');
                const content = card.querySelector('.reservas-content');
                content.classList.toggle('show');
                button.innerHTML = card.classList.contains('expanded') ? 
                    '<i class="fas fa-compress-alt"></i>' : 
                    '<i class="fas fa-expand-alt"></i>';
            });
        });
    });
    </script>
</body>
</html>