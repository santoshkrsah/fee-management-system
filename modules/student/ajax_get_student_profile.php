<?php
/**
 * AJAX: Get complete student profile with fee summary and payment history
 */
require_once '../../config/database.php';
require_once '../../includes/session.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id'])) {
    try {
        $student_id = (int)$_POST['student_id'];
        $selectedSession = getSelectedSession();
        $feeMode = getFeeMode();

        $db = getDB();

        // 1. Student personal details
        $student = $db->fetchOne("
            SELECT
                s.*,
                CONCAT(s.first_name, ' ', s.last_name) AS full_name,
                c.class_name,
                sec.section_name
            FROM students s
            JOIN classes c ON s.class_id = c.class_id
            JOIN sections sec ON s.section_id = sec.section_id
            WHERE s.student_id = :id
        ", ['id' => $student_id]);

        if (!$student) {
            echo json_encode(['success' => false, 'message' => 'Student not found']);
            exit;
        }

        // 2. Fee structure total (annual)
        $feeStructureRow = $db->fetchOne("
            SELECT COALESCE(fs.total_fee, 0) AS total_fee
            FROM fee_structure fs
            WHERE fs.class_id = :cid AND fs.academic_year = :year AND fs.status = 'active'
        ", ['cid' => $student['class_id'], 'year' => $selectedSession]);
        $annualFee = $feeStructureRow ? (float)$feeStructureRow['total_fee'] : 0.0;

        // Total paid (separate query to avoid GROUP BY issues)
        $paidRow = $db->fetchOne("
            SELECT COALESCE(SUM(fc.total_paid), 0) AS total_paid
            FROM fee_collection fc
            WHERE fc.student_id = :sid AND fc.academic_year = :year
        ", ['sid' => $student_id, 'year' => $selectedSession]);

        // Compute totals based on fee mode
        if ($feeMode === 'monthly') {
            // In monthly mode, total fee = sum of all monthly fee structures for the class
            try {
                $monthlyTotalRow = $db->fetchOne("
                    SELECT COALESCE(SUM(total_fee), 0) AS annual_total
                    FROM monthly_fee_structure
                    WHERE class_id = :cid AND academic_year = :year AND status = 'active'
                ", ['cid' => $student['class_id'], 'year' => $selectedSession]);
                $totalFee = $monthlyTotalRow ? (float)$monthlyTotalRow['annual_total'] : $annualFee;
            } catch (Exception $e) {
                // monthly_fee_structure table may not exist yet
                $totalFee = $annualFee;
            }
        } else {
            $totalFee = $annualFee;
        }

        $totalPaid = $paidRow ? (float)$paidRow['total_paid'] : 0.0;
        $balance = $totalFee - $totalPaid;

        // Determine payment status
        if ($totalPaid > 0 && $balance <= 0) {
            $status = 'Paid';
        } elseif ($totalPaid > 0) {
            $status = 'Partial';
        } else {
            $status = 'Unpaid';
        }

        // 3. Payment history
        // Try with fee_month column first; fall back if column doesn't exist yet
        try {
            $payments = $db->fetchAll("
                SELECT
                    fc.payment_id,
                    fc.receipt_no,
                    fc.payment_date,
                    fc.total_paid,
                    fc.payment_mode,
                    fc.fee_month,
                    fc.remarks
                FROM fee_collection fc
                WHERE fc.student_id = :sid AND fc.academic_year = :year
                ORDER BY fc.payment_date DESC, fc.payment_id DESC
            ", ['sid' => $student_id, 'year' => $selectedSession]);
        } catch (Exception $e) {
            // fee_month column may not exist yet if schema not updated
            $payments = $db->fetchAll("
                SELECT
                    fc.payment_id,
                    fc.receipt_no,
                    fc.payment_date,
                    fc.total_paid,
                    fc.payment_mode,
                    fc.remarks
                FROM fee_collection fc
                WHERE fc.student_id = :sid AND fc.academic_year = :year
                ORDER BY fc.payment_date DESC, fc.payment_id DESC
            ", ['sid' => $student_id, 'year' => $selectedSession]);
        }

        // 4. Monthly breakdown (if monthly mode)
        $monthlyBreakdown = [];
        if ($feeMode === 'monthly') {
            try {
                $monthlyBreakdown = $db->fetchAll("
                    SELECT
                        mfs.fee_month,
                        mfs.month_label,
                        mfs.total_fee AS monthly_fee,
                        COALESCE(SUM(fc.total_paid), 0) AS monthly_paid,
                        (mfs.total_fee - COALESCE(SUM(fc.total_paid), 0)) AS monthly_balance
                    FROM monthly_fee_structure mfs
                    LEFT JOIN fee_collection fc ON fc.student_id = :sid
                        AND fc.fee_month = mfs.fee_month
                        AND fc.academic_year = mfs.academic_year
                    WHERE mfs.class_id = :cid AND mfs.academic_year = :year AND mfs.status = 'active'
                    GROUP BY mfs.fee_month, mfs.month_label, mfs.total_fee
                    ORDER BY mfs.fee_month
                ", ['sid' => $student_id, 'cid' => $student['class_id'], 'year' => $selectedSession]);
            } catch (Exception $e) {
                // monthly_fee_structure table may not exist yet
                $monthlyBreakdown = [];
            }
        }

        echo json_encode([
            'success' => true,
            'student' => [
                'student_id' => $student['student_id'],
                'admission_no' => $student['admission_no'],
                'full_name' => $student['full_name'],
                'first_name' => $student['first_name'],
                'last_name' => $student['last_name'],
                'father_name' => $student['father_name'],
                'mother_name' => $student['mother_name'] ?? '',
                'date_of_birth' => $student['date_of_birth'],
                'gender' => $student['gender'],
                'class_name' => $student['class_name'],
                'section_name' => $student['section_name'],
                'roll_number' => $student['roll_number'] ?? '',
                'contact_number' => $student['contact_number'],
                'email' => $student['email'] ?? '',
                'address' => $student['address'],
                'admission_date' => $student['admission_date'],
                'academic_year' => $student['academic_year']
            ],
            'fee_summary' => [
                'total_fee' => $totalFee,
                'total_paid' => $totalPaid,
                'balance' => $balance,
                'status' => $status
            ],
            'payments' => $payments,
            'monthly_breakdown' => $monthlyBreakdown,
            'fee_mode' => $feeMode
        ]);

    } catch (Exception $e) {
        error_log($e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error loading student profile'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}
?>
