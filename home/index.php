<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel - Controle de Chaves IFCE</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap">
    <link rel="stylesheet" href="style2.css">
    <script src="https://kit.fontawesome.com/890bf7e297.js" crossorigin="anonymous"></script>
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="header">
            <h1>Painel de Controle</h1>
        </header>

        <section class="dashboard-stats">
            <div class="stat-card">
                <h3>Chaves Disponíveis</h3>
                <p class="stat-value"><?= contarChavesDisponiveis($pdo) ?></p>
                <div class="stat-icon"><i class="fas fa-key"></i></div>
            </div>
            
            <div class="stat-card">
                <h3>Empréstimos Ativos</h3>
                <p class="stat-value"><?= contarEmprestimosAtivos($pdo) ?></p>
                <div class="stat-icon"><i class="fas fa-exchange-alt"></i></div>
            </div>
            
            <div class="stat-card">
                <h3>Usuários Cadastrados</h3>
                <p class="stat-value"><?= contarUsuarios($pdo) ?></p>
                <div class="stat-icon"><i class="fas fa-users"></i></div>
            </div>
        </section>

        <section class="recent-loans">
            <h2><i class="fas fa-history"></i> Últimos Empréstimos</h2>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Chave</th>
                            <th>Pessoa</th>
                            <th>Data Empréstimo</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT 
                                    c.codigo AS chave,
                                    e.pessoa_nome AS pessoa, 
                                    e.data_emprestimo,
                                    CASE 
                                        WHEN e.data_devolucao IS NULL THEN 'Pendente'
                                        ELSE 'Devolvido'
                                    END AS status
                                FROM emprestimos e
                                LEFT JOIN chaves c ON e.chave_id = c.id  
                                ORDER BY e.data_emprestimo DESC 
                                LIMIT 5";

                        $result = $pdo->query($sql);
                        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                            echo "<tr>
                                    <td>".htmlspecialchars($row['chave'])."</td>
                                    <td>".htmlspecialchars($row['pessoa'])."</td>
                                    <td>".date('d/m/Y H:i', strtotime($row['data_emprestimo']))."</td>
                                    <td>".htmlspecialchars($row['status'])."</td>
                                </tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </section>

        <footer class="footer">
            Sistema de Controle de Chaves - IFCE Campus Cedro © <?= date('Y') ?>
        </footer>
    </main>

</body>
</html>