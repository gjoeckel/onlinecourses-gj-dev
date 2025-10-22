<?php
require_once 'db.php';
require_once 'config.php';

$db = new Database();
$errors = [];
$success = false;

// Get registration ID from URL
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    die("Invalid registration ID");
}

// Get registration data
$registration = $db->select("SELECT * FROM registrations WHERE id = ?", [$id])[0] ?? null;
if (!$registration) {
    die("Registration not found");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $year = filter_input(INPUT_POST, 'year', FILTER_VALIDATE_INT);
    $cohort = filter_input(INPUT_POST, 'cohort', FILTER_VALIDATE_INT);
    $organization = trim($_POST['organization'] ?? '');
    $college = trim($_POST['college'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $invitation_date = trim($_POST['invitation_date'] ?? '');
    $enrolled = isset($_POST['enrolled']) ? (int)$_POST['enrolled'] : 0;
    $certificate = isset($_POST['certificate']) ? (int)$_POST['certificate'] : 0;
    $alumni = isset($_POST['alumni']) ? (int)$_POST['alumni'] : 0;

    if (!$email || !preg_match('/\.edu$/', $email)) {
        $errors[] = "Please enter a valid .edu email address";
    }
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if ($cohort < 1 || $cohort > 12) $errors[] = "Please select a valid cohort month";
    if ($year < 24 || $year > 99) $errors[] = "Please select a valid year";
    if (empty($organization)) $errors[] = "Organization is required";
    if (empty($college)) $errors[] = "College is required";
    if (!in_array($role, ['faculty', 'staff'])) $errors[] = "Please select a valid role";

    if (empty($errors)) {
        try {
            $data = [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'year' => $year,
                'cohort' => $cohort,
                'organization' => $organization,
                'college' => $college,
                'role' => $role,
                'invitation_date' => $invitation_date,
                'enrolled' => $enrolled,
                'certificate' => $certificate,
                'alumni' => $alumni
            ];
            $db->update('registrations', $data, 'id = ?', [$id]);
            $success = true;
            // Refresh registration data
            $registration = $db->select("SELECT * FROM registrations WHERE id = ?", [$id])[0];
        } catch (Exception $e) {
            $errors[] = "Update failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php echo getHtmlHead('Edit Registration'); ?>
</head>
<body>
    <div class="container mt-5 pb-5">
        <h1>Edit Registration</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                Registration updated successfully!
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="needs-validation" novalidate>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="first_name" class="form-label">First Name</label>
                    <input type="text" class="form-control" id="first_name" name="first_name" required
                           value="<?php echo htmlspecialchars($registration['first_name']); ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="last_name" class="form-label">Last Name</label>
                    <input type="text" class="form-control" id="last_name" name="last_name" required
                           value="<?php echo htmlspecialchars($registration['last_name']); ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label for="email" class="form-label">Email (.edu address required)</label>
                <input type="email" class="form-control" id="email" name="email" required
                       pattern=".+\.edu$"
                       value="<?php echo htmlspecialchars($registration['email']); ?>">
                <div class="form-text">Must be a valid .edu email address</div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="cohort" class="form-label">Cohort Month</label>
                    <select class="form-select" id="cohort" name="cohort" required>
                        <option value="">Select month...</option>
                        <?php
                        $currentMonth = (int)date('n');
                        $currentYearFull = (int)date('Y');
                        $months = [
                            1 => 'January', 2 => 'February', 3 => 'March',
                            4 => 'April', 5 => 'May', 6 => 'June',
                            7 => 'July', 8 => 'August', 9 => 'September',
                            10 => 'October', 11 => 'November', 12 => 'December'
                        ];
                        $regYear = (int)$registration['year'];
                        $regCohort = (int)$registration['cohort'];
                        $tempYear = $currentYearFull;
                        for ($i = 0; $i < 2; $i++) {
                            $monthNum = $currentMonth + $i;
                            if ($monthNum > 12) {
                                $monthNum = 1;
                                $tempYear++;
                            }
                            $selected = ($regCohort == $monthNum && $regYear == ($tempYear % 100)) ? 'selected' : '';
                            echo "<option value='{$monthNum}' {$selected}>{$months[$monthNum]} {$tempYear}</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="year" class="form-label">Year</label>
                    <select class="form-select" id="year" name="year" required>
                        <option value="">Select year...</option>
                        <?php
                        $currentYear = (int)date('y');
                        $selected = ($registration['year'] == $currentYear) ? 'selected' : '';
                        echo "<option value='{$currentYear}' {$selected}>20{$currentYear}</option>";
                        ?>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="organization" class="form-label">Organization</label>
                <input type="text" class="form-control" id="organization" name="organization" required
                       value="<?php echo htmlspecialchars($registration['organization']); ?>">
            </div>
            
            <div class="mb-3">
                <label for="college" class="form-label">College</label>
                <input type="text" class="form-control" id="college" name="college" required
                       value="<?php echo htmlspecialchars($registration['college']); ?>">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Role</label>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="role" id="role_faculty" value="faculty" required
                           <?php echo ($registration['role'] === 'faculty') ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="role_faculty">Faculty</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="role" id="role_staff" value="staff"
                           <?php echo ($registration['role'] === 'staff') ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="role_staff">Staff</label>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="invitation_date" class="form-label">Invitation Date</label>
                <input type="text" class="form-control" id="invitation_date" name="invitation_date"
                       value="<?php echo htmlspecialchars($registration['invitation_date']); ?>">
            </div>
            
            <div class="mb-3">
                <label for="enrolled" class="form-label">Enrolled</label>
                <input type="number" class="form-control" id="enrolled" name="enrolled"
                       value="<?php echo htmlspecialchars($registration['enrolled']); ?>">
            </div>
            
            <div class="mb-3">
                <label for="certificate" class="form-label">Certificate</label>
                <input type="number" class="form-control" id="certificate" name="certificate"
                       value="<?php echo htmlspecialchars($registration['certificate']); ?>">
            </div>
            
            <div class="mb-3">
                <label for="alumni" class="form-label">Alumni</label>
                <input type="number" class="form-control" id="alumni" name="alumni"
                       value="<?php echo htmlspecialchars($registration['alumni']); ?>">
            </div>
            
            <div class="mb-3">
                <label class="form-label">System Information</label>
                <div class="form-text">
                    Created: <?php echo htmlspecialchars($registration['created_at']); ?><br>
                    Last Updated: <?php echo htmlspecialchars($registration['updated_at']); ?>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">Update Registration</button>
            <a href="registrations.php" class="btn btn-secondary">Back to List</a>
        </form>
    </div>
    
    <?php echo getJsIncludes(); ?>
</body>
</html> 