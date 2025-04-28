<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<aside class="sidebar">
    <div class="logo">
        <img src="../assets/img/IFCE-logo-braca.png.png" alt="IFCE Cedro">
    </div>
    <ul class="menu">
        <li>
            <a href="../home/index.php" class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">
                <i class="fas fa-home"></i> Início
            </a>
        </li>
        <li>
            <a href="../home/emprestimos.php" class="<?= basename($_SERVER['PHP_SELF']) === 'emprestimos.php' ? 'active' : '' ?>">
                <i class="fas fa-keyboard"></i> Empréstimos
            </a>
        </li>
        <li>
            <a href="../home/chaves.php" class="<?= basename($_SERVER['PHP_SELF']) === 'chaves.php' ? 'active' : '' ?>">
                <i class="fas fa-key"></i> Chaves
            </a>
        </li>
        <?php if(isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] == 'administrador'): ?>
            <li>
                <a href="../home/usuarios.php" class="<?= basename($_SERVER['PHP_SELF']) === 'usuarios.php' ? 'active' : '' ?>">
                    <i class="fas fa-users-cog"></i> Usuários
                </a>
            </li>
        <?php endif; ?>
    </ul>
    <div class="user-menu">
        <span>Bem-vindo, <?= htmlspecialchars($_SESSION['usuario_nome'] ?? 'Usuário') ?></span>
        <a href="../login/login.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i></a>
    </div>
</aside>
