<?php
/**
 * User Management Page
 * Accessible only by sysadmin role
 */
require_once '../config/database.php';
require_once '../includes/session.php';

requireRole(['sysadmin']);

$pageTitle = 'Manage Users';
$currentAdminId = getAdminId();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        redirectWithMessage('manage_users.php', 'error', 'Invalid security token. Please try again.');
    }

    $action = $_POST['action'] ?? '';
    $db = getDB();

    // ---- ADD USER ----
    if ($action === 'add') {
        $username  = sanitize($_POST['username'] ?? '');
        $password  = $_POST['password'] ?? '';
        $fullName  = sanitize($_POST['full_name'] ?? '');
        $email     = sanitize($_POST['email'] ?? '');
        $role      = sanitize($_POST['role'] ?? '');

        // Validate required fields
        if (empty($username) || empty($password) || empty($fullName) || empty($email) || empty($role)) {
            redirectWithMessage('manage_users.php', 'error', 'All fields are required.');
        }

        // Validate role
        if (!in_array($role, ['admin', 'operator'])) {
            redirectWithMessage('manage_users.php', 'error', 'Invalid role selected.');
        }

        // Validate email
        if (!isValidEmail($email)) {
            redirectWithMessage('manage_users.php', 'error', 'Please enter a valid email address.');
        }

        // Validate password length
        if (strlen($password) < 6) {
            redirectWithMessage('manage_users.php', 'error', 'Password must be at least 6 characters long.');
        }

        try {
            // Check unique username
            $existing = $db->fetchOne("SELECT admin_id FROM admin WHERE username = :username", ['username' => $username]);
            if ($existing) {
                redirectWithMessage('manage_users.php', 'error', 'Username already exists. Please choose a different username.');
            }

            // Check unique email
            $existing = $db->fetchOne("SELECT admin_id FROM admin WHERE email = :email", ['email' => $email]);
            if ($existing) {
                redirectWithMessage('manage_users.php', 'error', 'Email address already exists. Please use a different email.');
            }

            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            // Insert user
            $db->query(
                "INSERT INTO admin (username, password, full_name, email, role, status, created_at)
                 VALUES (:username, :password, :full_name, :email, :role, 'active', NOW())",
                [
                    'username'  => $username,
                    'password'  => $hashedPassword,
                    'full_name' => $fullName,
                    'email'     => $email,
                    'role'      => $role
                ]
            );

            redirectWithMessage('manage_users.php', 'success', 'User "' . $username . '" has been added successfully.');

        } catch (Exception $e) {
            error_log("Add User Error: " . $e->getMessage());
            redirectWithMessage('manage_users.php', 'error', 'Failed to add user. Please try again.');
        }
    }

    // ---- EDIT USER ----
    elseif ($action === 'edit') {
        $userId   = intval($_POST['user_id'] ?? 0);
        $fullName = sanitize($_POST['full_name'] ?? '');
        $email    = sanitize($_POST['email'] ?? '');
        $role     = sanitize($_POST['role'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($userId <= 0 || empty($fullName) || empty($email) || empty($role)) {
            redirectWithMessage('manage_users.php', 'error', 'Full name, email, and role are required.');
        }

        // Validate email
        if (!isValidEmail($email)) {
            redirectWithMessage('manage_users.php', 'error', 'Please enter a valid email address.');
        }

        try {
            // Fetch the target user
            $targetUser = $db->fetchOne("SELECT admin_id, role FROM admin WHERE admin_id = :id", ['id' => $userId]);
            if (!$targetUser) {
                redirectWithMessage('manage_users.php', 'error', 'User not found.');
            }

            // Cannot edit sysadmin role users (except self)
            if ($targetUser['role'] === 'sysadmin' && $userId !== $currentAdminId) {
                redirectWithMessage('manage_users.php', 'error', 'Cannot edit other system administrator accounts.');
            }

            // Cannot change own account's role
            if ($userId === $currentAdminId && $role !== $targetUser['role']) {
                redirectWithMessage('manage_users.php', 'error', 'You cannot change your own role.');
            }

            // For non-sysadmin users, role can only be admin or operator
            if ($targetUser['role'] !== 'sysadmin' && !in_array($role, ['admin', 'operator'])) {
                redirectWithMessage('manage_users.php', 'error', 'Invalid role selected.');
            }

            // Check unique email (exclude current user)
            $existing = $db->fetchOne(
                "SELECT admin_id FROM admin WHERE email = :email AND admin_id != :id",
                ['email' => $email, 'id' => $userId]
            );
            if ($existing) {
                redirectWithMessage('manage_users.php', 'error', 'Email address already in use by another user.');
            }

            // Validate password length if provided
            if (!empty($password) && strlen($password) < 6) {
                redirectWithMessage('manage_users.php', 'error', 'Password must be at least 6 characters long.');
            }

            // Determine the role to save
            $roleToSave = ($targetUser['role'] === 'sysadmin') ? 'sysadmin' : $role;

            // Build update query
            if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $db->query(
                    "UPDATE admin SET full_name = :full_name, email = :email, role = :role, password = :password WHERE admin_id = :id",
                    [
                        'full_name' => $fullName,
                        'email'     => $email,
                        'role'      => $roleToSave,
                        'password'  => $hashedPassword,
                        'id'        => $userId
                    ]
                );
            } else {
                $db->query(
                    "UPDATE admin SET full_name = :full_name, email = :email, role = :role WHERE admin_id = :id",
                    [
                        'full_name' => $fullName,
                        'email'     => $email,
                        'role'      => $roleToSave,
                        'id'        => $userId
                    ]
                );
            }

            // Update session if editing own account
            if ($userId === $currentAdminId) {
                $_SESSION['full_name'] = $fullName;
                $_SESSION['email'] = $email;
            }

            redirectWithMessage('manage_users.php', 'success', 'User updated successfully.');

        } catch (Exception $e) {
            error_log("Edit User Error: " . $e->getMessage());
            redirectWithMessage('manage_users.php', 'error', 'Failed to update user. Please try again.');
        }
    }

    // ---- UNLOCK ACCOUNT ----
    elseif ($action === 'unlock_account') {
        $userId = intval($_POST['user_id'] ?? 0);

        if ($userId <= 0) {
            redirectWithMessage('manage_users.php', 'error', 'Invalid user ID.');
        }

        try {
            $targetUser = $db->fetchOne("SELECT admin_id, username, failed_login_attempts, account_locked_until FROM admin WHERE admin_id = :id", ['id' => $userId]);
            if (!$targetUser) {
                redirectWithMessage('manage_users.php', 'error', 'User not found.');
            }

            $db->query("UPDATE admin SET failed_login_attempts = 0, account_locked_until = NULL WHERE admin_id = :id",
                ['id' => $userId]);

            // Also clear rate limit entries for this user
            $db->query("DELETE FROM login_attempts WHERE username = :username AND success = 0",
                ['username' => $targetUser['username']]);

            logAudit(getAdminId(), 'UNLOCK_ACCOUNT', 'admin', $userId,
                ['failed_login_attempts' => $targetUser['failed_login_attempts'], 'account_locked_until' => $targetUser['account_locked_until']],
                ['failed_login_attempts' => 0, 'account_locked_until' => null]);

            redirectWithMessage('manage_users.php', 'success', 'Account "' . $targetUser['username'] . '" has been unlocked and login attempts reset.');

        } catch (Exception $e) {
            error_log("Unlock Account Error: " . $e->getMessage());
            redirectWithMessage('manage_users.php', 'error', 'Failed to unlock account. Please try again.');
        }
    }

    // ---- TOGGLE STATUS ----
    elseif ($action === 'toggle_status') {
        $userId = intval($_POST['user_id'] ?? 0);

        if ($userId <= 0) {
            redirectWithMessage('manage_users.php', 'error', 'Invalid user ID.');
        }

        // Cannot deactivate own account
        if ($userId === $currentAdminId) {
            redirectWithMessage('manage_users.php', 'error', 'You cannot deactivate your own account.');
        }

        try {
            $targetUser = $db->fetchOne("SELECT admin_id, role, status, username FROM admin WHERE admin_id = :id", ['id' => $userId]);
            if (!$targetUser) {
                redirectWithMessage('manage_users.php', 'error', 'User not found.');
            }

            // Cannot deactivate sysadmin accounts
            if ($targetUser['role'] === 'sysadmin') {
                redirectWithMessage('manage_users.php', 'error', 'Cannot change the status of a system administrator account.');
            }

            $newStatus = ($targetUser['status'] === 'active') ? 'inactive' : 'active';
            $db->query(
                "UPDATE admin SET status = :status WHERE admin_id = :id",
                ['status' => $newStatus, 'id' => $userId]
            );

            $statusLabel = ($newStatus === 'active') ? 'activated' : 'deactivated';
            redirectWithMessage('manage_users.php', 'success', 'User "' . $targetUser['username'] . '" has been ' . $statusLabel . '.');

        } catch (Exception $e) {
            error_log("Toggle Status Error: " . $e->getMessage());
            redirectWithMessage('manage_users.php', 'error', 'Failed to update user status. Please try again.');
        }
    }
}

// Fetch all users for display
try {
    $db = getDB();
    $users = $db->fetchAll("SELECT admin_id, username, full_name, email, role, status, created_at, last_login, failed_login_attempts, account_locked_until FROM admin ORDER BY role ASC, username ASC");
} catch (Exception $e) {
    error_log("Fetch Users Error: " . $e->getMessage());
    $users = [];
}

$csrfToken = generateCSRFToken();

require_once '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <h2>
                <i class="fas fa-users-cog"></i> Manage Users
            </h2>
            <button type="button" class="btn btn-primary btn-custom" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fas fa-user-plus"></i> Add New User
            </button>
        </div>
    </div>
</div>

<!-- Users Table -->
<div class="row">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-list"></i> All Users
                <span class="badge bg-secondary ms-2"><?php echo count($users); ?></span>
            </div>
            <div class="card-body">
                <?php if (count($users) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-custom table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                    <?php if ($user['admin_id'] == $currentAdminId): ?>
                                        <span class="badge bg-secondary ms-1">You</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <?php
                                    $roleBadgeClass = match($user['role']) {
                                        'sysadmin' => 'bg-purple',
                                        'admin'    => 'bg-primary',
                                        'operator' => 'bg-info',
                                        default    => 'bg-secondary'
                                    };
                                    $roleLabel = match($user['role']) {
                                        'sysadmin' => 'System Admin',
                                        'admin'    => 'Admin',
                                        'operator' => 'Staff',
                                        default    => ucfirst($user['role'])
                                    };
                                    ?>
                                    <span class="badge <?php echo $roleBadgeClass; ?>"
                                          <?php if ($user['role'] === 'sysadmin'): ?>
                                          style="background-color: #6f42c1;"
                                          <?php endif; ?>>
                                        <?php echo $roleLabel; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user['status'] === 'active'): ?>
                                        <span class="badge bg-success"><i class="fas fa-check-circle"></i> Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><i class="fas fa-times-circle"></i> Inactive</span>
                                    <?php endif; ?>
                                    <?php if ($user['account_locked_until'] && strtotime($user['account_locked_until']) > time()): ?>
                                        <span class="badge bg-danger ms-1"><i class="fas fa-lock"></i> Locked</span>
                                    <?php elseif (($user['failed_login_attempts'] ?? 0) >= 5): ?>
                                        <span class="badge bg-warning text-dark ms-1"><i class="fas fa-exclamation-triangle"></i> At Limit</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['last_login']): ?>
                                        <?php echo formatDate($user['last_login'], 'd-m-Y H:i'); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Never</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($user['admin_id'] == $currentAdminId): ?>
                                        <!-- Own account: only allow editing name/email -->
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-icon edit-user-btn"
                                                title="Edit My Profile"
                                                data-id="<?php echo $user['admin_id']; ?>"
                                                data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                                data-fullname="<?php echo htmlspecialchars($user['full_name']); ?>"
                                                data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                data-role="<?php echo htmlspecialchars($user['role']); ?>"
                                                data-is-self="1">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    <?php elseif ($user['role'] === 'sysadmin'): ?>
                                        <!-- Other sysadmin accounts: no actions -->
                                        <span class="text-muted">--</span>
                                    <?php else: ?>
                                        <!-- Non-sysadmin, non-self: edit and toggle -->
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-icon edit-user-btn me-1"
                                                title="Edit User"
                                                data-id="<?php echo $user['admin_id']; ?>"
                                                data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                                data-fullname="<?php echo htmlspecialchars($user['full_name']); ?>"
                                                data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                data-role="<?php echo htmlspecialchars($user['role']); ?>"
                                                data-is-self="0">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" action="manage_users.php" class="d-inline"
                                              onsubmit="return confirm('Are you sure you want to <?php echo ($user['status'] === 'active') ? 'deactivate' : 'activate'; ?> this user?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="user_id" value="<?php echo $user['admin_id']; ?>">
                                            <?php if ($user['status'] === 'active'): ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger btn-icon" title="Deactivate User">
                                                    <i class="fas fa-user-slash"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" class="btn btn-sm btn-outline-success btn-icon" title="Activate User">
                                                    <i class="fas fa-user-check"></i>
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                        <?php if (($user['account_locked_until'] && strtotime($user['account_locked_until']) > time()) || ($user['failed_login_attempts'] ?? 0) >= 5): ?>
                                        <form method="POST" action="manage_users.php" class="d-inline"
                                              onsubmit="return confirm('Unlock account and reset login attempts for <?php echo htmlspecialchars($user['username']); ?>?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                            <input type="hidden" name="action" value="unlock_account">
                                            <input type="hidden" name="user_id" value="<?php echo $user['admin_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-warning btn-icon ms-1" title="Unlock Account">
                                                <i class="fas fa-unlock"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-center text-muted mb-0">No users found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="manage_users.php" id="addUserForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">
                        <i class="fas fa-user-plus"></i> Add New User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_username" class="form-label form-label-custom">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-custom" id="add_username" name="username"
                               required minlength="3" maxlength="50" pattern="[a-zA-Z0-9_]+"
                               placeholder="Enter username (letters, numbers, underscore)">
                        <div class="form-text">Only letters, numbers, and underscores allowed.</div>
                    </div>
                    <div class="mb-3">
                        <label for="add_password" class="form-label form-label-custom">Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control form-control-custom" id="add_password" name="password"
                                   required minlength="6" placeholder="Minimum 6 characters">
                            <button type="button" class="btn btn-outline-secondary toggle-password" data-target="add_password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="add_full_name" class="form-label form-label-custom">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-custom" id="add_full_name" name="full_name"
                               required maxlength="100" placeholder="Enter full name">
                    </div>
                    <div class="mb-3">
                        <label for="add_email" class="form-label form-label-custom">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control form-control-custom" id="add_email" name="email"
                               required maxlength="100" placeholder="Enter email address">
                    </div>
                    <div class="mb-3">
                        <label for="add_role" class="form-label form-label-custom">Role <span class="text-danger">*</span></label>
                        <select class="form-select form-control-custom" id="add_role" name="role" required>
                            <option value="">-- Select Role --</option>
                            <option value="admin">Admin</option>
                            <option value="operator">Staff</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-custom">
                        <i class="fas fa-save"></i> Add User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="manage_users.php" id="editUserForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">
                        <i class="fas fa-edit"></i> Edit User: <span id="edit_username_display"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_full_name" class="form-label form-label-custom">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-custom" id="edit_full_name" name="full_name"
                               required maxlength="100" placeholder="Enter full name">
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label form-label-custom">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control form-control-custom" id="edit_email" name="email"
                               required maxlength="100" placeholder="Enter email address">
                    </div>
                    <div class="mb-3" id="edit_role_group">
                        <label for="edit_role" class="form-label form-label-custom">Role <span class="text-danger">*</span></label>
                        <select class="form-select form-control-custom" id="edit_role" name="role" required>
                            <option value="admin">Admin</option>
                            <option value="operator">Staff</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_password" class="form-label form-label-custom">Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control form-control-custom" id="edit_password" name="password"
                                   minlength="6" placeholder="Leave blank to keep current password">
                            <button type="button" class="btn btn-outline-secondary toggle-password" data-target="edit_password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">Leave blank to keep the current password. Minimum 6 characters if changing.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-custom">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Edit User button click - populate modal with current data
    $('.edit-user-btn').on('click', function() {
        var userId    = $(this).data('id');
        var username  = $(this).data('username');
        var fullName  = $(this).data('fullname');
        var email     = $(this).data('email');
        var role      = $(this).data('role');
        var isSelf    = $(this).data('is-self');

        // Populate fields
        $('#edit_user_id').val(userId);
        $('#edit_username_display').text(username);
        $('#edit_full_name').val(fullName);
        $('#edit_email').val(email);
        $('#edit_password').val('');

        // Handle role field
        if (isSelf == 1 || role === 'sysadmin') {
            // For own account or sysadmin: show role as disabled, add hidden input with actual value
            $('#edit_role').val(role).prop('disabled', true);
            // Remove any previously added hidden role input
            $('#edit_hidden_role').remove();
            // Add hidden input so the role value is submitted
            $('<input>').attr({
                type: 'hidden',
                id: 'edit_hidden_role',
                name: 'role',
                value: role
            }).appendTo('#editUserForm');
            if (role === 'sysadmin') {
                // Temporarily add sysadmin option so it can display
                if ($('#edit_role option[value="sysadmin"]').length === 0) {
                    $('#edit_role').prepend('<option value="sysadmin">System Admin</option>');
                }
                $('#edit_role').val('sysadmin');
            }
        } else {
            // For other users: enable role selection
            $('#edit_role').prop('disabled', false);
            $('#edit_hidden_role').remove();
            // Remove sysadmin option if present
            $('#edit_role option[value="sysadmin"]').remove();
            $('#edit_role').val(role);
        }

        // Open the modal
        var editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
        editModal.show();
    });

    // Toggle password visibility
    $('.toggle-password').on('click', function() {
        var targetId = $(this).data('target');
        var input = $('#' + targetId);
        var icon = $(this).find('i');

        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Reset Add modal form on close
    $('#addUserModal').on('hidden.bs.modal', function() {
        $('#addUserForm')[0].reset();
    });

    // Clean up Edit modal on close
    $('#editUserModal').on('hidden.bs.modal', function() {
        $('#editUserForm')[0].reset();
        $('#edit_role').prop('disabled', false);
        $('#edit_hidden_role').remove();
        $('#edit_role option[value="sysadmin"]').remove();
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
