<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management - Sari-Sari Store</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #142883;
            --primary-light: #1d3ab3;
            --secondary: #2ecc71;
            --danger: #e74c3c;
            --warning: #f39c12;
            --dark: #333;
            --light: #f9f9f9;
            --gray: #ccc;
            --white: #fff;
            --shadow: 0 4px 15px rgba(0,0,0,0.1);
            --shadow-hover: 0 6px 20px rgba(0,0,0,0.15);
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            background: var(--light);
            color: var(--dark);
            display: flex;
        }

        .main-content {
            margin-left: 200px;
            padding: 30px;
            flex-grow: 1;
            transition: margin 0.3s ease;
            width: calc(100% - 200px);
        }

        /* When sidebar is collapsed */
        .sidebar.collapsed ~ .main-content {
            margin-left: 60px;
            width: calc(100% - 60px);
        }

        h1 {
            margin-bottom: 25px;
            font-size: 28px;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .success {
            background: var(--secondary);
            color: var(--white);
        }
        
        .error {
            background: var(--danger);
            color: var(--white);
        }
        
        /* Admin Management Section */
        .admin-management {
            background: var(--white);
            padding: 25px 30px;
            border-radius: 10px;
            box-shadow: var(--shadow);
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .admin-table th, .admin-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--gray);
        }
        
        .admin-table th {
            background-color: var(--primary-light);
            color: var(--white);
        }
        
        .admin-table tr:hover {
            background-color: rgba(29, 58, 179, 0.05);
        }
        
        .admin-actions {
            display: flex;
            gap: 8px;
        }
        
        .admin-btn {
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }
        
        .edit-btn {
            background: var(--secondary);
            color: white;
        }
        
        .edit-btn:hover {
            background: #25a25a;
        }
        
        .delete-btn {
            background: var(--danger);
            color: white;
        }
        
        .delete-btn:hover {
            background: #c0392b;
        }
        
        .status-active {
            color: var(--secondary);
            font-weight: 600;
        }
        
        .status-inactive {
            color: var(--danger);
            font-weight: 600;
        }
        
        .add-admin-btn {
            padding: 10px 15px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: 600;
            transition: background 0.3s;
        }
        
        .add-admin-btn:hover {
            background: var(--primary-light);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background: var(--white);
            padding: 25px 30px;
            border-radius: 10px;
            box-shadow: var(--shadow);
            width: 500px;
            max-width: 90%;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-header h2 {
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--dark);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid var(--gray);
            font-size: 15px;
        }
        
        .form-group input:focus, .form-group select:focus {
            border-color: var(--primary);
            outline: none;
        }
        
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-cancel {
            padding: 10px 20px;
            background: var(--gray);
            color: var(--dark);
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .btn-save {
            padding: 10px 20px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .btn-save:hover {
            background: var(--primary-light);
        }

        /* Confirmation Dialog Styles */
        .confirmation-dialog {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1001;
            justify-content: center;
            align-items: center;
        }
        
        .dialog-content {
            background: var(--white);
            padding: 25px 30px;
            border-radius: 10px;
            box-shadow: var(--shadow);
            width: 400px;
            max-width: 90%;
        }
        
        .dialog-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .dialog-header h3 {
            color: var(--danger);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .dialog-message {
            margin-bottom: 20px;
            line-height: 1.5;
        }
        
        .dialog-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .btn-no {
            padding: 8px 16px;
            background: var(--gray);
            color: var(--dark);
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .btn-yes {
            padding: 8px 16px;
            background: var(--danger);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
        }

        @media(max-width: 1024px) {
            .main-content {
                margin-left: 200px;
                width: calc(100% - 200px);
                padding: 20px;
            }
            
            .sidebar.collapsed ~ .main-content {
                margin-left: 60px;
                width: calc(100% - 60px);
            }
        }

        @media(max-width: 768px){
            .main-content {
                margin-left: 60px;
                width: calc(100% - 60px);
                padding: 15px;
            }
            
            .sidebar:not(.collapsed) ~ .main-content {
                margin-left: 200px;
                width: calc(100% - 200px);
            }
            
            .admin-table {
                display: block;
                overflow-x: auto;
            }
            
            .admin-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }
        
        @media(max-width: 480px){
            .main-content {
                margin-left: 50px;
                width: calc(100% - 50px);
                padding: 10px;
            }
            
            .sidebar:not(.collapsed) ~ .main-content {
                margin-left: 200px;
                width: calc(100% - 200px);
            }
            
            .admin-actions {
                flex-direction: column;
            }
            
            .modal-content {
                padding: 15px 20px;
            }
            
            h1 {
                font-size: 24px;
            }
            
            .admin-management {
                padding: 15px 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Include the sidebar.php file -->
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <h1><i class="fas fa-users-cog"></i> Admin Management</h1>

        <div class="alert success" id="successAlert" style="display: none;">
            <i class="fas fa-check-circle"></i> <span id="successMessage"></span>
        </div>
        
        <div class="alert error" id="errorAlert" style="display: none;">
            <i class="fas fa-exclamation-circle"></i> <span id="errorMessage"></span>
        </div>

        <!-- Admin Management Section -->
        <div class="admin-management">
            <div class="admin-header">
                <h3>Administrator Accounts</h3>
                <button class="add-admin-btn" onclick="openAddAdminModal()">
                    <i class="fas fa-user-plus"></i> Add New Admin
                </button>
            </div>
            
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Last Login</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="adminTableBody">
                    <tr id="admin-row-admin">
                        <td>admin</td>
                        <td>Super Admin</td>
                        <td>2023-12-15 14:30</td>
                        <td class="status-active">Active</td>
                        <td>
                            <div class="admin-actions">
                                <button class="admin-btn edit-btn" onclick="openEditAdminModal('admin')"><i class="fas fa-edit"></i> Edit</button>
                                <button class="admin-btn delete-btn" onclick="confirmDeleteAdmin('admin')"><i class="fas fa-trash"></i> Delete</button>
                            </div>
                        </td>
                    </tr>
                    <tr id="admin-row-manager">
                        <td>manager</td>
                        <td>Store Manager</td>
                        <td>2023-12-14 09:15</td>
                        <td class="status-active">Active</td>
                        <td>
                            <div class="admin-actions">
                                <button class="admin-btn edit-btn" onclick="openEditAdminModal('manager')"><i class="fas fa-edit"></i> Edit</button>
                                <button class="admin-btn delete-btn" onclick="confirmDeleteAdmin('manager')"><i class="fas fa-trash"></i> Delete</button>
                            </div>
                        </td>
                    </tr>
                    <tr id="admin-row-cashier1">
                        <td>cashier1</td>
                        <td>Cashier</td>
                        <td>2023-12-15 10:45</td>
                        <td class="status-active">Active</td>
                        <td>
                            <div class="admin-actions">
                                <button class="admin-btn edit-btn" onclick="openEditAdminModal('cashier1')"><i class="fas fa-edit"></i> Edit</button>
                                <button class="admin-btn delete-btn" onclick="confirmDeleteAdmin('cashier1')"><i class="fas fa-trash"></i> Delete</button>
                            </div>
                        </td>
                    </tr>
                    <tr id="admin-row-cashier2">
                        <td>cashier2</td>
                        <td>Cashier</td>
                        <td>2023-12-13 16:20</td>
                        <td class="status-inactive">Inactive</td>
                        <td>
                            <div class="admin-actions">
                                <button class="admin-btn edit-btn" onclick="openEditAdminModal('cashier2')"><i class="fas fa-edit"></i> Edit</button>
                                <button class="admin-btn delete-btn" onclick="confirmDeleteAdmin('cashier2')"><i class="fas fa-trash"></i> Delete</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Admin Modal -->
    <div class="modal" id="addAdminModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-plus"></i> Add New Admin</h2>
                <button class="close-modal" onclick="closeModal('addAdminModal')">&times;</button>
            </div>
            <div class="form-group">
                <label for="newUsername">Username</label>
                <input type="text" id="newUsername" placeholder="Enter username">
            </div>
            <div class="form-group">
                <label for="newPassword">Password</label>
                <input type="password" id="newPassword" placeholder="Enter password">
            </div>
            <div class="form-group">
                <label for="confirmPassword">Confirm Password</label>
                <input type="password" id="confirmPassword" placeholder="Confirm password">
            </div>
            <div class="form-group">
                <label for="adminRole">Role</label>
                <select id="adminRole">
                    <option value="admin">Super Admin</option>
                    <option value="manager">Store Manager</option>
                    <option value="cashier">Cashier</option>
                </select>
            </div>
            <div class="modal-actions">
                <button class="btn-cancel" onclick="closeModal('addAdminModal')">Cancel</button>
                <button class="btn-save" onclick="addNewAdmin()">Save Admin</button>
            </div>
        </div>
    </div>

    <!-- Edit Admin Modal -->
    <div class="modal" id="editAdminModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Edit Admin</h2>
                <button class="close-modal" onclick="closeModal('editAdminModal')">&times;</button>
            </div>
            <div class="form-group">
                <label for="editUsername">Username</label>
                <input type="text" id="editUsername" placeholder="Enter username" readonly>
            </div>
            <div class="form-group">
                <label for="editPassword">New Password (leave blank to keep current)</label>
                <input type="password" id="editPassword" placeholder="Enter new password">
            </div>
            <div class="form-group">
                <label for="editRole">Role</label>
                <select id="editRole">
                    <option value="admin">Super Admin</option>
                    <option value="manager">Store Manager</option>
                    <option value="cashier">Cashier</option>
                </select>
            </div>
            <div class="form-group">
                <label for="editStatus">Status</label>
                <select id="editStatus">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="modal-actions">
                <button class="btn-cancel" onclick="closeModal('editAdminModal')">Cancel</button>
                <button class="btn-save" onclick="saveAdminChanges()">Save Changes</button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Dialog -->
    <div class="confirmation-dialog" id="deleteConfirmDialog">
        <div class="dialog-content">
            <div class="dialog-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h3>
                <button class="close-modal" onclick="closeModal('deleteConfirmDialog')">&times;</button>
            </div>
            <div class="dialog-message" id="deleteConfirmMessage">
                Are you sure you want to delete this admin account? This action cannot be undone.
            </div>
            <div class="dialog-actions">
                <button class="btn-no" onclick="closeModal('deleteConfirmDialog')">Cancel</button>
                <button class="btn-yes" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>

    <script>
        // Store the admin to be deleted
        let adminToDelete = null;
        
        // Show success message
        function showSuccess(message) {
            const successAlert = document.getElementById('successAlert');
            const successMessage = document.getElementById('successMessage');
            
            successMessage.textContent = message;
            successAlert.style.display = 'block';
            
            // Hide error alert if shown
            document.getElementById('errorAlert').style.display = 'none';
            
            // Auto hide after 5 seconds
            setTimeout(() => {
                successAlert.style.display = 'none';
            }, 5000);
        }
        
        // Show error message
        function showError(message) {
            const errorAlert = document.getElementById('errorAlert');
            const errorMessage = document.getElementById('errorMessage');
            
            errorMessage.textContent = message;
            errorAlert.style.display = 'block';
            
            // Hide success alert if shown
            document.getElementById('successAlert').style.display = 'none';
            
            // Auto hide after 5 seconds
            setTimeout(() => {
                errorAlert.style.display = 'none';
            }, 5000);
        }
        
        // Open Add Admin Modal
        function openAddAdminModal() {
            document.getElementById('addAdminModal').style.display = 'flex';
            // Clear form fields
            document.getElementById('newUsername').value = '';
            document.getElementById('newPassword').value = '';
            document.getElementById('confirmPassword').value = '';
            document.getElementById('adminRole').value = 'cashier';
        }
        
        // Open Edit Admin Modal
        function openEditAdminModal(username) {
            document.getElementById('editAdminModal').style.display = 'flex';
            // In a real app, you would fetch the admin data from the server
            document.getElementById('editUsername').value = username;
            document.getElementById('editPassword').value = '';
            
            // Set role based on username (for demo purposes)
            if (username === 'admin') {
                document.getElementById('editRole').value = 'admin';
                document.getElementById('editStatus').value = 'active';
            } else if (username === 'manager') {
                document.getElementById('editRole').value = 'manager';
                document.getElementById('editStatus').value = 'active';
            } else {
                document.getElementById('editRole').value = 'cashier';
                document.getElementById('editStatus').value = username === 'cashier2' ? 'inactive' : 'active';
            }
        }
        
        // Close modal
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            adminToDelete = null;
        }
        
        // Add new admin
        function addNewAdmin() {
            const username = document.getElementById('newUsername').value;
            const password = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const role = document.getElementById('adminRole').value;
            
            if (!username || !password) {
                showError('Username and password are required!');
                return;
            }
            
            if (password !== confirmPassword) {
                showError('Passwords do not match!');
                return;
            }
            
            // In a real app, you would send this data to the server
            console.log('Adding new admin:', { username, password, role });
            
            // Show success message
            showSuccess('Admin account created successfully!');
            
            // Close the modal
            closeModal('addAdminModal');
            
            // In a real app, you would update the table with the new admin
        }
        
        // Save admin changes
        function saveAdminChanges() {
            const username = document.getElementById('editUsername').value;
            const password = document.getElementById('editPassword').value;
            const role = document.getElementById('editRole').value;
            const status = document.getElementById('editStatus').value;
            
            if (!username) {
                showError('Username is required!');
                return;
            }
            
            // In a real app, you would send this data to the server
            console.log('Saving admin changes:', { username, password, role, status });
            
            // Show success message
            showSuccess('Admin account updated successfully!');
            
            // Close the modal
            closeModal('editAdminModal');
            
            // Update the status in the table
            const statusCell = document.querySelector(`#admin-row-${username} td:nth-child(4)`);
            if (statusCell) {
                statusCell.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                statusCell.className = status === 'active' ? 'status-active' : 'status-inactive';
            }
            
            // Update the role in the table
            const roleCell = document.querySelector(`#admin-row-${username} td:nth-child(2)`);
            if (roleCell) {
                let roleDisplay = '';
                switch(role) {
                    case 'admin': roleDisplay = 'Super Admin'; break;
                    case 'manager': roleDisplay = 'Store Manager'; break;
                    case 'cashier': roleDisplay = 'Cashier'; break;
                }
                roleCell.textContent = roleDisplay;
            }
        }
        
        // Confirm admin deletion
        function confirmDeleteAdmin(username) {
            adminToDelete = username;
            const dialog = document.getElementById('deleteConfirmDialog');
            const message = document.getElementById('deleteConfirmMessage');
            
            message.textContent = `Are you sure you want to delete the admin account "${username}"? This action cannot be undone.`;
            dialog.style.display = 'flex';
            
            // Set up the confirm button
            const confirmBtn = document.getElementById('confirmDeleteBtn');
            confirmBtn.onclick = function() {
                deleteAdminAccount(username);
                closeModal('deleteConfirmDialog');
            };
        }
        
        // Delete admin account
        function deleteAdminAccount(username) {
            if (!adminToDelete) return;
            
            // In a real app, you would send a delete request to the server
            console.log('Deleting admin:', username);
            
            // Remove the row from the table
            const row = document.getElementById(`admin-row-${username}`);
            if (row) {
                row.remove();
            }
            
            // Show success message
            showSuccess('Admin account deleted successfully!');
            
            adminToDelete = null;
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const modals = document.getElementsByClassName('modal');
            for (let i = 0; i < modals.length; i++) {
                if (event.target === modals[i]) {
                    modals[i].style.display = 'none';
                    adminToDelete = null;
                }
            }
            
            const dialogs = document.getElementsByClassName('confirmation-dialog');
            for (let i = 0; i < dialogs.length; i++) {
                if (event.target === dialogs[i]) {
                    dialogs[i].style.display = 'none';
                    adminToDelete = null;
                }
            }
        }
    </script>
</body>
</html>