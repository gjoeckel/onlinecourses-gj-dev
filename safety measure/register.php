<?php
session_start();
require_once 'db.php';
require_once 'config.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    
    // Get and validate form data
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $cohort = filter_input(INPUT_POST, 'cohort', FILTER_VALIDATE_INT);
    $year = filter_input(INPUT_POST, 'year', FILTER_VALIDATE_INT);
    $organization = trim($_POST['organization'] ?? '');
    $college = trim($_POST['college'] ?? '');
    $role = trim($_POST['role'] ?? '');
    
    if (!$email || !preg_match('/\.edu$/', $email)) {
        $errors[] = "Please enter a valid .edu email address";
    }
    if (empty($firstName)) $errors[] = "First name is required";
    if (empty($lastName)) $errors[] = "Last name is required";
    if ($cohort < 1 || $cohort > 12) $errors[] = "Please select a valid cohort month";
    if ($year < 24 || $year > 99) $errors[] = "Please select a valid year";
    if (empty($organization)) $errors[] = "Organization is required";
    if (empty($college)) $errors[] = "College is required";
    if (!in_array($role, ['faculty', 'staff'])) $errors[] = "Please select a valid role";
    
    if (empty($errors)) {
        try {
            $data = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'year' => $year,
                'cohort' => $cohort,
                'organization' => $organization,
                'college' => $college,
                'role' => $role,
                'alumni' => 0,
                'certificate' => 0
            ];
            $db->insert('registrations', $data);
            $_SESSION['success'] = "Registration successful!";
            header('Location: register.php');
            exit;
        } catch (Exception $e) {
            $errors[] = "Registration failed. Please try again.";
        }
    }
}

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php echo getHtmlHead('Course Registration'); ?>
</head>
<body>
    <div class="container mt-5">
        <h1>Course Registration</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
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
                           value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="last_name" class="form-label">Last Name</label>
                    <input type="text" class="form-control" id="last_name" name="last_name" required
                           value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label for="email" class="form-label">Email (.edu address required)</label>
                <input type="email" class="form-control" id="email" name="email" required
                       pattern=".+\.edu$"
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                <div class="form-text">Must be a valid .edu email address</div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="cohort" class="form-label">Cohort Month</label>
                    <select class="form-select" id="cohort" name="cohort" required>
                        <option value="">Select month...</option>
                        <?php
                        $currentMonth = (int)date('n');
                        $currentYear = (int)date('Y');
                        $months = [
                            1 => 'January', 2 => 'February', 3 => 'March',
                            4 => 'April', 5 => 'May', 6 => 'June',
                            7 => 'July', 8 => 'August', 9 => 'September',
                            10 => 'October', 11 => 'November', 12 => 'December'
                        ];
                        
                        // Show current month and next month
                        for ($i = 0; $i < 2; $i++) {
                            $monthNum = $currentMonth + $i;
                            if ($monthNum > 12) {
                                $monthNum = 1;
                                $currentYear++;
                            }
                            $selected = (isset($_POST['cohort']) && $_POST['cohort'] == $monthNum) ? 'selected' : '';
                            echo "<option value='{$monthNum}' {$selected}>{$months[$monthNum]} {$currentYear}</option>";
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
                        $selected = (isset($_POST['year']) && $_POST['year'] == $currentYear) ? 'selected' : '';
                        echo "<option value='{$currentYear}' {$selected}>20{$currentYear}</option>";
                        ?>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="organization" class="form-label">Organization</label>
                <input type="text" class="form-control" id="organization" name="organization" required
                       value="<?php echo htmlspecialchars($_POST['organization'] ?? ''); ?>">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Role</label>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="role" id="role_faculty" value="faculty" required
                           <?php echo (isset($_POST['role']) && $_POST['role'] === 'faculty') ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="role_faculty">Faculty</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="role" id="role_staff" value="staff"
                           <?php echo (isset($_POST['role']) && $_POST['role'] === 'staff') ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="role_staff">Staff</label>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="college" class="form-label">College</label>
                <input type="text" class="form-control" id="college" name="college" required
                       value="<?php echo htmlspecialchars($_POST['college'] ?? ''); ?>">
            </div>
            
            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Register</button>
                <a href="registrations.php" class="btn btn-secondary">View Registrations</a>
            </div>
        </form>
    </div>
    
    <?php echo getJsIncludes(); ?>
</body>
</html> 