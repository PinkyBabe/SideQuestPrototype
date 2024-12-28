// Notification functions
function showAlert(message, type = 'error') {
    const container = document.getElementById('notification-container');
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    container.appendChild(alertDiv);
    container.style.display = 'block';
    
    setTimeout(() => {
        alertDiv.classList.add('fade-out');
        setTimeout(() => {
            alertDiv.remove();
            if (container.children.length === 0) {
                container.style.display = 'none';
            }
        }, 500);
    }, 3000);
}

// Logout functions
function showLogoutConfirmation() {
    document.getElementById('logout-confirmation').style.display = 'block';
}

function hideLogoutConfirmation() {
    document.getElementById('logout-confirmation').style.display = 'none';
}

async function logout() {
    try {
        const response = await fetch('includes/logout.php');
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        const data = await response.json();
        
        if (data.success) {
            window.location.href = 'login.php';
        } else {
            throw new Error(data.message || 'Logout failed');
        }
    } catch (error) {
        console.error('Logout error:', error);
        window.location.href = 'login.php'; // Redirect anyway on error
    }
}

function showModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
}

function hideModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function showNotification(message, type = 'error') {
    const container = document.getElementById('notification-container');
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    container.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('fade-out');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
} 