<?php
// admin/dashboard.php - Dashboard moderne

include '../class/Api.php';
$api = new Api();
$stats = $api->getHotspotStats();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Hotspot Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/bootstrap5-custom.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
</head>
<body style="background: #f5f7fa;">
    <div class="container-fluid p-4">
        <!-- Header -->
        <div class="navbar-modern d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-0"><i class="fas fa-wifi"></i> Hotspot Admin</h2>
            </div>
            <div>
                <span class="me-3">👤 <?= $_SESSION['username'] ?></span>
                <a href="logout.php" class="btn btn-outline-danger btn-sm">Déconnexion</a>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="row mt-4 mb-4">
            <div class="col-md-3">
                <div class="kpi-card">
                    <i class="fas fa-users fa-2x mb-3" style="color: #667eea;"></i>
                    <div class="kpi-number"><?= $stats['active_users'] ?></div>
                    <div class="kpi-label">Utilisateurs actifs</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="kpi-card">
                    <i class="fas fa-user-plus fa-2x mb-3" style="color: #764ba2;"></i>
                    <div class="kpi-number"><?= $stats['total_users'] ?></div>
                    <div class="kpi-label">Total utilisateurs</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="kpi-card">
                    <i class="fas fa-bolt fa-2x mb-3" style="color: #f59e0b;"></i>
                    <div class="kpi-number"><?= $stats['bandwidth_used'] ?> MB</div>
                    <div class="kpi-label">Bande passante</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="kpi-card">
                    <i class="fas fa-server fa-2x mb-3" style="color: #10b981;"></i>
                    <div class="kpi-number"><?= $stats['router_uptime'] ?></div>
                    <div class="kpi-label">Uptime Routeur</div>
                </div>
            </div>
        </div>

        <!-- Graphique -->
        <div class="row">
            <div class="col-md-8">
                <div class="kpi-card">
                    <h5 class="mb-4">📊 Connexions (dernières 24h)</h5>
                    <canvas id="chartConnections"></canvas>
                </div>
            </div>
            <div class="col-md-4">
                <div class="kpi-card">
                    <h5 class="mb-4">📈 Distribution</h5>
                    <canvas id="chartDistribution"></canvas>
                </div>
            </div>
        </div>

        <!-- Tableau utilisateurs actifs -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="kpi-card">
                    <h5 class="mb-3">👥 Utilisateurs Connectés</h5>
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>Utilisateur</th>
                                <th>Adresse IP</th>
                                <th>Durée</th>
                                <th>Bande passante</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($stats['active_sessions'] as $session): ?>
                            <tr>
                                <td><?= htmlspecialchars($session['username']) ?></td>
                                <td><code><?= $session['ip_address'] ?></code></td>
                                <td><?= $session['session_time'] ?></td>
                                <td><?= $session['bandwidth'] ?></td>
                                <td>
                                    <button class="btn-modern btn-primary-modern" 
                                            onclick="disconnectUser('<?= $session['username'] ?>')">
                                        Déconnecter
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Graphique connexions
        const ctxConn = document.getElementById('chartConnections').getContext('2d');
        new Chart(ctxConn, {
            type: 'line',
            data: {
                labels: ['00:00', '04:00', '08:00', '12:00', '16:00', '20:00'],
                datasets: [{
                    label: 'Connexions',
                    data: [5, 8, 15, 25, 20, 12],
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } }
            }
        });
    </script>
</body>
</html>
