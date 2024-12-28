<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth_middleware.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug session
error_log("Session data: " . print_r($_SESSION, true));

// Check if user is admin
checkUserRole(['admin']);

// Get database connection
$conn = Database::getInstance();

// Initialize stats array
$stats = [
    'faculty_count' => 0,
    'student_count' => 0,
    'active_posts' => 0,
    'completed_tasks' => 0
];

// Get counts
$stats['faculty_count'] = getFacultyCount();
$stats['student_count'] = getStudentCount();
$stats['active_posts'] = getActivePostsCount();
$stats['completed_tasks'] = getCompletedTasksCount();

// Get student list
$students = getStudentList();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SideQuest</title>
    <!-- Base styles first -->
    <link rel="stylesheet" href="css/style.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Shared dashboard styles -->
    <link rel="stylesheet" href="css/shared_dashboard.css">
    <!-- Admin specific styles last -->
    <link rel="stylesheet" href="css/admin.css">
    <!-- Cache control -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <!-- Force CSS reload -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Force reload CSS files
            const links = document.getElementsByTagName('link');
            for (let i = 0; i < links.length; i++) {
                if (links[i].rel === 'stylesheet') {
                    const href = links[i].href.split('?')[0];
                    links[i].href = href + '?v=' + new Date().getTime();
                }
            }
            // Add class to body after styles are loaded
            document.body.classList.add('styles-loaded');
        });
    </script>
</head>
<body>
    <div class="box sidebar-hidden">
        <div class="menu-toggle" id="menuToggle">
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
        <h1>SIDEQUEST</h1>
        <img id="dp" src="https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y" alt="Profile" onclick="showLogoutConfirmation()" style="width: 40px; height: 40px; border-radius: 50%; cursor: pointer;">
    </div>

    <div class="sidebar collapsed">
        <div class="sidebar-header">
            <h2>SIDEQUEST</h2>
        </div>
        <ul>
            <li data-tab="dashboard" class="active">Dashboard</li>
            <li data-tab="faculty">Manage Faculty</li>
            <li data-tab="students">Manage Students</li>
            <li data-tab="posts">Post Tracker</li>
            <li onclick="showLogoutConfirmation()">Logout</li>
        </ul>
    </div>

    <div class="main-content expanded">
        <!-- Dashboard Tab -->
        <div id="dashboard" class="tab-content">
            <h2>Dashboard</h2>
            <div class="stats-container">
                <div class="stat-card">
                    <h3>Faculty Members</h3>
                    <p data-stat="faculty_count"><?php echo $stats['faculty_count']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Students</h3>
                    <p data-stat="student_count"><?php echo $stats['student_count']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Active Posts</h3>
                    <p data-stat="active_posts"><?php echo $stats['active_posts']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Completed Tasks</h3>
                    <p data-stat="completed_tasks"><?php echo $stats['completed_tasks']; ?></p>
                </div>
            </div>
        </div>

        <!-- Faculty Management Tab -->
        <div id="faculty" class="tab-content">
            <div class="content-header">
                <h2>Faculty Management</h2>
                <button class="add-btn" onclick="showModal('addFacultyModal')">Add Faculty</button>
            </div>
            
            <div class="table-container">
                <table id="facultyList">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Room Number</th>
                            <th>Office Name</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="facultyTableBody">
                        <!-- Faculty list will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Students Management Tab -->
        <div id="students" class="tab-content">
            <div class="content-header">
                <h2>Student Management</h2>
                <button class="add-btn" onclick="showModal('addStudentModal')">Add Student</button>
            </div>
            
            <div class="table-container">
                <table id="studentList">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Course</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="studentTableBody">
                        <!-- Student rows will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Post Tracker Tab -->
        <div id="posts" class="tab-content">
            <div class="content-header">
                <h2>Post Tracker</h2>
            </div>
            
            <div class="faculty-list">
                <table>
                    <thead>
                        <tr>
                            <th>Faculty Name</th>
                            <th>Description</th>
                            <th>Job Type</th>
                            <th>Location</th>
                            <th>Meeting Time</th>
                            <th>Student</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="postTrackerBody">
                        <!-- Posts will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Faculty Modal -->
    <div id="addFacultyModal" class="modal">
        <div class="modal-content">
            <h2>Add Faculty</h2>
            <form id="addFacultyForm">
                <div class="form-group">
                    <label for="facultyFirstName" class="required">First Name</label>
                    <input type="text" id="facultyFirstName" name="firstName" required>
                </div>
                <div class="form-group">
                    <label for="facultyLastName" class="required">Last Name</label>
                    <input type="text" id="facultyLastName" name="lastName" required>
                </div>
                <div class="form-group">
                    <label for="facultyEmail" class="required">Email</label>
                    <input type="email" id="facultyEmail" name="email" required>
                </div>
                <div class="form-group">
                    <label for="facultyPassword" class="required">Password</label>
                    <input type="password" id="facultyPassword" name="password" required>
                </div>
                <div class="form-group">
                    <label for="facultyConfirmPassword" class="required">Confirm Password</label>
                    <input type="password" id="facultyConfirmPassword" name="confirmPassword" required>
                </div>
                <div class="form-group">
                    <label for="roomNumber">Room Number</label>
                    <input type="text" id="roomNumber" name="roomNumber">
                </div>
                <div class="form-group">
                    <label for="officeName" class="required">Office Name</label>
                    <input type="text" id="officeName" name="officeName" required>
                </div>
                <div class="modal-buttons">
                    <button type="submit" class="btn-primary">Add Faculty</button>
                    <button type="button" class="btn-secondary" onclick="hideModal('addFacultyModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Faculty Modal -->
    <div id="editFacultyModal" class="modal">
        <div class="modal-content">
            <h2>Edit Faculty</h2>
            <form id="editFacultyForm">
                <input type="hidden" id="editFacultyId" name="facultyId">
                <div class="form-group">
                    <label for="editFacultyFirstName">First Name</label>
                    <input type="text" id="editFacultyFirstName" name="firstName" required>
                </div>
                <div class="form-group">
                    <label for="editFacultyLastName">Last Name</label>
                    <input type="text" id="editFacultyLastName" name="lastName" required>
                </div>
                <div class="form-group">
                    <label for="editFacultyEmail">Email</label>
                    <input type="email" id="editFacultyEmail" name="email" required>
                </div>
                <div class="form-group">
                    <label for="editFacultyPassword">New Password (leave blank to keep current)</label>
                    <input type="password" id="editFacultyPassword" name="password">
                </div>
                <div class="form-group">
                    <label for="editFacultyConfirmPassword">Confirm New Password</label>
                    <input type="password" id="editFacultyConfirmPassword" name="confirmPassword">
                </div>
                <div class="form-group">
                    <label for="editRoomNumber">Room Number</label>
                    <input type="text" id="editRoomNumber" name="roomNumber" required>
                </div>
                <div class="form-group">
                    <label for="editOfficeName">Office Name</label>
                    <input type="text" id="editOfficeName" name="officeName" required>
                </div>
                <div class="modal-buttons">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <button type="button" class="btn btn-secondary" onclick="hideModal('editFacultyModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" class="modal">
        <div class="modal-content">
            <h2>Confirm Logout</h2>
            <p>Are you sure you want to logout?</p>
            <div class="modal-buttons">
                <button onclick="logout()" class="btn btn-danger">Logout</button>
                <button onclick="closeLogoutModal()" class="btn btn-secondary">Cancel</button>
            </div>
        </div>
    </div>

    <!-- View Faculty Modal -->
    <div id="viewFacultyModal" class="modal">
        <div class="modal-content">
            <h2>Faculty Details</h2>
            <div class="faculty-details">
                <div class="detail-row">
                    <label>Name:</label>
                    <span id="viewName"></span>
                </div>
                <div class="detail-row">
                    <label>Email:</label>
                    <span id="viewEmail"></span>
                </div>
                <div class="detail-row">
                    <label>Room Number:</label>
                    <span id="viewRoomNumber"></span>
                </div>
                <div class="detail-row">
                    <label>Office:</label>
                    <span id="viewOfficeName"></span>
                </div>
            </div>
            <div class="modal-buttons">
                <button type="button" class="btn btn-secondary" onclick="hideModal('viewFacultyModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- View Faculty Password Modal -->
    <div id="viewPasswordModal" class="modal">
        <div class="modal-content">
            <h2>Faculty Password</h2>
            <div class="student-details">
                <div class="detail-row">
                    <label>Name:</label>
                    <span id="viewPasswordName"></span>
                </div>
                <div class="detail-row">
                    <label>Email:</label>
                    <span id="viewPasswordEmail"></span>
                </div>
                <div class="detail-row">
                    <label>Password:</label>
                    <div style="display: flex; align-items: center;">
                        <span id="viewPasswordValue"></span>
                        <button class="copy-button" onclick="copyPassword('viewPasswordValue')">
                            Copy
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-buttons">
                <button type="button" class="btn-secondary" onclick="hideModal('viewPasswordModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- View Student Modal -->
    <div id="viewStudentModal" class="modal">
        <div class="modal-content">
            <h2>Student Details</h2>
            <div class="student-details">
                <div class="detail-row">
                    <label>Name:</label>
                    <span id="viewStudentName"></span>
                </div>
                <div class="detail-row">
                    <label>Email:</label>
                    <span id="viewStudentEmail"></span>
                </div>
                <div class="detail-row">
                    <label>Course:</label>
                    <span id="viewStudentCourse"></span>
                </div>
            </div>
            <div class="modal-buttons">
                <button type="button" class="btn btn-secondary" onclick="hideModal('viewStudentModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- View Student Password Modal -->
    <div id="viewStudentPasswordModal" class="modal">
        <div class="modal-content">
            <h2>Student Password</h2>
            <div class="student-details">
                <div class="detail-row">
                    <label>Name:</label>
                    <span id="viewStudentPasswordName"></span>
                </div>
                <div class="detail-row">
                    <label>Email:</label>
                    <span id="viewStudentPasswordEmail"></span>
                </div>
                <div class="detail-row">
                    <label>Password:</label>
                    <div style="display: flex; align-items: center;">
                        <span id="viewStudentPasswordValue"></span>
                        <button class="copy-button" onclick="copyPassword('viewStudentPasswordValue')">
                            Copy
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-buttons">
                <button type="button" class="btn-secondary" onclick="hideModal('viewStudentPasswordModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div id="addStudentModal" class="modal">
        <div class="modal-content">
            <h2>Add Student</h2>
            <form id="addStudentForm">
                <div class="form-group">
                    <label for="studentFirstName" class="required">First Name</label>
                    <input type="text" id="studentFirstName" name="firstName" required>
                </div>
                <div class="form-group">
                    <label for="studentLastName" class="required">Last Name</label>
                    <input type="text" id="studentLastName" name="lastName" required>
                </div>
                <div class="form-group">
                    <label for="studentEmail" class="required">Email</label>
                    <input type="email" id="studentEmail" name="email" required>
                </div>
                <div class="form-group">
                    <label for="studentPassword" class="required">Password</label>
                    <input type="password" id="studentPassword" name="password" required>
                </div>
                <div class="form-group">
                    <label for="studentConfirmPassword" class="required">Confirm Password</label>
                    <input type="password" id="studentConfirmPassword" name="confirmPassword" required>
                </div>
                <div class="form-group">
                    <label for="studentCourse" class="required">Course</label>
                    <select id="studentCourse" name="course" required>
                        <option value="">Select Course</option>
                        <!-- Courses will be loaded dynamically -->
                    </select>
                </div>
                <div class="modal-buttons">
                    <button type="submit" class="btn-primary">Add Student</button>
                    <button type="button" class="btn-secondary" onclick="hideModal('addStudentModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div id="editStudentModal" class="modal">
        <div class="modal-content">
            <h2>Edit Student</h2>
            <form id="editStudentForm">
                <input type="hidden" id="editStudentId" name="studentId">
                <div class="form-group">
                    <label for="editStudentFirstName">First Name</label>
                    <input type="text" id="editStudentFirstName" name="firstName" required>
                </div>
                <div class="form-group">
                    <label for="editStudentLastName">Last Name</label>
                    <input type="text" id="editStudentLastName" name="lastName" required>
                </div>
                <div class="form-group">
                    <label for="editStudentEmail">Email</label>
                    <input type="email" id="editStudentEmail" name="email" required>
                </div>
                <div class="form-group">
                    <label for="editStudentPassword">New Password (leave blank to keep current)</label>
                    <input type="password" id="editStudentPassword" name="password">
                </div>
                <div class="form-group">
                    <label for="editStudentConfirmPassword">Confirm New Password</label>
                    <input type="password" id="editStudentConfirmPassword" name="confirmPassword">
                </div>
                <div class="form-group">
                    <label for="editCourse">Course</label>
                    <select id="editCourse" name="course" required>
                        <option value="">Select Course</option>
                        <?php
                        // Get course list from database
                        $courses = $conn->query("SELECT id, code, name FROM courses ORDER BY code");
                        while ($course = $courses->fetch_assoc()) {
                            echo "<option value='{$course['id']}'>{$course['code']} - {$course['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="modal-buttons">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <button type="button" class="btn btn-secondary" onclick="hideModal('editStudentModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/admin.js"></script>
    <?php include 'includes/components/notification.php'; ?>
    <script src="js/shared.js"></script>

    <style>
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1000;
    }

    .modal-content {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .alert {
        padding: 15px 25px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        margin-bottom: 10px;
        animation: slideIn 0.3s ease;
    }

    .alert-error {
        background: linear-gradient(135deg, #ff4d4d, #ff1a1a);
    }

    .alert-success {
        background: linear-gradient(135deg, #4CAF50, #45a049);
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    .fade-out {
        animation: fadeOut 0.5s ease forwards;
    }

    @keyframes fadeOut {
        to {
            opacity: 0;
            transform: translateY(-20px);
        }
    }
    </style>
</body>
</html> 