<?php
/**
 * Edit Student
 */
require_once '../../config/database.php';
require_once '../../includes/session.php';

requireRole(['sysadmin', 'admin']);

$pageTitle = 'Edit Student';
$error = '';
$student_id = (int)($_GET['id'] ?? 0);

if ($student_id <= 0) {
    redirectWithMessage('view_students.php', 'error', 'Invalid student ID.');
}

$db = getDB();
$allSessions = getAllSessions();

// Fetch student details
$student = $db->fetchOne(
    "SELECT * FROM students WHERE student_id = :id",
    ['id' => $student_id]
);

if (!$student) {
    redirectWithMessage('view_students.php', 'error', 'Student not found.');
}

// Fetch classes
$classes = $db->fetchAll("SELECT * FROM classes WHERE status = 'active' ORDER BY class_numeric");

// Fetch sections for student's class
$sections = $db->fetchAll(
    "SELECT * FROM sections WHERE class_id = :class_id AND status = 'active' ORDER BY section_name",
    ['class_id' => $student['class_id']]
);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate inputs
        $admission_no = sanitize($_POST['admission_no']);
        $first_name = sanitize($_POST['first_name']);
        $last_name = sanitize($_POST['last_name']);
        $father_name = sanitize($_POST['father_name']);
        $mother_name = sanitize($_POST['mother_name'] ?? '');
        $date_of_birth = sanitize($_POST['date_of_birth']);
        $gender = sanitize($_POST['gender']);
        $class_id = (int)$_POST['class_id'];
        $section_id = (int)$_POST['section_id'];
        $roll_number = sanitize($_POST['roll_number'] ?? '');
        $contact_number = sanitize($_POST['contact_number']);
        $email = sanitize($_POST['email'] ?? '');
        $address = sanitize($_POST['address']);
        $admission_date = sanitize($_POST['admission_date']);
        $academic_year = sanitize($_POST['academic_year']);

        // Validate required fields
        if (empty($admission_no) || empty($first_name) || empty($last_name) ||
            empty($father_name) || empty($date_of_birth) || empty($gender) ||
            empty($class_id) || empty($section_id) || empty($contact_number) ||
            empty($address) || empty($admission_date) || empty($academic_year)) {
            throw new Exception('All required fields must be filled.');
        }

        // Validate phone number
        if (!isValidPhone($contact_number)) {
            throw new Exception('Invalid phone number format.');
        }

        // Validate email if provided
        if (!empty($email) && !isValidEmail($email)) {
            throw new Exception('Invalid email format.');
        }

        // Check if admission number already exists for another student
        $existingStudent = $db->fetchOne(
            "SELECT student_id FROM students WHERE admission_no = :admission_no AND student_id != :id",
            ['admission_no' => $admission_no, 'id' => $student_id]
        );

        if ($existingStudent) {
            throw new Exception('Admission number already exists.');
        }

        // Update student
        $query = "UPDATE students SET
            admission_no = :admission_no,
            first_name = :first_name,
            last_name = :last_name,
            father_name = :father_name,
            mother_name = :mother_name,
            date_of_birth = :date_of_birth,
            gender = :gender,
            class_id = :class_id,
            section_id = :section_id,
            roll_number = :roll_number,
            contact_number = :contact_number,
            email = :email,
            address = :address,
            admission_date = :admission_date,
            academic_year = :academic_year
            WHERE student_id = :id
        ";

        $db->query($query, [
            'admission_no' => $admission_no,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'father_name' => $father_name,
            'mother_name' => $mother_name,
            'date_of_birth' => $date_of_birth,
            'gender' => $gender,
            'class_id' => $class_id,
            'section_id' => $section_id,
            'roll_number' => $roll_number,
            'contact_number' => $contact_number,
            'email' => $email,
            'address' => $address,
            'admission_date' => $admission_date,
            'academic_year' => $academic_year,
            'id' => $student_id
        ]);

        // If contact number changed and student hasn't changed their password, update password too
        if ($contact_number !== $student['contact_number']) {
            $studentRecord = $db->fetchOne("SELECT password_changed FROM students WHERE student_id = :id", ['id' => $student_id]);
            if ($studentRecord && !$studentRecord['password_changed']) {
                $db->query("UPDATE students SET password = :password WHERE student_id = :id", [
                    'password' => password_hash($contact_number, PASSWORD_BCRYPT, ['cost' => 12]),
                    'id' => $student_id
                ]);
            }
        }

        redirectWithMessage('view_students.php', 'success', 'Student updated successfully!');

    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="fas fa-user-edit"></i> Edit Student
        </h2>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-header">
                Student Information
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label-custom">Admission No <span class="text-danger">*</span></label>
                            <input type="text" name="admission_no" class="form-control form-control-custom"
                                   value="<?php echo htmlspecialchars($student['admission_no']); ?>" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label-custom">Admission Date <span class="text-danger">*</span></label>
                            <input type="date" name="admission_date" class="form-control form-control-custom"
                                   value="<?php echo $student['admission_date']; ?>" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label-custom">Academic Session <span class="text-danger">*</span></label>
                            <select name="academic_year" class="form-control form-control-custom" required>
                                <?php foreach($allSessions as $sess): ?>
                                <option value="<?php echo htmlspecialchars($sess['session_name']); ?>"
                                    <?php echo (($student['academic_year'] ?? '') === $sess['session_name']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sess['session_name']); ?>
                                    <?php if ($sess['is_active']): ?>(Active)<?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label-custom">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control form-control-custom"
                                   value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label-custom">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control form-control-custom"
                                   value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label-custom">Date of Birth <span class="text-danger">*</span></label>
                            <input type="date" name="date_of_birth" class="form-control form-control-custom"
                                   value="<?php echo $student['date_of_birth']; ?>" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label-custom">Father's Name <span class="text-danger">*</span></label>
                            <input type="text" name="father_name" class="form-control form-control-custom"
                                   value="<?php echo htmlspecialchars($student['father_name']); ?>" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label-custom">Mother's Name</label>
                            <input type="text" name="mother_name" class="form-control form-control-custom"
                                   value="<?php echo htmlspecialchars($student['mother_name'] ?? ''); ?>">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label-custom">Gender <span class="text-danger">*</span></label>
                            <select name="gender" class="form-control form-control-custom" required>
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo ($student['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo ($student['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo ($student['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label-custom">Class <span class="text-danger">*</span></label>
                            <select name="class_id" id="class_id" class="form-control form-control-custom" required>
                                <option value="">Select Class</option>
                                <?php foreach($classes as $class): ?>
                                <option value="<?php echo $class['class_id']; ?>"
                                    <?php echo ($student['class_id'] == $class['class_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($class['class_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label-custom">Section <span class="text-danger">*</span></label>
                            <select name="section_id" id="section_id" class="form-control form-control-custom" required>
                                <option value="">Select Section</option>
                                <?php foreach($sections as $section): ?>
                                <option value="<?php echo $section['section_id']; ?>"
                                    <?php echo ($student['section_id'] == $section['section_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($section['section_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label-custom">Roll Number</label>
                            <input type="text" name="roll_number" class="form-control form-control-custom"
                                   value="<?php echo htmlspecialchars($student['roll_number'] ?? ''); ?>">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label-custom">Contact Number <span class="text-danger">*</span></label>
                            <input type="text" name="contact_number" class="form-control form-control-custom"
                                   value="<?php echo htmlspecialchars($student['contact_number']); ?>" maxlength="15" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label-custom">Email</label>
                            <input type="email" name="email" class="form-control form-control-custom"
                                   value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>">
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label-custom">Address <span class="text-danger">*</span></label>
                            <textarea name="address" class="form-control form-control-custom" rows="3" required><?php echo htmlspecialchars($student['address']); ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-custom">
                            <i class="fas fa-save"></i> Update Student
                        </button>
                        <a href="view_students.php" class="btn btn-secondary btn-custom">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
