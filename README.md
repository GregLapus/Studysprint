# StudySprint - Personal Study Session Organizer

A web-based CRUD application designed to help students organize, track, and manage their study sessions effectively.

## Features

### 🔐 User Authentication
- User registration and login system
- Secure session management
- Persistent user data using localStorage

### 📚 Study Session Management
- **Create**: Add new study sessions with title, subject, date, time, duration, and notes
- **Read**: View all study sessions with filtering options (All, Pending, Completed)
- **Update**: Edit existing study sessions
- **Delete**: Remove completed or unwanted sessions

### 📊 Progress Tracking
- Total sessions counter
- Completed sessions tracker
- Total hours studied calculator
- Visual status indicators (Pending, Completed, Cancelled)

### 🎨 Modern UI/UX
- Responsive design for all devices
- Beautiful gradient backgrounds
- Smooth animations and transitions
- Intuitive modal interfaces
- Color-coded status indicators

## Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Storage**: Browser localStorage for data persistence
- **Design**: Responsive CSS Grid and Flexbox layouts
- **Architecture**: Object-oriented JavaScript with class-based structure

## File Structure

```
StudySprint/
├── index.html          # Main application HTML
├── styles.css          # Complete styling and responsive design
├── script.js           # Application logic and CRUD operations
└── README.md           # Project documentation
```

## Getting Started

1. **Download or clone** the project files to your local machine
2. **Open `index.html`** in your preferred web browser
3. **Register a new account** or login with existing credentials
4. **Start organizing** your study sessions!

## Usage Guide

### Registration
1. Click "Register" on the login page
2. Enter your full name, email, and password
3. Click "Register" to create your account

### Adding Study Sessions
1. Click the "+ Add Session" button on the dashboard
2. Fill in session details:
   - Session Title
   - Subject
   - Date and Time
   - Duration (in hours)
   - Notes (optional)
   - Status (Pending, Completed, or Cancelled)
3. Click "Save Session"

### Managing Sessions
- **View**: All sessions are displayed on the dashboard
- **Filter**: Use tabs to view All, Pending, or Completed sessions
- **Edit**: Click the "Edit" button on any session card
- **Complete**: Mark pending sessions as completed
- **Delete**: Remove unwanted sessions

### Progress Tracking
The dashboard header displays:
- Total number of study sessions
- Number of completed sessions
- Total hours studied across all completed sessions

## Data Storage

The application uses browser localStorage to persist:
- User authentication data
- Study session records
- User preferences

Data is stored locally in the browser and remains available even after closing the browser window.

## Browser Compatibility

This application is compatible with all modern browsers:
- Chrome (recommended)
- Firefox
- Safari
- Edge

## Security Features

- Password-based authentication
- User session isolation
- Client-side data validation
- XSS prevention through proper input handling

## Future Enhancements

Potential features for future versions:
- Cloud synchronization
- Study reminders and notifications
- Advanced analytics and reporting
- Subject-wise performance tracking
- Study streak gamification
- Export functionality (PDF, CSV)
- Calendar integration
- Study timer/pomodoro integration

## Educational Value

This project demonstrates:
- CRUD operations in a practical application
- Frontend web development best practices
- Responsive design principles
- Local storage implementation
- User authentication systems
- Modern JavaScript class-based architecture

## Contributing

Feel free to fork this project and contribute improvements. This is an educational project designed to showcase web development skills and CRUD operation implementation.

---

**StudySprint** - Empowering students to take control of their academic routines through effective organization and tracking.
