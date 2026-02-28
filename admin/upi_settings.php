<?php
/**
 * UPI Payment Settings - System Administrator Only
 * Manage UPI ID, payee name, and enable/disable UPI payments
 */
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/upi_helper.php';

requireRole(['sysadmin']);

$pageTitle = 'UPI Payment Settings';
$error = '';
$success = '';

// Fetch current UPI settings
$upiSettings = getUpiSettings();
$siteSettings = getSettings();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token. Please try again.');
        }

        $upiEnabled = isset($_POST['upi_enabled']) && $_POST['upi_enabled'] === '1';
        $upiId = trim($_POST['upi_id'] ?? '');
        $payeeName = sanitize($_POST['upi_payee_name'] ?? '');

        // Validate UPI ID format (must contain @)
        if ($upiEnabled && empty($upiId)) {
            throw new Exception('UPI ID is required when UPI payments are enabled.');
        }

        if (!empty($upiId) && strpos($upiId, '@') === false) {
            throw new Exception('Invalid UPI ID format. It should contain @ (e.g., merchant@upi).');
        }

        if (empty($payeeName)) {
            $payeeName = $siteSettings['school_name'] ?? 'School';
        }

        // Store old values for audit
        $oldValues = [
            'upi_enabled' => $upiSettings['upi_enabled'],
            'upi_id' => !empty($upiSettings['upi_id']) ? '***masked***' : '',
            'upi_payee_name' => $upiSettings['upi_payee_name']
        ];

        // Encrypt UPI ID before storage
        $encryptedUpiId = !empty($upiId) ? encryptData($upiId) : '';

        // Save settings
        $settingsToSave = [
            'upi_enabled' => ['value' => $upiEnabled, 'type' => 'boolean'],
            'upi_id' => ['value' => $encryptedUpiId, 'type' => 'string'],
            'upi_payee_name' => ['value' => $payeeName, 'type' => 'string']
        ];

        $result = SettingsHelper::setMultiple($settingsToSave, getAdminId());

        if (!$result) {
            throw new Exception('Failed to save UPI settings. Please try again.');
        }

        // Audit log
        $newValues = [
            'upi_enabled' => $upiEnabled,
            'upi_id' => !empty($upiId) ? '***masked***' : '',
            'upi_payee_name' => $payeeName
        ];
        logAudit(getAdminId(), 'UPI_SETTINGS_UPDATED', 'system_settings', null,
                 json_encode($oldValues), json_encode($newValues));

        redirectWithMessage('/admin/upi_settings.php', 'success', 'UPI payment settings updated successfully!');

    } catch (Exception $e) {
        $error = $e->getMessage();
        // Re-fetch settings
        $upiSettings = getUpiSettings();
    }
}

$csrfToken = generateCSRFToken();

require_once '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="fas fa-qrcode"></i> UPI Payment Settings
        </h2>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-cog"></i> UPI Configuration
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <form method="POST" action="" id="upiSettingsForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

                    <!-- UPI Enabled Toggle -->
                    <div class="mb-4">
                        <label class="form-label-custom">UPI Payment Status</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="upiEnabledCheckbox"
                                   <?php echo $upiSettings['upi_enabled'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="upiEnabledCheckbox">
                                <span id="upiStatusLabel">
                                    <?php echo $upiSettings['upi_enabled'] ? 'Enabled - Students can pay via UPI' : 'Disabled - UPI payments are turned off'; ?>
                                </span>
                            </label>
                        </div>
                        <input type="hidden" name="upi_enabled" id="upiEnabledInput"
                               value="<?php echo $upiSettings['upi_enabled'] ? '1' : '0'; ?>">
                    </div>

                    <!-- UPI ID -->
                    <div class="mb-3">
                        <label class="form-label-custom">UPI ID (VPA) <span class="text-danger">*</span></label>
                        <input type="text" name="upi_id" id="upiIdInput"
                               class="form-control form-control-custom"
                               value="<?php echo htmlspecialchars($upiSettings['upi_id']); ?>"
                               placeholder="e.g., schoolname@upi or 9876543210@paytm">
                        <small class="text-muted">The UPI Virtual Payment Address where students will send payments. This will be encrypted in the database.</small>
                    </div>

                    <!-- Payee Name -->
                    <div class="mb-3">
                        <label class="form-label-custom">Payee Name</label>
                        <input type="text" name="upi_payee_name" id="payeeNameInput"
                               class="form-control form-control-custom"
                               value="<?php echo htmlspecialchars($upiSettings['upi_payee_name'] ?: ($siteSettings['school_name'] ?? '')); ?>"
                               placeholder="School / Institution Name">
                        <small class="text-muted">Name displayed to students when scanning the QR code. Defaults to school name if empty.</small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-custom">
                            <i class="fas fa-save"></i> Save UPI Settings
                        </button>
                        <a href="/admin/settings.php" class="btn btn-secondary btn-custom">
                            <i class="fas fa-arrow-left"></i> Back to Settings
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- QR Code Preview -->
        <div class="card card-custom mb-3">
            <div class="card-header">
                <i class="fas fa-qrcode"></i> QR Code Preview
            </div>
            <div class="card-body text-center">
                <div id="qrPreview" class="mb-3" style="display:inline-block;"></div>
                <div id="qrPlaceholder" class="text-muted py-4">
                    <i class="fas fa-qrcode" style="font-size: 48px; opacity: 0.3;"></i>
                    <p class="mt-2 mb-0">Enter UPI ID and click<br>"Generate Preview" to see QR code</p>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" id="generatePreviewBtn">
                    <i class="fas fa-eye"></i> Generate Preview
                </button>
                <div id="previewUpiId" class="mt-2 small text-muted" style="display:none;"></div>
            </div>
        </div>

        <!-- Help Card -->
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-info-circle"></i> Help
            </div>
            <div class="card-body">
                <p class="text-muted mb-2">
                    <strong>UPI ID</strong> - Your Virtual Payment Address (VPA) for receiving payments. Format: <code>name@bank</code>
                </p>
                <p class="text-muted mb-2">
                    <strong>Payee Name</strong> - Shown to students on the payment screen and QR code.
                </p>
                <p class="text-muted mb-2">
                    <strong>Enable/Disable</strong> - Toggle to control whether students see the "Pay via UPI" option.
                </p>
                <hr>
                <p class="text-muted mb-2">
                    <strong>How it works:</strong>
                </p>
                <ol class="text-muted small mb-0">
                    <li>Configure your UPI ID here</li>
                    <li>Students scan QR code and pay</li>
                    <li>Students submit UTR number</li>
                    <li>Admin verifies and approves payment</li>
                    <li>Receipt is auto-generated</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- QR Code JS Library -->
<script src="/assets/js/qrcode.min.js"></script>
<script>
$(document).ready(function() {
    // Toggle handler
    $('#upiEnabledCheckbox').on('change', function() {
        var enabled = $(this).is(':checked');
        $('#upiEnabledInput').val(enabled ? '1' : '0');
        $('#upiStatusLabel').text(enabled
            ? 'Enabled - Students can pay via UPI'
            : 'Disabled - UPI payments are turned off');
    });

    // QR Code Preview
    var qrcode = null;
    $('#generatePreviewBtn').on('click', function() {
        var upiId = $('#upiIdInput').val().trim();
        var payeeName = $('#payeeNameInput').val().trim() || 'School';

        if (!upiId || upiId.indexOf('@') === -1) {
            alert('Please enter a valid UPI ID (must contain @)');
            return;
        }

        var upiUrl = 'upi://pay?pa=' + encodeURIComponent(upiId)
                   + '&pn=' + encodeURIComponent(payeeName)
                   + '&am=1.00&cu=INR&tn=TEST';

        // Clear previous QR
        $('#qrPreview').empty();
        $('#qrPlaceholder').hide();

        qrcode = new QRCode(document.getElementById('qrPreview'), {
            text: upiUrl,
            width: 200,
            height: 200,
            colorDark: '#000000',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.M
        });

        $('#previewUpiId').html('<strong>UPI ID:</strong> ' + $('<span>').text(upiId).html() + '<br><small>Test amount: ₹ 1.00</small>').show();
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
