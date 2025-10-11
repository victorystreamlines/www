// ========================================
// ALIGNED DASHBOARD CONNECTIONS CODE
// ========================================
// Replace the loadDashboardConnections() function in Dashboard-Hostinger.html
// with this version for better alignment

// Load connections in Dashboard with Test Connection
async function loadDashboardConnections() {
    const listEl = document.getElementById('configuredConnectionsList');
    const connections = getHostingerConnections();

    if (connections.length === 0) {
        listEl.innerHTML = `
            <p style="color: rgba(254, 243, 199, 0.6); text-align: center; padding: 40px;">
                No Hostinger connections configured.<br>
                <span style="font-size: 14px;">Go to <strong>Hostinger Connections</strong> in the sidebar to add your first connection.</span>
            </p>`;
        return;
    }

    let html = '';
    connections.forEach(conn => {
        const typeIcon = conn.type === 'vps' ? '🖥️' : '🌐';
        const connId = `conn_${conn.id}`;
        html += `
            <div class="database-item" id="${connId}">
                <div class="conn-header">
                    <div class="conn-icon">${typeIcon}</div>
                    <div class="conn-title">${conn.name}</div>
                </div>
                
                <div class="conn-info">
                    <div class="conn-info-row">
                        <span class="conn-info-icon">📁</span>
                        <span class="conn-info-text">${conn.dbName}</span>
                    </div>
                    <div class="conn-info-row">
                        <span class="conn-info-icon">🖥️</span>
                        <span class="conn-info-text">${conn.host}</span>
                    </div>
                </div>
                
                <div id="${connId}_status" class="conn-status" style="background: rgba(59, 130, 246, 0.2); border: 1px solid #3b82f6; color: #93c5fd;">
                    <span class="spinner"></span>
                    <span>Testing connection...</span>
                </div>
                
                <button class="btn btn-primary conn-button" style="width: 100%; padding: 10px; font-size: 13px;" onclick="testConnectionManual('${conn.id}')">
                    <span>🔄</span> Test Again
                </button>
            </div>`;
    });

    listEl.innerHTML = html;

    // Auto-test all connections
    connections.forEach(conn => {
        testConnection(conn.id);
    });
}
