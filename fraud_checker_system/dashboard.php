<?php
/**
 * FraudGuard Pro - Next Generation Dashboard
 * Inspired by GitHub, Vercel & Linear UI
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | FraudGuard Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@100;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <style>
        :root {
            --bg-body: #09090b;
            --bg-card: #121214;
            --bg-sidebar: #09090b;
            --accent: #ffffff;
            --accent-muted: #a1a1aa;
            --border: #27272a;
            --brand: #3b82f6;
            --brand-glow: rgba(59, 130, 246, 0.2);
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --font-mono: 'Geist Mono', monospace;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Geist', sans-serif; background-color: var(--bg-body); color: var(--accent); overflow: hidden; height: 100vh; }

        /* Sidebar Navigation */
        .app-container { display: flex; height: 100vh; }
        .sidebar { width: 260px; background: var(--bg-sidebar); border-right: 1px solid var(--border); padding: 24px; display: flex; flex-direction: column; }
        .logo { font-size: 1.1rem; font-weight: 800; margin-bottom: 40px; display: flex; align-items: center; gap: 10px; color: #fff; }
        .logo span { color: var(--brand); }
        
        .nav-link { padding: 10px 14px; border-radius: 10px; color: var(--accent-muted); text-decoration: none; font-size: 0.95rem; font-weight: 500; margin-bottom: 6px; display: flex; align-items: center; gap: 12px; transition: 0.2s; }
        .nav-link:hover, .nav-link.active { background: #18181b; color: #fff; }
        .nav-link i { font-size: 1rem; }

        /* Main Workspace */
        .main-workspace { flex: 1; display: flex; flex-direction: column; overflow-y: auto; background: linear-gradient(to bottom right, #09090b, #121214); }
        .top-bar { padding: 16px 40px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; backdrop-filter: blur(10px); position: sticky; top: 0; z-index: 100; }
        
        /* Advanced Search Box */
        .search-wrapper { position: relative; width: 400px; }
        .search-wrapper input { width: 100%; background: #18181b; border: 1px solid var(--border); padding: 10px 16px 10px 40px; border-radius: 12px; color: #fff; outline: none; transition: 0.3s; }
        .search-wrapper i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--accent-muted); }
        .search-wrapper input:focus { border-color: var(--brand); box-shadow: 0 0 0 3px var(--brand-glow); }

        .content-area { padding: 40px; max-width: 1400px; margin: 0 auto; width: 100%; }

        /* Stats Blocks */
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-box { background: var(--bg-card); border: 1px solid var(--border); border-radius: 20px; padding: 24px; position: relative; overflow: hidden; }
        .stat-box h3 { font-size: 0.85rem; color: var(--accent-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px; }
        .stat-box .number { font-size: 2rem; font-weight: 700; color: #fff; }
        .stat-box .trend { position: absolute; top: 24px; right: 24px; font-size: 0.8rem; font-weight: 600; }

        /* Main Analysis View */
        .analysis-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 30px; margin-top: 30px; }
        @media (max-width: 1100px) { .analysis-grid { grid-template-columns: 1fr; } }

        .glass-card { background: rgba(18, 18, 20, 0.6); border: 1px solid var(--border); border-radius: 24px; padding: 32px; backdrop-filter: blur(10px); }
        .card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px; }
        .card-header h2 { font-size: 1.25rem; font-weight: 700; }

        /* Courier Row */
        .courier-row { display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; background: #18181b; border-radius: 16px; margin-bottom: 12px; border: 1px solid transparent; transition: 0.3s; }
        .courier-row:hover { border-color: var(--border); transform: scale(1.01); background: #27272a; }
        .courier-meta h4 { font-size: 1rem; color: #fff; margin-bottom: 4px; }
        .courier-meta p { font-size: 0.85rem; color: var(--accent-muted); }

        /* Risk Badges */
        .badge { padding: 6px 14px; border-radius: 30px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
        .risk-low { background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); }
        .risk-medium { background: rgba(245, 158, 11, 0.1); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.2); }
        .risk-high { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }

        /* Action Panel */
        .decision-panel { background: linear-gradient(135deg, #1e1b4b 0%, #0f172a 100%); border-radius: 24px; padding: 32px; border: 1px solid #312e81; position: relative; overflow: hidden; }
        .decision-panel::after { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(59,130,246,0.1) 0%, transparent 70%); pointer-events: none; }
        .rec-icon { font-size: 2.5rem; color: var(--brand); margin-bottom: 20px; }
        .rec-title { font-size: 1.4rem; font-weight: 800; margin-bottom: 10px; color: #fff; }
        .rec-desc { color: var(--accent-muted); line-height: 1.6; margin-bottom: 24px; }

        /* Loading Spinner */
        .loader-overlay { position: fixed; inset: 0; background: rgba(9, 9, 11, 0.8); backdrop-filter: blur(4px); display: none; place-items: center; z-index: 2000; }
        .spinner { width: 40px; height: 40px; border: 3px solid rgba(255,255,255,0.1); border-top-color: var(--brand); border-radius: 50%; animation: rotate 0.8s linear infinite; }
        @keyframes rotate { to { transform: rotate(360deg); } }

        .hidden { display: none; }
        .animate-fade { animation: fadeIn 0.5s ease both; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* Bulk Section Overlay */
        .bulk-trigger { color: var(--brand); font-weight: 600; font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; gap: 8px; }
        .bulk-trigger:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <div class="loader-overlay" id="loader">
        <div class="spinner"></div>
    </div>

    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <i class="fas fa-terminal"></i> FraudGuard <span>PRO</span>
            </div>
            <nav>
                <a href="#" class="nav-link active"><i class="fas fa-layer-group"></i> Overview</a>
                <a href="#" class="nav-link"><i class="fas fa-history"></i> Investigations</a>
                <a href="#" class="nav-link"><i class="fas fa-file-csv"></i> Batch Export</a>
                <a href="#" class="nav-link"><i class="fas fa-code"></i> API Console</a>
                <a href="#" class="nav-link"><i class="fas fa-cog"></i> Settings</a>
            </nav>
            <div style="margin-top: auto; padding-top: 20px; border-top: 1px solid var(--border);">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 32px; height: 32px; background: var(--brand); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: bold;">M</div>
                    <div>
                        <p style="font-size: 0.85rem; font-weight: 600;">Merchant One</p>
                        <p style="font-size: 0.75rem; color: var(--accent-muted);">Premium Plan</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Workspace -->
        <main class="main-workspace">
            <header class="top-bar">
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" id="phoneInput" placeholder="Quick check phone number..." maxlength="11" onkeydown="if(event.key==='Enter') checkFraud()">
                </div>
                <div style="display: flex; align-items: center; gap: 24px;">
                    <div class="bulk-trigger" onclick="document.getElementById('csvUpload').click()">
                        <i class="fas fa-file-import"></i> Bulk CSV
                    </div>
                    <input type="file" id="csvUpload" class="hidden" accept=".csv" onchange="handleBulkUpload(event)">
                    <div style="width: 1px; height: 20px; background: var(--border);"></div>
                    <i class="far fa-bell" style="color: var(--accent-muted); cursor: pointer;"></i>
                    <i class="far fa-question-circle" style="color: var(--accent-muted); cursor: pointer;"></i>
                </div>
            </header>

            <div class="content-area">
                <!-- Progress for Bulk -->
                <div id="bulkProgressContainer" class="hidden glass-card" style="margin-bottom: 30px; border-color: var(--brand);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <span id="bulkStatusText" style="font-weight: 600;">Processing Batch...</span>
                        <span id="bulkProgressPercent" style="font-weight: 700;">0%</span>
                    </div>
                    <div style="height: 6px; background: #18181b; border-radius: 10px; overflow: hidden;">
                        <div id="bulkProgressBar" style="width: 0%; height: 100%; background: var(--brand); transition: 0.4s;"></div>
                    </div>
                </div>

                <div id="dashboardStats" class="animate-fade" style="display: none;">
                    <!-- Stats Grid -->
                    <div class="stats-row">
                        <div class="stat-box">
                            <h3>Aggregated Parcels</h3>
                            <div class="number" id="totalOrders">0</div>
                            <span class="trend" style="color: var(--success);"><i class="fas fa-arrow-up"></i> Live</span>
                        </div>
                        <div class="stat-box">
                            <h3>Success Rate</h3>
                            <div class="number" id="successRate">0%</div>
                            <span class="trend" id="rateColor">--</span>
                        </div>
                        <div class="stat-box">
                            <h3>Risk Probability</h3>
                            <div class="number" id="riskLevel">--</div>
                            <span class="trend" id="riskDot">●</span>
                        </div>
                        <div class="stat-box">
                            <h3>Database Hits</h3>
                            <div class="number" id="totalCancel">0</div>
                            <span class="trend" style="color: var(--danger);">Canceled</span>
                        </div>
                    </div>

                    <!-- Analysis Grid -->
                    <div class="analysis-grid">
                        <div class="glass-card">
                            <div class="card-header">
                                <h2><i class="fas fa-microchip" style="margin-right: 10px; color: var(--brand);"></i> Courier Analysis</h2>
                                <button onclick="window.print()" style="background: transparent; border: none; color: var(--accent-muted); cursor: pointer;"><i class="fas fa-download"></i></button>
                            </div>
                            <div id="courierList">
                                <!-- Dynamic -->
                            </div>
                            <div id="chartContainer" style="margin-top: 40px; padding-top: 30px; border-top: 1px solid var(--border);">
                                <div id="deliveryChart"></div>
                            </div>
                        </div>

                        <div class="decision-panel">
                            <div class="rec-icon" id="recIcon"><i class="fas fa-robot"></i></div>
                            <h4 class="rec-title" id="recTitle">AI Analysis Pending</h4>
                            <p class="rec-desc" id="recText">Provide a customer phone number to run our multi-vector risk assessment engine.</p>
                            
                            <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); padding: 20px; border-radius: 16px;">
                                <h5 style="font-size: 0.8rem; text-transform: uppercase; color: var(--accent-muted); margin-bottom: 10px;">Security Intelligence</h5>
                                <p style="font-size: 0.85rem; color: #fff; font-weight: 500;">Based on aggregated data, this customer's behavior suggests <span id="intelText">--</span> delivery reliability.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div id="emptyState" style="text-align: center; padding: 100px 20px;">
                    <div style="font-size: 3rem; color: var(--border); margin-bottom: 20px;"><i class="fas fa-search"></i></div>
                    <h2 style="font-size: 1.5rem; margin-bottom: 10px;">Ready to Analyze</h2>
                    <p style="color: var(--accent-muted); max-width: 400px; margin: 0 auto;">Input a phone number in the top search bar to begin the fraud detection process.</p>
                </div>
            </div>
        </main>
    </div>

    <script>
        async function checkFraud() {
            const phone = document.getElementById('phoneInput').value;
            if (!phone || phone.length < 11) {
                alert('Invalid phone number.');
                return;
            }

            const loader = document.getElementById('loader');
            const stats = document.getElementById('dashboardStats');
            const empty = document.getElementById('emptyState');
            
            loader.style.display = 'grid';

            try {
                const response = await fetch('api_bridge.php?phone=' + phone);
                const result = await response.json();

                empty.style.display = 'none';
                stats.style.display = 'block';

                // Update UI
                document.getElementById('totalOrders').innerText = result.aggregate.total_orders;
                document.getElementById('successRate').innerText = result.aggregate.success_rate + '%';
                document.getElementById('totalCancel').innerText = result.aggregate.total_cancel;
                document.getElementById('riskLevel').innerText = result.aggregate.risk_level;
                
                const riskLevel = result.aggregate.risk_level.toLowerCase();
                const riskColor = riskLevel === 'low' ? 'var(--success)' : (riskLevel === 'medium' ? 'var(--warning)' : 'var(--danger)');
                document.getElementById('riskDot').style.color = riskColor;
                document.getElementById('intelText').innerText = riskLevel;

                // Recommendation
                document.getElementById('recTitle').innerText = result.aggregate.risk_level + ' Risk Profile';
                document.getElementById('recText').innerText = result.aggregate.recommendation;
                document.getElementById('recIcon').innerHTML = `<i class="fas ${riskLevel === 'low' ? 'fa-shield-check' : 'fa-biohazard'}" style="color: ${riskColor}"></i>`;

                // Courier List
                const courierList = document.getElementById('courierList');
                courierList.innerHTML = '';
                for (const [name, stats] of Object.entries(result.details)) {
                    const courierRisk = (stats.risk || 'Low').toLowerCase();
                    const item = document.createElement('div');
                    item.className = 'courier-row';
                    item.innerHTML = `
                        <div class="courier-meta">
                            <h4>${name} Courier</h4>
                            <p>${stats.success} Success • ${stats.cancel} Returns</p>
                        </div>
                        <span class="badge risk-${courierRisk}">${courierRisk}</span>
                    `;
                    courierList.appendChild(item);
                }

                renderChart(result.aggregate.total_success, result.aggregate.total_cancel);

            } catch (error) {
                alert('API Connection Failed.');
            } finally {
                loader.style.display = 'none';
            }
        }

        let chart = null;
        function renderChart(success, cancel) {
            const options = {
                series: [success, cancel],
                chart: { type: 'donut', height: 280, background: 'transparent' },
                labels: ['Delivered', 'Returned'],
                colors: ['#3b82f6', '#ef4444'],
                theme: { mode: 'dark' },
                stroke: { show: false },
                legend: { position: 'bottom', labels: { colors: '#a1a1aa' } },
                plotOptions: { pie: { donut: { size: '75%', background: 'transparent' } } }
            };

            if (chart) chart.destroy();
            chart = new ApexCharts(document.querySelector("#deliveryChart"), options);
            chart.render();
        }

        async function handleBulkUpload(event) {
            const file = event.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = async (e) => {
                const text = e.target.result;
                const phones = text.split(/\r?\n/).map(p => p.trim()).filter(p => p.length >= 11);
                
                const container = document.getElementById('bulkProgressContainer');
                const bar = document.getElementById('bulkProgressBar');
                const percentText = document.getElementById('bulkProgressPercent');
                const status = document.getElementById('bulkStatusText');

                container.classList.remove('hidden');
                let processed = 0;

                for (const phone of phones) {
                    status.innerText = `Analyzing batch: ${phone}...`;
                    try { await fetch('api_bridge.php?phone=' + phone); } catch (err) {}
                    processed++;
                    const percent = Math.round((processed / phones.length) * 100);
                    bar.style.width = percent + '%';
                    percentText.innerText = percent + '%';
                }

                status.innerText = 'Batch Processing Complete!';
                setTimeout(() => container.classList.add('hidden'), 5000);
            };
            reader.readAsText(file);
        }
    </script>
</body>
</html>
