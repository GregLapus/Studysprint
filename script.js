// StudySprint Application
class StudySprint {
    constructor() {
        this.currentUser = null;
        this.sessions = [];
        this.currentFilter = 'all';
        this.editingSessionId = null;
        this.init();
    }

    init() {
        this.loadFromStorage();
        this.setupEventListeners();
        this.checkAuth();
    }

    // Storage Management
    loadFromStorage() {
        const userData = localStorage.getItem('studySprintUser');
        if (userData) {
            this.currentUser = JSON.parse(userData);
        }

        const sessionsData = localStorage.getItem('studySprintSessions');
        if (sessionsData) {
            this.sessions = JSON.parse(sessionsData);
        }
    }

    saveToStorage() {
        if (this.currentUser) {
            localStorage.setItem('studySprintUser', JSON.stringify(this.currentUser));
        }
        localStorage.setItem('studySprintSessions', JSON.stringify(this.sessions));
    }

    // Authentication
    checkAuth() {
        if (this.currentUser) {
            this.showDashboard();
            // Ensure user info is updated
            setTimeout(() => this.updateUserInfo(), 100);
        } else {
            this.showLogin();
        }
    }

    showLogin() {
        this.hideAllPages();
        document.getElementById('loginPage').classList.add('active');
    }

    showRegister() {
        this.hideAllPages();
        document.getElementById('registerPage').classList.add('active');
    }

    showDashboard() {
        this.hideAllPages();
        document.getElementById('dashboardPage').classList.add('active');
        this.updateUserInfo();
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
        document.getElementById('loginForm').addEventListener('submit', (e) => this.handleLogin(e));
        document.getElementById('registerForm').addEventListener('submit', (e) => this.handleRegister(e));
        document.getElementById('showRegister').addEventListener('click', (e) => {
            e.preventDefault();
            this.showRegister();
        });
        document.getElementById('showLogin').addEventListener('click', (e) => {
            e.preventDefault();
            this.showLogin();
        });
        document.getElementById('logoutBtn').addEventListener('click', () => this.handleLogout());

        // Session management
        document.getElementById('addSessionBtn').addEventListener('click', () => this.showSessionModal());
        document.getElementById('sessionForm').addEventListener('submit', (e) => this.handleSessionSubmit(e));
        document.getElementById('closeModal').addEventListener('click', () => this.hideSessionModal());
        document.getElementById('cancelBtn').addEventListener('click', () => this.hideSessionModal());

        // Filter tabs
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.handleFilterChange(e));
        });

        // Close modal on outside click
        document.getElementById('sessionModal').addEventListener('click', (e) => {
            if (e.target.id === 'sessionModal') {
                this.hideSessionModal();
            }
        });
    }

    // Authentication Handlers
    handleLogin(e) {
        e.preventDefault();
        const email = document.getElementById('loginEmail').value;
        const password = document.getElementById('loginPassword').value;

        // Get registered users
        const users = JSON.parse(localStorage.getItem('studySprintUsers') || '[]');
        const user = users.find(u => u.email === email && u.password === password);

        if (user) {
            this.currentUser = user;
            this.saveToStorage();
            this.showDashboard();
            this.showNotification('Login successful!', 'success');
        } else {
            this.showNotification('Invalid email or password', 'error');
        }

        e.target.reset();
    }

    handleRegister(e) {
        e.preventDefault();
        const name = document.getElementById('registerName').value;
        const email = document.getElementById('registerEmail').value;
        const password = document.getElementById('registerPassword').value;

        // Get existing users
        const users = JSON.parse(localStorage.getItem('studySprintUsers') || '[]');
        
        // Check if user already exists
        if (users.find(u => u.email === email)) {
            this.showNotification('User with this email already exists', 'error');
            return;
        }

        // Create new user
        const newUser = {
            id: Date.now().toString(),
            name,
            email,
            password,
            createdAt: new Date().toISOString()
        };

        users.push(newUser);
        localStorage.setItem('studySprintUsers', JSON.stringify(users));

        this.currentUser = newUser;
        this.saveToStorage();
        this.showDashboard();
        this.showNotification('Registration successful!', 'success');

        e.target.reset();
    }

    handleLogout() {
        this.currentUser = null;
        this.sessions = [];
        this.saveToStorage();
        this.showLogin();
        this.showNotification('Logged out successfully', 'info');
    }

    // Session Management
    showSessionModal(sessionId = null) {
        const modal = document.getElementById('sessionModal');
        const modalTitle = document.getElementById('modalTitle');
        const form = document.getElementById('sessionForm');

        if (sessionId) {
            const session = this.sessions.find(s => s.id === sessionId);
            if (session) {
                modalTitle.textContent = 'Edit Study Session';
                document.getElementById('sessionTitle').value = session.title;
                document.getElementById('sessionSubject').value = session.subject;
                document.getElementById('sessionDate').value = session.date;
                document.getElementById('sessionTime').value = session.time;
                document.getElementById('sessionDuration').value = session.duration;
                document.getElementById('sessionNotes').value = session.notes || '';
                document.getElementById('sessionStatus').value = session.status;
                this.editingSessionId = sessionId;
            }
        } else {
            modalTitle.textContent = 'Add Study Session';
            form.reset();
            // Set default date to today
            document.getElementById('sessionDate').value = new Date().toISOString().split('T')[0];
            this.editingSessionId = null;
        }

        modal.classList.add('active');
    }

    hideSessionModal() {
        document.getElementById('sessionModal').classList.remove('active');
        document.getElementById('sessionForm').reset();
        this.editingSessionId = null;
    }

    handleSessionSubmit(e) {
        e.preventDefault();

        const sessionData = {
            title: document.getElementById('sessionTitle').value,
            subject: document.getElementById('sessionSubject').value,
            date: document.getElementById('sessionDate').value,
            time: document.getElementById('sessionTime').value,
            duration: parseFloat(document.getElementById('sessionDuration').value),
            notes: document.getElementById('sessionNotes').value,
            status: document.getElementById('sessionStatus').value,
            userId: this.currentUser.id
        };

        if (this.editingSessionId) {
            // Update existing session
            const index = this.sessions.findIndex(s => s.id === this.editingSessionId);
            if (index !== -1) {
                this.sessions[index] = { ...this.sessions[index], ...sessionData };
                this.showNotification('Session updated successfully!', 'success');
            }
        } else {
            // Add new session
            const newSession = {
                id: Date.now().toString(),
                ...sessionData,
                createdAt: new Date().toISOString()
            };
            this.sessions.push(newSession);
            this.showNotification('Session added successfully!', 'success');
        }

        this.saveToStorage();
        this.loadSessions();
        this.updateStats();
        this.hideSessionModal();
    }

    deleteSession(sessionId) {
        if (confirm('Are you sure you want to delete this session?')) {
            this.sessions = this.sessions.filter(s => s.id !== sessionId);
            this.saveToStorage();
            this.loadSessions();
            this.updateStats();
            this.showNotification('Session deleted successfully!', 'info');
        }
    }

    markSessionComplete(sessionId) {
        const session = this.sessions.find(s => s.id === sessionId);
        if (session) {
            session.status = 'completed';
            this.saveToStorage();
            this.loadSessions();
            this.updateStats();
            this.showNotification('Session marked as completed!', 'success');
        }
    }

    // UI Updates
    loadSessions() {
        const sessionsList = document.getElementById('sessionsList');
        const userSessions = this.sessions.filter(s => s.userId === this.currentUser.id);
        const filteredSessions = this.filterSessions(userSessions, this.currentFilter);

        if (filteredSessions.length === 0) {
            sessionsList.innerHTML = `
                <div class="empty-state">
                    <h3>No study sessions found</h3>
                    <p>Start by adding your first study session!</p>
                </div>
            `;
            return;
        }

        // Sort sessions by date and time
        filteredSessions.sort((a, b) => {
            const dateA = new Date(`${a.date} ${a.time}`);
            const dateB = new Date(`${b.date} ${b.time}`);
            return dateB - dateA;
        });

        sessionsList.innerHTML = filteredSessions.map(session => this.createSessionCard(session)).join('');

        // Add event listeners to session cards
        this.attachSessionCardListeners();
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

    attachSessionCardListeners() {
        // Event listeners are added inline in the HTML
    }

    filterSessions(sessions, filter) {
        switch (filter) {
            case 'pending':
                return sessions.filter(s => s.status === 'pending');
            case 'completed':
                return sessions.filter(s => s.status === 'completed');
            default:
                return sessions;
        }
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

    updateStats() {
        const userSessions = this.sessions.filter(s => s.userId === this.currentUser.id);
        const completedSessions = userSessions.filter(s => s.status === 'completed');
        const totalHours = completedSessions.reduce((sum, s) => sum + s.duration, 0);

        document.getElementById('totalSessions').textContent = userSessions.length;
        document.getElementById('completedSessions').textContent = completedSessions.length;
        document.getElementById('totalHours').textContent = totalHours.toFixed(1);
    }

    updateUserInfo() {
        console.log('Updating user info for:', this.currentUser);
        if (this.currentUser && this.currentUser.name) {
            const welcomeText = `Welcome ${this.currentUser.name}`;
            document.getElementById('userName').textContent = welcomeText;
            console.log('Set welcome text to:', welcomeText);
        } else {
            console.log('No current user found');
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
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }
}

// Initialize the application
const app = new StudySprint();
