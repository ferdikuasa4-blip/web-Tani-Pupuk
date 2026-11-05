<nav class="navbar navbar-dark topbar px-3">
    <a class="navbar-brand fw-bold" href="index.php?page=<?= htmlspecialchars($landing, ENT_QUOTES, 'UTF-8') ?>">
        🌱 <?= htmlspecialchars($_SESSION['app_name'] ?? 'TANI PUPUK') ?>
    </a>
    <div class="text-white">
        <?= htmlspecialchars($u['nama'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?> &nbsp; 
        <a class="text-white" href="index.php?page=logout">Keluar</a>
    </div>
</nav>
<div class="layout">