<?php
/**
 * Bulk Upload Students via CSV
 * Sysadmin only
 */
require_once '../../config/database.php';
require_once '../../includes/session.php';

requireRole(['sysadmin']);

$pageTitle = 'Bulk Upload Students';
$errors = [];
$successCount = 0;
$skipCount = 0;
$rowErrors = [];

$db = getDB();
$selectedSession = getSelectedSession();

// Pre-load class and section mappings
$classRows = $db->fetchAll("SELECT class_id, class_name FROM classes WHERE status = 'active'");
$classMap = [];
foreach ($classRows as $row) {
    $classMap[strtolower(trim($row['class_name']))] = $row['class_id'];
}

$sectionRows = $db->fetchAll("SELECT section_id, class_id, section_name FROM sections WHERE status = 'active'");
$sectionMap = [];
foreach ($sectionRows as $row) {
    $key = $row['class_id'] . '_' . strtolower(trim($row['section_name']));
    $sectionMap[$key] = $row['section_id'];
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } elseif (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Please select a valid CSV file to upload.';
    } else {
        $file = $_FILES['csv_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($ext !== 'csv') {
            $errors[] = 'Only CSV files are allowed. Please save your Excel file as CSV before uploading.';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $errors[] = 'File size exceeds 5MB limit.';
        } else {
            // Read entire file content and clean encoding
            $rawContent = file_get_contents($file['tmp_name']);
            if ($rawContent === false || strlen(trim($rawContent)) === 0) {
                $errors[] = 'Unable to read the uploaded file or file is empty.';
            } else {
                // Remove BOM if present (UTF-8, UTF-16 LE/BE)
                $rawContent = preg_replace('/^\xEF\xBB\xBF/', '', $rawContent);
                $rawContent = preg_replace('/^\xFF\xFE/', '', $rawContent);
                $rawContent = preg_replace('/^\xFE\xFF/', '', $rawContent);

                // Normalize line endings to \n
                $rawContent = str_replace(["\r\n", "\r"], "\n", $rawContent);

                // Convert encoding to UTF-8 if needed
                $encoding = mb_detect_encoding($rawContent, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
                if ($encoding && $encoding !== 'UTF-8') {
                    $rawContent = mb_convert_encoding($rawContent, 'UTF-8', $encoding);
                }

                // Clean all non-printable characters except newlines and tabs
                $rawContent = preg_replace('/[^\x09\x0A\x20-\x7E\xC0-\xFF]/', '', $rawContent);

                // Strip any HTML tags that may have leaked into the file (e.g. from PHP errors)
                $rawContent = strip_tags($rawContent);

                // Remove any blank or HTML-only lines from the beginning
                $lines = explode("\n", $rawContent);
                while (!empty($lines) && trim($lines[0]) === '') {
                    array_shift($lines);
                }
                $rawContent = implode("\n", $lines);

                $expectedHeaders = [
                    'admission_no', 'first_name', 'last_name', 'father_name',
                    'mother_name', 'date_of_birth', 'gender', 'class_name',
                    'section_name', 'roll_number', 'contact_number', 'email',
                    'address', 'admission_date'
                ];

                // Try each delimiter and pick the one that finds all headers
                $delimiters = [',', ';', "\t", '|'];
                $delimiter = null;
                $header = null;

                // Get the first line for testing
                $firstLine = trim($lines[0] ?? '');

                foreach ($delimiters as $tryDelim) {
                    $parsed = str_getcsv($firstLine, $tryDelim);
                    $parsed = array_map(function($h) {
                        return strtolower(trim(preg_replace('/[^\x20-\x7E]/', '', $h)));
                    }, $parsed);

                    $missing = array_diff($expectedHeaders, $parsed);
                    if (empty($missing)) {
                        $delimiter = $tryDelim;
                        $header = $parsed;
                        break;
                    }
                }

                if (!$delimiter || !$header) {
                    $errors[] = 'Could not parse column headers. The file may not be a valid CSV. Please download the template, fill data in Excel, then use File > Save As > "CSV (Comma delimited) (*.csv)" to save it.';
                    $errors[] = 'First line detected: "' . htmlspecialchars(substr($firstLine, 0, 200)) . '"';
                } else {
                    // Write cleaned content to temp file for fgetcsv
                    $tmpFile = tempnam(sys_get_temp_dir(), 'csv_');
                    file_put_contents($tmpFile, $rawContent);
                    $handle = fopen($tmpFile, 'r');

                    // Skip the header line (already parsed)
                    fgetcsv($handle, 0, $delimiter);

                    // Map header positions
                    $colIndex = array_flip($header);
                    $rowNum = 1;

                    // Use transaction for bulk insert performance
                    $db->beginTransaction();
                    try {
                        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                            $rowNum++;

                            // Skip empty rows
                            if (empty(array_filter($row))) {
                                continue;
                            }

                            // Pad row if shorter than header
                            while (count($row) < count($header)) {
                                $row[] = '';
                            }

                            $admission_no = trim($row[$colIndex['admission_no']]);
                            $first_name   = trim($row[$colIndex['first_name']]);
                            $last_name    = trim($row[$colIndex['last_name']]);
                            $father_name  = trim($row[$colIndex['father_name']]);
                            $mother_name  = trim($row[$colIndex['mother_name']]);
                            $dob          = trim($row[$colIndex['date_of_birth']]);
                            $gender       = trim($row[$colIndex['gender']]);
                            $className    = trim($row[$colIndex['class_name']]);
                            $sectionName  = trim($row[$colIndex['section_name']]);
                            $roll_number  = trim($row[$colIndex['roll_number']]);
                            $contact      = trim($row[$colIndex['contact_number']]);
                            $email        = trim($row[$colIndex['email']]);
                            $address      = trim($row[$colIndex['address']]);
                            $admDate      = trim($row[$colIndex['admission_date']]);

                            $lineErrors = [];

                            // Required field checks
                            if (empty($admission_no)) $lineErrors[] = 'admission_no is required';
                            if (empty($first_name))   $lineErrors[] = 'first_name is required';
                            if (empty($last_name))    $lineErrors[] = 'last_name is required';
                            if (empty($father_name))  $lineErrors[] = 'father_name is required';
                            if (empty($dob))          $lineErrors[] = 'date_of_birth is required';
                            if (empty($gender))       $lineErrors[] = 'gender is required';
                            if (empty($className))    $lineErrors[] = 'class_name is required';
                            if (empty($sectionName))  $lineErrors[] = 'section_name is required';
                            if (empty($contact))      $lineErrors[] = 'contact_number is required';
                            if (empty($address))      $lineErrors[] = 'address is required';
                            if (empty($admDate))      $lineErrors[] = 'admission_date is required';

                            // Validate gender
                            $genderNorm = ucfirst(strtolower($gender));
                            if (!empty($gender) && !in_array($genderNorm, ['Male', 'Female', 'Other'])) {
                                $lineErrors[] = "invalid gender '$gender'";
                            }

                            // Validate dates
                            if (!empty($dob) && !strtotime($dob)) {
                                $lineErrors[] = "invalid date_of_birth '$dob'";
                            }
                            if (!empty($admDate) && !strtotime($admDate)) {
                                $lineErrors[] = "invalid admission_date '$admDate'";
                            }

                            // Validate contact
                            if (!empty($contact) && !preg_match('/^[0-9]{10,15}$/', $contact)) {
                                $lineErrors[] = "invalid contact_number '$contact'";
                            }

                            // Validate email
                            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                $lineErrors[] = "invalid email '$email'";
                            }

                            // Map class name to class_id
                            $classKey = strtolower($className);
                            $classId = $classMap[$classKey] ?? null;
                            if (!empty($className) && !$classId) {
                                $lineErrors[] = "class '$className' not found";
                            }

                            // Map section name to section_id
                            $sectionId = null;
                            if ($classId) {
                                $secKey = $classId . '_' . strtolower($sectionName);
                                $sectionId = $sectionMap[$secKey] ?? null;
                                if (!empty($sectionName) && !$sectionId) {
                                    $lineErrors[] = "section '$sectionName' not found for class '$className'";
                                }
                            }

                            // Check duplicate admission_no
                            if (empty($lineErrors) && !empty($admission_no)) {
                                $existing = $db->fetchOne(
                                    "SELECT student_id FROM students WHERE admission_no = :adm",
                                    ['adm' => $admission_no]
                                );
                                if ($existing) {
                                    $lineErrors[] = "admission_no '$admission_no' already exists";
                                    $skipCount++;
                                }
                            }

                            if (!empty($lineErrors)) {
                                $rowErrors[] = "Row $rowNum: " . implode('; ', $lineErrors);
                                continue;
                            }

                            // Insert the student
                            try {
                                $db->query(
                                    "INSERT INTO students (
                                        admission_no, first_name, last_name, father_name, mother_name,
                                        date_of_birth, gender, class_id, section_id, roll_number,
                                        contact_number, email, address, admission_date, academic_year
                                    ) VALUES (
                                        :admission_no, :first_name, :last_name, :father_name, :mother_name,
                                        :date_of_birth, :gender, :class_id, :section_id, :roll_number,
                                        :contact_number, :email, :address, :admission_date, :academic_year
                                    )",
                                    [
                                        'admission_no'  => $admission_no,
                                        'first_name'    => $first_name,
                                        'last_name'     => $last_name,
                                        'father_name'   => $father_name,
                                        'mother_name'   => $mother_name,
                                        'date_of_birth' => date('Y-m-d', strtotime($dob)),
                                        'gender'        => $genderNorm,
                                        'class_id'      => $classId,
                                        'section_id'    => $sectionId,
                                        'roll_number'   => $roll_number,
                                        'contact_number'=> $contact,
                                        'email'         => $email,
                                        'address'       => $address,
                                        'admission_date'=> date('Y-m-d', strtotime($admDate)),
                                        'academic_year' => $selectedSession
                                    ]
                                );
                                $successCount++;
                            } catch (Exception $e) {
                                $rowErrors[] = "Row $rowNum: Database error - " . $e->getMessage();
                            }
                        }
                        $db->commit();
                    } catch (Exception $e) {
                        $db->rollback();
                        $errors[] = 'A critical error occurred during import: ' . $e->getMessage();
                        $successCount = 0;
                    }
                    fclose($handle);
                    if (isset($tmpFile) && file_exists($tmpFile)) {
                        unlink($tmpFile);
                    }
                }
            }
        }
    }
}

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="fas fa-file-upload"></i> Bulk Upload Students
            <span class="badge bg-primary ms-2"><?php echo htmlspecialchars($selectedSession); ?></span>
        </h2>
    </div>
</div>

<?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)): ?>
<div class="row mb-4">
    <div class="col-12">
        <?php if ($successCount > 0): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <strong><?php echo $successCount; ?></strong> student(s) uploaded successfully.
        </div>
        <?php endif; ?>

        <?php if (!empty($rowErrors)): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> <strong><?php echo count($rowErrors); ?></strong> row(s) had errors and were skipped:
            <ul class="mb-0 mt-2">
                <?php foreach ($rowErrors as $re): ?>
                <li><?php echo htmlspecialchars($re); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if ($successCount === 0 && empty($rowErrors)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No data rows found in the uploaded file.
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <?php foreach ($errors as $err): ?>
                <?php echo htmlspecialchars($err); ?><br>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-upload"></i> Upload CSV File
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <div class="mb-3">
                        <label class="form-label-custom">Select CSV File <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" class="form-control form-control-custom" accept=".csv" required>
                        <small class="text-muted">Max file size: 5MB. Only .csv files are accepted.</small>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-custom">
                            <i class="fas fa-upload"></i> Upload &amp; Import
                        </button>
                        <a href="view_students.php" class="btn btn-secondary btn-custom">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-info-circle"></i> Instructions
            </div>
            <div class="card-body">
                <ol class="mb-3" style="padding-left: 1.2rem;">
                    <li class="mb-2">Download the CSV template using the button below.</li>
                    <li class="mb-2">Open it in Excel or any spreadsheet application.</li>
                    <li class="mb-2">Fill in student data following the sample rows.</li>
                    <li class="mb-2">Save/export the file as <strong>CSV (Comma delimited)</strong>.</li>
                    <li class="mb-2">Upload the CSV file using the form.</li>
                </ol>

                <a href="download_template.php" class="btn btn-success btn-custom w-100 mb-3">
                    <i class="fas fa-download"></i> Download Template
                </a>

                <h6 class="fw-bold mt-3">Required Fields:</h6>
                <ul class="small mb-2" style="padding-left: 1.2rem;">
                    <li>admission_no (unique)</li>
                    <li>first_name, last_name</li>
                    <li>father_name</li>
                    <li>date_of_birth (YYYY-MM-DD)</li>
                    <li>gender (Male / Female / Other)</li>
                    <li>class_name (e.g. Class 1)</li>
                    <li>section_name (e.g. A)</li>
                    <li>contact_number (10-15 digits)</li>
                    <li>address</li>
                    <li>admission_date (YYYY-MM-DD)</li>
                </ul>

                <h6 class="fw-bold">Optional Fields:</h6>
                <ul class="small mb-0" style="padding-left: 1.2rem;">
                    <li>mother_name</li>
                    <li>roll_number</li>
                    <li>email</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
