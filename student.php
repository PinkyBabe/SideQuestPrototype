<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth_middleware.php';

// Check if user is student
checkUserRole(['student']);

// Get student data
$student_id = $_SESSION['user_id'];
$conn = Database::getInstance();

try {
    // Get complete student information with simpler query first
    $query = "SELECT 
        u.*,
        c.name as course_name,
        c.code as course_code
    FROM users u 
    LEFT JOIN courses c ON u.course_id = c.id 
    WHERE u.id = ?";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param('i', $student_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception("Result failed: " . $stmt->error);
    }

    $student = $result->fetch_assoc();
    if (!$student) {
        throw new Exception("Student not found");
    }

    // Get quest counts separately
    $pending_query = "SELECT COUNT(*) as count FROM quests WHERE student_id = ? AND status = 'pending'";
    $stmt = $conn->prepare($pending_query);
    if ($stmt) {
        $stmt->bind_param('i', $student_id);
        $stmt->execute();
        $pending_result = $stmt->get_result()->fetch_assoc();
        $student['pending_count'] = $pending_result['count'];
    } else {
        $student['pending_count'] = 0;
    }

    $completed_query = "SELECT COUNT(*) as count FROM quests WHERE student_id = ? AND status = 'completed'";
    $stmt = $conn->prepare($completed_query);
    if ($stmt) {
        $stmt->bind_param('i', $student_id);
        $stmt->execute();
        $completed_result = $stmt->get_result()->fetch_assoc();
        $student['completed_count'] = $completed_result['count'];
    } else {
        $student['completed_count'] = 0;
    }

    $earnings_query = "SELECT COALESCE(SUM(cash_reward), 0) as total FROM quests WHERE student_id = ? AND status = 'completed'";
    $stmt = $conn->prepare($earnings_query);
    if ($stmt) {
        $stmt->bind_param('i', $student_id);
        $stmt->execute();
        $earnings_result = $stmt->get_result()->fetch_assoc();
        $student['total_earnings'] = $earnings_result['total'];
    } else {
        $student['total_earnings'] = 0;
    }

    // Get recent quests with error handling
    $recent_quests = [];
    $recent_query = "SELECT 
        q.*,
        u.first_name as faculty_name,
        u.email as faculty_email,
        u.office_name as department,
        u.room_number
    FROM quests q
    JOIN users u ON q.faculty_id = u.id
    WHERE q.student_id = ?
    AND q.status IN ('pending', 'completed')
    ORDER BY 
        CASE 
            WHEN q.status = 'pending' THEN q.created_at
            ELSE q.completed_at
        END DESC
    LIMIT 5";

    $stmt = $conn->prepare($recent_query);
    if ($stmt) {
        $stmt->bind_param('i', $student_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $row['rewards'] = [
                    'cash' => $row['cash_reward'] ?? 0,
                    'snack' => ($row['snack_reward'] ?? 0) == 1
                ];
                $recent_quests[] = $row;
            }
        }
    }

} catch (Exception $e) {
    error_log("Error in student.php: " . $e->getMessage());
    // Return a more specific error message for debugging
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Side Quest - Student Dashboard</title>
    <link rel="stylesheet" href="css/shared.css">
    <link rel="stylesheet" href="css/student.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="user-id" content="<?php echo $_SESSION['user_id']; ?>">
</head>
<body>
    <!-- Top area -->
    <div class="box">
        <h1>SIDEQUEST <input id="searchbar" type="text" placeholder="Search for quests..."></h1>
        <i class="fas fa-user profile-icon" onclick="showLogoutConfirmation()" style="cursor: pointer;"></i>
    </div>

    <nav>
        <ul>
            <li><a href="#" onclick="navigateTo('home')" class="active">HOME</a></li>
            <li><a href="#" onclick="navigateTo('profile')">PROFILE</a></li>
            <li><a href="#" onclick="navigateTo('workspace')">WORKSPACE</a></li>
        </ul>
    </nav>

    <!-- Home section with faculty posts -->
    <section id="home" class="container section">
        <div class="section-header">
            <h2>Available Quests</h2>
        </div>
        
        <!-- Loading indicator -->
        <div id="loading-posts" class="loading" style="display: none;">
            <div class="spinner"></div>
            <p>Loading quests...</p>
        </div>
        
        <!-- Posts container -->
        <div id="posts-container" class="posts-grid"></div>
    </section>

    <!-- Profile section -->
    <section id="profile" class="container section" style="display: none;">
        <div class="cover_area">
            <div class="cover_page">
                <img id="prof_pic" 
                    src="<?php echo htmlspecialchars($student['profile_pic'] ?? 'images/default_avatar.png'); ?>"
                    alt="Profile Picture">
                <br>
                <div class="profile-info">
                    <div id="name">
                        <?php 
                            if (isset($student['first_name']) && isset($student['last_name'])) {
                                echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']);
                            } else {
                                echo 'Student';
                            }
                        ?>
                    </div>
                    <div id="role">Student</div>
                    <div id="course">
                        <?php 
                            if (isset($student['course_name'])) {
                                echo htmlspecialchars($student['course_name']);
                                if (isset($student['course_code'])) {
                                    echo ' (' . htmlspecialchars($student['course_code']) . ')';
                                }
                            }
                        ?>
                    </div>
                    <div id="email">
                        <i class="fas fa-envelope"></i>
                        <?php echo htmlspecialchars($student['email'] ?? ''); ?>
                    </div>
                    <?php if (isset($student['year_level'])): ?>
                    <div id="year-level">
                        <i class="fas fa-graduation-cap"></i>
                        Year <?php echo htmlspecialchars($student['year_level']); ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="stats">
                </div>

                <div class="quest-history">
                </div>
            </div>
        </div>
    </section>

    <!-- Workspace section -->
    <section id="workspace" class="container section" style="display: none;">
        <div class="workspace-header">
            <h2>My Quests</h2>
            <div class="workspace-tabs">
                <button class="tab-btn active" data-tab="pending">Pending</button>
                <button class="tab-btn" data-tab="completed">Completed</button>
            </div>
        </div>

        <div class="workspace-content">
            <div id="pending-tab" class="tab-content active">
                <div class="quest-list" id="pending-quests"></div>
            </div>
            <div id="completed-tab" class="tab-content">
                <div class="quest-list" id="completed-quests"></div>
            </div>
        </div>
    </section>

    <!-- Notification container -->
    <div id="notification-container"></div>

    <!-- Logout confirmation modal -->
    <div id="logout-confirmation" class="modal">
        <div class="modal-content">
            <h2>Confirm Logout</h2>
            <p>Are you sure you want to logout?</p>
            <div class="modal-buttons">
                <button onclick="logout()" class="btn-danger">Logout</button>
                <button onclick="closeLogoutModal()" class="btn-secondary">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Quest details modal -->
    <div id="quest-details-modal" class="modal">
        <div class="modal-content">
            <h2>Quest Details</h2>
            <div id="quest-details-content"></div>
            <div class="modal-buttons">
                <button onclick="hideModal('quest-details-modal')" class="btn-secondary">Close</button>
            </div>
        </div>
    </div>

    <!-- Submit work modal -->
    <div id="submit-work-modal" class="modal">
        <!-- ... existing submit work modal content ... -->
    </div>

    <!-- Include notification component -->
    <?php include 'includes/components/notification.php'; ?>
    
    <!-- Scripts -->
    <script src="js/shared.js"></script>
    <script src="js/student.js"></script>
</body>
</html> 