<?php
/**
 * School Settings - System Administrator Only
 */
require_once '../config/database.php';
require_once '../includes/session.php';

requireRole(['sysadmin']);

$pageTitle = 'School Settings';
$error = '';
$success = '';

// Fetch current settings
$settings = getSettings();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = getDB();

        // Sanitize and update text settings
        $school_name = sanitize($_POST['school_name'] ?? '');
        $school_address = sanitize($_POST['school_address'] ?? '');
        $school_phone = sanitize($_POST['school_phone'] ?? '');
        $school_email = sanitize($_POST['school_email'] ?? '');

        if (empty($school_name)) {
            throw new Exception('School Name is required.');
        }

        if (!empty($school_email) && !isValidEmail($school_email)) {
            throw new Exception('Invalid email format.');
        }

        if (!empty($school_phone) && !isValidPhone($school_phone)) {
            throw new Exception('Invalid phone number format.');
        }

        // Update each setting
        $settingsToUpdate = [
            'school_name' => $school_name,
            'school_address' => $school_address,
            'school_phone' => $school_phone,
            'school_email' => $school_email
        ];

        foreach ($settingsToUpdate as $key => $val) {
            $db->query("UPDATE settings SET setting_value = :val WHERE setting_key = :key", [
                'val' => $val,
                'key' => $key
            ]);
        }

        // Update fee mode setting
        $fee_mode = sanitize($_POST['fee_mode'] ?? 'annual');
        if (!in_array($fee_mode, ['annual', 'monthly'])) {
            $fee_mode = 'annual';
        }
        $db->query(
            "INSERT INTO settings (setting_key, setting_value) VALUES (:key, :val)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
            ['key' => 'fee_mode', 'val' => $fee_mode]
        );

        // Handle school logo upload and removal
        if (isset($_POST['remove_school_logo']) && !empty($_POST['remove_school_logo'])) {
            // Remove existing logo files
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/assets/img/';
            foreach ($allowedExtensions as $ext) {
                $logoFile = $uploadDir . 'school_logo.' . $ext;
                if (file_exists($logoFile)) {
                    unlink($logoFile);
                }
            }
            $db->query("UPDATE settings SET setting_value = '' WHERE setting_key = 'school_logo'");
        } elseif (isset($_FILES['school_logo']) && $_FILES['school_logo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['school_logo'];
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            $maxSize = 2 * 1024 * 1024; // 2MB

            // Validate file type
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($file['type'], $allowedTypes) || !in_array($fileExtension, $allowedExtensions)) {
                throw new Exception('Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.');
            }

            // Validate file size
            if ($file['size'] > $maxSize) {
                throw new Exception('File size exceeds the maximum limit of 2MB.');
            }

            // Set upload path relative to project root
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/assets/img/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileName = 'school_logo.' . $fileExtension;
            $uploadPath = $uploadDir . $fileName;
            $relativePath = 'assets/img/' . $fileName;

            // Remove old logo files if they exist (different extension)
            foreach ($allowedExtensions as $ext) {
                $oldFile = $uploadDir . 'school_logo.' . $ext;
                if (file_exists($oldFile) && $oldFile !== $uploadPath) {
                    unlink($oldFile);
                }
            }

            // Move uploaded file (overwrites if same name exists)
            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                throw new Exception('Failed to upload logo file. Please try again.');
            }

            // Update logo path in settings
            $db->query("UPDATE settings SET setting_value = :val WHERE setting_key = :key", [
                'val' => $relativePath,
                'key' => 'school_logo'
            ]);
        }

        // Handle site icon (favicon) upload and removal
        if (isset($_POST['remove_site_icon']) && !empty($_POST['remove_site_icon'])) {
            // Remove existing icon files
            $allowedExtensions = ['ico', 'jpg', 'jpeg', 'png', 'gif', 'svg'];
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/assets/img/';
            foreach ($allowedExtensions as $ext) {
                $iconFile = $uploadDir . 'site_icon.' . $ext;
                if (file_exists($iconFile)) {
                    unlink($iconFile);
                }
            }
            $db->query("UPDATE settings SET setting_value = '' WHERE setting_key = 'site_icon'");
        } elseif (isset($_FILES['site_icon']) && $_FILES['site_icon']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['site_icon'];
            $allowedTypes = ['image/x-icon', 'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/svg+xml'];
            $allowedExtensions = ['ico', 'jpg', 'jpeg', 'png', 'gif', 'svg'];
            $maxSize = 1 * 1024 * 1024; // 1MB for favicon

            // Validate file type
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($file['type'], $allowedTypes) || !in_array($fileExtension, $allowedExtensions)) {
                throw new Exception('Invalid file type for site icon. Allowed: ICO, JPG, PNG, GIF, SVG.');
            }

            // Validate file size
            if ($file['size'] > $maxSize) {
                throw new Exception('Site icon file size exceeds the maximum limit of 1MB.');
            }

            // Set upload path relative to project root
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/assets/img/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileName = 'site_icon.' . $fileExtension;
            $uploadPath = $uploadDir . $fileName;
            $relativePath = 'assets/img/' . $fileName;

            // Remove old icon files if they exist (different extension)
            foreach ($allowedExtensions as $ext) {
                $oldFile = $uploadDir . 'site_icon.' . $ext;
                if (file_exists($oldFile) && $oldFile !== $uploadPath) {
                    unlink($oldFile);
                }
            }

            // Move uploaded file (overwrites if same name exists)
            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                throw new Exception('Failed to upload site icon. Please try again.');
            }

            // Update icon path in settings
            $db->query(
                "INSERT INTO settings (setting_key, setting_value) VALUES (:key, :val)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
                ['key' => 'site_icon', 'val' => $relativePath]
            );
        }

        // Refresh settings after update
        redirectWithMessage('/admin/settings.php', 'success', 'School settings updated successfully!');

    } catch (Exception $e) {
        $error = $e->getMessage();
        // Re-fetch settings to show current values on error
        $settings = getSettings();
    }
}

require_once '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="fas fa-cog"></i> School Settings
        </h2>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-school"></i> School Information
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label-custom">School Name <span class="text-danger">*</span></label>
                            <input type="text" name="school_name" class="form-control form-control-custom"
                                   value="<?php echo htmlspecialchars($settings['school_name'] ?? ''); ?>" required>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label-custom">School Address</label>
                            <textarea name="school_address" class="form-control form-control-custom" rows="3"><?php echo htmlspecialchars($settings['school_address'] ?? ''); ?></textarea>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label-custom">School Phone</label>
                            <input type="text" name="school_phone" class="form-control form-control-custom"
                                   value="<?php echo htmlspecialchars($settings['school_phone'] ?? ''); ?>" maxlength="15">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label-custom">School Email</label>
                            <input type="email" name="school_email" class="form-control form-control-custom"
                                   value="<?php echo htmlspecialchars($settings['school_email'] ?? ''); ?>">
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label-custom">School Logo</label>
                            <input type="file" name="school_logo" class="form-control form-control-custom"
                                   accept=".jpg,.jpeg,.png,.gif">
                            <small class="text-muted">Accepted formats: JPG, JPEG, PNG, GIF. Maximum size: 2MB.</small>
                        </div>

                        <?php if (!empty($settings['school_logo']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $settings['school_logo'])): ?>
                        <div class="col-md-12 mb-3">
                            <label class="form-label-custom">Current Logo</label>
                            <div class="p-3 border rounded bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <img src="/<?php echo htmlspecialchars($settings['school_logo']); ?>"
                                     alt="School Logo"
                                     style="max-height: 120px; max-width: 70%;"
                                     class="img-fluid">
                                <button type="button" class="btn btn-sm btn-danger" onclick="document.getElementById('removeLogoConfirm').style.display='block'">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                            </div>
                            <div id="removeLogoConfirm" style="display:none;" class="alert alert-warning mt-2">
                                <p class="mb-2">Are you sure you want to remove the school logo?</p>
                                <button type="button" class="btn btn-sm btn-danger" onclick="document.querySelector('input[name=remove_school_logo]').value='1'; document.querySelector('form').submit()">
                                    <i class="fas fa-check"></i> Yes, Remove Logo
                                </button>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="document.getElementById('removeLogoConfirm').style.display='none'">
                                    Cancel
                                </button>
                            </div>
                            <input type="hidden" name="remove_school_logo" value="0">
                        </div>
                        <?php endif; ?>

                        <div class="col-md-12 mb-3">
                            <label class="form-label-custom">Site Icon / Favicon</label>
                            <input type="file" name="site_icon" class="form-control form-control-custom"
                                   accept=".ico,.jpg,.jpeg,.png,.gif,.svg">
                            <small class="text-muted">Accepted formats: ICO, JPG, JPEG, PNG, GIF, SVG. Maximum size: 1MB. This icon appears in browser tabs.</small>
                        </div>

                        <?php if (!empty($settings['site_icon']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $settings['site_icon'])): ?>
                        <div class="col-md-12 mb-3">
                            <label class="form-label-custom">Current Site Icon</label>
                            <div class="p-3 border rounded bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <img src="/<?php echo htmlspecialchars($settings['site_icon']); ?>"
                                     alt="Site Icon"
                                     style="max-height: 60px; max-width: 70%;"
                                     class="img-fluid">
                                <button type="button" class="btn btn-sm btn-danger" onclick="document.getElementById('removeIconConfirm').style.display='block'">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                            </div>
                            <div id="removeIconConfirm" style="display:none;" class="alert alert-warning mt-2">
                                <p class="mb-2">Are you sure you want to remove the site icon?</p>
                                <button type="button" class="btn btn-sm btn-danger" onclick="document.querySelector('input[name=remove_site_icon]').value='1'; document.querySelector('form').submit()">
                                    <i class="fas fa-check"></i> Yes, Remove Icon
                                </button>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="document.getElementById('removeIconConfirm').style.display='none'">
                                    Cancel
                                </button>
                            </div>
                            <input type="hidden" name="remove_site_icon" value="0">
                        </div>
                        <?php endif; ?>

                        <!-- Fee Configuration -->
                        <hr class="my-4">
                        <h6 class="text-muted mb-3"><i class="fas fa-calendar-alt"></i> Fee Configuration</h6>
                        <div class="col-md-12 mb-3">
                            <label class="form-label-custom">Fee Collection Mode</label>
                            <div class="form-check form-switch fee-mode-switch mt-2">
                                <input class="form-check-input" type="checkbox"
                                       id="feeModeSwitchCheckbox"
                                       <?php echo (($settings['fee_mode'] ?? 'annual') === 'monthly') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="feeModeSwitchCheckbox">
                                    <span id="feeModeLabel">
                                        <?php echo (($settings['fee_mode'] ?? 'annual') === 'monthly') ? 'Monthly Mode' : 'Annual Mode'; ?>
                                    </span>
                                </label>
                            </div>
                            <input type="hidden" name="fee_mode" id="feeModeInput"
                                   value="<?php echo htmlspecialchars($settings['fee_mode'] ?? 'annual'); ?>">
                            <small class="text-muted d-block mt-1">
                                <strong>Annual:</strong> One fee structure per class for the entire year. &nbsp;
                                <strong>Monthly:</strong> Different fee amounts for each month (April-March).
                            </small>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-custom">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                        <a href="/admin/dashboard.php" class="btn btn-secondary btn-custom">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-info-circle"></i> Help
            </div>
            <div class="card-body">
                <p class="text-muted mb-2">
                    <strong>School Name</strong> - Displayed in the navigation bar and on receipts.
                </p>
                <p class="text-muted mb-2">
                    <strong>School Address</strong> - Shown on printed receipts and reports.
                </p>
                <p class="text-muted mb-2">
                    <strong>School Phone</strong> - Contact number displayed on reports.
                </p>
                <p class="text-muted mb-2">
                    <strong>School Email</strong> - Email address displayed on reports.
                </p>
                <p class="text-muted mb-2">
                    <strong>School Logo</strong> - Displayed in the navigation bar and on printed receipts. Upload a clear image for best results.
                </p>
                <p class="text-muted mb-2">
                    <strong>Site Icon</strong> - Also known as favicon, appears in browser tabs and bookmarks. Recommended: ICO format at 32x32 pixels.
                </p>
                <hr>
                <p class="text-muted mb-2">
                    <strong>Annual Mode</strong> - One fee structure per class for the entire academic year.
                </p>
                <p class="text-muted mb-0">
                    <strong>Monthly Mode</strong> - Set different fees for each month (April to March). Useful when fee amounts vary month to month.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#feeModeSwitchCheckbox').on('change', function() {
        var isMonthly = $(this).is(':checked');
        $('#feeModeInput').val(isMonthly ? 'monthly' : 'annual');
        $('#feeModeLabel').text(isMonthly ? 'Monthly Mode' : 'Annual Mode');
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
