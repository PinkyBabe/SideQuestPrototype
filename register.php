
<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/components/notification.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$conn = Database::getInstance();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - SideQuest</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/login.css">
    <style>
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            background: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            display: none;
            animation: slideIn 0.3s ease;
        }

        .notification.success {
            background: #28a745;
            color: white;
        }

        .notification.error {
            background: #dc3545;
            color: white;
        }

        .notification-content {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .close-btn {
            background: none;
            border: none;
            color: currentColor;
            font-size: 20px;
            cursor: pointer;
            padding: 0 5px;
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

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
    </style>
</head>
<body>
    <div id="notificationContainer"></div>

    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>SIDEQUEST</h1>
                <h2>Student Registration</h2>
            </div>
            
            <form id="registerForm" class="login-form">
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
                        <?php
                        $courses = $conn->query("SELECT id, code, name FROM courses ORDER BY code");
                        while ($course = $courses->fetch_assoc()) {
                            echo "<option value='{$course['id']}'>{$course['code']} - {$course['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-buttons">
                    <button type="submit" class="login-btn">Register</button>
                    <a href="login.php" class="register-link">Back to Login</a>
                </div>
            </form>
        </div>
    </div>

    <script>
    function showNotification(message, type = 'success') {
        const container = document.getElementById('notificationContainer');
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <span class="message">${message}</span>
                <button onclick="closeNotification(this)" class="close-btn">&times;</button>
            </div>
        `;
        container.appendChild(notification);
        
        // Show notification
        setTimeout(() => notification.style.display = 'block', 100);
        
        // Auto hide after 5 seconds
        setTimeout(() => closeNotification(notification.querySelector('.close-btn')), 5000);
    }

    function closeNotification(button) {
        const notification = button.closest('.notification');
        notification.style.animation = 'slideOut 0.3s ease forwards';
        setTimeout(() => notification.remove(), 300);
    }

    document.getElementById('registerForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Check if passwords match
        const password = document.getElementById('studentPassword').value;
        const confirmPassword = document.getElementById('studentConfirmPassword').value;
        
        if (password !== confirmPassword) {
            showNotification('Passwords do not match', 'error');
            return;
        }
        
        const formData = new FormData(this);
        const submitButton = this.querySelector('button[type="submit"]');
        
        try {
            submitButton.disabled = true;
            submitButton.textContent = 'Creating Account...';
            
            const response = await fetch('includes/register_process.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showNotification('Account created successfully! Redirecting to login...', 'success');
                setTimeout(() => {
                    window.location.href = 'login.php?registered=true';
                }, 2000);
            } else {
                throw new Error(data.message || 'Registration failed');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification(error.message, 'error');
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = 'Register';
        }
    });
    </script>
</body>
</html> 