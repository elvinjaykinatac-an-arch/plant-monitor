<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$db = getDB();

$latest = $db->query("SELECT * FROM sensor_readings ORDER BY recorded_at DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$readings = $db->query("SELECT * FROM sensor_readings ORDER BY recorded_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
$readings = array_reverse($readings);
$totalCount = $db->query("SELECT COUNT(*) FROM sensor_readings")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plant Monitor — Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; background: #0d1f0f; color: #f0fdf4; min-height: 100vh; }

        .sidebar {
            position: fixed;
            top: 0; left: 0; bottom: 0;
            width: 240px;
            background: rgba(255,255,255,0.03);
            border-right: 1px solid rgba(34,197,94,0.15);
            padding: 2rem 1.5rem;
            display: flex;
            flex-direction: column;
            z-index: 10;
        }
        .sidebar-logo { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 3rem; }
        .sidebar-logo .icon {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, #16a34a, #4ade80);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
        }
        .sidebar-logo span { font-family: 'Playfair Display', serif; font-size: 1.1rem; color: #f0fdf4; }
        .nav-item {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0.7rem 1rem; border-radius: 8px;
            color: rgba(255,255,255,0.5); text-decoration: none;
            font-size: 0.9rem; transition: all 0.2s; margin-bottom: 0.25rem;
        }
        .nav-item.active, .nav-item:hover { background: rgba(74,222,128,0.1); color: #4ade80; }
        .sidebar-bottom { margin-top: auto; }
        .user-badge {
            background: rgba(255,255,255,0.05);
            border-radius: 10px; padding: 0.75rem 1rem; margin-bottom: 0.75rem;
        }
        .user-badge .name { font-size: 0.85rem; font-weight: 500; color: #f0fdf4; }
        .user-badge .role { font-size: 0.75rem; color: rgba(255,255,255,0.4); }
        .logout-btn {
            display: flex; align-items: center; gap: 0.5rem;
            color: rgba(239,68,68,0.7); text-decoration: none;
            font-size: 0.85rem; padding: 0.5rem 1rem;
            border-radius: 8px; transition: all 0.2s;
        }
        .logout-btn:hover { background: rgba(239,68,68,0.1); color: #f87171; }

        .main { margin-left: 240px; padding: 2.5rem; }
        .page-header { margin-bottom: 2rem; }
        .page-header h1 { font-family: 'Playfair Display', serif; font-size: 2rem; color: #f0fdf4; }
        .page-header p { color: rgba(255,255,255,0.4); font-size: 0.9rem; margin-top: 0.25rem; }

        .status-badge {
            display: inline-flex; align-items: center; gap: 0.4rem;
            padding: 0.3rem 0.8rem; border-radius: 20px;
            font-size: 0.8rem; font-weight: 500;
        }
        .status-badge.on { background: rgba(74,222,128,0.15); color: #4ade80; border: 1px solid rgba(74,222,128,0.3); }
        .status-badge.off { background: rgba(255,255,255,0.05); color: rgba(255,255,255,0.4); border: 1px solid rgba(255,255,255,0.1); }
        .dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.25rem; margin-bottom: 2rem;
        }
        .card {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px; padding: 1.5rem;
            transition: border-color 0.2s;
        }
        .card:hover { border-color: rgba(74,222,128,0.3); }
        .card-label {
            font-size: 0.75rem; text-transform: uppercase;
            letter-spacing: 0.1em; color: rgba(255,255,255,0.4); margin-bottom: 0.75rem;
        }
        .card-value { font-size: 2.2rem; font-weight: 600; color: #f0fdf4; line-height: 1; }
        .card-value.green { color: #4ade80; }
        .card-value.blue { color: #60a5fa; }
        .card-sub { font-size: 0.8rem; color: rgba(255,255,255,0.3); margin-top: 0.4rem; }
        .card-icon { font-size: 1.5rem; margin-bottom: 0.5rem; }

        .chart-section, .table-section {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px; padding: 1.5rem; margin-bottom: 2rem;
        }
        .chart-section h2, .table-section h2 {
            font-size: 1rem; font-weight: 500;
            margin-bottom: 1.25rem; color: rgba(255,255,255,0.7);
        }
        .chart-section canvas { max-height: 220px; }

        table { width: 100%; border-collapse: collapse; }
        th {
            text-align: left; font-size: 0.75rem;
            text-transform: uppercase; letter-spacing: 0.08em;
            color: rgba(255,255,255,0.3); padding: 0.5rem 0.75rem;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        td {
            padding: 0.75rem; font-size: 0.875rem;
            color: rgba(255,255,255,0.7);
            border-bottom: 1px solid rgba(255,255,255,0.04);
        }
        tr:last-child td { border-bottom: none; }
        tr:hover td { color: #f0fdf4; }
        .no-data { text-align: center; padding: 3rem; color: rgba(255,255,255,0.3); font-size: 0.9rem; }

        .refresh-indicator { display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.78rem; color: rgba(255,255,255,0.3); }
        .refresh-dot { width: 6px; height: 6px; border-radius: 50%; background: #4ade80; animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-logo">
            <div class="icon">🌿</div>
            <span>Plant Monitor</span>
        </div>
        <nav>
            <a href="dashboard.php" class="nav-item active">📊 Dashboard</a>
        </nav>
        <div class="sidebar-bottom">
            <div class="user-badge">
                <div class="name">👤 <?= htmlspecialchars($_SESSION['username']) ?></div>
                <div class="role">Administrator</div>
            </div>
            <a href="?logout=1" class="logout-btn">🚪 Logout</a>
        </div>
    </aside>

    <main class="main">
        <div class="page-header">
            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                <div>
                    <h1>Dashboard</h1>
                    <p>Live plant monitoring & watering status</p>
                </div>
                <div class="refresh-indicator">
                    <div class="refresh-dot"></div>
                    Auto-refresh every 10s
                </div>
            </div>
        </div>

        <div class="cards-grid">
            <div class="card">
                <div class="card-icon">💧</div>
                <div class="card-label">Soil Moisture</div>
                <div class="card-value green">
                    <?= $latest ? round($latest['moisture_percent'], 1) . '%' : '--' ?>
                </div>
                <div class="card-sub">Raw: <?= $latest ? $latest['soil_moisture'] : '--' ?></div>
            </div>

            <div class="card">
                <div class="card-icon">⚡</div>
                <div class="card-label">Pump Status</div>
                <div style="margin-top: 0.5rem;">
                    <?php if ($latest): ?>
                        <span class="status-badge <?= strtolower($latest['pump_status']) === 'on' ? 'on' : 'off' ?>">
                            <span class="dot"></span>
                            <?= htmlspecialchars($latest['pump_status']) ?>
                        </span>
                    <?php else: ?>
                        <span class="card-value" style="font-size:1.5rem;">--</span>
                    <?php endif; ?>
                </div>
                <div class="card-sub">Water pump state</div>
            </div>

            <?php if ($latest && $latest['temperature'] !== null): ?>
            <div class="card">
                <div class="card-icon">🌡️</div>
                <div class="card-label">Temperature</div>
                <div class="card-value blue"><?= $latest['temperature'] ?>°C</div>
                <div class="card-sub">Ambient temp</div>
            </div>
            <div class="card">
                <div class="card-icon">💦</div>
                <div class="card-label">Humidity</div>
                <div class="card-value blue"><?= $latest['humidity'] ?>%</div>
                <div class="card-sub">Relative humidity</div>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-icon">📈</div>
                <div class="card-label">Total Readings</div>
                <div class="card-value"><?= number_format($totalCount) ?></div>
                <div class="card-sub">All time records</div>
            </div>

            <div class="card">
                <div class="card-icon">🕐</div>
                <div class="card-label">Last Update</div>
                <div class="card-value" style="font-size: 1rem; margin-top:0.4rem;">
                    <?= $latest ? date('h:i:s A', strtotime($latest['recorded_at'])) : '--' ?>
                </div>
                <div class="card-sub">
                    <?= $latest ? date('M d, Y', strtotime($latest['recorded_at'])) : 'No data yet' ?>
                </div>
            </div>
        </div>

        <div class="chart-section">
            <h2>📉 Moisture History (Last 20 Readings)</h2>
            <?php if (count($readings) > 0): ?>
                <canvas id="moistureChart"></canvas>
            <?php else: ?>
                <div class="no-data">No readings yet. Waiting for ESP8266 data...</div>
            <?php endif; ?>
        </div>

        <div class="table-section">
            <h2>📋 Recent Readings</h2>
            <?php if (count($readings) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Moisture %</th>
                            <th>Raw Value</th>
                            <th>Pump</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($readings) as $r): ?>
                        <tr>
                            <td><?= date('M d, h:i:s A', strtotime($r['recorded_at'])) ?></td>
                            <td><?= round($r['moisture_percent'], 1) ?>%</td>
                            <td><?= $r['soil_moisture'] ?></td>
                            <td>
                                <span class="status-badge <?= strtolower($r['pump_status']) === 'on' ? 'on' : 'off' ?>">
                                    <span class="dot"></span>
                                    <?= htmlspecialchars($r['pump_status']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">No data yet. Connect your ESP8266 to start seeing readings.</div>
            <?php endif; ?>
        </div>
    </main>

    <?php if (count($readings) > 0): ?>
    <script>
        const labels = <?= json_encode(array_map(fn($r) => date('H:i:s', strtotime($r['recorded_at'])), $readings)) ?>;
        const moistureData = <?= json_encode(array_map(fn($r) => round($r['moisture_percent'], 1), $readings)) ?>;
        const pumpData = <?= json_encode(array_map(fn($r) => strtolower($r['pump_status']) === 'on' ? 100 : 0, $readings)) ?>;

        const ctx = document.getElementById('moistureChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Moisture %',
                        data: moistureData,
                        borderColor: '#4ade80',
                        backgroundColor: 'rgba(74,222,128,0.08)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#4ade80',
                        pointRadius: 4
                    },
                    {
                        label: 'Pump ON/OFF',
                        data: pumpData,
                        borderColor: 'rgba(96,165,250,0.6)',
                        backgroundColor: 'rgba(96,165,250,0.05)',
                        borderWidth: 1.5,
                        borderDash: [4, 4],
                        fill: false,
                        tension: 0,
                        pointRadius: 0
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { labels: { color: 'rgba(255,255,255,0.5)', font: { size: 11 } } }
                },
                scales: {
                    x: {
                        ticks: { color: 'rgba(255,255,255,0.3)', font: { size: 10 } },
                        grid: { color: 'rgba(255,255,255,0.04)' }
                    },
                    y: {
                        min: 0, max: 100,
                        ticks: { color: 'rgba(255,255,255,0.3)', font: { size: 10 } },
                        grid: { color: 'rgba(255,255,255,0.04)' }
                    }
                }
            }
        });

        setTimeout(() => location.reload(), 10000);
    </script>
    <?php else: ?>
    <script>setTimeout(() => location.reload(), 10000);</script>
    <?php endif; ?>
</body>
</html>