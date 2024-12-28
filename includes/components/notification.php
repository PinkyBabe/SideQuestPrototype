<!-- Notification/Alert Component -->
<div id="notification-container" style="display: none; position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>

<!-- Logout Confirmation Modal -->
<div id="logout-confirmation" class="modal" style="display: none;">
    <div class="modal-content">
        <h2>Confirm Logout</h2>
        <p>Are you sure you want to logout?</p>
        <div class="modal-buttons">
            <button onclick="logout()" class="btn btn-danger">Yes</button>
            <button onclick="hideLogoutConfirmation()" class="btn btn-secondary">No</button>
        </div>
    </div>
</div>

<?php
function showNotification($message, $type = 'success') {
    return "
    <div class='notification $type' id='notification'>
        <div class='notification-content'>
            <span class='message'>$message</span>
            <button onclick='closeNotification()' class='close-btn'>&times;</button>
        </div>
    </div>";
}
?> 