<?php
require_once '../class/i18n.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= i18n::t('dashboard.title') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/bootstrap5-custom.css" rel="stylesheet">
</head>
<body style="background: #f5f7fa;">
    <div class="container-fluid">
        <!-- Sidebar Navigation -->
        <div class="row">
            <!-- Sidebar gauche -->
            <div class="col-md-2">
                <div class="sidebar-modern p-3">
                    <h5 class="mb-4">🔧 Menu</h5>
                    <a href="dashboard.php" class="active">
                        <i class="fas fa-tachometer-alt"></i> <?= i18n::t('menu.dashboard') ?>
                    </a>
                    <a href="users.php">
                        <i class="fas fa-users"></i> <?= i18n::t('menu.users') ?>
                    </a>
                    <a href="vouchers.php">
                        <i class="fas fa-ticket-alt"></i> <?= i18n::t('menu.vouchers') ?>
                    </a>
                    <a href="reports.php">
                        <i class="fas fa-chart-bar"></i> <?= i18n::t('menu.reports') ?>
                    </a>
                    <a href="settings.php">
                        <i class="fas fa-cog"></i> <?= i18n::t('menu.settings') ?>
                    </a>
                    <hr>
                    <a href="logout.php" style="color: #e74c3c;">
                        <i class="fas fa-sign-out-alt"></i> <?= i18n::t('menu.logout') ?>
                    </a>
                </div>
            </div>

            <!-- Contenu principal -->
            <div class="col-md-10 p-4">
                <!-- Header -->
                <div class="navbar-modern d-flex justify-content-between align-items-center mb-4">
                    <h2><?= i18n::t('dashboard.welcome') ?></h2>
                    <div>
                        <span class="me-3">👤 Admin</span>
                        <div class="language-selector d-inline">
                            <a href="set-language.php?lang=fr" class="btn btn-sm btn-outline-primary">FR</a>
                            <a href="set-language.php?lang=en" class="btn btn-sm btn-outline-secondary">EN</a>
                        </div>
                    </div>
                </div>

                <!-- KPI Row 1 -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="kpi-card text-center">
                            <i class="fas fa-wifi fa-3x mb-3" style="color: #667eea;"></i>
                            <div class="kpi-number">42</div>
                            <div class="kpi-label"><?= i18n::t('dashboard.active_users') ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card text-center">
                            <i class="fas fa-user-check fa-3x mb-3" style="color: #764ba2;"></i>
                            <div class="kpi-number">287</div>
                            <div class="kpi-label"><?= i18n::t('dashboard.total_users') ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card text-center">
                            <i class="fas fa-download fa-3x mb-3" style="color: #f59e0b;"></i>
                            <div class="kpi-number">12.5 GB</div>
                            <div class="kpi-label"><?= i18n::t('dashboard.bandwidth') ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card text-center">
                            <i class="fas fa-server fa-3x mb-3" style="color: #10b981;"></i>
                            <div class="kpi-number">45 j</div>
                            <div class="kpi-label"><?= i18n::t('dashboard.uptime') ?></div>
                        </div>
                    </div>
                </div>

                <!-- Row 2 : Graphiques -->
                <div class="row mb-4">
                    <div class="col-md-7">
                        <div class="kpi-card">
                            <h5><?= i18n::t('dashboard.connections_24h') ?></h5>
                            <!-- Graphique ici -->
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="kpi-card">
                            <h5>📊 Top Utilisateurs</h5>
                            <!-- Liste top users -->
                        </div>
                    </div>
                </div>

                <!-- Row 3 : Actions rapides -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="kpi-card">
                            <h5 class="mb-3">⚡ Actions rapides</h5>
                            <button class="btn-modern btn-primary-modern me-2">
                                ➕ <?= i18n::t('users.add') ?>
                            </button>
                            <button class="btn-modern btn-primary-modern me-2">
                                🎟️ <?= i18n::t('vouchers.generate') ?>
                            </button>
                            <button class="btn-modern btn-primary-modern me-2">
                                📥 <?= i18n::t('button.export') ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>