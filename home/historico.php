<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';

// Verificar permissão
if ($_SESSION['usuario_tipo'] !== 'administrador' && $_SESSION['usuario_tipo'] !== 'recepcionista') {
    header('Location: index.php');
    exit;
}

// Parâmetros de filtragem
$pagina_atual    = isset($_GET['pagina'])        ? (int)$_GET['pagina'] : 1;
$itens_por_pagina = 15;
$offset          = ($pagina_atual - 1) * $itens_por_pagina;

// Filtros
$busca         = isset($_GET['busca'])         ? trim($_GET['busca'])         : '';
$data_inicio   = isset($_GET['data_inicio'])   ? $_GET['data_inicio']         : '';
$data_fim      = isset($_GET['data_fim'])      ? $_GET['data_fim']            : '';
$chave_id      = isset($_GET['chave_id'])      ? (int)$_GET['chave_id']       : 0;
$pessoa_nome   = isset($_GET['pessoa_nome'])   ? trim($_GET['pessoa_nome'])   : '';

// Construir consulta SQL
$sql_where = [];
$params    = [];

if (!empty($busca)) {
    $sql_where[]           = "(e.pessoa_nome   LIKE :busca OR c.codigo LIKE :busca2)";
    $params[':busca']      = "%{$busca}%";
    $params[':busca2']     = "%{$busca}%";
}

if (!empty($data_inicio) && !empty($data_fim)) {
    $sql_where[]               = "DATE(e.data_emprestimo) BETWEEN :data_inicio AND :data_fim";
    $params[':data_inicio']    = $data_inicio;
    $params[':data_fim']       = $data_fim;
}

if ($chave_id > 0) {
    $sql_where[]               = "c.id = :chave_id";
    $params[':chave_id']       = $chave_id;
}

if (!empty($pessoa_nome)) {
    $sql_where[]               = "e.pessoa_nome LIKE :pessoa_nome";
    $params[':pessoa_nome']    = "%{$pessoa_nome}%";
}

// Consulta base com dois joins para pegar quem emprestou e quem devolveu
$sql_base = "
    SELECT 
        e.id,
        c.codigo               AS chave,
        e.pessoa_nome,
        e.data_emprestimo,
        e.data_devolucao,
        u1.nome                AS quem_pegou,
        u2.nome                AS quem_devolveu
    FROM emprestimos e
    LEFT JOIN chaves   c  ON e.chave_id              = c.id
    LEFT JOIN usuarios u1 ON e.usuario_id            = u1.id
    LEFT JOIN usuarios u2 ON e.usuario_devolucao_id  = u2.id
";

if (!empty($sql_where)) {
    $sql_base .= " WHERE " . implode(" AND ", $sql_where);
}

// Total de registros
$sql_count   = "SELECT COUNT(*) FROM ({$sql_base}) AS total";
$stmt_count  = $pdo->prepare($sql_count);
$stmt_count->execute($params);
$total_registros = $stmt_count->fetchColumn();
$total_paginas   = ceil($total_registros / $itens_por_pagina);

// Dados paginados
$sql        = $sql_base . " ORDER BY e.data_emprestimo DESC LIMIT :limit OFFSET :offset";
$params[':limit']  = $itens_por_pagina;
$params[':offset'] = $offset;

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmt->bindValue($key, $value, $type);
}
$stmt->execute();
$registros = $stmt->fetchAll();

// Lista de chaves para o filtro
$chaves = $pdo->query("SELECT id, codigo FROM chaves ORDER BY codigo")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico - IFCE</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="style2.css">
    <script src="https://kit.fontawesome.com/890bf7e297.js" crossorigin="anonymous"></script>
</head>
<body>
    <main class="main-content-full">
        <header class="header">
            <a href="emprestimos.php" class="btn-voltar">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
            <h1><i class="fas fa-clock-rotate-left"></i> Histórico Detalhado</h1>
        </header>

        <section class="card">
            <form method="GET" class="filtros-avancados">
                <div class="grid-4-col">
                    <div class="form-group">
                        <label>Data Início:</label>
                        <input type="date" name="data_inicio" class="form-control" 
                            value="<?= htmlspecialchars($data_inicio) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Data Fim:</label>
                        <input type="date" name="data_fim" class="form-control" 
                            value="<?= htmlspecialchars($data_fim) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Chave:</label>
                        <select name="chave_id" class="form-control">
                            <option value="">Todas</option>
                            <?php foreach ($chaves as $ch): ?>
                                <option 
                                    value="<?= $ch['id'] ?>" 
                                    <?= $ch['id'] === $chave_id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($ch['codigo']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Nome Pessoa:</label>
                        <input type="text" name="pessoa_nome" class="form-control"
                            value="<?= htmlspecialchars($pessoa_nome) ?>">
                    </div>
                </div>
                
                <div class="form-group text-center">
                    <button type="submit" class="btn-verde">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                    <a href="historico.php" class="btn-limpar">
                        <i class="fas fa-eraser"></i> Limpar
                    </a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Chave</th>
                            <th>Pessoa</th>
                            <th>Empréstimo</th>
                            <th>Devolução</th>
                            <th>Responsável pelo Empréstimo</th>
                            <th>Responsável pela Devolução</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($registros)): ?>
                            <tr>
                                <td colspan="6" class="text-center">Nenhum registro encontrado</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($registros as $reg): ?>
                                <tr>
                                    <td><?= htmlspecialchars($reg['chave']) ?></td>
                                    <td><?= htmlspecialchars($reg['pessoa_nome']) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($reg['data_emprestimo'])) ?></td>
                                    <td>
                                        <?php if ($reg['data_devolucao']): ?>
                                            <?= date('d/m/Y H:i', strtotime($reg['data_devolucao'])) ?>
                                        <?php else: ?>
                                            <span class="status-pendente">Pendente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($reg['quem_pegou']   ?? '—') ?></td>
                                    <td><?= htmlspecialchars($reg['quem_devolveu']?? '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_paginas > 1): ?>
                <nav class="paginacao-container">
                    <ul class="paginacao">
                        <?php if ($pagina_atual > 1): ?>
                            <li>
                                <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina_atual - 1])) ?>" 
                                   class="pagina-link anterior">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                            <li>
                                <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>" 
                                   class="pagina-link <?= $i === $pagina_atual ? 'ativa' : '' ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($pagina_atual < $total_paginas): ?>
                            <li>
                                <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina_atual + 1])) ?>" 
                                   class="pagina-link proxima">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>