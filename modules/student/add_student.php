<?php
/**
 * Add New Student
 */
require_once '../../config/database.php';
require_once '../../includes/session.php';

requireLogin();

$pageTitle = 'Add New Student';
$error = '';
$success = '';

// Fetch classes
$db = getDB();
$classes = $db->fetchAll("SELECT * FROM classes WHERE status = 'active' ORDER BY class_numeric");
$allSessions = getAllSessions();
$selectedSession = getSelectedSession();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate inputs
        $admission_no = sanitize($_POST['admission_no']);
        $first_name = sanitize($_POST['first_name']);
        $last_name = sanitize($_POST['last_name'] ?? '');
        $father_name = sanitize($_POST['father_name']);
        $mother_name = sanitize($_POST['mother_name'] ?? '');
        $date_of_birth = sanitize($_POST['date_of_birth']);
        $gender = sanitize($_POST['gender']);
        $class_id = (int)$_POST['class_id'];
        $section_id = (int)$_POST['section_id'];
        $roll_number = sanitize($_POST['roll_number'] ?? '');
        $contact_number = sanitize($_POST['contact_number']);
        $whatsapp_number = sanitize($_POST['whatsapp_number'] ?? '');
        $aadhar_number = sanitize($_POST['aadhar_number'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $address = sanitize($_POST['address']);
        $admission_date = sanitize($_POST['admission_date']);
        $academic_year = sanitize($_POST['academic_year']);

        // Validate required fields (Roll Number now required, Last Name now optional)
        if (empty($admission_no) || empty($first_name) ||
            empty($father_name) || empty($date_of_birth) || empty($gender) ||
            empty($class_id) || empty($section_id) || empty($roll_number) ||
            empty($contact_number) || empty($address) || empty($admission_date) || empty($academic_year)) {
            throw new Exception('All required fields must be filled.');
        }

        // Validate phone number
        if (!isValidPhone($contact_number)) {
            throw new Exception('Invalid phone number format.');
        }

        // Validate WhatsApp number if provided (must be 10 digits)
        if (!empty($whatsapp_number)) {
            $whatsapp_clean = preg_replace('/[^0-9]/', '', $whatsapp_number);
            if (strlen($whatsapp_clean) !== 10) {
                throw new Exception('WhatsApp number must be 10 digits.');
            }
            $whatsapp_number = $whatsapp_clean;
        }

        // Validate Aadhar number if provided (must be 12 digits)
        if (!empty($aadhar_number)) {
            $aadhar_clean = preg_replace('/[^0-9]/', '', $aadhar_number);
            if (strlen($aadhar_clean) !== 12) {
                throw new Exception('Aadhar number must be 12 digits.');
            }
            $aadhar_number = $aadhar_clean;

            // Check for duplicate Aadhar number
            $existingAadhar = $db->fetchOne(
                "SELECT student_id FROM students WHERE aadhar_number = :aadhar_number",
                ['aadhar_number' => $aadhar_number]
            );

            if ($existingAadhar) {
                throw new Exception('This Aadhar number is already registered with another student.');
            }
        }

        // Validate email if provided
        if (!empty($email) && !isValidEmail($email)) {
            throw new Exception('Invalid email format.');
        }

        // Check if admission number already exists
        $existingStudent = $db->fetchOne(
            "SELECT student_id FROM students WHERE admission_no = :admission_no",
            ['admission_no' => $admission_no]
        );

        if ($existingStudent) {
            throw new Exception('Admission number already exists.');
        }

        // Insert student
        $query = "INSERT INTO students (
            admission_no, first_name, last_name, father_name, mother_name,
            date_of_birth, gender, class_id, section_id, roll_number,
            contact_number, whatsapp_number, aadhar_number, email, password,
            address, admission_date, academic_year
        ) VALUES (
            :admission_no, :first_name, :last_name, :father_name, :mother_name,
            :date_of_birth, :gender, :class_id, :section_id, :roll_number,
            :contact_number, :whatsapp_number, :aadhar_number, :email, :password,
            :address, :admission_date, :academic_year
        )";

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
            'whatsapp_number' => !empty($whatsapp_number) ? $whatsapp_number : null,
            'aadhar_number' => !empty($aadhar_number) ? $aadhar_number : null,
            'email' => $email,
            'password' => password_hash($contact_number, PASSWORD_BCRYPT, ['cost' => 12]),
            'address' => $address,
            'admission_date' => $admission_date,
            'academic_year' => $academic_year
        ]);

        redirectWithMessage('view_students.php', 'success', 'Student added successfully!');

    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="fas fa-user-plus"></i> Add New Student
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
                            <input type="text" name="admission_no" class="form-control form-control-custom" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label-custom">Admission Date <span class="text-danger">*</span></label>
                            <input type="date" name="admission_date" class="form-control form-control-custom max-today" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label-custom">Academic Session <span class="text-danger">*</span></label>
                            <select name="academic_year" class="form-control form-control-custom" required>
                                <?php foreach($allSessions as $sess): ?>
                                <option value="<?php echo htmlspecialchars($sess['session_name']); ?>"
                                    <?php echo ($selectedSession === $sess['session_name']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sess['session_name']); ?>
                                    <?php if ($sess['is_active']): ?>(Active)<?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label-custom">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control form-control-custom" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label-custom">Last Name</label>
                            <input type="text" name="last_name" class="form-control form-control-custom">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label-custom">Date of Birth <span class="text-danger">*</span></label>
                            <input type="date" name="date_of_birth" class="form-control form-control-custom max-today" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label-custom">Father's Name <span class="text-danger">*</span></label>
                            <input type="text" name="father_name" class="form-control form-control-custom" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label-custom">Mother's Name</label>
                            <input type="text" name="mother_name" class="form-control form-control-custom">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label-custom">Gender <span class="text-danger">*</span></label>
                            <select name="gender" class="form-control form-control-custom" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label-custom">Class <span class="text-danger">*</span></label>
                            <select name="class_id" id="class_id" class="form-control form-control-custom" required>
                                <option value="">Select Class</option>
                                <?php foreach($classes as $class): ?>
                                <option value="<?php echo $class['class_id']; ?>">
                                    <?php echo htmlspecialchars($class['class_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label-custom">Section <span class="text-danger">*</span></label>
                            <select name="section_id" id="section_id" class="form-control form-control-custom" required>
                                <option value="">Select Section</option>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label-custom">Roll Number <span class="text-danger">*</span></label>
                            <input type="text" name="roll_number" class="form-control form-control-custom" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label-custom">Contact Number <span class="text-danger">*</span></label>
                            <input type="text" name="contact_number" class="form-control form-control-custom" maxlength="15" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label-custom">WhatsApp Number</label>
                            <input type="text" name="whatsapp_number" class="form-control form-control-custom" maxlength="15" placeholder="10 digits">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label-custom">Email</label>
                            <input type="email" name="email" class="form-control form-control-custom">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label-custom">Aadhar Number</label>
                            <input type="text" name="aadhar_number" class="form-control form-control-custom" maxlength="12" placeholder="12 digits">
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label-custom">Address <span class="text-danger">*</span></label>
                            <textarea name="address" class="form-control form-control-custom" rows="3" required></textarea>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-custom">
                            <i class="fas fa-save"></i> Save Student
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
