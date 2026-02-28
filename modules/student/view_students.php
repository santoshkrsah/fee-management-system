<?php
/**
 * View All Students (Paginated)
 */
require_once '../../config/database.php';
require_once '../../includes/session.php';

requireLogin();

$pageTitle = 'View Students';

// Selected academic session
$selectedSession = getSelectedSession();

// Filters
$search = sanitize($_GET['search'] ?? '');
$class_filter = (int)($_GET['class_id'] ?? 0);
$section_filter = (int)($_GET['section_id'] ?? 0);

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

try {
    $db = getDB();

    // Build WHERE clause (shared by COUNT and SELECT)
    $where = " WHERE s.status = 'active' AND s.academic_year = :academic_year";
    $params = ['academic_year' => $selectedSession];

    if (!empty($search)) {
        $where .= " AND (
            s.admission_no LIKE :search1 OR
            s.first_name LIKE :search2 OR
            s.last_name LIKE :search3 OR
            s.father_name LIKE :search4 OR
            s.contact_number LIKE :search5 OR
            s.roll_number LIKE :search6
        )";
        $searchVal = '%' . $search . '%';
        $params['search1'] = $searchVal;
        $params['search2'] = $searchVal;
        $params['search3'] = $searchVal;
        $params['search4'] = $searchVal;
        $params['search5'] = $searchVal;
        $params['search6'] = $searchVal;
    }

    if ($class_filter > 0) {
        $where .= " AND s.class_id = :class_id";
        $params['class_id'] = $class_filter;
    }

    if ($section_filter > 0) {
        $where .= " AND s.section_id = :section_id";
        $params['section_id'] = $section_filter;
    }

    // COUNT query for total
    $countQuery = "SELECT COUNT(*) AS total FROM students s" . $where;
    $countRow = $db->fetchOne($countQuery, $params);
    $totalStudents = (int)($countRow['total'] ?? 0);
    $totalPages = max(1, (int)ceil($totalStudents / $perPage));

    // Clamp page
    if ($page > $totalPages) {
        $page = $totalPages;
        $offset = ($page - 1) * $perPage;
    }

    // Main query with LIMIT/OFFSET (integers embedded directly — safe via intval)
    $query = "
        SELECT
            s.*,
            c.class_name,
            sec.section_name,
            CONCAT(s.first_name, ' ', s.last_name) as full_name
        FROM students s
        JOIN classes c ON s.class_id = c.class_id
        JOIN sections sec ON s.section_id = sec.section_id
    " . $where . "
        ORDER BY c.class_numeric, sec.section_name, s.roll_number, s.first_name
        LIMIT " . intval($perPage) . " OFFSET " . intval($offset);

    $students = $db->fetchAll($query, $params);

    // Get classes for filter
    $classes = $db->fetchAll("SELECT * FROM classes WHERE status = 'active' ORDER BY class_numeric");

} catch(Exception $e) {
    error_log($e->getMessage());
    $students = [];
    $classes = [];
    $totalStudents = 0;
    $totalPages = 1;
}

// Helper: build pagination URL preserving current filters
function buildPageUrl($pageNum) {
    $params = $_GET;
    $params['page'] = $pageNum;
    return '?' . http_build_query($params);
}

$showingFrom = $totalStudents > 0 ? $offset + 1 : 0;
$showingTo = min($offset + $perPage, $totalStudents);

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="fas fa-users"></i> View Students
            <span class="badge bg-primary ms-2"><?php echo htmlspecialchars($selectedSession); ?></span>
            <a href="add_student.php" class="btn btn-primary btn-custom float-end">
                <i class="fas fa-plus"></i> Add New Student
            </a>
        </h2>
    </div>
</div>

<!-- Filter Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control form-control-custom"
                               placeholder="Search by name, admission no, father name..."
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>

                    <div class="col-md-3">
                        <select name="class_id" class="form-control form-control-custom">
                            <option value="">All Classes</option>
                            <?php foreach($classes as $class): ?>
                            <option value="<?php echo $class['class_id']; ?>"
                                <?php echo ($class_filter == $class['class_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['class_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary btn-custom me-2">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <a href="view_students.php" class="btn btn-secondary btn-custom">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Students Table -->
<div class="row">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>
                    <i class="fas fa-list"></i> Total Students: <?php echo $totalStudents; ?>
                    <?php if ($totalStudents > 0): ?>
                        <small class="text-muted ms-2">(Showing <?php echo $showingFrom; ?>-<?php echo $showingTo; ?>)</small>
                    <?php endif; ?>
                </span>
                <?php if (canDelete() && count($students) > 0): ?>
                <span>
                    <button type="button" class="btn btn-success btn-sm me-1" id="bulkExportBtn" disabled onclick="exportSelected()">
                        <i class="fas fa-file-export"></i> Export Selected (<span id="exportCount">0</span>)
                    </button>
                    <button type="submit" form="bulkDeleteForm" class="btn btn-danger btn-sm" id="bulkDeleteBtn" disabled onclick="return confirm('Are you sure you want to delete the selected students? This action cannot be undone.');">
                        <i class="fas fa-trash"></i> Delete Selected (<span id="selectedCount">0</span>)
                    </button>
                </span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (count($students) > 0): ?>
                <?php if (canDelete()): ?>
                <form id="bulkDeleteForm" method="POST" action="bulk_delete_students.php">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-custom table-hover">
                        <thead>
                            <tr>
                                <?php if (canDelete()): ?>
                                <th style="width: 40px;"><input type="checkbox" id="selectAll" class="form-check-input"></th>
                                <?php endif; ?>
                                <th>Admission No</th>
                                <th>Name</th>
                                <th>Father Name</th>
                                <th>Class</th>
                                <th>Section</th>
                                <th>Roll No</th>
                                <th>Contact</th>
                                <?php if (canEdit()): ?>
                                <th>Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($students as $student): ?>
                            <tr>
                                <?php if (canDelete()): ?>
                                <td><input type="checkbox" name="student_ids[]" value="<?php echo $student['student_id']; ?>" class="form-check-input row-checkbox"></td>
                                <?php endif; ?>
                                <td><strong><?php echo htmlspecialchars($student['admission_no']); ?></strong></td>
                                <td>
                                    <a href="javascript:void(0)" class="text-decoration-none student-profile-link"
                                       data-student-id="<?php echo $student['student_id']; ?>">
                                        <?php echo htmlspecialchars($student['full_name']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($student['father_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['class_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['section_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['roll_number'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($student['contact_number']); ?></td>
                                <?php if (canEdit()): ?>
                                <td>
                                    <a href="edit_student.php?id=<?php echo $student['student_id']; ?>"
                                       class="btn btn-sm btn-warning btn-icon" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if (canDelete()): ?>
                                    <a href="delete_student.php?id=<?php echo $student['student_id']; ?>"
                                       class="btn btn-sm btn-danger btn-icon delete-btn" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (canDelete()): ?>
                </form>
                <?php endif; ?>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav aria-label="Student pagination" class="mt-3">
                    <ul class="pagination pagination-custom justify-content-center mb-0">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo buildPageUrl(1); ?>" title="First">&laquo;</a>
                        </li>
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo buildPageUrl($page - 1); ?>" title="Previous">&lsaquo;</a>
                        </li>

                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        if ($startPage > 1): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif;

                        for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="<?php echo buildPageUrl($i); ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor;

                        if ($endPage < $totalPages): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>

                        <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo buildPageUrl($page + 1); ?>" title="Next">&rsaquo;</a>
                        </li>
                        <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo buildPageUrl($totalPages); ?>" title="Last">&raquo;</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>

                <?php else: ?>
                <p class="text-center text-muted">No students found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (canDelete()): ?>
<script>
$(document).ready(function() {
    function updateSelectedCount() {
        var count = $('.row-checkbox:checked').length;
        $('#selectedCount').text(count);
        $('#exportCount').text(count);
        $('#bulkDeleteBtn').prop('disabled', count === 0);
        $('#bulkExportBtn').prop('disabled', count === 0);
    }

    $('#selectAll').on('change', function() {
        $('.row-checkbox').prop('checked', this.checked);
        updateSelectedCount();
    });

    $(document).on('change', '.row-checkbox', function() {
        var total = $('.row-checkbox').length;
        var checked = $('.row-checkbox:checked').length;
        $('#selectAll').prop('checked', total === checked);
        updateSelectedCount();
    });
});

function exportSelected() {
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = 'export_students.php';
    form.style.display = 'none';

    var csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = 'csrf_token';
    csrf.value = '<?php echo generateCSRFToken(); ?>';
    form.appendChild(csrf);

    $('.row-checkbox:checked').each(function() {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'student_ids[]';
        input.value = this.value;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}
</script>
<?php endif; ?>

<!-- Student Profile Modal -->
<div class="modal fade" id="studentProfileModal" tabindex="-1" aria-labelledby="studentProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="studentProfileModalLabel">
                    <i class="fas fa-user"></i> Student Profile
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="studentProfileBody">
                <!-- Loading spinner -->
                <div id="profileLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading student profile...</p>
                </div>

                <!-- Profile content (hidden initially) -->
                <div id="profileError" class="alert alert-danger" style="display:none;">
                    <i class="fas fa-exclamation-circle"></i> <span id="profileErrorMsg">Error loading profile.</span>
                </div>

                <div id="profileContent" style="display:none;">

                    <!-- Payment Status Badge -->
                    <div class="text-center mb-3">
                        <span id="profileStatusBadge" class="badge fs-6"></span>
                    </div>

                    <!-- Personal Details Section -->
                    <div class="card card-custom mb-3">
                        <div class="card-header py-2">
                            <i class="fas fa-id-card"></i> Personal Details
                        </div>
                        <div class="card-body py-2">
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <small class="text-muted">Full Name</small><br>
                                    <strong id="profileName"></strong>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <small class="text-muted">Admission No</small><br>
                                    <strong id="profileAdmNo"></strong>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <small class="text-muted">Father's Name</small><br>
                                    <span id="profileFather"></span>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <small class="text-muted">Mother's Name</small><br>
                                    <span id="profileMother"></span>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <small class="text-muted">Date of Birth</small><br>
                                    <span id="profileDob"></span>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <small class="text-muted">Gender</small><br>
                                    <span id="profileGender"></span>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <small class="text-muted">Class / Section</small><br>
                                    <span id="profileClass"></span>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <small class="text-muted">Roll Number</small><br>
                                    <span id="profileRoll"></span>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <small class="text-muted">Contact</small><br>
                                    <span id="profileContact"></span>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <small class="text-muted">Email</small><br>
                                    <span id="profileEmail"></span>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <small class="text-muted">Address</small><br>
                                    <span id="profileAddress"></span>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <small class="text-muted">Admission Date</small><br>
                                    <span id="profileAdmDate"></span>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <small class="text-muted">Academic Year</small><br>
                                    <span id="profileAcademicYear"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Fee Summary Cards -->
                    <div class="row mb-3">
                        <div class="col-4">
                            <div class="card text-center border-primary">
                                <div class="card-body py-2">
                                    <small class="text-muted">Total Fee</small>
                                    <h5 class="mb-0 text-primary" id="profileTotalFee"></h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card text-center border-success">
                                <div class="card-body py-2">
                                    <small class="text-muted">Paid</small>
                                    <h5 class="mb-0 text-success" id="profileTotalPaid"></h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card text-center border-danger">
                                <div class="card-body py-2">
                                    <small class="text-muted">Balance</small>
                                    <h5 class="mb-0 text-danger" id="profileBalance"></h5>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly Breakdown (only in monthly mode) -->
                    <div id="profileMonthlyBreakdown" style="display:none;">
                        <div class="card card-custom mb-3">
                            <div class="card-header py-2">
                                <i class="fas fa-calendar-alt"></i> Month-wise Fee Status
                            </div>
                            <div class="card-body py-2">
                                <div id="profileMonthlyTable"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment History Table -->
                    <div class="card card-custom">
                        <div class="card-header py-2">
                            <i class="fas fa-history"></i> Payment History
                            <span id="profilePaymentCount" class="badge bg-primary ms-1"></span>
                        </div>
                        <div class="card-body py-2">
                            <div id="profilePaymentTable"></div>
                            <div id="profileNoPayments" style="display:none;">
                                <p class="text-center text-muted mb-0">No payments recorded yet.</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <div class="modal-footer">
                <a href="#" id="profileCollectFeeBtn" class="btn btn-success btn-custom">
                    <i class="fas fa-money-bill-wave"></i> Collect Fee
                </a>
                <button type="button" class="btn btn-secondary btn-custom" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Move modal to body to prevent stacking context issues with backdrop
    $('#studentProfileModal').appendTo('body');

    // Open student profile modal
    $(document).on('click', '.student-profile-link', function(e) {
        e.preventDefault();
        var studentId = $(this).data('student-id');
        openStudentProfile(studentId);
    });

    function openStudentProfile(studentId) {
        $('#profileLoading').show();
        $('#profileContent').hide();
        $('#profileError').hide();

        var modalEl = document.getElementById('studentProfileModal');
        var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();

        $.ajax({
            url: 'ajax_get_student_profile.php',
            method: 'POST',
            data: { student_id: studentId },
            dataType: 'json',
            success: function(response) {
                $('#profileLoading').hide();
                if (response.success) {
                    $('#profileError').hide();
                    populateProfile(response);
                    $('#profileContent').show();
                } else {
                    $('#profileErrorMsg').text(response.message || 'Failed to load profile.');
                    $('#profileError').show();
                }
            },
            error: function(xhr, status, error) {
                $('#profileLoading').hide();
                $('#profileErrorMsg').text('Error loading profile. Please try again.');
                $('#profileError').show();
                console.error('Profile AJAX error:', status, error, xhr.responseText);
            }
        });
    }

    function populateProfile(data) {
        var s = data.student;
        var f = data.fee_summary;
        var payments = data.payments;

        // Personal details
        $('#profileName').text(s.full_name);
        $('#profileAdmNo').text(s.admission_no);
        $('#profileFather').text(s.father_name);
        $('#profileMother').text(s.mother_name || '-');
        $('#profileDob').text(formatDateDisplay(s.date_of_birth));
        $('#profileGender').text(s.gender);
        $('#profileClass').text(s.class_name + ' - ' + s.section_name);
        $('#profileRoll').text(s.roll_number || '-');
        $('#profileContact').text(s.contact_number);
        $('#profileEmail').text(s.email || '-');
        $('#profileAddress').text(s.address);
        $('#profileAdmDate').text(formatDateDisplay(s.admission_date));
        $('#profileAcademicYear').text(s.academic_year);

        // Fee summary
        $('#profileTotalFee').text(formatCurrencyJS(f.total_fee));
        $('#profileTotalPaid').text(formatCurrencyJS(f.total_paid));
        $('#profileBalance').text(formatCurrencyJS(f.balance));

        // Status badge
        var badge = $('#profileStatusBadge');
        badge.removeClass('bg-success bg-warning bg-danger');
        if (f.status === 'Paid') {
            badge.addClass('bg-success').text('FULLY PAID');
        } else if (f.status === 'Partial') {
            badge.addClass('bg-warning').text('PARTIALLY PAID');
        } else {
            badge.addClass('bg-danger').text('UNPAID');
        }

        // Collect Fee button link
        $('#profileCollectFeeBtn').attr('href',
            '../fee_collection/collect_fee.php?student=' + s.student_id
        );

        // Monthly breakdown
        if (data.fee_mode === 'monthly' && data.monthly_breakdown && data.monthly_breakdown.length > 0) {
            var mHtml = '<div class="table-responsive"><table class="table table-sm table-bordered mb-0">';
            mHtml += '<thead class="table-light"><tr><th>Month</th><th class="text-end">Fee</th><th class="text-end">Paid</th><th class="text-end">Balance</th><th>Status</th></tr></thead><tbody>';
            data.monthly_breakdown.forEach(function(m) {
                var mBalance = parseFloat(m.monthly_balance);
                var mPaid = parseFloat(m.monthly_paid);
                var statusBadge = '';
                if (mBalance <= 0 && mPaid > 0) {
                    statusBadge = '<span class="badge bg-success">Paid</span>';
                } else if (mPaid > 0) {
                    statusBadge = '<span class="badge bg-warning">Partial</span>';
                } else {
                    statusBadge = '<span class="badge bg-danger">Unpaid</span>';
                }
                mHtml += '<tr>';
                mHtml += '<td>' + escapeHtml(m.month_label) + '</td>';
                mHtml += '<td class="text-end">' + formatCurrencyJS(m.monthly_fee) + '</td>';
                mHtml += '<td class="text-end">' + formatCurrencyJS(m.monthly_paid) + '</td>';
                mHtml += '<td class="text-end">' + formatCurrencyJS(m.monthly_balance) + '</td>';
                mHtml += '<td>' + statusBadge + '</td>';
                mHtml += '</tr>';
            });
            mHtml += '</tbody></table></div>';
            $('#profileMonthlyTable').html(mHtml);
            $('#profileMonthlyBreakdown').show();
        } else {
            $('#profileMonthlyBreakdown').hide();
        }

        // Payment history
        var monthLabels = {
            1:'April', 2:'May', 3:'June', 4:'July', 5:'August', 6:'September',
            7:'October', 8:'November', 9:'December', 10:'January', 11:'February', 12:'March'
        };
        $('#profilePaymentCount').text(payments.length + ' payment(s)');
        if (payments.length > 0) {
            var hasMonth = data.fee_mode === 'monthly';
            var html = '<div class="table-responsive"><table class="table table-sm table-hover mb-0">';
            html += '<thead class="table-light"><tr>';
            html += '<th>Receipt</th><th>Date</th>';
            if (hasMonth) html += '<th>Month</th>';
            html += '<th class="text-end">Amount</th><th>Mode</th>';
            html += '</tr></thead><tbody>';

            payments.forEach(function(p) {
                html += '<tr>';
                html += '<td><strong>' + escapeHtml(p.receipt_no) + '</strong></td>';
                html += '<td>' + formatDateDisplay(p.payment_date) + '</td>';
                if (hasMonth) {
                    html += '<td>' + (p.fee_month ? (monthLabels[p.fee_month] || '-') : '-') + '</td>';
                }
                html += '<td class="text-end">' + formatCurrencyJS(p.total_paid) + '</td>';
                html += '<td><span class="badge bg-secondary">' + escapeHtml(p.payment_mode) + '</span></td>';
                html += '</tr>';
            });

            html += '</tbody></table></div>';
            $('#profilePaymentTable').html(html).show();
            $('#profileNoPayments').hide();
        } else {
            $('#profilePaymentTable').hide();
            $('#profileNoPayments').show();
        }
    }

    function formatDateDisplay(dateStr) {
        if (!dateStr) return '-';
        var parts = dateStr.split('-');
        if (parts.length === 3) {
            return parts[2] + '-' + parts[1] + '-' + parts[0];
        }
        return dateStr;
    }

    function formatCurrencyJS(amount) {
        return '\u20B9 ' + parseFloat(amount || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>
