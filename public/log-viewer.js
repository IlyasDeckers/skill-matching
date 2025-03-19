/**
 * InfluxDB Log Viewer
 * A simple JavaScript client for retrieving and displaying logs from InfluxDB
 */
class LogViewer {
    /**
     * Constructor
     *
     * @param {Object} options - Configuration options
     * @param {string} options.apiUrl - Base URL for the log API
     * @param {string} options.containerSelector - CSS selector for the container element
     * @param {number} options.interval - Polling interval in milliseconds (default: 5000)
     * @param {number} options.limit - Maximum number of logs to fetch (default: 100)
     * @param {string} options.timeRange - Time range to fetch logs for (default: '5m')
     * @param {string|null} options.service - Filter by service (default: null)
     * @param {string|null} options.level - Filter by log level (default: null)
     * @param {boolean} options.autoScroll - Whether to auto-scroll to new logs (default: true)
     */
    constructor(options) {
        this.options = Object.assign({
            apiUrl: 'http://127.0.0.1:9501/api/logs/recent',
            containerSelector: '#log-container',
            interval: 5000,
            limit: 100,
            timeRange: '5m',
            service: null,
            level: null,
            autoScroll: true
        }, options);

        console.log(this.options.apiUrl);

        this.container = document.querySelector(this.options.containerSelector);
        if (!this.container) {
            throw new Error(`Container element "${this.options.containerSelector}" not found`);
        }

        this.logs = [];
        this.lastTimestamp = null;
        this.pollInterval = null;
        this.isPolling = false;
        this.filters = {
            service: this.options.service,
            level: this.options.level
        };

        // Initialize UI
        this.initUI();
    }

    /**
     * Initialize the user interface
     */
    initUI() {
        // Create elements for logs, filters, and controls
        this.container.innerHTML = `
            <div class="log-viewer">
                <div class="log-controls">
                    <div class="log-filters">
                        <select class="service-filter">
                            <option value="">All Services</option>
                        </select>
                        <select class="level-filter">
                            <option value="">All Levels</option>
                            <option value="emergency">Emergency</option>
                            <option value="alert">Alert</option>
                            <option value="critical">Critical</option>
                            <option value="error">Error</option>
                            <option value="warning">Warning</option>
                            <option value="notice">Notice</option>
                            <option value="info">Info</option>
                            <option value="debug">Debug</option>
                        </select>
                        <select class="time-range">
                            <option value="1m">Last 1 minute</option>
                            <option value="5m" selected>Last 5 minutes</option>
                            <option value="15m">Last 15 minutes</option>
                            <option value="30m">Last 30 minutes</option>
                            <option value="1h">Last 1 hour</option>
                            <option value="3h">Last 3 hours</option>
                            <option value="6h">Last 6 hours</option>
                            <option value="12h">Last 12 hours</option>
                            <option value="24h">Last 24 hours</option>
                        </select>
                    </div>
                    <div class="log-actions">
                        <button class="refresh-btn">Refresh</button>
                        <label>
                            <input type="checkbox" class="auto-refresh" checked>
                            Auto Refresh
                        </label>
                        <label>
                            <input type="checkbox" class="auto-scroll" checked>
                            Auto Scroll
                        </label>
                    </div>
                </div>
                <div class="log-table-container">
                    <table class="log-table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Level</th>
                                <th>Service</th>
                                <th>Message</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="log-status">
                    <span class="log-count">0 logs</span>
                    <span class="log-status-message"></span>
                </div>
            </div>
        `;

        // Add base styles
        const style = document.createElement('style');
        style.textContent = `
            .log-viewer {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                display: flex;
                flex-direction: column;
                height: 100%;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            .log-controls {
                display: flex;
                justify-content: space-between;
                padding: 10px;
                background: #f5f5f5;
                border-bottom: 1px solid #ddd;
            }
            .log-filters, .log-actions {
                display: flex;
                gap: 10px;
                align-items: center;
            }
            .log-table-container {
                flex: 1;
                overflow: auto;
                position: relative;
            }
            .log-table {
                width: 100%;
                border-collapse: collapse;
            }
            .log-table th, .log-table td {
                padding: 8px;
                text-align: left;
                border-bottom: 1px solid #eee;
            }
            .log-table th {
                background: #f9f9f9;
                position: sticky;
                top: 0;
                z-index: 1;
            }
            .log-table tbody tr:hover {
                background-color: #f5f5f5;
            }
            .log-level {
                display: inline-block;
                padding: 2px 6px;
                border-radius: 3px;
                font-size: 0.8em;
                font-weight: bold;
                color: white;
            }
            .log-level-emergency { background-color: #000; }
            .log-level-alert { background-color: #7811F7; }
            .log-level-critical { background-color: #FF00AA; }
            .log-level-error { background-color: #DC3545; }
            .log-level-warning { background-color: #FFC107; }
            .log-level-notice { background-color: #17A2B8; }
            .log-level-info { background-color: #28A745; }
            .log-level-debug { background-color: #6C757D; }
            .log-details-btn {
                background: none;
                border: none;
                cursor: pointer;
                color: #0066cc;
                text-decoration: underline;
            }
            .log-status {
                padding: 8px 10px;
                background: #f5f5f5;
                border-top: 1px solid #ddd;
                display: flex;
                justify-content: space-between;
            }
            .error-message {
                color: #DC3545;
                padding: 20px;
                text-align: center;
            }
            .log-timestamp {
                white-space: nowrap;
            }
        `;
        document.head.appendChild(style);

        // Get DOM references
        this.tableBody = this.container.querySelector('.log-table tbody');
        this.logCount = this.container.querySelector('.log-count');
        this.statusMessage = this.container.querySelector('.log-status-message');
        this.serviceFilter = this.container.querySelector('.service-filter');
        this.levelFilter = this.container.querySelector('.level-filter');
        this.timeRangeSelect = this.container.querySelector('.time-range');
        this.refreshBtn = this.container.querySelector('.refresh-btn');
        this.autoRefreshCheckbox = this.container.querySelector('.auto-refresh');
        this.autoScrollCheckbox = this.container.querySelector('.auto-scroll');

        // Set initial values
        if (this.filters.service) {
            this.serviceFilter.value = this.filters.service;
        }
        if (this.filters.level) {
            this.levelFilter.value = this.filters.level;
        }
        this.timeRangeSelect.value = this.options.timeRange;

        // Bind event listeners
        this.refreshBtn.addEventListener('click', () => this.fetchLogs());
        this.serviceFilter.addEventListener('change', () => this.updateFilters());
        this.levelFilter.addEventListener('change', () => this.updateFilters());
        this.timeRangeSelect.addEventListener('change', () => this.updateFilters());
        this.autoRefreshCheckbox.addEventListener('change', () => this.toggleAutoRefresh());

        // Load services from API
        this.loadServices();

        // Start initial fetch and polling
        this.fetchLogs();
        this.startPolling();
    }

    /**
     * Fetch available services from the API
     */
    async loadServices() {
        try {
            const response = await fetch(`${this.options.apiUrl.replace('/recent', '')}/services`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();

            if (data.success && Array.isArray(data.services)) {
                // Clear existing options except the first one
                while (this.serviceFilter.options.length > 1) {
                    this.serviceFilter.remove(1);
                }

                // Add service options
                data.services.forEach(service => {
                    const option = document.createElement('option');
                    option.value = service;
                    option.textContent = service;
                    this.serviceFilter.appendChild(option);
                });

                // Set selected value if filter is active
                if (this.filters.service) {
                    this.serviceFilter.value = this.filters.service;
                }
            }
        } catch (error) {
            console.error('Error loading services:', error);
        }
    }

    /**
     * Update filters and refresh logs
     */
    updateFilters() {
        this.filters.service = this.serviceFilter.value || null;
        this.filters.level = this.levelFilter.value || null;
        this.options.timeRange = this.timeRangeSelect.value;

        // Reset last timestamp to get fresh logs
        this.lastTimestamp = null;
        this.logs = [];
        this.tableBody.innerHTML = '';

        // Fetch logs with new filters
        this.fetchLogs();
    }

    /**
     * Toggle auto-refresh on/off
     */
    toggleAutoRefresh() {
        if (this.autoRefreshCheckbox.checked) {
            this.startPolling();
        } else {
            this.stopPolling();
        }
    }

    /**
     * Start polling for new logs
     */
    startPolling() {
        if (!this.isPolling) {
            this.isPolling = true;
            this.pollInterval = setInterval(() => this.fetchLogs(), this.options.interval);
            this.statusMessage.textContent = 'Auto-refresh is on';
        }
    }

    /**
     * Stop polling for new logs
     */
    stopPolling() {
        if (this.isPolling) {
            clearInterval(this.pollInterval);
            this.isPolling = false;
            this.pollInterval = null;
            this.statusMessage.textContent = 'Auto-refresh is off';
        }
    }

    /**
     * Fetch logs from the API
     */
    async fetchLogs() {
        try {
            this.statusMessage.textContent = 'Fetching logs...';

            // Build query parameters
            const params = new URLSearchParams({
                timeRange: this.options.timeRange,
                limit: this.options.limit
            });

            if (this.filters.service) {
                params.append('service', this.filters.service);
            }

            if (this.filters.level) {
                params.append('level', this.filters.level);
            }

            // Make API request
            const response = await fetch(`${this.options.apiUrl}?${params}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success && Array.isArray(data.logs)) {
                // Process and display logs
                this.processLogs(data.logs);
                this.logCount.textContent = `${this.logs.length} logs`;
                this.statusMessage.textContent = this.isPolling ?
                    `Auto-refresh is on (every ${this.options.interval/1000}s)` :
                    'Auto-refresh is off';
            } else {
                throw new Error('Invalid response format');
            }
        } catch (error) {
            console.error('Error fetching logs:', error);
            this.statusMessage.textContent = `Error: ${error.message}`;
        }
    }

    /**
     * Process logs and update the UI
     *
     * @param {Array} newLogs - New logs from the API
     */
    processLogs(newLogs) {
        if (!newLogs.length) return;

        // Sort logs by timestamp (newest first)
        newLogs.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));

        // Find unique logs that we don't already have
        const uniqueNewLogs = newLogs.filter(newLog => {
            return !this.logs.some(existingLog =>
                existingLog.timestamp === newLog.timestamp &&
                existingLog.message === newLog.message
            );
        });

        if (!uniqueNewLogs.length) return;

        // Update our logs array
        this.logs = [...uniqueNewLogs, ...this.logs].slice(0, this.options.limit);

        // Render the new logs
        this.renderLogs();

        // Auto-scroll if enabled
        if (this.autoScrollCheckbox.checked) {
            this.scrollToNewest();
        }
    }

    /**
     * Render logs to the table
     */
    renderLogs() {
        // Clear existing logs
        this.tableBody.innerHTML = '';

        // Add each log entry
        this.logs.forEach((log, index) => {
            const row = document.createElement('tr');
            row.setAttribute('data-index', index);

            // Format timestamp
            const date = new Date(log.timestamp);
            const formattedDate = date.toLocaleTimeString() + ' ' +
                date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });

            row.innerHTML = `
                <td class="log-timestamp">${formattedDate}</td>
                <td>
                    <span class="log-level log-level-${log.level || 'info'}">${log.level || 'info'}</span>
                </td>
                <td>${log.service || '-'}</td>
                <td>${this.escapeHtml(log.message || '')}</td>
                <td>
                    ${Object.keys(log.context || {}).length ?
                '<button class="log-details-btn">Details</button>' :
                '-'}
                </td>
            `;

            // Add event listener for details button
            const detailsBtn = row.querySelector('.log-details-btn');
            if (detailsBtn) {
                detailsBtn.addEventListener('click', () => this.showLogDetails(log));
            }

            this.tableBody.appendChild(row);
        });
    }

    /**
     * Show detailed information for a log entry
     *
     * @param {Object} log - The log entry to show details for
     */
    showLogDetails(log) {
        // Create modal for details
        const modal = document.createElement('div');
        modal.className = 'log-details-modal';
        modal.innerHTML = `
            <div class="log-details-content">
                <div class="log-details-header">
                    <h3>Log Details</h3>
                    <button class="close-btn">&times;</button>
                </div>
                <div class="log-details-body">
                    <div class="log-detail">
                        <strong>Timestamp:</strong> ${new Date(log.timestamp).toISOString()}
                    </div>
                    <div class="log-detail">
                        <strong>Level:</strong> ${log.level || 'info'}
                    </div>
                    <div class="log-detail">
                        <strong>Service:</strong> ${log.service || '-'}
                    </div>
                    <div class="log-detail">
                        <strong>Message:</strong> ${this.escapeHtml(log.message || '')}
                    </div>
                    <div class="log-detail">
                        <strong>Context:</strong>
                        <pre>${JSON.stringify(log.context || {}, null, 2)}</pre>
                    </div>
                </div>
            </div>
        `;

        // Add modal styles
        const style = document.createElement('style');
        style.textContent = `
            .log-details-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 1000;
            }
            .log-details-content {
                background-color: white;
                border-radius: 5px;
                width: 80%;
                max-width: 800px;
                max-height: 80vh;
                overflow: auto;
            }
            .log-details-header {
                padding: 10px 15px;
                border-bottom: 1px solid #ddd;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .log-details-header h3 {
                margin: 0;
            }
            .close-btn {
                background: none;
                border: none;
                font-size: 1.5rem;
                cursor: pointer;
            }
            .log-details-body {
                padding: 15px;
            }
            .log-detail {
                margin-bottom: 10px;
            }
            .log-detail pre {
                background-color: #f5f5f5;
                padding: 10px;
                border-radius: 4px;
                overflow-x: auto;
            }
        `;
        document.head.appendChild(style);

        // Add to document
        document.body.appendChild(modal);

        // Add close handler
        modal.querySelector('.close-btn').addEventListener('click', () => {
            document.body.removeChild(modal);
        });

        // Close on click outside
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                document.body.removeChild(modal);
            }
        });
    }

    /**
     * Scroll to the newest log entry
     */
    scrollToNewest() {
        this.tableBody.firstChild?.scrollIntoView({ behavior: 'smooth' });
    }

    /**
     * Escape HTML to prevent XSS
     *
     * @param {string} text - Text to escape
     * @return {string} Escaped HTML
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Clear all logs from the display
     */
    clearLogs() {
        this.logs = [];
        this.tableBody.innerHTML = '';
        this.logCount.textContent = '0 logs';
    }

    /**
     * Destroy the log viewer and clean up
     */
    destroy() {
        // Stop polling
        this.stopPolling();

        // Remove event listeners
        this.refreshBtn.removeEventListener('click', this.fetchLogs);
        this.serviceFilter.removeEventListener('change', this.updateFilters);
        this.levelFilter.removeEventListener('change', this.updateFilters);
        this.timeRangeSelect.removeEventListener('change', this.updateFilters);
        this.autoRefreshCheckbox.removeEventListener('change', this.toggleAutoRefresh);

        // Clear the container
        this.container.innerHTML = '';
    }

    /**
     * Export logs to JSON file
     */
    exportLogs() {
        if (this.logs.length === 0) {
            alert('No logs to export');
            return;
        }

        const dataStr = JSON.stringify(this.logs, null, 2);
        const dataUri = 'data:application/json;charset=utf-8,' + encodeURIComponent(dataStr);

        const exportFileName = `logs_export_${new Date().toISOString().replace(/[:.]/g, '-')}.json`;

        const linkElement = document.createElement('a');
        linkElement.setAttribute('href', dataUri);
        linkElement.setAttribute('download', exportFileName);
        linkElement.style.display = 'none';

        document.body.appendChild(linkElement);
        linkElement.click();
        document.body.removeChild(linkElement);
    }

    /**
     * Get stats about the current logs
     *
     * @return {Object} Stats object with counts by level and service
     */
    getStats() {
        const stats = {
            total: this.logs.length,
            byLevel: {},
            byService: {}
        };

        this.logs.forEach(log => {
            // Count by level
            const level = log.level || 'unknown';
            stats.byLevel[level] = (stats.byLevel[level] || 0) + 1;

            // Count by service
            const service = log.service || 'unknown';
            stats.byService[service] = (stats.byService[service] || 0) + 1;
        });

        return stats;
    }

    /**
     * Display log statistics
     */
    showStats() {
        const stats = this.getStats();

        // Create modal for stats
        const modal = document.createElement('div');
        modal.className = 'log-stats-modal';

        // Create HTML for level stats
        let levelStatsHtml = '';
        for (const [level, count] of Object.entries(stats.byLevel)) {
            const percentage = Math.round((count / stats.total) * 100);
            levelStatsHtml += `
                <div class="stat-item">
                    <div class="stat-label">
                        <span class="log-level log-level-${level}">${level}</span>
                    </div>
                    <div class="stat-bar-container">
                        <div class="stat-bar" style="width: ${percentage}%"></div>
                        <div class="stat-value">${count} (${percentage}%)</div>
                    </div>
                </div>
            `;
        }

        // Create HTML for service stats
        let serviceStatsHtml = '';
        for (const [service, count] of Object.entries(stats.byService)) {
            const percentage = Math.round((count / stats.total) * 100);
            serviceStatsHtml += `
                <div class="stat-item">
                    <div class="stat-label">${service}</div>
                    <div class="stat-bar-container">
                        <div class="stat-bar" style="width: ${percentage}%"></div>
                        <div class="stat-value">${count} (${percentage}%)</div>
                    </div>
                </div>
            `;
        }

        modal.innerHTML = `
            <div class="log-stats-content">
                <div class="log-stats-header">
                    <h3>Log Statistics</h3>
                    <button class="close-btn">&times;</button>
                </div>
                <div class="log-stats-body">
                    <p>Total logs: ${stats.total}</p>
                    
                    <h4>Logs by Level</h4>
                    <div class="stats-section">
                        ${levelStatsHtml}
                    </div>
                    
                    <h4>Logs by Service</h4>
                    <div class="stats-section">
                        ${serviceStatsHtml}
                    </div>
                </div>
            </div>
        `;

        // Add stats modal styles
        const style = document.createElement('style');
        style.textContent = `
            .log-stats-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 1000;
            }
            .log-stats-content {
                background-color: white;
                border-radius: 5px;
                width: 80%;
                max-width: 800px;
                max-height: 80vh;
                overflow: auto;
            }
            .log-stats-header {
                padding: 10px 15px;
                border-bottom: 1px solid #ddd;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .log-stats-header h3 {
                margin: 0;
            }
            .log-stats-body {
                padding: 15px;
            }
            .stats-section {
                margin-bottom: 20px;
            }
            .stat-item {
                display: flex;
                margin-bottom: 8px;
                align-items: center;
            }
            .stat-label {
                width: 100px;
                flex-shrink: 0;
            }
            .stat-bar-container {
                flex-grow: 1;
                height: 20px;
                background-color: #f5f5f5;
                border-radius: 3px;
                position: relative;
                overflow: hidden;
            }
            .stat-bar {
                height: 100%;
                background-color: #4e73df;
                border-radius: 3px;
            }
            .stat-value {
                position: absolute;
                right: 10px;
                top: 0;
                line-height: 20px;
                font-size: 0.9em;
                color: #333;
            }
        `;
        document.head.appendChild(style);

        // Add to document
        document.body.appendChild(modal);

        // Add close handler
        modal.querySelector('.close-btn').addEventListener('click', () => {
            document.body.removeChild(modal);
        });

        // Close on click outside
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                document.body.removeChild(modal);
            }
        });
    }
}