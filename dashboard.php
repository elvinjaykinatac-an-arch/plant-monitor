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
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plant Monitor — Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        [data-theme="dark"] {
            --bg: #0d1f0f;
            --bg2: rgba(255,255,255,0.04);
            --border: rgba(255,255,255,0.08);
            --border-green: rgba(34,197,94,0.15);
            --text: #f0fdf4;
            --text-muted: rgba(255,255,255,0.4);
            --text-dim: rgba(255,255,255,0.3);
            --sidebar-bg: rgba(255,255,255,0.03);
            --card-hover: rgba(74,222,128,0.3);
            --nav-active-bg: rgba(74,222,128,0.1);
            --nav-active-color: #4ade80;
            --user-badge-bg: rgba(255,255,255,0.05);
            --logout-color: rgba(239,68,68,0.7);
            --logout-hover-bg: rgba(239,68,68,0.1);
            --logout-hover-color: #f87171;
            --table-border: rgba(255,255,255,0.06);
            --table-row-border: rgba(255,255,255,0.04);
            --toggle-bg: rgba(255,255,255,0.08);
            --toggle-hover: rgba(255,255,255,0.15);
            --no-data: rgba(255,255,255,0.3);
            --faq-q-bg: rgba(74,222,128,0.08);
            --faq-q-border: rgba(74,222,128,0.2);
            --input-bg: rgba(255,255,255,0.06);
            --input-border: rgba(255,255,255,0.1);
            --btn-filter-bg: #16a34a;
            --btn-clear-bg: rgba(255,255,255,0.08);
            --btn-clear-color: rgba(255,255,255,0.6);
        }

        [data-theme="light"] {
            --bg: #f0fdf4;
            --bg2: rgba(0,0,0,0.03);
            --border: rgba(0,0,0,0.08);
            --border-green: rgba(22,163,74,0.2);
            --text: #052e16;
            --text-muted: rgba(0,0,0,0.5);
            --text-dim: rgba(0,0,0,0.35);
            --sidebar-bg: rgba(255,255,255,0.8);
            --card-hover: rgba(22,163,74,0.4);
            --nav-active-bg: rgba(22,163,74,0.1);
            --nav-active-color: #16a34a;
            --user-badge-bg: rgba(0,0,0,0.04);
            --logout-color: rgba(220,38,38,0.7);
            --logout-hover-bg: rgba(220,38,38,0.08);
            --logout-hover-color: #dc2626;
            --table-border: rgba(0,0,0,0.08);
            --table-row-border: rgba(0,0,0,0.04);
            --toggle-bg: rgba(0,0,0,0.06);
            --toggle-hover: rgba(0,0,0,0.12);
            --no-data: rgba(0,0,0,0.35);
            --faq-q-bg: rgba(22,163,74,0.06);
            --faq-q-border: rgba(22,163,74,0.2);
            --input-bg: rgba(0,0,0,0.04);
            --input-border: rgba(0,0,0,0.15);
            --btn-filter-bg: #16a34a;
            --btn-clear-bg: rgba(0,0,0,0.06);
            --btn-clear-color: rgba(0,0,0,0.5);
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            transition: background 0.3s, color 0.3s;
        }

        .sidebar {
            position: fixed;
            top: 0; left: 0; bottom: 0; width: 240px;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--border-green);
            padding: 2rem 1.5rem;
            display: flex; flex-direction: column;
            z-index: 10; backdrop-filter: blur(10px);
            transition: background 0.3s, border-color 0.3s;
        }
        .sidebar-logo { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 3rem; }
        .sidebar-logo .icon {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, #16a34a, #4ade80);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
        }
        .sidebar-logo span { font-family: 'Playfair Display', serif; font-size: 1.1rem; color: var(--text); }
        .nav-item {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0.7rem 1rem; border-radius: 8px;
            color: var(--text-muted); text-decoration: none;
            font-size: 0.9rem; transition: all 0.2s; margin-bottom: 0.25rem;
        }
        .nav-item.active, .nav-item:hover { background: var(--nav-active-bg); color: var(--nav-active-color); }
        .sidebar-bottom { margin-top: auto; }
        .user-badge {
            background: var(--user-badge-bg);
            border-radius: 10px; padding: 0.75rem 1rem; margin-bottom: 0.75rem;
        }
        .user-badge .name { font-size: 0.85rem; font-weight: 500; color: var(--text); }
        .user-badge .role { font-size: 0.75rem; color: var(--text-muted); }
        .logout-btn {
            display: flex; align-items: center; gap: 0.5rem;
            color: var(--logout-color); text-decoration: none;
            font-size: 0.85rem; padding: 0.5rem 1rem;
            border-radius: 8px; transition: all 0.2s;
        }
        .logout-btn:hover { background: var(--logout-hover-bg); color: var(--logout-hover-color); }

        .main { margin-left: 240px; padding: 2.5rem; }
        .page-header { margin-bottom: 2rem; }
        .page-header h1 { font-family: 'Playfair Display', serif; font-size: 2rem; color: var(--text); }
        .page-header p { color: var(--text-muted); font-size: 0.9rem; margin-top: 0.25rem; }
        .last-updated { font-size: 0.75rem; color: var(--text-dim); margin-top: 0.4rem; }

        .header-right { display: flex; align-items: center; gap: 1rem; }
        .theme-toggle {
            display: flex; align-items: center; gap: 0.5rem;
            background: var(--toggle-bg); border: 1px solid var(--border);
            border-radius: 20px; padding: 0.35rem 0.75rem;
            cursor: pointer; font-size: 0.8rem; color: var(--text-muted);
            transition: all 0.2s; user-select: none;
        }
        .theme-toggle:hover { background: var(--toggle-hover); color: var(--text); }

        .live-indicator { display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.78rem; color: var(--text-dim); }
        .live-dot { width: 6px; height: 6px; border-radius: 50%; background: #4ade80; animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }

        .status-badge {
            display: inline-flex; align-items: center; gap: 0.4rem;
            padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.8rem; font-weight: 500;
        }
        .status-badge.on { background: rgba(74,222,128,0.15); color: #4ade80; border: 1px solid rgba(74,222,128,0.3); }
        .status-badge.off { background: var(--bg2); color: var(--text-muted); border: 1px solid var(--border); }
        .dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.25rem; margin-bottom: 2rem;
        }
        .card {
            background: var(--bg2); border: 1px solid var(--border);
            border-radius: 16px; padding: 1.5rem;
            transition: border-color 0.2s, background 0.3s;
        }
        .card:hover { border-color: var(--card-hover); }
        .card-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-muted); margin-bottom: 0.75rem; }
        .card-value { font-size: 2.2rem; font-weight: 600; color: var(--text); line-height: 1; }
        .card-value.green { color: #4ade80; }
        .card-sub { font-size: 0.8rem; color: var(--text-dim); margin-top: 0.4rem; }
        .card-icon { font-size: 1.5rem; margin-bottom: 0.5rem; }

        .chart-section, .table-section, .faq-section, .history-section {
            background: var(--bg2); border: 1px solid var(--border);
            border-radius: 16px; padding: 1.5rem; margin-bottom: 2rem;
            transition: background 0.3s, border-color 0.3s;
        }
        .chart-section h2, .table-section h2, .faq-section h2, .history-section h2 {
            font-size: 1rem; font-weight: 500; margin-bottom: 1.25rem; color: var(--text-muted);
        }
        .chart-section canvas { max-height: 220px; }

        table { width: 100%; border-collapse: collapse; }
        th {
            text-align: left; font-size: 0.75rem; text-transform: uppercase;
            letter-spacing: 0.08em; color: var(--text-dim); padding: 0.5rem 0.75rem;
            border-bottom: 1px solid var(--table-border);
        }
        td { padding: 0.75rem; font-size: 0.875rem; color: var(--text-muted); border-bottom: 1px solid var(--table-row-border); }
        tr:last-child td { border-bottom: none; }
        tr:hover td { color: var(--text); }
        .no-data { text-align: center; padding: 3rem; color: var(--no-data); font-size: 0.9rem; }

        /* ===== DATE FILTER ===== */
        .filter-bar {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-bottom: 1.25rem;
            padding: 1rem 1.25rem;
            background: var(--bg2);
            border: 1px solid var(--border);
            border-radius: 12px;
        }

        .filter-bar label {
            font-size: 0.8rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            white-space: nowrap;
        }

        .filter-bar input[type="date"] {
            padding: 0.5rem 0.75rem;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 8px;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.875rem;
            outline: none;
            transition: border-color 0.2s;
            cursor: pointer;
        }

        .filter-bar input[type="date"]:focus {
            border-color: #4ade80;
        }

        .filter-bar input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(0.5);
            cursor: pointer;
        }

        .btn-filter {
            padding: 0.5rem 1.25rem;
            background: var(--btn-filter-bg);
            border: none;
            border-radius: 8px;
            color: white;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: opacity 0.2s, transform 0.1s;
            white-space: nowrap;
        }
        .btn-filter:hover { opacity: 0.85; transform: translateY(-1px); }
        .btn-filter:active { transform: translateY(0); }

        .btn-clear {
            padding: 0.5rem 1rem;
            background: var(--btn-clear-bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--btn-clear-color);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .btn-clear:hover { background: var(--toggle-hover); color: var(--text); }

        .filter-result-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.25rem 0.75rem;
            background: rgba(74,222,128,0.1);
            border: 1px solid rgba(74,222,128,0.2);
            border-radius: 20px;
            font-size: 0.78rem;
            color: #4ade80;
            margin-left: auto;
        }

        /* ===== FAQ ===== */
        .faq-item {
            border: 1px solid var(--border); border-radius: 12px;
            margin-bottom: 0.75rem; overflow: hidden; transition: border-color 0.2s;
        }
        .faq-item:hover { border-color: var(--faq-q-border); }
        .faq-question {
            display: flex; justify-content: space-between; align-items: center;
            padding: 1rem 1.25rem; cursor: pointer;
            background: var(--faq-q-bg); transition: background 0.2s; gap: 1rem;
        }
        .faq-question:hover { background: var(--nav-active-bg); }
        .faq-question span { font-size: 0.9rem; font-weight: 500; color: var(--text); line-height: 1.4; }
        .faq-icon { font-size: 1.1rem; color: var(--nav-active-color); transition: transform 0.3s; flex-shrink: 0; }
        .faq-answer {
            display: none; padding: 1rem 1.25rem; font-size: 0.875rem;
            color: var(--text-muted); line-height: 1.6; border-top: 1px solid var(--border);
        }
        .faq-item.open .faq-answer { display: block; }
        .faq-item.open .faq-icon { transform: rotate(45deg); }
        .faq-item.open .faq-question { background: var(--nav-active-bg); }
        .faq-item.open .faq-question span { color: var(--nav-active-color); }
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
            <a href="#history" class="nav-item" onclick="document.getElementById('history').scrollIntoView({behavior:'smooth'}); return false;">📅 History</a>
            <a href="#faq" class="nav-item" onclick="document.getElementById('faq').scrollIntoView({behavior:'smooth'}); return false;">❓ FAQ</a>
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
                    <div class="last-updated">Last updated: <span id="lastUpdatedTime">--</span></div>
                </div>
                <div class="header-right">
                    <div class="live-indicator">
                        <div class="live-dot"></div>
                        Live
                    </div>
                    <div class="theme-toggle" onclick="toggleTheme()">
                        <span id="themeIcon">🌙</span>
                        <span id="themeLabel">Dark</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- CARDS -->
        <div class="cards-grid">
            <div class="card">
                <div class="card-icon">💧</div>
                <div class="card-label">Soil Moisture</div>
                <div class="card-value green" id="moisturePercent">
                    <?= $latest ? round($latest['moisture_percent'], 1) . '%' : '--' ?>
                </div>
                <div class="card-sub">Raw: <span id="moistureRaw"><?= $latest ? $latest['soil_moisture'] : '--' ?></span></div>
            </div>

            <div class="card">
                <div class="card-icon">⚡</div>
                <div class="card-label">Pump Status</div>
                <div style="margin-top: 0.5rem;" id="pumpStatusContainer">
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

            <div class="card">
                <div class="card-icon">📈</div>
                <div class="card-label">Total Readings</div>
                <div class="card-value" id="totalCount"><?= number_format($totalCount) ?></div>
                <div class="card-sub">All time records</div>
            </div>

            <div class="card">
                <div class="card-icon">🕐</div>
                <div class="card-label">Last Update</div>
                <div class="card-value" style="font-size: 1rem; margin-top:0.4rem;" id="lastTime">
                    <?= $latest ? date('h:i:s A', strtotime($latest['recorded_at'])) : '--' ?>
                </div>
                <div class="card-sub" id="lastDate">
                    <?= $latest ? date('M d, Y', strtotime($latest['recorded_at'])) : 'No data yet' ?>
                </div>
            </div>
        </div>

        <!-- CHART -->
        <div class="chart-section">
            <h2>📉 Moisture History (Last 20 Readings)</h2>
            <canvas id="moistureChart" <?= count($readings) === 0 ? 'style="display:none"' : '' ?>></canvas>
            <div class="no-data" id="chartNoData" <?= count($readings) > 0 ? 'style="display:none"' : '' ?>>
                No readings yet. Waiting for ESP8266 data...
            </div>
        </div>

        <!-- LIVE TABLE -->
        <div class="table-section">
            <h2>📋 Recent Readings (Last 20)</h2>
            <div id="tableContainer">
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
        </div>

        <!-- HISTORY WITH DATE FILTER -->
        <div class="history-section" id="history">
            <h2>📅 Historical Data</h2>

            <!-- Filter Bar -->
            <div class="filter-bar">
                <label>From</label>
                <input type="date" id="startDate">
                <label>To</label>
                <input type="date" id="endDate">
                <button class="btn-filter" onclick="applyFilter()">🔍 Search</button>
                <button class="btn-clear" onclick="clearFilter()">✕ Clear</button>
                <div class="filter-result-badge" id="filterBadge" style="display:none;">
                    <span id="filterCount">0</span> records found
                </div>
            </div>

            <!-- History Table -->
            <div id="historyTableContainer">
                <div class="no-data">Select a date range and click Search to view historical data.</div>
            </div>
        </div>

        <!-- FAQ -->
        <div class="faq-section" id="faq">
            <h2>❓ Frequently Asked Questions</h2>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>Can I use the system without internet?</span>
                    <span class="faq-icon">+</span>
                </div>
                <div class="faq-answer">
                    No. The system requires an internet connection to send data to the Railway cloud database and to access the web dashboard. However, the ESP8266 will still control the pump locally based on soil moisture even without internet.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>Can I use a mobile hotspot instead of WiFi?</span>
                    <span class="faq-icon">+</span>
                </div>
                <div class="faq-answer">
                    Yes! You can use a mobile hotspot. Just make sure it is set to 2.4GHz band. The ESP8266 does NOT support 5GHz networks. The phone must have mobile data for the ESP8266 to reach the Railway server.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>How many email alerts will I receive?</span>
                    <span class="faq-icon">+</span>
                </div>
                <div class="faq-answer">
                    Only 2 emails per watering cycle — one when the pump turns ON (soil is dry) and one when the pump turns OFF (soil is wet). The system will NOT spam your inbox with repeated emails for the same status.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>Can I access the dashboard on my phone?</span>
                    <span class="faq-icon">+</span>
                </div>
                <div class="faq-answer">
                    Yes! The dashboard is accessible on any device with a web browser — computer, smartphone, or tablet. Just open the browser and go to the system URL.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>Can I change the email recipient?</span>
                    <span class="faq-icon">+</span>
                </div>
                <div class="faq-answer">
                    Yes. The recipient email can be changed anytime in the Railway environment variables (ALERT_EMAIL) without changing any code.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>What happens if the WiFi disconnects?</span>
                    <span class="faq-icon">+</span>
                </div>
                <div class="faq-answer">
                    The ESP8266 will automatically attempt to reconnect to WiFi. The pump will still function based on the soil moisture sensor readings. Data will resume sending to the database once WiFi is restored.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>How often does the sensor read and send data?</span>
                    <span class="faq-icon">+</span>
                </div>
                <div class="faq-answer">
                    The sensor reads and sends data every 5 seconds. The dashboard also auto-updates every 5 seconds without needing to refresh the page manually.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>What does the Dark/Light mode toggle do?</span>
                    <span class="faq-icon">+</span>
                </div>
                <div class="faq-answer">
                    The 🌙 Dark / ☀️ Light toggle switches the dashboard appearance between dark and light mode. Your preference is saved automatically and will be remembered even after closing the browser.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>What is the default login credentials?</span>
                    <span class="faq-icon">+</span>
                </div>
                <div class="faq-answer">
                    The default username is <strong>admin</strong> and the default password is <strong>admin2026</strong>. It is recommended to change the password after the first login for security.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>Why is the dashboard showing "--" for all values?</span>
                    <span class="faq-icon">+</span>
                </div>
                <div class="faq-answer">
                    This means the ESP8266 has not sent any data yet. Make sure the NodeMCU is powered on, connected to WiFi, and the Arduino code is running. Check the Serial Monitor for any error messages.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>How do I use the Historical Data filter?</span>
                    <span class="faq-icon">+</span>
                </div>
                <div class="faq-answer">
                    Scroll down to the "📅 Historical Data" section. Select a Start Date and End Date using the date pickers, then click the 🔍 Search button. All readings within that date range will be displayed. Click ✕ Clear to reset the filter.
                </div>
            </div>
        </div>
    </main>

    <script>
        // ===== THEME =====
        function toggleTheme() {
            const html = document.documentElement;
            const isDark = html.getAttribute('data-theme') === 'dark';
            const newTheme = isDark ? 'light' : 'dark';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            document.getElementById('themeIcon').textContent = newTheme === 'dark' ? '🌙' : '☀️';
            document.getElementById('themeLabel').textContent = newTheme === 'dark' ? 'Dark' : 'Light';
            updateChartColors();
        }

        const savedTheme = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
        document.getElementById('themeIcon').textContent = savedTheme === 'dark' ? '🌙' : '☀️';
        document.getElementById('themeLabel').textContent = savedTheme === 'dark' ? 'Dark' : 'Light';

        // ===== FAQ =====
        function toggleFaq(questionEl) {
            const item = questionEl.parentElement;
            const isOpen = item.classList.contains('open');
            document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('open'));
            if (!isOpen) item.classList.add('open');
        }

        // ===== DATE FILTER =====
        // Set default dates (today and 7 days ago)
        const today = new Date();
        const weekAgo = new Date();
        weekAgo.setDate(today.getDate() - 7);

        document.getElementById('endDate').value = today.toISOString().split('T')[0];
        document.getElementById('startDate').value = weekAgo.toISOString().split('T')[0];

        function applyFilter() {
            const start = document.getElementById('startDate').value;
            const end = document.getElementById('endDate').value;

            if (!start || !end) {
                alert('Please select both start and end dates.');
                return;
            }

            if (start > end) {
                alert('Start date cannot be later than end date.');
                return;
            }

            const container = document.getElementById('historyTableContainer');
            container.innerHTML = '<div class="no-data">🔍 Loading...</div>';

            fetch(fetch_data.php?start=${start}&end=${end})
                .then(res => res.json())
                .then(data => {
                    const badge = document.getElementById('filterBadge');
                    const countEl = document.getElementById('filterCount');

                    if (!data.readings || data.readings.length === 0) {
                        container.innerHTML = '<div class="no-data">No records found for the selected date range.</div>';
                        badge.style.display = 'none';
                        return;
                    }

                    badge.style.display = 'inline-flex';
                    countEl.textContent = data.count;

                    const rows = data.readings.map(r => `
                        <tr>
                            <td>${r.recorded_at_formatted}</td>
                            <td>${parseFloat(r.moisture_percent).toFixed(1)}%</td>
                            <td>${r.soil_moisture}</td>
                            <td>
                                <span class="status-badge ${r.pump_status.toLowerCase() === 'on' ? 'on' : 'off'}">
                                    <span class="dot"></span>
                                    ${r.pump_status}
                                </span>
                            </td>
                        </tr>`).join('');

                    container.innerHTML = `
                        <table>
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Moisture %</th>
                                    <th>Raw Value</th>
                                    <th>Pump</th>
                                </tr>
                            </thead>
                            <tbody>${rows}</tbody>
                        </table>`;
                })
                .catch(() => {
                    container.innerHTML = '<div class="no-data">Error loading data. Please try again.</div>';
                });
        }

        function clearFilter() {
            document.getElementById('startDate').value = weekAgo.toISOString().split('T')[0];
            document.getElementById('endDate').value = today.toISOString().split('T')[0];
            document.getElementById('historyTableContainer').innerHTML =
                '<div class="no-data">Select a date range and click Search to view historical data.</div>';
            document.getElementById('filterBadge').style.display = 'none';
        }

        // ===== CHART =====
        const initialLabels = <?= json_encode(array_map(fn($r) => date('H:i:s', strtotime($r['recorded_at'])), $readings)) ?>;
        const initialMoisture = <?= json_encode(array_map(fn($r) => round($r['moisture_percent'], 1), $readings)) ?>;
        const initialPump = <?= json_encode(array_map(fn($r) => strtolower($r['pump_status']) === 'on' ? 100 : 0, $readings)) ?>;

        function getTickColor() {
            return document.documentElement.getAttribute('data-theme') === 'dark'
                ? 'rgba(255,255,255,0.3)' : 'rgba(0,0,0,0.4)';
        }
        function getGridColor() {
            return document.documentElement.getAttribute('data-theme') === 'dark'
                ? 'rgba(255,255,255,0.04)' : 'rgba(0,0,0,0.06)';
        }

        const ctx = document.getElementById('moistureChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: initialLabels,
                datasets: [
                    {
                        label: 'Moisture %',
                        data: initialMoisture,
                        borderColor: '#4ade80',
                        backgroundColor: 'rgba(74,222,128,0.08)',
                        borderWidth: 2, fill: true, tension: 0.4,
                        pointBackgroundColor: '#4ade80', pointRadius: 4
                    },
                    {
                        label: 'Pump ON/OFF',
                        data: initialPump,
                        borderColor: 'rgba(96,165,250,0.6)',
                        backgroundColor: 'rgba(96,165,250,0.05)',
                        borderWidth: 1.5, borderDash: [4, 4],
                        fill: false, tension: 0, pointRadius: 0
                    }
                ]
            },
            options: {
                responsive: true,
                animation: { duration: 500 },
                plugins: {
                    legend: { labels: { color: getTickColor(), font: { size: 11 } } }
                },
                scales: {
                    x: { ticks: { color: getTickColor(), font: { size: 10 } }, grid: { color: getGridColor() } },
                    y: { min: 0, max: 100, ticks: { color: getTickColor(), font: { size: 10 } }, grid: { color: getGridColor() } }
                }
            }
        });

        function updateChartColors() {
            chart.options.scales.x.ticks.color = getTickColor();
            chart.options.scales.y.ticks.color = getTickColor();
            chart.options.scales.x.grid.color = getGridColor();
            chart.options.scales.y.grid.color = getGridColor();
            chart.options.plugins.legend.labels.color = getTickColor();
            chart.update();
        }

        // ===== LIVE FETCH =====
        function fetchLatestData() {
            fetch('fetch_data.php')
                .then(res => res.json())
                .then(data => {
                    if (!data.latest) return;
                    const l = data.latest;

                    document.getElementById('moisturePercent').textContent = parseFloat(l.moisture_percent).toFixed(1) + '%';
                    document.getElementById('moistureRaw').textContent = l.soil_moisture;
                    document.getElementById('totalCount').textContent = parseInt(data.total).toLocaleString();
                    document.getElementById('lastTime').textContent = l.time;
                    document.getElementById('lastDate').textContent = l.date;
                    document.getElementById('lastUpdatedTime').textContent = new Date().toLocaleTimeString();

                    const isOn = l.pump_status.toLowerCase() === 'on';
                    document.getElementById('pumpStatusContainer').innerHTML = `
                        <span class="status-badge ${isOn ? 'on' : 'off'}">
                            <span class="dot"></span>${l.pump_status}
                        </span>`;

                    if (data.readings && data.readings.length > 0) {
                        document.getElementById('moistureChart').style.display = 'block';
                        document.getElementById('chartNoData').style.display = 'none';
                        chart.data.labels = data.readings.map(r => r.time_short);
                        chart.data.datasets[0].data = data.readings.map(r => parseFloat(r.moisture_percent).toFixed(1));
                        chart.data.datasets[1].data = data.readings.map(r => r.pump_status.toLowerCase() === 'on' ? 100 : 0);
                        chart.update('none');

                        const rows = [...data.readings].reverse().map(r => `
                            <tr>
                                <td>${r.recorded_at_formatted}</td>
                                <td>${parseFloat(r.moisture_percent).toFixed(1)}%</td>
                                <td>${r.soil_moisture}</td>
                                <td>
                                    <span class="status-badge ${r.pump_status.toLowerCase() === 'on' ? 'on' : 'off'}">
                                        <span class="dot"></span>${r.pump_status}
                                    </span>
                                </td>
                            </tr>`).join('');

                        document.getElementById('tableContainer').innerHTML = `
                            <table>
                                <thead><tr><th>Time</th><th>Moisture %</th><th>Raw Value</th><th>Pump</th></tr></thead>
                                <tbody>${rows}</tbody>
                            </table>`;
                    }
                })
                .catch(err => console.log('Fetch error:', err));
        }

        setInterval(fetchLatestData, 5000);
        document.getElementById('lastUpdatedTime').textContent = new Date().toLocaleTimeString();
    </script>
</body>
</html>
