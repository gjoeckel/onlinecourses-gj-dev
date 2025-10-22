<?php
require_once 'db.php';
require_once 'config.php';

$db = new Database();
$errors = [];
$success = false;

// Get enterprises and organizations for dropdowns
$enterprises = $db->select("SELECT id, name FROM enterprises WHERE status = 'active' ORDER BY name");
$organizations = $db->select("SELECT id, name, enterprise_id FROM organizations WHERE status = 'active' ORDER BY name");

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
    $name = trim($_POST['name'] ?? '');
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $cohort = trim($_POST['cohort'] ?? '');
    $enterprise_id = trim($_POST['enterprise_id'] ?? '');
    $organization_id = trim($_POST['organization_id'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $invitation_date = trim($_POST['invitation_date'] ?? '');
    $enrolled = isset($_POST['enrolled']) ? (int)$_POST['enrolled'] : 0;
    $certificate = isset($_POST['certificate']) ? (int)$_POST['certificate'] : 0;
    $alumni = isset($_POST['alumni']) ? (int)$_POST['alumni'] : 0;
    $deletion_status = trim($_POST['deletion_status'] ?? 'active');

    if (!$email || !preg_match('/\.edu$/', $email)) {
        $errors[] = "Please enter a valid .edu email address";
    }
    if (empty($name)) $errors[] = "Name is required";
    if (!preg_match('/^\d{4}-\d{2}$/', $cohort)) $errors[] = "Please select a valid cohort (YYYY-MM)";
    if (empty($enterprise_id)) $errors[] = "Enterprise is required";
    if (empty($organization_id)) $errors[] = "Organization is required";
    if (!in_array($role, ['faculty', 'staff'])) $errors[] = "Please select a valid role";

    if (empty($errors)) {
        try {
            $data = [
                'name' => $name,
                'email' => $email,
                'cohort' => $cohort,
                'enterprise_id' => $enterprise_id,
                'organization_id' => $organization_id,
                'role' => $role,
                'invitation_date' => $invitation_date,
                'enrolled' => $enrolled,
                'certificate' => $certificate,
                'alumni' => $alumni,
                'deletion_status' => $deletion_status,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Update main registrations table
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
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="name" name="name" required
                           value="<?php echo htmlspecialchars($registration['name']); ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label for="email" class="form-label">Email (.edu address required)</label>
                <input type="email" class="form-control" id="email" name="email" required
                       pattern=".+\.edu$"
                       value="<?php echo htmlspecialchars($registration['email']); ?>">
                <div class="form-text">Must be a valid .edu email address</div>
            </div>
            
            <div class="mb-3">
                <label for="cohort" class="form-label">Cohort (YYYY-MM)</label>
                <select class="form-select" id="cohort" name="cohort" required>
                    <option value="">Select cohort...</option>
                    <?php
                    $now = new DateTime();
                    $cohorts = [];
                    $cohorts[] = $now->format('Y-m');
                    $now->modify('+1 month');
                    $cohorts[] = $now->format('Y-m');
                    $selectedCohort = $registration['cohort'] ?? '';
                    foreach ($cohorts as $c) {
                        $selected = ($selectedCohort === $c) ? 'selected' : '';
                        echo "<option value='$c' $selected>$c</option>";
                    }
                    ?>
                </select>
                <div class="form-text">Format: YYYY-MM (e.g., 2024-07)</div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="enterprise_id" class="form-label">Enterprise</label>
                    <select class="form-select" id="enterprise_id" name="enterprise_id" required>
                        <option value="">Select enterprise...</option>
                        <?php foreach ($enterprises as $enterprise): ?>
                            <option value="<?php echo $enterprise['id']; ?>" 
                                    <?php echo ($registration['enterprise_id'] ?? '') == $enterprise['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($enterprise['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="organization_id" class="form-label">Organization</label>
                    <select class="form-select" id="organization_id" name="organization_id" required>
                        <option value="">Select organization...</option>
                        <?php 
                        // Show organizations for the currently selected enterprise
                        if (!empty($registration['enterprise_id'])) {
                            foreach ($organizations as $org) {
                                if ($org['enterprise_id'] == $registration['enterprise_id']) {
                                    $selected = ($registration['organization_id'] ?? '') == $org['id'] ? 'selected' : '';
                                    echo "<option value='{$org['id']}' $selected>" . htmlspecialchars($org['name']) . "</option>";
                                }
                            }
                        }
                        ?>
                    </select>
                </div>
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
                <label for="deletion_status" class="form-label">Account Status</label>
                <select class="form-select" id="deletion_status" name="deletion_status">
                    <option value="active" <?php echo ($registration['deletion_status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="deleted" <?php echo ($registration['deletion_status'] ?? 'active') === 'deleted' ? 'selected' : ''; ?>>Deleted (Soft Delete)</option>
                </select>
                <div class="form-text">"Deleted" hides the user from normal operations but preserves data</div>
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
    
    <script>
        // Organizations filtering based on selected enterprise
        const organizationsByEnterprise = <?php echo json_encode(array_reduce($organizations, function($carry, $org) {
            $carry[$org['enterprise_id']][] = $org;
            return $carry;
        }, [])); ?>;
        const organizationSelect = document.getElementById('organization_id');
        const enterpriseSelect = document.getElementById('enterprise_id');
        
        enterpriseSelect.addEventListener('change', function() {
            const enterpriseId = this.value;
            organizationSelect.innerHTML = '<option value="">Select organization...</option>';
            
            if (enterpriseId && organizationsByEnterprise[enterpriseId]) {
                organizationsByEnterprise[enterpriseId].forEach(org => {
                    const option = document.createElement('option');
                    option.value = org.id;
                    option.textContent = org.name;
                    organizationSelect.appendChild(option);
                });
            }
        });
    </script>
</body>
</html> 