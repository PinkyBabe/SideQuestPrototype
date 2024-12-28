<head>
    <!-- ... other styles ... -->
    <link rel="stylesheet" href="css/shared_dashboard.css">
</head>

<section id="workspace" class="container section">
    <div class="workspace-header">
        <h2>Quest Tracker</h2>
        <div class="workspace-tabs">
            <button class="tab-btn active" onclick="showWorkspaceTab('ongoing')">Ongoing</button>
            <button class="tab-btn" onclick="showWorkspaceTab('pending')">Pending</button>
            <button class="tab-btn" onclick="showWorkspaceTab('completed')">Completed</button>
            <button class="tab-btn" onclick="showWorkspaceTab('cancelled')">Cancelled</button>
        </div>
    </div>

    <div class="workspace-content">
        <div id="ongoing-tab" class="tab-content active">
            <div class="quest-list" id="ongoing-quests"></div>
        </div>
        <div id="pending-tab" class="tab-content">
            <div class="quest-list" id="pending-quests"></div>
        </div>
        <div id="completed-tab" class="tab-content">
            <div class="quest-list" id="completed-quests"></div>
        </div>
        <div id="cancelled-tab" class="tab-content">
            <div class="quest-list" id="cancelled-quests"></div>
        </div>
    </div>
</section> 