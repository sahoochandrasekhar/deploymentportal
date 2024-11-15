<!-- footer.php -->
<footer class="main-footer">
   

    <!-- Support Information Table -->
    <h4>AWS Team Contacts</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Support Level</th>
                <th>Name</th>
                <th>Mobile Number</th>
                <th>Email ID</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Support Contact Person 1</td>
                <td>Chandrasekhar Sahoo</td>
                <td>7325813055</td>
                <td><a href="mailto:chandra.sahoo@apmosys.com">chandra.sahoo@apmosys.com</a></td>
            </tr>
            <tr>
                <td>Support Contact Person 2</td>
                <td>Raju Jena</td>
                <td>9348161379</td>
                <td><a href="mailto:raju.jena@apmosys.com">raju.jena@apmosys.com</a></td>
            </tr>
            <tr>
                <td>Escalation</td>
                <td>Asutosh Maharana</td>
                <td>9938780118</td>
                <td><a href="mailto:asutosh.maharana@apmosys.com">asutosh.maharana@apmosys.com</a></td>
            </tr>
        </tbody>
    </table>

    <div class="alert alert-info">
        <p>For any urgent queries, please reach out to the above support levels based on the severity of the issue.</p>
    </div>
    <div class="float-right d-none d-sm-inline">
        Support Deployment Portal - ApMoSys
    </div>
    <strong>&copy; Sahara - 2024  </strong>
</footer>

<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.1.0/dist/js/adminlte.min.js"></script>
</body>
</html>

<?php if (isset($message)): ?>
    <script>
        // Show the notification
        var notification = document.getElementById('notification');
        notification.style.display = 'block';  // Make it visible
        
        // After 5 seconds, fade it out
        setTimeout(function() {
            notification.classList.add('fade-out'); // Add fade-out class
            setTimeout(function() {
                notification.style.display = 'none'; // Hide the notification
            }, 500); // Wait for the fade-out animation to complete
        }, 5000); // Display for 5 seconds
    </script>
<?php endif; ?>
