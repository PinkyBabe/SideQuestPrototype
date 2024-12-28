<?php
session_start();

// If already logged in, redirect to appropriate page
if (isset($_SESSION['user_id'])) {
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    switch($_SESSION['role']) {
        case 'admin':
            header('Location: admin.php');
            break;
        case 'faculty':
            header('Location: faculty.php');
            break;
        case 'student':
            header('Location: student.php');
            break;
    }
    exit();
}

// Check if this is a logout or session expiry
$message = '';
if (isset($_GET['session']) && $_GET['session'] === 'expired') {
    $message = 'Your session has expired. Please log in again.';
} else if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $message = 'You have been successfully logged out.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SideQuest - Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #ff8a00, #e52e71);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            animation: fadeIn 0.5s ease;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            border-color: #e52e71;
            outline: none;
        }

        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(to right, #ff8a00, #e52e71);
            border: none;
            border-radius: 5px;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: opacity 0.3s ease;
        }

        button:hover {
            opacity: 0.9;
        }

        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }

        .register-link a {
            color: #e52e71;
            text-decoration: none;
            font-weight: bold;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        #error-message {
            background: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: none;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: none;
            text-align: center;
        }

        .message.error {
            background: #ffebee;
            color: #c62828;
        }

        .message.show {
            display: block;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Welcome Back!</h1>
            <p>Login to your SideQuest account</p>
        </div>
        
        <?php if ($message): ?>
        <div class="message show"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <div id="error-message"></div>
        
        <form onsubmit="handleLogin(event)">
            <div class="form-group">
                <input type="email" name="email" id="email" required placeholder="Email">
            </div>
            <div class="form-group">
                <input type="password" name="password" id="password" required placeholder="Password">
            </div>
            <button type="submit">Login</button>
        </form>
        
        <div class="register-link">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </div>

    <script>
    // Show message if it exists in URL parameters
    window.onload = function() {
        const urlParams = new URLSearchParams(window.location.search);
        const message = document.querySelector('.message');
        if (message) {
            message.classList.add('show');
            setTimeout(() => {
                message.classList.remove('show');
            }, 5000);
        }
    }

    async function handleLogin(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        formData.append('action', 'login');

        const errorMessage = document.getElementById('error-message');
        const submitButton = form.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.textContent;

        try {
            errorMessage.style.display = 'none';
            submitButton.disabled = true;
            submitButton.textContent = 'Logging in...';

            const response = await fetch('includes/auth.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                submitButton.textContent = 'Redirecting...';
                switch (data.role) {
                    case 'admin':
                        window.location.href = 'admin.php';
                        break;
                    case 'faculty':
                        window.location.href = 'faculty.php';
                        break;
                    case 'student':
                        window.location.href = 'student.php';
                        break;
                    default:
                        throw new Error('Unknown user role');
                }
            } else {
                throw new Error(data.error || 'Login failed');
            }
        } catch (error) {
            console.error('Login error:', error);
            errorMessage.textContent = error.message || 'An error occurred during login';
            errorMessage.style.display = 'block';
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = originalButtonText;
        }
    }
    </script>
</body>
</html>
  </rewritten_file> 