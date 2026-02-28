<?php
require_once 'config/database.php';

// Arrays of Indian names
$firstNames = [
    'Male' => ['Aarav', 'Arjun', 'Aditya', 'Rohan', 'Rahul', 'Ravi', 'Karan', 'Amit', 'Vivek', 'Raj', 'Sanjay', 'Vijay', 'Nitin', 'Harsh', 'Dev', 'Aryan', 'Dhruv', 'Kabir', 'Samar', 'Yash', 'Ishaan', 'Advait', 'Vihaan', 'Reyansh', 'Shaurya'],
    'Female' => ['Aadhya', 'Ananya', 'Diya', 'Ishita', 'Kavya', 'Meera', 'Priya', 'Riya', 'Saanvi', 'Tara', 'Zara', 'Aisha', 'Navya', 'Pari', 'Siya', 'Kiara', 'Myra', 'Sara', 'Anika', 'Shruti', 'Pooja', 'Neha', 'Simran', 'Tanvi', 'Ridhi']
];

$lastNames = ['Sharma', 'Verma', 'Patel', 'Kumar', 'Singh', 'Gupta', 'Jain', 'Agarwal', 'Reddy', 'Shah', 'Mehta', 'Desai', 'Kapoor', 'Malhotra', 'Chopra', 'Nair', 'Iyer', 'Rao', 'Pandey', 'Tiwari', 'Mishra', 'Saxena', 'Joshi', 'Bose', 'Roy'];

$streets = ['MG Road', 'Park Street', 'Mall Road', 'Residency Road', 'Brigade Road', 'Station Road', 'Gandhi Nagar', 'Nehru Street', 'Sardar Patel Marg', 'Ashok Nagar'];

$cities = ['Mumbai', 'Delhi', 'Bangalore', 'Hyderabad', 'Chennai', 'Kolkata', 'Pune', 'Ahmedabad', 'Jaipur', 'Lucknow'];

try {
    $db = getDB()->getConnection();

    // Get all sections mapped by class_id
    $sectionsQuery = $db->query("SELECT section_id, class_id FROM sections ORDER BY class_id, section_id");
    $sectionsByClass = [];
    foreach ($sectionsQuery->fetchAll() as $section) {
        $sectionsByClass[$section['class_id']][] = $section['section_id'];
    }

    echo "Generating 50 random students...\n\n";

    for ($i = 1; $i <= 50; $i++) {
        // Random gender
        $gender = (rand(0, 1) == 0) ? 'Male' : 'Female';

        // Random names
        $firstName = $firstNames[$gender][array_rand($firstNames[$gender])];
        $lastName = $lastNames[array_rand($lastNames)];
        $fatherName = $firstNames['Male'][array_rand($firstNames['Male'])] . ' ' . $lastName;
        $motherName = $firstNames['Female'][array_rand($firstNames['Female'])] . ' ' . $lastName;

        // Generate unique admission number
        $admissionNo = 'ADM' . date('Y') . str_pad($i + 100, 4, '0', STR_PAD_LEFT);

        // Random date of birth (age 5-18 years)
        $age = rand(5, 18);
        $dob = date('Y-m-d', strtotime("-$age years -" . rand(0, 365) . " days"));

        // Assign class based on age
        if ($age >= 3 && $age <= 4) $classId = 1; // Nursery
        elseif ($age == 5) $classId = 2; // LKG
        elseif ($age == 6) $classId = 3; // UKG
        elseif ($age == 7) $classId = 4; // Class 1
        elseif ($age == 8) $classId = 5; // Class 2
        elseif ($age == 9) $classId = 6; // Class 3
        elseif ($age == 10) $classId = 7; // Class 4
        elseif ($age == 11) $classId = 8; // Class 5
        elseif ($age == 12) $classId = 9; // Class 6
        elseif ($age == 13) $classId = 10; // Class 7
        elseif ($age == 14) $classId = 11; // Class 8
        elseif ($age == 15) $classId = 12; // Class 9
        elseif ($age == 16) $classId = 13; // Class 10
        elseif ($age == 17) $classId = 14; // Class 11
        else $classId = 15; // Class 12

        // Get random section for this class
        $availableSections = $sectionsByClass[$classId];
        $sectionId = $availableSections[array_rand($availableSections)];

        // Roll number
        $rollNumber = str_pad(rand(1, 50), 2, '0', STR_PAD_LEFT);

        // Contact details
        $contactNumber = '98' . rand(10000000, 99999999);
        $email = strtolower($firstName . '.' . $lastName . rand(1, 999) . '@gmail.com');

        // Address
        $houseNo = rand(1, 999);
        $street = $streets[array_rand($streets)];
        $city = $cities[array_rand($cities)];
        $pincode = rand(100000, 999999);
        $address = "$houseNo, $street, $city - $pincode";

        // Admission date (within last 2 years)
        $admissionDate = date('Y-m-d', strtotime('-' . rand(0, 730) . ' days'));

        // Academic year
        $academicYear = '2026-2027';

        // Insert student
        $sql = "INSERT INTO students (
            admission_no, first_name, last_name, father_name, mother_name,
            date_of_birth, gender, class_id, section_id, roll_number,
            contact_number, email, address, admission_date, academic_year, status
        ) VALUES (
            :admission_no, :first_name, :last_name, :father_name, :mother_name,
            :date_of_birth, :gender, :class_id, :section_id, :roll_number,
            :contact_number, :email, :address, :admission_date, :academic_year, 'active'
        )";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':admission_no' => $admissionNo,
            ':first_name' => $firstName,
            ':last_name' => $lastName,
            ':father_name' => $fatherName,
            ':mother_name' => $motherName,
            ':date_of_birth' => $dob,
            ':gender' => $gender,
            ':class_id' => $classId,
            ':section_id' => $sectionId,
            ':roll_number' => $rollNumber,
            ':contact_number' => $contactNumber,
            ':email' => $email,
            ':address' => $address,
            ':admission_date' => $admissionDate,
            ':academic_year' => $academicYear
        ]);

        echo "[$i/50] Added: $firstName $lastName (Admission: $admissionNo, Class: $classId, Section: $sectionId)\n";
    }

    echo "\n✓ Successfully added 50 random students!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
