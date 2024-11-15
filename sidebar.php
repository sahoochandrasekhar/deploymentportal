<?php
// Check if the user is logged in and if is_admin is set
if (isset($_SESSION['is_admin'])) {
    $is_admin = $_SESSION['is_admin']; // Set $is_admin from session
} else {
    $is_admin = 0; // Default value if is_admin is not set
}
?>
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="dashboard.php" class="brand-link">
        <!-- Add Logo Image Here, assuming logo is stored in the project root directory -->
        <img src="logo.png" alt="Logo" class="brand-image" style="width: 200px; height: 40px; object-fit: contain;">
    </a>

    <div class="sidebar">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                
                <!-- General Section -->
                <li class="nav-header">General</li>

                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link">
                        <i class="nav-icon fas fa-tachometer-alt"></i>  <!-- Icon for Dashboard -->
                        <p>Dashboard</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="deployment.php" class="nav-link">
                        <i class="nav-icon fas fa-cogs"></i>  <!-- Icon for Deployment -->
                        <p>Deployment</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="rollback.php" class="nav-link">
                        <i class="nav-icon fas fa-undo-alt"></i>  <!-- Icon for Rollback -->
                        <p>Roll-Back</p>
                    </a>
                </li>

                <?php if ($is_admin == 1): ?>
                    <!-- Administration Section (for Admins only) -->
                    <li class="nav-header">Administration</li>

                    <li class="nav-item">
                        <a href="approve_users.php" class="nav-link">
                            <i class="nav-icon fas fa-users-cog"></i>  <!-- Icon for Manage Users -->
                            <p>Manage Users</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="historical.php" class="nav-link">
                            <i class="nav-icon fas fa-history"></i>  <!-- Icon for Logs or History -->
                            <p>Historical</p>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Logout Button (can be added here if needed) -->

            </ul>
        </nav>
    </div>
</aside>

<style>
/* Change Sidebar Background Color */
.main-sidebar {
    background-color: #0b2f5a; /* Dark purple color */
    display: flex;
    flex-direction: column;
    height: 100vh;
}

/* Sidebar content (links) */
.sidebar {
    flex-grow: 1; /* Make the links section take up available space */
    overflow-y: auto; /* Allow scrolling if content exceeds height */
}

/* Change Text Color in Sidebar */
.nav-sidebar .nav-link {
    color: white !important; /* Make all text in the sidebar white */
}

/* Change Active Link Background Color */
.nav-sidebar .nav-link.active {
    background-color: rgba(255, 255, 255, 0.1); /* Lighten the active link color for better visibility */
}

/* Change Icon Color in Sidebar */
.nav-sidebar .nav-link i {
    color: white !important; /* Make the icons white */
}

/* Change Hover State of Sidebar Links */
.nav-sidebar .nav-link:hover {
    background-color: rgba(255, 255, 255, 0.2); /* Slightly lighten the link on hover */
    color: white !important; /* Ensure text color stays white on hover */
}

/* Sidebar Brand (Logo) */
.main-sidebar .brand-link {
    background-color: #0b2f5a; /* Same color for brand link */
    color: white !important; /* Ensure brand text is white */
    display: flex;
    align-items: center;
    padding: 10px; /* Optional padding */
}

/* Logo Styling */
.main-sidebar .brand-link .brand-image {
    width: 200px; /* Set desired width for the logo */
    height: 40px; /* Set desired height for the logo */
    object-fit: contain; /* Ensure the logo fits without being distorted */
    margin-right: 10px; /* Optional spacing between logo and text */
}

/* Logout button at the bottom */
.logout-item {
    margin-top: auto; /* Push logout button to the bottom of the sidebar */
    padding: 10px 15px; /* Add some padding around the button */
}

.logout-item .btn {
    width: 100%; /* Make the logout button take full width */
}
</style>
