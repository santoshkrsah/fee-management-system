<?php
/**
 * Manage Classes & Sections
 * Accessible only by sysadmin role
 */
require_once '../config/database.php';
require_once '../includes/session.php';

requireRole(['sysadmin']);

$pageTitle = 'Manage Classes & Sections';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        redirectWithMessage('manage_classes.php', 'error', 'Invalid security token. Please try again.');
    }

    $action = $_POST['action'] ?? '';
    $db = getDB();

    // ---- ADD CLASS ----
    if ($action === 'add_class') {
        $className = sanitize($_POST['class_name'] ?? '');
        $classNumeric = intval($_POST['class_numeric'] ?? 0);

        if (empty($className) || $classNumeric <= 0) {
            redirectWithMessage('manage_classes.php', 'error', 'Class name and numeric order are required.');
        }

        try {
            $existing = $db->fetchOne(
                "SELECT class_id FROM classes WHERE class_name = :name",
                ['name' => $className]
            );
            if ($existing) {
                redirectWithMessage('manage_classes.php', 'error', 'Class "' . $className . '" already exists.');
            }

            $db->query(
                "INSERT INTO classes (class_name, class_numeric, status) VALUES (:name, :num, 'active')",
                ['name' => $className, 'num' => $classNumeric]
            );

            redirectWithMessage('manage_classes.php', 'success', 'Class "' . $className . '" added successfully.');

        } catch (Exception $e) {
            error_log("Add Class Error: " . $e->getMessage());
            redirectWithMessage('manage_classes.php', 'error', 'Failed to add class. Please try again.');
        }
    }

    // ---- EDIT CLASS ----
    elseif ($action === 'edit_class') {
        $classId = intval($_POST['class_id'] ?? 0);
        $className = sanitize($_POST['class_name'] ?? '');
        $classNumeric = intval($_POST['class_numeric'] ?? 0);

        if ($classId <= 0 || empty($className) || $classNumeric <= 0) {
            redirectWithMessage('manage_classes.php', 'error', 'Class name and numeric order are required.');
        }

        try {
            $existing = $db->fetchOne(
                "SELECT class_id FROM classes WHERE class_name = :name AND class_id != :id",
                ['name' => $className, 'id' => $classId]
            );
            if ($existing) {
                redirectWithMessage('manage_classes.php', 'error', 'Another class with name "' . $className . '" already exists.');
            }

            $db->query(
                "UPDATE classes SET class_name = :name, class_numeric = :num WHERE class_id = :id",
                ['name' => $className, 'num' => $classNumeric, 'id' => $classId]
            );

            redirectWithMessage('manage_classes.php', 'success', 'Class updated successfully.');

        } catch (Exception $e) {
            error_log("Edit Class Error: " . $e->getMessage());
            redirectWithMessage('manage_classes.php', 'error', 'Failed to update class. Please try again.');
        }
    }

    // ---- DELETE CLASS ----
    elseif ($action === 'delete_class') {
        $classId = intval($_POST['class_id'] ?? 0);

        if ($classId <= 0) {
            redirectWithMessage('manage_classes.php', 'error', 'Invalid class ID.');
        }

        try {
            $className = $db->fetchOne("SELECT class_name FROM classes WHERE class_id = :id", ['id' => $classId]);

            // Delete linked fee collections (payments referencing fee structures of this class)
            $db->query(
                "DELETE fc FROM fee_collection fc
                 INNER JOIN fee_structure fs ON fc.fee_structure_id = fs.fee_structure_id
                 WHERE fs.class_id = :id",
                ['id' => $classId]
            );

            // Delete monthly mode fee collections (fee_structure_id is NULL, linked via monthly_fee_structure)
            $db->query(
                "DELETE fc FROM fee_collection fc
                 INNER JOIN monthly_fee_structure mfs ON fc.monthly_fee_structure_id = mfs.monthly_fee_id
                 WHERE mfs.class_id = :id",
                ['id' => $classId]
            );

            // Delete linked monthly fee structures
            $db->query("DELETE FROM monthly_fee_structure WHERE class_id = :id", ['id' => $classId]);

            // Delete linked fee structures
            $db->query("DELETE FROM fee_structure WHERE class_id = :id", ['id' => $classId]);

            // Delete linked students
            $db->query("DELETE FROM students WHERE class_id = :id", ['id' => $classId]);

            // Delete class (CASCADE will remove sections)
            $db->query("DELETE FROM classes WHERE class_id = :id", ['id' => $classId]);

            $name = $className ? $className['class_name'] : 'Class';
            redirectWithMessage('manage_classes.php', 'success', '"' . $name . '" and its sections have been deleted.');

        } catch (Exception $e) {
            error_log("Delete Class Error: " . $e->getMessage());
            redirectWithMessage('manage_classes.php', 'error', 'Failed to delete class. Please try again.');
        }
    }

    // ---- ADD SECTION ----
    elseif ($action === 'add_section') {
        $classId = intval($_POST['class_id'] ?? 0);
        $sectionName = strtoupper(sanitize($_POST['section_name'] ?? ''));

        if ($classId <= 0 || empty($sectionName)) {
            redirectWithMessage('manage_classes.php', 'error', 'Class and section name are required.');
        }

        try {
            $existing = $db->fetchOne(
                "SELECT section_id FROM sections WHERE class_id = :cid AND section_name = :name",
                ['cid' => $classId, 'name' => $sectionName]
            );
            if ($existing) {
                redirectWithMessage('manage_classes.php', 'error', 'Section "' . $sectionName . '" already exists for this class.');
            }

            $db->query(
                "INSERT INTO sections (class_id, section_name, status) VALUES (:cid, :name, 'active')",
                ['cid' => $classId, 'name' => $sectionName]
            );

            redirectWithMessage('manage_classes.php', 'success', 'Section "' . $sectionName . '" added successfully.');

        } catch (Exception $e) {
            error_log("Add Section Error: " . $e->getMessage());
            redirectWithMessage('manage_classes.php', 'error', 'Failed to add section. Please try again.');
        }
    }

    // ---- EDIT SECTION ----
    elseif ($action === 'edit_section') {
        $sectionId = intval($_POST['section_id'] ?? 0);
        $sectionName = strtoupper(sanitize($_POST['section_name'] ?? ''));

        if ($sectionId <= 0 || empty($sectionName)) {
            redirectWithMessage('manage_classes.php', 'error', 'Section name is required.');
        }

        try {
            // Get current section to find its class_id
            $current = $db->fetchOne(
                "SELECT class_id FROM sections WHERE section_id = :id",
                ['id' => $sectionId]
            );
            if (!$current) {
                redirectWithMessage('manage_classes.php', 'error', 'Section not found.');
            }

            $existing = $db->fetchOne(
                "SELECT section_id FROM sections WHERE class_id = :cid AND section_name = :name AND section_id != :sid",
                ['cid' => $current['class_id'], 'name' => $sectionName, 'sid' => $sectionId]
            );
            if ($existing) {
                redirectWithMessage('manage_classes.php', 'error', 'Section "' . $sectionName . '" already exists for this class.');
            }

            $db->query(
                "UPDATE sections SET section_name = :name WHERE section_id = :id",
                ['name' => $sectionName, 'id' => $sectionId]
            );

            redirectWithMessage('manage_classes.php', 'success', 'Section updated successfully.');

        } catch (Exception $e) {
            error_log("Edit Section Error: " . $e->getMessage());
            redirectWithMessage('manage_classes.php', 'error', 'Failed to update section. Please try again.');
        }
    }

    // ---- DELETE SECTION ----
    elseif ($action === 'delete_section') {
        $sectionId = intval($_POST['section_id'] ?? 0);

        if ($sectionId <= 0) {
            redirectWithMessage('manage_classes.php', 'error', 'Invalid section ID.');
        }

        try {
            // Check for linked students
            $studentCount = $db->fetchOne(
                "SELECT COUNT(*) as cnt FROM students WHERE section_id = :id",
                ['id' => $sectionId]
            );
            if ($studentCount && $studentCount['cnt'] > 0) {
                redirectWithMessage('manage_classes.php', 'error', 'Cannot delete: ' . $studentCount['cnt'] . ' student(s) are in this section. Remove or reassign them first.');
            }

            $section = $db->fetchOne(
                "SELECT section_name FROM sections WHERE section_id = :id",
                ['id' => $sectionId]
            );
            $db->query("DELETE FROM sections WHERE section_id = :id", ['id' => $sectionId]);

            $name = $section ? $section['section_name'] : 'Section';
            redirectWithMessage('manage_classes.php', 'success', 'Section "' . $name . '" deleted successfully.');

        } catch (Exception $e) {
            error_log("Delete Section Error: " . $e->getMessage());
            redirectWithMessage('manage_classes.php', 'error', 'Failed to delete section. Please try again.');
        }
    }
}

// Fetch all classes with section and student counts
try {
    $db = getDB();

    $classes = $db->fetchAll("
        SELECT c.*,
               (SELECT COUNT(*) FROM students s WHERE s.class_id = c.class_id) as student_count,
               (SELECT COUNT(*) FROM fee_structure fs WHERE fs.class_id = c.class_id) as fee_count
        FROM classes c
        ORDER BY c.class_numeric ASC
    ");

    // Fetch all sections grouped by class
    $allSections = $db->fetchAll("
        SELECT s.*,
               (SELECT COUNT(*) FROM students st WHERE st.section_id = s.section_id) as student_count
        FROM sections s
        ORDER BY s.section_name ASC
    ");

    $sectionsByClass = [];
    foreach ($allSections as $sec) {
        $sectionsByClass[$sec['class_id']][] = $sec;
    }

} catch (Exception $e) {
    error_log("Fetch Classes Error: " . $e->getMessage());
    $classes = [];
    $sectionsByClass = [];
}

$csrfToken = generateCSRFToken();

require_once '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <h2>
                <i class="fas fa-th-list"></i> Manage Classes & Sections
            </h2>
            <button type="button" class="btn btn-primary btn-custom" data-bs-toggle="modal" data-bs-target="#addClassModal">
                <i class="fas fa-plus"></i> Add New Class
            </button>
        </div>
    </div>
</div>

<!-- Classes Table -->
<div class="row">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-list"></i> All Classes
                <span class="badge bg-secondary ms-2"><?php echo count($classes); ?></span>
            </div>
            <div class="card-body">
                <?php if (count($classes) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-custom table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Class Name</th>
                                <th>Order</th>
                                <th>Sections</th>
                                <th>Students</th>
                                <th>Fee Structure</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($classes as $index => $class): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><strong><?php echo htmlspecialchars($class['class_name']); ?></strong></td>
                                <td><?php echo $class['class_numeric']; ?></td>
                                <td>
                                    <?php
                                    $sections = $sectionsByClass[$class['class_id']] ?? [];
                                    if (!empty($sections)):
                                        foreach ($sections as $sec):
                                    ?>
                                        <span class="badge bg-info me-1 mb-1">
                                            <?php echo htmlspecialchars($sec['section_name']); ?>
                                            (<?php echo $sec['student_count']; ?>)
                                            <?php if ($sec['student_count'] == 0): ?>
                                            <form method="POST" action="manage_classes.php" class="d-inline"
                                                  onsubmit="return confirm('Delete section <?php echo htmlspecialchars($sec['section_name']); ?>?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                <input type="hidden" name="action" value="delete_section">
                                                <input type="hidden" name="section_id" value="<?php echo $sec['section_id']; ?>">
                                                <button type="submit" class="btn-close btn-close-white ms-1" style="font-size: 0.5rem;" title="Delete Section"></button>
                                            </form>
                                            <?php endif; ?>
                                        </span>
                                    <?php
                                        endforeach;
                                    else:
                                    ?>
                                        <span class="text-muted">No sections</span>
                                    <?php endif; ?>

                                    <!-- Add Section Button -->
                                    <button type="button" class="btn btn-sm btn-outline-success add-section-btn"
                                            data-class-id="<?php echo $class['class_id']; ?>"
                                            data-class-name="<?php echo htmlspecialchars($class['class_name']); ?>"
                                            title="Add Section">
                                        <i class="fas fa-plus" style="font-size: 0.6rem;"></i>
                                    </button>
                                </td>
                                <td>
                                    <?php if ($class['student_count'] > 0): ?>
                                        <span class="badge bg-primary"><?php echo $class['student_count']; ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($class['fee_count'] > 0): ?>
                                        <span class="badge bg-success">Defined</span>
                                    <?php else: ?>
                                        <span class="text-muted">Not set</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($class['status'] === 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-icon edit-class-btn me-1"
                                            title="Edit Class"
                                            data-id="<?php echo $class['class_id']; ?>"
                                            data-name="<?php echo htmlspecialchars($class['class_name']); ?>"
                                            data-numeric="<?php echo $class['class_numeric']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <form method="POST" action="manage_classes.php" class="d-inline"
                                          onsubmit="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($class['class_name']); ?>? This will permanently remove all its sections, students, fee structure, and payment records.');">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                        <input type="hidden" name="action" value="delete_class">
                                        <input type="hidden" name="class_id" value="<?php echo $class['class_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger btn-icon" title="Delete Class">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-center text-muted mb-0">No classes found. Add a class to get started.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Class Modal -->
<div class="modal fade" id="addClassModal" tabindex="-1" aria-labelledby="addClassModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="manage_classes.php">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="add_class">
                <div class="modal-header">
                    <h5 class="modal-title" id="addClassModalLabel">
                        <i class="fas fa-plus"></i> Add New Class
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_class_name" class="form-label form-label-custom">Class Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-custom" id="add_class_name" name="class_name"
                               required maxlength="50" placeholder="e.g. Class 1, Nursery, LKG">
                    </div>
                    <div class="mb-3">
                        <label for="add_class_numeric" class="form-label form-label-custom">Numeric Order <span class="text-danger">*</span></label>
                        <input type="number" class="form-control form-control-custom" id="add_class_numeric" name="class_numeric"
                               required min="1" max="99" placeholder="e.g. 1, 2, 3 (for sorting)">
                        <div class="form-text">Used to sort classes in order. Lower numbers appear first.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-custom">
                        <i class="fas fa-save"></i> Add Class
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Class Modal -->
<div class="modal fade" id="editClassModal" tabindex="-1" aria-labelledby="editClassModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="manage_classes.php" id="editClassForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="edit_class">
                <input type="hidden" name="class_id" id="edit_class_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="editClassModalLabel">
                        <i class="fas fa-edit"></i> Edit Class
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_class_name" class="form-label form-label-custom">Class Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-custom" id="edit_class_name" name="class_name"
                               required maxlength="50">
                    </div>
                    <div class="mb-3">
                        <label for="edit_class_numeric" class="form-label form-label-custom">Numeric Order <span class="text-danger">*</span></label>
                        <input type="number" class="form-control form-control-custom" id="edit_class_numeric" name="class_numeric"
                               required min="1" max="99">
                        <div class="form-text">Used to sort classes in order. Lower numbers appear first.</div>
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

<!-- Add Section Modal -->
<div class="modal fade" id="addSectionModal" tabindex="-1" aria-labelledby="addSectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST" action="manage_classes.php">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="add_section">
                <input type="hidden" name="class_id" id="add_section_class_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSectionModalLabel">
                        <i class="fas fa-plus"></i> Add Section to <span id="add_section_class_name"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_section_name" class="form-label form-label-custom">Section Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-custom" id="add_section_name" name="section_name"
                               required maxlength="10" placeholder="e.g. A, B, C">
                        <div class="form-text">Will be converted to uppercase.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-custom">
                        <i class="fas fa-save"></i> Add Section
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Edit Class button
    $('.edit-class-btn').on('click', function() {
        $('#edit_class_id').val($(this).data('id'));
        $('#edit_class_name').val($(this).data('name'));
        $('#edit_class_numeric').val($(this).data('numeric'));
        var editModal = new bootstrap.Modal(document.getElementById('editClassModal'));
        editModal.show();
    });

    // Add Section button
    $('.add-section-btn').on('click', function() {
        $('#add_section_class_id').val($(this).data('class-id'));
        $('#add_section_class_name').text($(this).data('class-name'));
        $('#add_section_name').val('');
        var addSectionModal = new bootstrap.Modal(document.getElementById('addSectionModal'));
        addSectionModal.show();
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
