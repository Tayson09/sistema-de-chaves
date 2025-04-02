<?php
require_once __DIR__ . '/../includes/auth.php';
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel - Controle de Chaves IFCE</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;700&display=swap">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="main-header">
        <div class="header-content">
            <img src="assets/img/ifce-logo.png" alt="IFCE Cedro" class="header-logo">
            <nav class="main-nav">
                <ul>
                    <li><a href="index.php" class="active"><i class="fas fa-home"></i> Início</a></li>
                    <li><a href="emprestimos.php"><i class="fas fa-key"></i> Empréstimos</a></li>
                    <li><a href="chaves.php"><i class="fas fa-keyboard"></i> Chaves</a></li>
                    <?php if($_SESSION['usuario_tipo'] == 'administrador'): ?>
                        <li><a href="usuarios.php"><i class="fas fa-users-cog"></i> Usuários</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <div class="user-menu">
                <span>Bem-vindo, <?= $_SESSION['usuario_nome'] ?></span>
                <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </div>
    </header>

    <main class="dashboard-container">
        <section class="dashboard-stats">
            <div class="stat-card">
                <h3>Chaves Disponíveis</h3>
                <div class="stat-value">24</div>
                <div class="stat-icon"><i class="fas fa-key"></i></div>
            </div>
            
            <div class="stat-card">
                <h3>Empréstimos Ativos</h3>
                <div class="stat-value">15</div>
                <div class="stat-icon"><i class="fas fa-exchange-alt"></i></div>
            </div>
            
            <div class="stat-card">
                <h3>Usuários Cadastrados</h3>
                <div class="stat-value">38</div>
                <div class="stat-icon"><i class="fas fa-users"></i></div>
            </div>
        </section>

        <section class="recent-activity">
            <h2><i class="fas fa-history"></i> Últimos Empréstimos</h2>
            <div class="activity-table">
                <table>
                    <thead>
                        <tr>
                            <th>Chave</th>
                            <th>Responsável</th>
                            <th>Data</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Dados dinâmicos do PHP aqui -->
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <footer class="main-footer">
        <p>Sistema de Controle de Chaves - IFCE Campus Cedro © <?= date('Y') ?></p>
    </footer>

    <!-- Font Awesome para ícones -->
    <script src="https://kit.fontawesome.com/your-kit-code.js" crossorigin="anonymous"></script>
</body>
</html>