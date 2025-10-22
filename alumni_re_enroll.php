<?php
session_start();
require_once 'db.php';
require_once 'config.php';

$errors = [];
$success = false;

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user's registration info
$db = new Database();
$user = $db->select(
    "SELECT * FROM registrations WHERE id = ?",
    [$_SESSION['user_id']]
)[0] ?? null;

if (!$user) {
    header('Location: login.php');
    exit;
}

// Verify user is eligible for re-enrollment
if ($user['status'] !== 'expired') {
    header('Location: course_access.php');
    exit;
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate payment info
    $cardNumber = trim($_POST['card_number'] ?? '');
    $expiryMonth = trim($_POST['expiry_month'] ?? '');
    $expiryYear = trim($_POST['expiry_year'] ?? '');
    $cvv = trim($_POST['cvv'] ?? '');
    $name = trim($_POST['name'] ?? '');
    
    if (empty($cardNumber) || !preg_match('/^\d{16}$/', $cardNumber)) {
        $errors[] = "Please enter a valid 16-digit card number";
    }
    if (empty($expiryMonth) || !preg_match('/^(0[1-9]|1[0-2])$/', $expiryMonth)) {
        $errors[] = "Please enter a valid expiry month (01-12)";
    }
    if (empty($expiryYear) || !preg_match('/^\d{4}$/', $expiryYear)) {
        $errors[] = "Please enter a valid expiry year";
    }
    if (empty($cvv) || !preg_match('/^\d{3,4}$/', $cvv)) {
        $errors[] = "Please enter a valid CVV";
    }
    if (empty($name)) {
        $errors[] = "Please enter the name on the card";
    }
    
    if (empty($errors)) {
        try {
            // TODO: Integrate with actual payment processor
            
            // Update user's status to reenrolled
            $db->update('registrations', [
                'status' => 'reenrolled',
                'reenrolleddate' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$user['id']]);
            
            // Get the re-enrollment course
            $reenrollCourse = $db->select(
                "SELECT * FROM courses WHERE cohort LIKE ? ORDER BY id DESC LIMIT 1",
                ['%reenroll%']
            )[0] ?? null;
            
            if ($reenrollCourse) {
                // Enroll user in re-enrollment course
                $enrollSuccess = enrollCanvasUser($user['canvas_user_id'], $reenrollCourse['course_id']);
                if ($enrollSuccess) {
                    // Update course_id
                    $db->update('registrations', [
                        'course_id' => $reenrollCourse['id']
                    ], 'id = ?', [$user['id']]);
                    
                    $_SESSION['success'] = "Payment successful! You will receive an invitation email for the re-enrollment cohort.";
                    header('Location: course_access.php');
                    exit;
                } else {
                    $errors[] = "Failed to enroll in re-enrollment course. Please contact support.";
                }
            } else {
                $errors[] = "Re-enrollment course not found. Please contact support.";
            }
        } catch (Exception $e) {
            $errors[] = "Payment failed. Please try again.";
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
    <?php echo getHtmlHead('Re-enroll in Course'); ?>
    <style>
        .payment-form {
            max-width: 600px;
            margin: 2rem auto;
        }
        .card-details {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="card payment-form">
            <div class="card-body">
                <h1 class="card-title text-center mb-4">Re-enroll in Course</h1>
                
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
                
                <div class="card-details">
                    <h5>Course Details</h5>
                    <p>You are re-enrolling in the accessibility course. The cost is $25.</p>
                    <p>Your previous cohort: <?php echo htmlspecialchars($user['cohort']); ?></p>
                </div>
                
                <form method="POST" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="name" class="form-label">Name on Card</label>
                        <input type="text" class="form-control" id="name" name="name" required
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="card_number" class="form-label">Card Number</label>
                        <input type="text" class="form-control" id="card_number" name="card_number" required
                               pattern="\d{16}" maxlength="16"
                               value="<?php echo htmlspecialchars($_POST['card_number'] ?? ''); ?>">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="expiry_month" class="form-label">Expiry Month</label>
                            <input type="text" class="form-control" id="expiry_month" name="expiry_month" required
                                   pattern="(0[1-9]|1[0-2])" maxlength="2" placeholder="MM"
                                   value="<?php echo htmlspecialchars($_POST['expiry_month'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="expiry_year" class="form-label">Expiry Year</label>
                            <input type="text" class="form-control" id="expiry_year" name="expiry_year" required
                                   pattern="\d{4}" maxlength="4" placeholder="YYYY"
                                   value="<?php echo htmlspecialchars($_POST['expiry_year'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="cvv" class="form-label">CVV</label>
                        <input type="text" class="form-control" id="cvv" name="cvv" required
                               pattern="\d{3,4}" maxlength="4"
                               value="<?php echo htmlspecialchars($_POST['cvv'] ?? ''); ?>">
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Pay $25 and Re-enroll</button>
                    </div>
                </form>
                
                <div class="mt-4 text-center">
                    <p class="text-muted">Your payment information is secure and encrypted.</p>
                    <p>Need help? Contact <a href="mailto:support@yourdomain.com">support@yourdomain.com</a></p>
                </div>
            </div>
        </div>
    </div>
    
    <?php echo getJsIncludes(); ?>
    <script>
        // Add client-side validation
        document.querySelector('form').addEventListener('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            this.classList.add('was-validated');
        });
    </script>
</body>
</html> 