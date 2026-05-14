<?php
// Set content type to JavaScript
header('Content-Type: application/javascript');
?>

// StudySprint PHP Application
class StudySprint {
    constructor() {
        this.currentFilter = 'all';
        this.editingSessionId = null;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.checkAuth();
    }

    // Authentication
    checkAuth() {
        const isLoginPage = document.getElementById('loginPage')?.classList.contains('active');
        if (isLoginPage) {
            this.showLogin();
        } else {
            this.showDashboard();
        }
    }

    showLogin() {
        this.hideAllPages();
        document.getElementById('loginPage')?.classList.add('active');
    }

    showRegister() {
        this.hideAllPages();
        document.getElementById('registerPage')?.classList.add('active');
    }

    showDashboard() {
        this.hideAllPages();
        document.getElementById('dashboardPage')?.classList.add('active');
        this.loadSessions();
        this.updateStats();
    }

    hideAllPages() {
        document.querySelectorAll('.page').forEach(page => {
            page.classList.remove('active');
        });
    }

    // Event Listeners
    setupEventListeners() {
        // Auth forms
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');
        
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => this.handleLogin(e));
        }
        
        if (registerForm) {
            registerForm.addEventListener('submit', (e) => this.handleRegister(e));
        }

        // Auth switches
        const showRegister = document.getElementById('showRegister');
        const showLogin = document.getElementById('showLogin');
        
        if (showRegister) {
            showRegister.addEventListener('click', (e) => {
                e.preventDefault();
                this.showRegister();
            });
        }
        
        if (showLogin) {
            showLogin.addEventListener('click', (e) => {
                e.preventDefault();
                this.showLogin();
            });
        }

        // Logout
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', () => this.handleLogout());
        }

        // Session management
        const addSessionBtn = document.getElementById('addSessionBtn');
        const sessionForm = document.getElementById('sessionForm');
        const closeModal = document.getElementById('closeModal');
        const cancelBtn = document.getElementById('cancelBtn');
        
        if (addSessionBtn) {
            addSessionBtn.addEventListener('click', () => this.showSessionModal());
        }
        
        if (sessionForm) {
            sessionForm.addEventListener('submit', (e) => this.handleSessionSubmit(e));
        }
        
        if (closeModal) {
            closeModal.addEventListener('click', () => this.hideSessionModal());
        }
        
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => this.hideSessionModal());
        }

        // Filter tabs
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.handleFilterChange(e));
        });

        // Close modal on outside click
        const sessionModal = document.getElementById('sessionModal');
        if (sessionModal) {
            sessionModal.addEventListener('click', (e) => {
                if (e.target.id === 'sessionModal') {
                    this.hideSessionModal();
                }
            });
        }
    }

    // API Calls
    async apiCall(url, data) {
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(data)
            });
            
            const result = await response.json();
            return result;
        } catch (error) {
            console.error('API Error:', error);
            return { success: false, message: 'Network error' };
        }
    }

    async apiGet(url) {
        try {
            const response = await fetch(url);
            const result = await response.json();
            return result;
        } catch (error) {
            console.error('API Error:', error);
            return { success: false, message: 'Network error' };
        }
    }

    // Authentication Handlers
    async handleLogin(e) {
        e.preventDefault();
        const email = document.getElementById('loginEmail').value;
        const password = document.getElementById('loginPassword').value;

        const result = await this.apiCall('index.php', {
            action: 'login',
            email: email,
            password: password
        });

        if (result.success) {
            window.location.reload();
        } else {
            this.showNotification(result.message || 'Login failed', 'error');
        }
    }

    async handleRegister(e) {
        e.preventDefault();
        const name = document.getElementById('registerName').value;
        const email = document.getElementById('registerEmail').value;
        const password = document.getElementById('registerPassword').value;

        const result = await this.apiCall('index.php', {
            action: 'register',
            name: name,
            email: email,
            password: password
        });

        if (result.success) {
            window.location.reload();
        } else {
            this.showNotification(result.message || 'Registration failed', 'error');
        }
    }

    async handleLogout() {
        const result = await this.apiCall('index.php', { action: 'logout' });
        if (result.success) {
            window.location.reload();
        }
    }

    // Session Management
    showSessionModal(sessionId = null) {
        const modal = document.getElementById('sessionModal');
        const modalTitle = document.getElementById('modalTitle');
        const form = document.getElementById('sessionForm');

        if (sessionId) {
            // Load session data for editing
            this.loadSessionData(sessionId);
            modalTitle.textContent = 'Edit Study Session';
            this.editingSessionId = sessionId;
        } else {
            modalTitle.textContent = 'Add Study Session';
            form.reset();
            // Set default date to today
            document.getElementById('sessionDate').value = new Date().toISOString().split('T')[0];
            this.editingSessionId = null;
        }

        modal.classList.add('active');
    }

    async loadSessionData(sessionId) {
        // This would need an endpoint to get single session data
        // For now, we'll use the sessions list data
        const result = await this.apiGet('index.php?action=get_sessions');
        if (result.success) {
            const session = result.sessions.find(s => s.id == sessionId);
            if (session) {
                document.getElementById('sessionTitle').value = session.title;
                document.getElementById('sessionSubject').value = session.subject;
                document.getElementById('sessionDate').value = session.date;
                document.getElementById('sessionTime').value = session.time;
                document.getElementById('sessionDuration').value = session.duration;
                document.getElementById('sessionNotes').value = session.notes || '';
                document.getElementById('sessionStatus').value = session.status;
            }
        }
    }

    hideSessionModal() {
        document.getElementById('sessionModal').classList.remove('active');
        document.getElementById('sessionForm').reset();
        this.editingSessionId = null;
    }

    async handleSessionSubmit(e) {
        e.preventDefault();

        const sessionData = {
            title: document.getElementById('sessionTitle').value,
            subject: document.getElementById('sessionSubject').value,
            date: document.getElementById('sessionDate').value,
            time: document.getElementById('sessionTime').value,
            duration: parseFloat(document.getElementById('sessionDuration').value),
            notes: document.getElementById('sessionNotes').value,
            status: document.getElementById('sessionStatus').value
        };

        let result;
        if (this.editingSessionId) {
            result = await this.apiCall('index.php', {
                action: 'update_session',
                session_id: this.editingSessionId,
                ...sessionData
            });
            if (result.success) {
                this.showNotification('Session updated successfully!', 'success');
            }
        } else {
            result = await this.apiCall('index.php', {
                action: 'add_session',
                ...sessionData
            });
            if (result.success) {
                this.showNotification('Session added successfully!', 'success');
            }
        }

        if (result.success) {
            this.loadSessions();
            this.updateStats();
            this.hideSessionModal();
        } else {
            this.showNotification(result.message || 'Operation failed', 'error');
        }
    }

    async deleteSession(sessionId) {
        if (confirm('Are you sure you want to delete this session?')) {
            const result = await this.apiCall('index.php', {
                action: 'delete_session',
                session_id: sessionId
            });

            if (result.success) {
                this.loadSessions();
                this.updateStats();
                this.showNotification('Session deleted successfully!', 'info');
            } else {
                this.showNotification(result.message || 'Delete failed', 'error');
            }
        }
    }

    async markSessionComplete(sessionId) {
        const result = await this.apiCall('index.php', {
            action: 'complete_session',
            session_id: sessionId
        });

        if (result.success) {
            this.loadSessions();
            this.updateStats();
            this.showNotification('Session marked as completed!', 'success');
        } else {
            this.showNotification(result.message || 'Operation failed', 'error');
        }
    }

    // UI Updates
    async loadSessions() {
        const result = await this.apiGet(`index.php?action=get_sessions&filter=${this.currentFilter}`);
        
        const sessionsList = document.getElementById('sessionsList');
        if (!sessionsList) return;

        if (!result.success || result.sessions.length === 0) {
            sessionsList.innerHTML = `
                <div class="empty-state">
                    <h3>No study sessions found</h3>
                    <p>Start by adding your first study session!</p>
                </div>
            `;
            return;
        }

        sessionsList.innerHTML = result.sessions.map(session => this.createSessionCard(session)).join('');
    }

    createSessionCard(session) {
        const statusClass = `status-${session.status}`;
        const cardClass = session.status === 'completed' ? 'session-card completed' : 
                         session.status === 'cancelled' ? 'session-card cancelled' : 'session-card';

        return `
            <div class="${cardClass}" data-session-id="${session.id}">
                <div class="session-header">
                    <div>
                        <div class="session-title">${session.title}</div>
                        <div class="session-subject">${session.subject}</div>
                    </div>
                    <span class="session-status ${statusClass}">${session.status}</span>
                </div>
                <div class="session-details">
                    <div class="session-detail">
                        📅 ${this.formatDate(session.date)}
                    </div>
                    <div class="session-detail">
                        🕐 ${session.time}
                    </div>
                    <div class="session-detail">
                        ⏱️ ${session.duration}h
                    </div>
                </div>
                ${session.notes ? `<div class="session-notes">${session.notes}</div>` : ''}
                <div class="session-actions">
                    ${session.status !== 'completed' ? `
                        <button class="btn btn-small btn-complete" onclick="app.markSessionComplete('${session.id}')">
                            Complete
                        </button>
                    ` : ''}
                    <button class="btn btn-small btn-edit" onclick="app.showSessionModal('${session.id}')">
                        Edit
                    </button>
                    <button class="btn btn-small btn-delete" onclick="app.deleteSession('${session.id}')">
                        Delete
                    </button>
                </div>
            </div>
        `;
    }

    handleFilterChange(e) {
        const filter = e.target.dataset.filter;
        this.currentFilter = filter;

        // Update active tab
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        e.target.classList.add('active');

        this.loadSessions();
    }

    async updateStats() {
        const result = await this.apiGet('index.php?action=get_stats');
        
        if (result.success) {
            document.getElementById('totalSessions').textContent = result.stats.total_sessions;
            document.getElementById('completedSessions').textContent = result.stats.completed_sessions;
            document.getElementById('totalHours').textContent = result.stats.total_hours;
        }
    }

    // Utility Functions
    formatDate(dateString) {
        const options = { month: 'short', day: 'numeric', year: 'numeric' };
        return new Date(dateString).toLocaleDateString(undefined, options);
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 10000;
            transform: translateX(100%);
            transition: transform 0.3s ease;
        `;

        // Set background color based on type
        switch (type) {
            case 'success':
                notification.style.background = '#28a745';
                break;
            case 'error':
                notification.style.background = '#dc3545';
                break;
            default:
                notification.style.background = '#667eea';
        }

        document.body.appendChild(notification);

        // Show notification
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);

        // Hide notification after 3 seconds
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (document.body.contains(notification)) {
                    document.body.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }
}

// Initialize the application
const app = new StudySprint();
