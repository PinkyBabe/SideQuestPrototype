// Global variables
let currentSection = 'home';
const studentId = document.querySelector('meta[name="user-id"]').content;

// Document ready function
document.addEventListener('DOMContentLoaded', function() {
    // Set up navigation
    document.querySelectorAll('nav a').forEach(link => {
        link.addEventListener('click', async (e) => {
            e.preventDefault();
            const section = link.getAttribute('onclick').match(/'(.*?)'/)[1];
            await navigateTo(section);
            // Update stats when changing sections
            await updateProfileStats();
        });
    });
    
    // Set up search functionality
    const searchbar = document.getElementById('searchbar');
    if (searchbar) {
        searchbar.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            filterPosts(searchTerm);
        });
    }
    
    // Set up workspace tab buttons
    document.querySelectorAll('.workspace-tabs .tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const tab = btn.getAttribute('data-tab');
            showWorkspaceTab(tab);
        });
    });
    
    // Show home section by default and load initial data
    navigateTo('home');
    updateProfileStats();
    
    // Update stats periodically (every 30 seconds)
    setInterval(updateProfileStats, 30000);
});

// Navigation function
function navigateTo(section) {
    // Hide all sections
    document.querySelectorAll('.section').forEach(s => {
        s.style.display = 'none';
    });
    
    // Remove active class from all nav links
    document.querySelectorAll('nav a').forEach(link => {
        link.classList.remove('active');
    });
    
    // Show selected section
    const selectedSection = document.getElementById(section);
    if (selectedSection) {
        selectedSection.style.display = 'block';
    }
    
    // Add active class to selected nav link
    const activeLink = document.querySelector(`nav a[onclick="navigateTo('${section}')"]`);
    if (activeLink) {
        activeLink.classList.add('active');
    }
    
    // Load section-specific content
    if (section === 'home') {
        loadFacultyPosts();
    } else if (section === 'workspace') {
        loadWorkspace();
    }
}

// Load faculty posts
async function loadFacultyPosts() {
    try {
        const response = await fetch('includes/get_faculty_posts.php');
        if (!response.ok) throw new Error('Failed to fetch posts');
        
        const data = await response.json();
        if (!data.success) throw new Error(data.message);
        
        displayFacultyPosts(data.posts);
    } catch (error) {
        console.error('Error:', error);
        showNotification(error.message, 'error');
    }
}

// Display faculty posts
function displayFacultyPosts(posts) {
    const container = document.getElementById('posts-container');
    container.innerHTML = '';

    if (!posts.length) {
        container.innerHTML = '<div class="no-quests">No available quests at the moment</div>';
        return;
    }

    posts.forEach(post => {
        const postElement = createPostElement(post);
        container.appendChild(postElement);
    });
}

// Create post element
function createPostElement(post) {
    const postDiv = document.createElement('div');
    postDiv.className = 'post';
    
    postDiv.innerHTML = `
        <div class="post-header">
            <div class="faculty-info">
                <i class="fas fa-user profile-icon"></i>
                <div>
                    <span class="faculty-name">${post.faculty_name}</span>
                    <span class="department">${post.department}</span>
                </div>
            </div>
            <div class="post-meta">
                <span class="post-date">${formatDate(post.created_at)}</span>
            </div>
        </div>
        <div class="post-content">
            <p class="description">${post.description}</p>
            <div class="post-details">
                <span><i class="fas fa-briefcase"></i> ${post.jobType}</span>
                <span><i class="fas fa-map-marker-alt"></i> ${post.location}</span>
                <span><i class="fas fa-clock"></i> ${post.estimatedHours} hours</span>
                <span><i class="fas fa-calendar"></i> ${formatDate(post.meetingTime)}</span>
            </div>
            <div class="quest-rewards">
                ${post.rewards.cash ? `<span class="reward cash">₱${post.rewards.cash}</span>` : ''}
                ${post.rewards.snack ? `<span class="reward food">Snack</span>` : ''}
            </div>
            <div class="quest-actions">
                <button onclick="acceptQuest(${post.id})" class="btn-primary">Accept Quest</button>
            </div>
        </div>
    `;
    
    return postDiv;
}

// Quest action functions
async function acceptQuest(questId) {
    try {
        const response = await fetch('includes/accept_quest.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ quest_id: questId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('Quest accepted successfully!', 'success');
            loadFacultyPosts(); // Refresh the posts
            updateProfileStats(); // Update the stats
        } else {
            throw new Error(data.message || 'Failed to accept quest');
        }
    } catch (error) {
        console.error('Error accepting quest:', error);
        showAlert(error.message, 'error');
    }
}

// View quest details
async function viewQuestDetails(questId) {
    try {
        const response = await fetch(`includes/get_quest_details.php?id=${questId}`);
        const data = await response.json();
        
        if (data.success) {
            const quest = data.quest;
            const modal = document.getElementById('quest-details-modal');
            const content = document.getElementById('quest-details-content');
            
            content.innerHTML = `
                <div class="quest-details">
                    <div class="faculty-details">
                        <h3>Faculty Information</h3>
                        <p><strong>Name:</strong> ${quest.faculty_name}</p>
                        <p><strong>Department:</strong> ${quest.office_name}</p>
                        <p><strong>Room:</strong> ${quest.room_number || 'Not specified'}</p>
                    </div>
                    <div class="quest-info">
                        <h3>Quest Details</h3>
                        <p><strong>Type:</strong> ${quest.job_type}</p>
                        <p><strong>Location:</strong> ${quest.location}</p>
                        <p><strong>Meeting Time:</strong> ${formatDate(quest.meeting_time)}</p>
                        <p><strong>Estimated Hours:</strong> ${quest.estimated_hours}</p>
                        <p><strong>Description:</strong> ${quest.description}</p>
                    </div>
                    <div class="quest-rewards">
                        <h3>Rewards</h3>
                        ${quest.cash_reward ? `<p><strong>Cash:</strong> ₱${quest.cash_reward}</p>` : ''}
                        ${quest.snack_reward ? '<p><strong>Bonus:</strong> Free meal</p>' : ''}
                    </div>
                </div>
                <div class="modal-buttons">
                    ${quest.has_applied 
                        ? `<button class="btn-applied" disabled>Application Submitted</button>`
                        : `<button onclick="acceptQuest(${quest.id})" class="btn-primary">Accept Quest</button>`
                    }
                    <button onclick="hideModal('quest-details-modal')" class="btn-secondary">Close</button>
                </div>
            `;
            
            showModal('quest-details-modal');
        } else {
            throw new Error(data.message || 'Failed to load quest details');
        }
    } catch (error) {
        console.error('Error loading quest details:', error);
        showNotification(error.message, 'error');
    }
}

// Update profile stats
async function updateProfileStats() {
    try {
        // Add cache-busting parameter
        const response = await fetch('includes/get_student_stats.php?t=' + new Date().getTime());
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        
        if (data.success) {
            // Update stats safely
            const stats = data.stats;
            console.log('Received stats:', stats); // Debug log
            
            // Update profile stats with count information
            updateStatsDisplay('pending-quests', `${stats.pending_quests} Pending Quests`);
            updateStatsDisplay('completed-quests', `${stats.completed_quests} Completed Quests`);
            updateStatsDisplay('total-earnings', `₱${stats.total_earnings} Total Earnings`);
            
            // Also update workspace stats if they exist
            updateStatsDisplay('workspace-pending-quests', `${stats.pending_quests} Pending Quests`);
            updateStatsDisplay('workspace-completed-quests', `${stats.completed_quests} Completed Quests`);
            updateStatsDisplay('workspace-total-earnings', `₱${stats.total_earnings} Total Earnings`);
            
            console.log('Stats updated successfully'); // Debug log
        } else {
            throw new Error(data.message || 'Failed to update stats');
        }
    } catch (error) {
        console.error('Error updating profile stats:', error);
        showNotification('Failed to update stats', 'error');
    }
}

// Helper function to safely update stat displays
function updateStatsDisplay(elementId, value) {
    const element = document.getElementById(elementId);
    if (element) {
        console.log(`Updating ${elementId} to:`, value); // Debug log
        element.textContent = value;
    } else {
        console.warn(`Element ${elementId} not found`); // Debug log
    }
}

// Get student stats
async function getStudentStats() {
    try {
        const response = await fetch('includes/get_student_stats.php');
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Failed to fetch stats');
        }
        
        return data.stats;
    } catch (error) {
        console.error('Error fetching student stats:', error);
        showNotification('Failed to fetch stats', 'error');
        throw error;
    }
}

// Update workspace stats
function updateWorkspaceStats(stats) {
    const statsElements = {
        'workspace-pending-quests': `${stats.pending_quests || '0'} Pending Quests`,
        'workspace-completed-quests': `${stats.completed_quests || '0'} Completed Quests`,
        'workspace-total-earnings': `₱${stats.total_earnings || '0'} Total Earnings`,
        'pending-quests': `${stats.pending_quests || '0'} Pending Quests`,
        'completed-quests': `${stats.completed_quests || '0'} Completed Quests`,
        'total-earnings': `₱${stats.total_earnings || '0'} Total Earnings`
    };

    Object.entries(statsElements).forEach(([id, value]) => {
        updateStatsDisplay(id, value);
    });
}

// Load workspace data
async function loadWorkspace() {
    try {
        // First try to get stats
        const stats = await getStudentStats();
        if (stats) {
            updateWorkspaceStats(stats);
        }
        
        // Then show the workspace tab
        await showWorkspaceTab('pending');
    } catch (error) {
        console.error('Error loading workspace:', error);
        showNotification('Failed to load workspace data', 'error');
    }
}

// Show workspace tab
async function showWorkspaceTab(status) {
    // Update active tab
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.getAttribute('data-tab') === status) {
            btn.classList.add('active');
        }
    });

    // Show selected tab content
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    document.getElementById(`${status}-tab`).classList.add('active');

    try {
        const response = await fetch(`includes/get_student_quests.php?status=${status}`);
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message || 'Failed to load quests');
        }

        const questList = document.getElementById(`${status}-quests`);
        if (!questList) {
            console.error(`Container for ${status} quests not found`);
            return;
        }

        questList.innerHTML = '';

        if (!data.quests || data.quests.length === 0) {
            questList.innerHTML = `<div class="no-quests">No ${status} quests found</div>`;
            return;
        }

        data.quests.forEach(quest => {
            const questCard = document.createElement('div');
            questCard.className = 'quest-card';
            questCard.innerHTML = `
                <div class="quest-header">
                    <h3>${quest.description}</h3>
                    <span class="status-badge ${quest.status}">${quest.status}</span>
                </div>
                <div class="faculty-info">
                    <i class="fas fa-user-tie"></i>
                    <div class="faculty-details">
                        <span class="faculty-name">${quest.faculty_name}</span>
                        <span class="faculty-email">${quest.faculty_email}</span>
                        <div class="department">${quest.department}</div>
                        <div class="room">Room: ${quest.room_number || 'Not specified'}</div>
                    </div>
                </div>
                <div class="quest-details">
                    <div class="detail-row">
                        <i class="fas fa-briefcase"></i>
                        <span>${quest.jobType}</span>
                    </div>
                    <div class="detail-row">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>${quest.location}</span>
                    </div>
                    <div class="detail-row">
                        <i class="fas fa-clock"></i>
                        <span>${formatDate(quest.meetingTime)}</span>
                    </div>
                    <div class="detail-row">
                        <i class="fas fa-hourglass-half"></i>
                        <span>${quest.estimatedHours} hours</span>
                    </div>
                </div>
                <div class="quest-rewards">
                    ${quest.rewards.cash ? `<span class="reward cash">₱${quest.rewards.cash}</span>` : ''}
                    ${quest.rewards.snack ? '<span class="reward food">Snack</span>' : ''}
                </div>
                <div class="quest-actions">
                    ${getQuestActions(quest)}
                </div>
            `;
            questList.appendChild(questCard);
        });
    } catch (error) {
        console.error('Error loading quests:', error);
        const questList = document.getElementById(`${status}-quests`);
        if (questList) {
            questList.innerHTML = '<div class="error-message">Failed to load quests. Please try again later.</div>';
        }
        showNotification(error.message, 'error');
    }
}

// Helper function to get quest action buttons
function getQuestActions(quest) {
    return ''; // Return empty string to show no buttons in workspace
}

// Submit work
async function submitWork(questId) {
    try {
        const response = await fetch('includes/submit_work.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ quest_id: questId })
        });

        const data = await response.json();
        if (data.success) {
            showAlert('Work submitted successfully', 'success');
            await loadWorkspace(); // Refresh the entire workspace
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error submitting work:', error);
        showAlert('Failed to submit work');
    }
}

// Initialize workspace when page loads
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('workspace')) {
        loadWorkspace();
    }
});

// Modal functions
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        setTimeout(() => modal.classList.add('show'), 10);
    }
}

function hideModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => modal.style.display = 'none', 300);
    }
}

// Notification function
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    document.getElementById('notification-container').appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Search functionality
function filterPosts(searchTerm) {
    const posts = document.querySelectorAll('.post');
    posts.forEach(post => {
        const text = post.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            post.style.display = 'block';
        } else {
            post.style.display = 'none';
        }
    });
}

// Helper function to format dates
function formatDate(dateString) {
    if (!dateString) return 'Not specified';
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Logout functions
function showLogoutConfirmation() {
    const modal = document.getElementById('logout-confirmation');
    if (modal) {
        showModal('logout-confirmation');
    }
}

function closeLogoutModal() {
    hideModal('logout-confirmation');
}

async function logout() {
    try {
        const response = await fetch('includes/process_logout.php');
        const data = await response.json();
        
        if (data.success) {
            window.location.href = 'login.php';
        } else {
            throw new Error(data.message || 'Logout failed');
        }
    } catch (error) {
        console.error('Error during logout:', error);
        showNotification('Error during logout. Please try again.', 'error');
    }
}

// Add event listeners for tab switching
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.tab-btn').forEach(button => {
        button.addEventListener('click', () => {
            const status = button.getAttribute('data-tab');
            showWorkspaceTab(status);
        });
    });
});

// Post tracking functions
async function toggleBookmark(postId) {
    try {
        const response = await fetch('includes/toggle_bookmark.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ post_id: postId })
        });
        
        const data = await response.json();
        if (data.success) {
            const button = document.querySelector(`[data-post-id="${postId}"] .fa-bookmark, [data-post-id="${postId}"] .fa-bookmark-o`);
            if (button) {
                button.classList.toggle('fa-bookmark');
                button.classList.toggle('fa-bookmark-o');
            }
            showNotification(data.message, 'success');
        } else {
            throw new Error(data.message || 'Failed to toggle bookmark');
        }
    } catch (error) {
        console.error('Error toggling bookmark:', error);
        showNotification(error.message, 'error');
    }
}

async function sharePost(postId) {
    try {
        // Get the post URL
        const postUrl = `${window.location.origin}${window.location.pathname}?post=${postId}`;
        
        // Try to use the Share API if available
        if (navigator.share) {
            await navigator.share({
                title: 'Quest Post',
                text: 'Check out this quest!',
                url: postUrl
            });
            showNotification('Post shared successfully!', 'success');
        } else {
            // Fallback to copying to clipboard
            await navigator.clipboard.writeText(postUrl);
            showNotification('Link copied to clipboard!', 'success');
        }
    } catch (error) {
        console.error('Error sharing post:', error);
        showNotification('Failed to share post', 'error');
    }
}

async function reportPost(postId) {
    try {
        // Create and show the report modal
        const modal = document.createElement('div');
        modal.id = 'report-modal';
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content">
                <h2>Report Post</h2>
                <form id="report-form">
                    <select id="report-reason" required>
                        <option value="">Select a reason</option>
                        <option value="inappropriate">Inappropriate content</option>
                        <option value="spam">Spam</option>
                        <option value="misleading">Misleading information</option>
                        <option value="other">Other</option>
                    </select>
                    <textarea id="report-details" placeholder="Additional details (optional)"></textarea>
                    <div class="modal-buttons">
                        <button type="submit" class="btn-primary">Submit Report</button>
                        <button type="button" class="btn-secondary" onclick="hideModal('report-modal')">Cancel</button>
                    </div>
                </form>
            </div>
        `;
        
        document.body.appendChild(modal);
        showModal('report-modal');
        
        // Handle form submission
        document.getElementById('report-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const reason = document.getElementById('report-reason').value;
            const details = document.getElementById('report-details').value;
            
            const response = await fetch('includes/report_post.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    post_id: postId,
                    reason: reason,
                    details: details
                })
            });
            
            const data = await response.json();
            if (data.success) {
                showNotification('Post reported successfully', 'success');
                hideModal('report-modal');
                setTimeout(() => {
                    modal.remove();
                }, 300);
            } else {
                throw new Error(data.message || 'Failed to report post');
            }
        });
    } catch (error) {
        console.error('Error reporting post:', error);
        showNotification(error.message, 'error');
    }
}