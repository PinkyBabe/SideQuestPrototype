// Global variables
let currentSection = 'profile';
let posts = [];

// Get faculty statistics
async function getFacultyStats() {
    try {
        const response = await fetch('includes/get_faculty_stats.php');
        if (!response.ok) throw new Error('Failed to fetch faculty stats');
        
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Failed to load faculty stats');
        
        return data.stats;
    } catch (error) {
        console.error('Error fetching faculty stats:', error);
        throw error;
    }
}

// Update workspace stats
function updateWorkspaceStats(stats) {
    // Only update stats if they exist in the DOM
    const statsElements = {
        'workspace-active-quests': stats.active_quests || '0',
        'workspace-pending-quests': stats.pending_quests || '0',
        'workspace-completed-quests': stats.completed_quests || '0',
        'workspace-total-quests': stats.total_quests || '0',
        'posted-quests': stats.total_quests || '0',
        'active-quests': stats.active_quests || '0',
        'completed-quests': stats.completed_quests || '0'
    };

    // Safely update each stat element if it exists
    Object.entries(statsElements).forEach(([id, value]) => {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = value;
        }
    });
}

// Workspace functionality
async function loadWorkspace() {
    try {
        // Get faculty stats
        const stats = await getFacultyStats();
        if (stats) {
            updateWorkspaceStats(stats);
        }

        // Show active tab by default
        await showWorkspaceTab('active');

        // Add event listeners to tab buttons if not already added
        document.querySelectorAll('.workspace-tabs .tab-btn').forEach(button => {
            button.addEventListener('click', () => {
                const status = button.getAttribute('data-tab');
                showWorkspaceTab(status);
            });
        });
    } catch (error) {
        console.error('Error loading workspace:', error);
        showAlert('Failed to load workspace data', 'error');
    }
}

// Show workspace tab
async function showWorkspaceTab(status) {
    try {
        console.log('Starting showWorkspaceTab with status:', status); // Debug log

        // Update tab UI
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
            tab.style.display = 'none'; // Explicitly hide tabs
        });
        const activeTab = document.getElementById(`${status}-tab`);
        if (activeTab) {
            activeTab.classList.add('active');
            activeTab.style.display = 'block'; // Explicitly show active tab
            console.log('Activated tab:', status); // Debug log
        } else {
            console.error(`Tab element not found: ${status}-tab`);
        }
        
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.getAttribute('data-tab') === status) {
                btn.classList.add('active');
            }
        });

        // Fetch quests
        console.log('Fetching quests for status:', status); // Debug log
        const response = await fetch(`includes/get_faculty_quests.php?status=${status}`);
        if (!response.ok) throw new Error('Failed to fetch quests');
        
        const data = await response.json();
        console.log('Received quest data:', data); // Debug log

        if (!data.success) throw new Error(data.message || 'Failed to load quests');
        
        // Get quest list container
        const questList = document.getElementById(`${status}-quests-list`);
        if (!questList) {
            console.error(`Container for ${status} quests not found`);
            return;
        }

        console.log('Found quest list container:', questList); // Debug log

        // Clear container
        questList.innerHTML = '';

        // Display quests
        if (!data.quests || data.quests.length === 0) {
            console.log('No quests found for status:', status); // Debug log
            questList.innerHTML = `<div class="no-quests">No ${status} quests found</div>`;
            return;
        }

        console.log(`Creating ${data.quests.length} quest cards`); // Debug log

        // Create quest cards
        data.quests.forEach((quest, index) => {
            console.log(`Creating card ${index + 1} for quest:`, quest); // Debug log
            const questCard = createQuestCard(quest);
            questList.appendChild(questCard);
            console.log(`Appended card ${index + 1}`); // Debug log
        });

        console.log('Finished loading quest cards'); // Debug log

    } catch (error) {
        console.error('Error loading quests:', error);
        showAlert(error.message, 'error');
    }
}

// Create quest card
function createQuestCard(quest) {
    const card = document.createElement('div');
    card.className = 'quest-card';
    
    // Determine status class and button text
    let statusClass = '';
    let buttonText = '';
    let buttonAction = '';
    
    switch (quest.status ? quest.status.toLowerCase() : 'active') {
        case 'in_progress':
        case 'accepted':
            statusClass = 'in-progress';
            buttonText = 'Quest Done';
            buttonAction = `completeQuest(${quest.id})`;
            break;
        case 'completed':
            statusClass = 'completed';
            break;
        case 'active':
        default:
            statusClass = 'active';
    }
    
    card.innerHTML = `
        <div class="quest-header">
            <h3>${quest.description || 'No description'}</h3>
            <span class="status-badge ${statusClass}">${quest.status || 'Active'}</span>
        </div>
        <div class="quest-details">
            <p><strong>Job Type:</strong> ${quest.job_type || 'Not specified'}</p>
            <p><strong>Location:</strong> ${quest.location || 'Not specified'}</p>
            <p><strong>Meeting Time:</strong> ${formatDateTime(quest.meeting_time) || 'Not specified'}</p>
            <p><strong>Estimated Hours:</strong> ${quest.estimated_hours || 'Not specified'}</p>
            <p><strong>Rewards:</strong>
                ${quest.cash_reward ? `₱${quest.cash_reward}` : ''}
                ${quest.snack_reward ? (quest.cash_reward ? ' + ' : '') + 'Snacks' : ''}
                ${(!quest.cash_reward && !quest.snack_reward) ? 'None' : ''}
            </p>
            ${quest.student_name ? `<p><strong>Assigned to:</strong> ${quest.student_name}</p>` : ''}
        </div>
        ${(quest.status && (quest.status.toLowerCase() === 'in_progress' || quest.status.toLowerCase() === 'accepted')) ? `
            <div class="quest-actions" style="margin-top: 15px; text-align: right;">
                <button 
                    onclick="${buttonAction}" 
                    style="
                        background-color: #4CAF50;
                        color: white;
                        padding: 10px 20px;
                        border: none;
                        border-radius: 5px;
                        cursor: pointer;
                        font-size: 14px;
                        display: inline-flex;
                        align-items: center;
                        gap: 5px;
                    "
                >
                    <i class="fas fa-check"></i> ${buttonText}
                </button>
            </div>
        ` : ''}
    `;
    
    return card;
}

// Get quest actions
function getQuestActions(quest) {
    if (quest.status === 'pending') {
        return `
            <div class="quest-actions">
                <button onclick="completeQuest(${quest.id})" class="complete-btn">
                    <i class="fas fa-check"></i> Complete Quest
                </button>
            </div>`;
    }
    return '';
}

// Format status
function formatStatus(status) {
    const statusMap = {
        'active': 'Active',
        'pending': 'In Progress',
        'completed': 'Completed',
        'cancelled': 'Cancelled'
    };
    return statusMap[status.toLowerCase()] || status;
}

// Navigation
function navigateTo(section) {
    console.log('Navigating to section:', section); // Debug log
    
    // Hide all sections
    document.querySelectorAll('.container').forEach(el => {
        el.style.display = 'none';
    });
    
    // Show selected section
    const selectedSection = document.getElementById(section);
    if (selectedSection) {
        selectedSection.style.display = 'block';
        currentSection = section;
        
        // Update active nav link
        document.querySelectorAll('nav a').forEach(link => {
            if (link.getAttribute('onclick').includes(section)) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });

        // Load section specific content
        if (section === 'workspace') {
            console.log('Loading workspace section...'); // Debug log
            loadWorkspace();
        } else if (section === 'profile') {
            loadFacultyPosts();
        }
    } else {
        console.error(`Section "${section}" not found`);
    }
}

// Post area functionality
function expandPostArea() {
    document.getElementById('post_textarea').style.display = 'none';
    document.getElementById('expanded_post').classList.remove('hidden');
}

function collapsePostArea() {
    document.getElementById('post_textarea').style.display = 'block';
    document.getElementById('expanded_post').classList.add('hidden');
    resetForm();
}

function resetForm() {
    document.getElementById('expanded_textarea').value = '';
    document.getElementById('job_description').selectedIndex = 0;
    document.getElementById('specify_job').classList.add('hidden');
    document.getElementById('specify_job').value = '';
    document.getElementById('location').value = '';
    document.getElementById('meeting_time').value = '';
    document.getElementById('estimated_hours').value = '';
    document.getElementById('reward_type').selectedIndex = 0;
    document.getElementById('cash_amount').value = '';
    document.getElementById('meal_type').selectedIndex = 0;
    document.getElementById('cash_fields').classList.add('hidden');
    document.getElementById('meal_fields').classList.add('hidden');
}

function toggleSpecifyField() {
    const jobSelect = document.getElementById('job_description');
    const specifyField = document.getElementById('specify_job');
    
    if (jobSelect.value === 'Others') {
        specifyField.classList.remove('hidden');
    } else {
        specifyField.classList.add('hidden');
        specifyField.value = '';
    }
}

function toggleCashField() {
    const cashField = document.getElementById('cash_amount');
    const cashCheckbox = document.getElementById('reward_cash');
    
    if (cashCheckbox.checked) {
        cashField.classList.remove('hidden');
    } else {
        cashField.classList.add('hidden');
        cashField.value = '';
    }
}

// Post submission
async function submitPost() {
    try {
        // Get form data
        const formData = {
            description: document.getElementById('expanded_textarea').value.trim(),
            jobType: document.getElementById('job_description').value,
            location: document.getElementById('location').value.trim(),
            meetingTime: document.getElementById('meeting_time').value,
            estimatedHours: parseInt(document.getElementById('estimated_hours').value),
            rewards: {
                type: document.getElementById('reward_type').value,
                cash: document.getElementById('cash_amount').value ? parseInt(document.getElementById('cash_amount').value) : 0,
                meal: document.getElementById('meal_type').value || null
            },
            snack_reward: 0,
            status: 'active' // Set initial status to active
        };

        console.log('Submitting quest with data:', formData); // Debug log

        // Add snack_reward field
        formData.snack_reward = formData.rewards.type === 'food' || formData.rewards.type === 'both' ? 1 : 0;

        // Validate form data
        if (!formData.description) throw new Error('Please enter a quest description');
        if (!formData.jobType) throw new Error('Please select a job type');
        if (!formData.location) throw new Error('Please enter a location');
        if (!formData.meetingTime) throw new Error('Please select a meeting time');
        if (!formData.estimatedHours || formData.estimatedHours <= 0) throw new Error('Please enter valid estimated hours');
        if (!formData.rewards.type) throw new Error('Please select a reward type');

        // Send data to server
        console.log('Sending quest data to server...'); // Debug log
        const response = await fetch('includes/create_quest.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });

        const data = await response.json();
        console.log('Server response:', data); // Debug log
        
        if (data.success) {
            showAlert('Quest created successfully!', 'success');
            collapsePostArea();
            resetForm();
            
            // Reload workspace to show new quest
            if (currentSection === 'workspace') {
                console.log('Reloading workspace after quest creation...'); // Debug log
                await loadWorkspace();
            } else {
                // Navigate to workspace to show the new quest
                console.log('Navigating to workspace after quest creation...'); // Debug log
                navigateTo('workspace');
            }
        } else {
            throw new Error(data.message || 'Failed to create quest');
        }
    } catch (error) {
        console.error('Error creating quest:', error);
        showAlert(error.message, 'error');
    }
}

// Load and display posts
async function loadPosts() {
    try {
        const response = await fetch('includes/get_faculty_posts.php');
        if (!response.ok) {
            throw new Error('Failed to load posts');
        }
        
        const data = await response.json();
        
        if (data.success) {
            posts = data.posts;
            displayPosts();
        } else {
            throw new Error(data.message || 'Failed to load posts');
        }
    } catch (error) {
        console.error('Error loading posts:', error);
        showAlert('Failed to load posts: ' + error.message);
    }
}

function displayPosts(postsToDisplay = posts) {
    const container = document.getElementById('posts_container');
    if (!container) return;
    
    container.innerHTML = '';
    
    if (!postsToDisplay || postsToDisplay.length === 0) {
        container.innerHTML = '<p class="no-posts">No quests found.</p>';
        return;
    }
    
    postsToDisplay.forEach(post => {
        const postElement = createPostElement(post);
        container.appendChild(postElement);
    });
}

function createPostElement(post) {
    const div = document.createElement('div');
    div.className = 'post';
    
    div.innerHTML = `
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
                <span class="job-type">${post.jobType}</span>
                <span class="location"><i class="fas fa-map-marker-alt"></i> ${post.location}</span>
                <span class="meeting-time"><i class="fas fa-clock"></i> Meet: ${formatDate(post.meetingTime)}</span>
                <span class="hours"><i class="fas fa-hourglass-half"></i> ${post.estimatedHours} hours of work</span>
            </div>
            <div class="quest-rewards">
                ${post.rewards.cash ? `<span class="reward cash">💰 ₱${post.rewards.cash}</span>` : ''}
                ${post.rewards.meal ? `<span class="reward food">🍴 ${formatMealType(post.rewards.meal)}</span>` : ''}
            </div>
            <div class="quest-stats">
                <span class="status ${post.status.toLowerCase()}">${post.status}</span>
            </div>
        </div>
    `;
    
    return div;
}

// Complete quest function
async function completeQuest(questId) {
    if (!confirm('Are you sure you want to mark this quest as completed?')) {
        return;
    }

    fetch('includes/complete_quest.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ quest_id: questId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Quest marked as completed!', 'success');
            loadWorkspace(); // Refresh the workspace
        } else {
            throw new Error(data.message || 'Failed to complete quest');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert(error.message, 'error');
    });
}

// Update quest status
async function updateQuestStatus(questId, newStatus) {
    try {
        const response = await fetch('includes/update_quest_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                quest_id: questId,
                status: newStatus
            })
        });

        const data = await response.json();
        if (data.success) {
            showAlert('Quest status updated successfully', 'success');
            // Refresh the workspace to show updated quest statuses
            loadWorkspace();
        } else {
            throw new Error(data.message || 'Failed to update quest status');
        }
    } catch (error) {
        console.error('Error updating quest status:', error);
        showAlert('Failed to update quest status: ' + error.message);
    }
}

// Initialize workspace when page loads
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM Content Loaded'); // Debug log
    
    // Set up navigation event listeners
    document.querySelectorAll('nav a').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const section = link.getAttribute('onclick').match(/'(.*?)'/)[1];
            navigateTo(section);
        });
    });

    // Show profile section by default
    navigateTo('profile');

    // Set up workspace tab event listeners
    document.querySelectorAll('.workspace-tabs .tab-btn').forEach(button => {
        button.addEventListener('click', () => {
            const status = button.getAttribute('data-tab');
            console.log('Tab clicked:', status); // Debug log
            showWorkspaceTab(status);
        });
    });
});

// Utility functions
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function getNextStatusText(currentStatus) {
    switch (currentStatus.toLowerCase()) {
        case 'pending':
            return 'Accept';
        case 'in_progress':
            return 'Complete';
        case 'completed':
            return 'Completed';
        default:
            return 'Update';
    }
}

function showAlert(message, type = 'error') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.classList.add('fade-out');
        setTimeout(() => alertDiv.remove(), 500);
    }, 3000);
}

// Search functionality
function setupSearch() {
    const searchbar = document.getElementById('searchbar');
    let timeout = null;
    
    searchbar.addEventListener('input', (e) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            const searchTerm = e.target.value.toLowerCase();
            filterPosts(searchTerm);
        }, 300);
    });
}

function filterPosts(searchTerm) {
    const filteredPosts = posts.filter(post => 
        post.jobType.toLowerCase().includes(searchTerm) ||
        post.description.toLowerCase().includes(searchTerm)
    );
    displayPosts(filteredPosts);
}

// Logout functionality
function showLogoutConfirmation() {
    document.getElementById('logout-confirmation').style.display = 'block';
}

function hideLogoutConfirmation() {
    document.getElementById('logout-confirmation').style.display = 'none';
}

async function logout() {
    try {
        const response = await fetch('includes/logout.php');
        const data = await response.json();
        
        if (data.success) {
            window.location.href = 'login.php';
        } else {
            throw new Error(data.message || 'Logout failed');
        }
    } catch (error) {
        console.error('Logout error:', error);
        window.location.href = 'login.php';
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Show profile section by default
    navigateTo('profile');
    
    // Load initial posts
    loadPosts();
    
    // Setup search functionality
    setupSearch();
    
    // Prevent back button after logout
    window.history.pushState(null, '', window.location.href);
    window.onpopstate = function() {
        window.history.pushState(null, '', window.location.href);
    };
});

async function updateTaskStatus(taskId, currentStatus) {
    try {
        const nextStatus = getNextStatus(currentStatus);
        const response = await fetch('includes/update_quest_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                quest_id: taskId,
                status: nextStatus
            })
        });

        const data = await response.json();
        
        if (data.success) {
            showAlert('Status updated successfully', 'success');
            loadWorkspace(); // Refresh workspace
        } else {
            throw new Error(data.message || 'Failed to update status');
        }
    } catch (error) {
        console.error('Error updating status:', error);
        showAlert(error.message);
    }
}

function getNextStatus(currentStatus) {
    switch (currentStatus.toLowerCase()) {
        case 'accepted':
            return 'in_progress';
        case 'in_progress':
            return 'completed';
        default:
            return currentStatus;
    }
}

function toggleRewardFields() {
    const rewardType = document.getElementById('reward_type').value;
    const cashFields = document.getElementById('cash_fields');
    const mealFields = document.getElementById('meal_fields');
    
    cashFields.classList.toggle('hidden', !(rewardType === 'cash' || rewardType === 'both'));
    mealFields.classList.toggle('hidden', !(rewardType === 'food' || rewardType === 'both'));
    
    // Reset values when hiding fields
    if (!(rewardType === 'cash' || rewardType === 'both')) {
        document.getElementById('cash_amount').value = '';
    }
    if (!(rewardType === 'food' || rewardType === 'both')) {
        document.getElementById('meal_type').selectedIndex = 0;
    }
}

// Add helper function to format meal types
function formatMealType(mealType) {
    const mealTypes = {
        'breakfast': 'Breakfast',
        'am_snack': 'AM Snack',
        'lunch': 'Lunch',
        'pm_snack': 'PM Snack',
        'dinner': 'Dinner'
    };
    return mealTypes[mealType] || mealType;
}

async function loadSubmission(submissionId) {
    try {
        const response = await fetch(`includes/get_submission.php?id=${submissionId}`);
        const data = await response.json();
        
        if (data.success) {
            showSubmissionModal(data.submission);
        } else {
            throw new Error(data.message || 'Failed to load submission');
        }
    } catch (error) {
        console.error('Error loading submission:', error);
        showNotification(error.message, 'error');
    }
}

function showSubmissionModal(submission) {
    const modal = document.getElementById('submissionModal');
    if (!modal) {
        // Create modal if it doesn't exist
        const modalHtml = `
            <div id="submissionModal" class="modal">
                <div class="modal-content">
                    <h2>Review Submission</h2>
                    <div class="submission-details">
                        <div class="submission-text">
                            <h3>Student's Notes</h3>
                            <p id="submission-text-content"></p>
                        </div>
                        <div class="submission-file" id="submission-file-section">
                            <h3>Attached File</h3>
                            <p id="submission-file-info"></p>
                            <a id="submission-file-link" target="_blank" class="btn-secondary">Download File</a>
                        </div>
                        <div class="feedback-form">
                            <h3>Provide Feedback</h3>
                            <textarea id="feedback-text" placeholder="Enter your feedback..."></textarea>
                            <div class="feedback-actions">
                                <button class="btn-success" onclick="submitFeedback('approved')">Approve</button>
                                <button class="btn-danger" onclick="submitFeedback('rejected')">Reject</button>
                                <button class="btn-secondary" onclick="hideModal('submissionModal')">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHtml);
    }
    
    // Update modal content
    document.getElementById('submission-text-content').textContent = submission.submission_text;
    
    const fileSection = document.getElementById('submission-file-section');
    const fileInfo = document.getElementById('submission-file-info');
    const fileLink = document.getElementById('submission-file-link');
    
    if (submission.file_path) {
        fileSection.style.display = 'block';
        fileInfo.textContent = submission.file_name;
        fileLink.href = submission.file_path;
    } else {
        fileSection.style.display = 'none';
    }
    
    // Store submission ID for feedback submission
    document.getElementById('submissionModal').dataset.submissionId = submission.id;
    
    showModal('submissionModal');
}

async function submitFeedback(status) {
    try {
        const submissionId = document.getElementById('submissionModal').dataset.submissionId;
        const feedback = document.getElementById('feedback-text').value;
        
        if (!feedback.trim()) {
            throw new Error('Please provide feedback before submitting');
        }
        
        const response = await fetch('includes/submit_feedback.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                submission_id: submissionId,
                feedback: feedback,
                status: status
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Feedback submitted successfully', 'success');
            hideModal('submissionModal');
            loadWorkspace(); // Refresh workspace
        } else {
            throw new Error(data.message || 'Failed to submit feedback');
        }
    } catch (error) {
        console.error('Error submitting feedback:', error);
        showNotification(error.message, 'error');
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

// Load faculty posts
async function loadFacultyPosts(status = 'all') {
    try {
        const response = await fetch('includes/get_faculty_posts.php');
        if (!response.ok) throw new Error('Failed to fetch posts');
        
        const data = await response.json();
        if (!data.success) throw new Error(data.message);
        
        displayFacultyPosts(data.posts, status);
    } catch (error) {
        console.error('Error:', error);
        showNotification(error.message, 'error');
    }
}

// Display faculty posts
function displayFacultyPosts(posts, filterStatus = 'all') {
    const container = document.getElementById('faculty-posts-container');
    if (!container) {
        console.error('Faculty posts container not found');
        return;
    }

    container.innerHTML = '';

    const filteredPosts = filterStatus === 'all' 
        ? posts 
        : posts.filter(post => post.status === filterStatus);

    if (!filteredPosts.length) {
        container.innerHTML = '<div class="no-posts">No posts found</div>';
        return;
    }

    filteredPosts.forEach(post => {
        const postElement = createPostElement(post);
        container.appendChild(postElement);
    });
}

// Create post element
function createPostElement(post) {
    const postDiv = document.createElement('div');
    postDiv.className = 'post-card';
    
    postDiv.innerHTML = `
        <div class="post-header">
            <div class="post-status ${post.status}">${formatStatus(post.status)}</div>
            <div class="post-date">${formatDate(post.created_at)}</div>
        </div>
        <div class="post-content">
            <div class="post-description">${post.description}</div>
            <div class="post-meta">
                <div class="meta-item">
                    <i class="fas fa-briefcase"></i>
                    <span>${post.jobType}</span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>${post.location}</span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-clock"></i>
                    <span>${post.estimatedHours} hours</span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-users"></i>
                    <span>${post.application_count || 0} applicants</span>
                </div>
            </div>
            <div class="post-rewards">
                ${post.rewards.cash ? `<span class="reward-badge cash">₱${post.rewards.cash}</span>` : ''}
                ${post.rewards.snack ? '<span class="reward-badge snack">Snack</span>' : ''}
            </div>
        </div>
    `;
    
    return postDiv;
}

// Filter posts
function filterPosts() {
    const status = document.getElementById('status-filter').value;
    const filteredPosts = posts.filter(post => 
        status === 'all' || post.status === status
    );
    displayFacultyPosts(filteredPosts);
}

// Format status text
function formatStatus(status) {
    const statusMap = {
        'active': 'Active',
        'in_progress': 'In Progress',
        'completed': 'Completed'
    };
    return statusMap[status] || status;
}

// Format date
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

// Load posts when profile is shown
document.addEventListener('DOMContentLoaded', () => {
    if (currentSection === 'profile') {
        loadFacultyPosts();
    }
});

// Get quest action buttons based on status
function getQuestActionButtons(quest) {
    const status = quest.status.toLowerCase();
    let buttons = '';

    if (status === 'active') {
        buttons = `
            <button class="action-btn view-btn" onclick="event.stopPropagation(); showQuestDetails(${quest.id})">
                <i class="fas fa-eye"></i> View Details
            </button>
            <button class="action-btn cancel-btn" onclick="event.stopPropagation(); cancelQuest(${quest.id})">
                <i class="fas fa-times"></i> Cancel Quest
            </button>
        `;
    } else if (status === 'pending') {
        buttons = `
            <button class="action-btn complete-btn" onclick="event.stopPropagation(); completeQuest(${quest.id})">
                <i class="fas fa-check"></i> Complete Quest
            </button>
            <button class="action-btn cancel-btn" onclick="event.stopPropagation(); cancelQuest(${quest.id})">
                <i class="fas fa-times"></i> Cancel Quest
            </button>
        `;
    } else if (status === 'completed') {
        buttons = `
            <button class="action-btn view-btn" onclick="event.stopPropagation(); showQuestDetails(${quest.id})">
                <i class="fas fa-eye"></i> View Details
            </button>
        `;
    }

    return buttons;
}

// Quest action functions
async function approveQuest(questId) {
    await updateQuestStatus(questId, 'active');
}

async function rejectQuest(questId) {
    await updateQuestStatus(questId, 'cancelled');
}

async function cancelQuest(questId) {
    try {
        const response = await fetch('includes/update_quest_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                quest_id: questId,
                status: 'cancelled'
            })
        });

        const data = await response.json();
        if (data.success) {
            showAlert('Quest cancelled successfully', 'success');
            await loadWorkspace();
        } else {
            throw new Error(data.message || 'Failed to cancel quest');
        }
    } catch (error) {
        console.error('Error cancelling quest:', error);
        showAlert(error.message, 'error');
    }
}

async function reactivateQuest(questId) {
    try {
        const response = await fetch('includes/update_quest_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                quest_id: questId,
                status: 'active'
            })
        });

        const data = await response.json();
        if (data.success) {
            showAlert('Quest reactivated successfully', 'success');
            await loadWorkspace();
        } else {
            throw new Error(data.message || 'Failed to reactivate quest');
        }
    } catch (error) {
        console.error('Error reactivating quest:', error);
        showAlert(error.message, 'error');
    }
}

function formatDateTime(dateTime) {
    if (!dateTime) return 'Not specified';
    return new Date(dateTime).toLocaleString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Accept quest function
async function acceptQuest(questId) {
    try {
        const response = await fetch('includes/update_quest_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                quest_id: questId,
                status: 'in_progress'
            })
        });

        const data = await response.json();
        if (data.success) {
            showAlert('Quest accepted successfully', 'success');
            loadWorkspace(); // Refresh the workspace
        } else {
            throw new Error(data.message || 'Failed to accept quest');
        }
    } catch (error) {
        console.error('Error accepting quest:', error);
        showAlert(error.message, 'error');
    }
}