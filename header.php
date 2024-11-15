<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="favicon.ico">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.1.0/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="dashboardstyle.css">
    
    <!-- Font Awesome CDN for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Include TinyMCE from CDN -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/5/tinymce.min.js"></script>
    <!-- jQuery Library -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <!-- jQuery UI Library (for Autocomplete) -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

    <!-- Additional custom styles for the spinner -->
    <style>
        /* Loading overlay (this covers the whole page with a semi-transparent background) */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5); /* Semi-transparent dark background */
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999; /* Ensure it's on top of other content */
            visibility: hidden; /* Hidden by default */
        }

        /* Loading spinner styles */
        .spinner {
            border: 4px solid #f3f3f3; /* Light grey background */
            border-top: 4px solid #3498db; /* Blue spinner color */
            border-radius: 50%;
            width: 60px; /* Increased size for better visibility */
            height: 60px; /* Increased size for better visibility */
            animation: spin 1.5s linear infinite; /* Faster spin animation */
        }

        /* Keyframes for spinning animation */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Adjusting the size of the spinner if needed */
        .loading-overlay .spinner {
            width: 70px;
            height: 70px;
        }
    </style>

</head>
<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">

        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="dashboard.php" class="nav-link">Dashboard</a>
                </li>
            </ul>
            
            <!-- Right navbar links -->
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <form action="logout.php" method="POST" id="logoutForm">
                        <button type="submit" class="btn btn-danger rounded-circle" style="width: 40px; height: 40px; padding: 0; text-align: center;">
                            <i class="fas fa-sign-out-alt" style="font-size: 20px;"></i>
                        </button>
                    </form>
                </li>
            </ul>
        </nav>

        <!-- Add the loading spinner -->
        <div id="loading" class="loading-overlay">
            <div class="spinner"></div>
        </div>

        <style>
            /* Customize the circular logout button */
            .navbar-nav .nav-item form button {
                display: flex;
                justify-content: center;
                align-items: center;
                background-color: #dc3545;
                border: none;
                border-radius: 50%;
                color: white;
                cursor: pointer;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            }

            /* Change the button color on hover */
            .navbar-nav .nav-item form button:hover {
                background-color: #c82333;
            }
        </style>
    </div>

    <!-- Add the JavaScript to control the loading spinner visibility -->
    <script>
        // Function to show the loading spinner on page load
        function showLoadingSpinner() {
            document.getElementById('loading').style.visibility = 'visible';
        }

        // Function to hide the loading spinner after page load
        function hideLoadingSpinner() {
            document.getElementById('loading').style.visibility = 'hidden';
        }

        // Show the loading spinner as soon as the page starts loading
        window.onload = function() {
            // Show the spinner immediately when the page starts loading
            showLoadingSpinner();
            
            // Hide the spinner once everything has loaded
            setTimeout(function() {
                hideLoadingSpinner();
            }, 100);  // Wait for a small moment after loading to hide spinner
        };

        // Add event listener to the logout form to show the loader before submit
        document.getElementById("logoutForm").addEventListener("submit", function(event) {
            event.preventDefault();  // Prevent the form from submitting immediately
            showLoadingSpinner();    // Show the spinner

            // Simulate form submission delay (you can remove this if using real backend)
            setTimeout(function() {
                // Simulate form submission after delay
                window.location.href = "logout.php"; // Redirect to logout page
            }, 1000); // Adjust the delay if needed
        });

        // Example for other buttons or forms:
        document.querySelectorAll('button').forEach(function(button) {
            button.addEventListener("click", function() {
                showLoadingSpinner(); // Show spinner on button click
                // Simulate a delay (if needed) before hiding the spinner
                setTimeout(function() {
                    hideLoadingSpinner();
                }, 1000); // Adjust the delay as needed
            });
        });
    </script>

</body>
</html>
