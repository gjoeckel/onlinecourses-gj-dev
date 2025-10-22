<?php
session_start();
require_once 'db.php';
require_once 'config.php';

$errors = [];
$success = false;

$apiUrl = $config['canvas']['api_url'];
$accessToken = $config['canvas']['access_token'];

// Get enterprises and organizations for dropdowns
$db = new Database();
$enterprises = $db->select("SELECT id, name FROM enterprises WHERE status = 'active' ORDER BY name");
$organizations = $db->select("SELECT id, name, enterprise_id FROM organizations ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    
    // Get and validate form data
    $name = trim($_POST['name'] ?? '');
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $cohort = trim($_POST['cohort'] ?? '');
    $enterprise_id = trim($_POST['enterprise_id'] ?? '');
    $organization_id = trim($_POST['organization_id'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $selectedCourseId = trim($_POST['course_id'] ?? '');
    
    if (!$email || !preg_match('/\.edu$/', $email)) {
        $errors[] = "Please enter a valid .edu email address";
    }
    if (empty($name)) $errors[] = "Name is required";
    if (!preg_match('/^\\d{4}-\\d{2}$/', $cohort)) $errors[] = "Please select a valid cohort (YYYY-MM)";
    if (empty($enterprise_id)) $errors[] = "Enterprise is required";
    if (empty($organization_id)) $errors[] = "Organization is required";
    if (!in_array($role, ['faculty', 'staff'])) $errors[] = "Please select a valid role";
    if (empty($selectedCourseId)) $errors[] = "Please select a course";
    
    // Validate that the selected course exists and is active
    if (!empty($selectedCourseId)) {
        $course = $db->select("SELECT * FROM courses WHERE id = ?", [$selectedCourseId]);
        if (empty($course)) {
            $errors[] = "Selected course does not exist";
        } else {
            $course = $course[0];
            // Check if course is currently active (you can modify this logic as needed)
            $currentDate = date('Y-m-d');
            if ($currentDate < $course['open_date'] || $currentDate > $course['close_date']) {
                $errors[] = "Selected course is not currently active";
            }
        }
    }
    
    if (empty($errors)) {
        try {
            // Check for existing registration
            $existingRegistration = $db->select(
                "SELECT * FROM registrations WHERE email = ? ORDER BY created_at DESC LIMIT 1",
                [$email]
            );

            if (!empty($existingRegistration)) {
                $existingUser = $existingRegistration[0];
                $existingStatus = $existingUser['status'];
                $existingCohort = $existingUser['cohort'];
                
                // Get current date for cohort comparison
                $currentDate = new DateTime();
                $cohortDate = new DateTime($cohort . '-01');
                $lastMonthDate = (clone $currentDate)->modify('-1 month');
                $lastMonthDate->setDate($lastMonthDate->format('Y'), $lastMonthDate->format('m'), 1);
                
                // Check if cohort is current (this month, last month, re-enrollment, or review)
                $isCurrentCohort = (
                    $cohortDate->format('Y-m') === $currentDate->format('Y-m') || // This month
                    $cohortDate->format('Y-m') === $lastMonthDate->format('Y-m') || // Last month
                    strpos($cohort, 'reenroll') !== false || // Re-enrollment cohort
                    strpos($cohort, 'review') !== false // Review cohort
                );

                if (!$isCurrentCohort && in_array($existingStatus, ['submitter', 'active'])) {
                    // For closed cohort or last month's cohort with submitter/active status
                    // Set enrollment to inactive and enroll in new cohort
                    $canvasUserId = $existingUser['canvas_user_id'];
                    if ($canvasUserId) {
                        // Set enrollment to inactive
                        $inactiveSuccess = setEnrollmentState($canvasUserId, 'inactive');
                        if ($inactiveSuccess) {
                            // Enroll in new cohort
                            $enrollSuccess = enrollCanvasUser($canvasUserId);
                            if ($enrollSuccess) {
                                // Update registration
                                $db->update('registrations', [
                                    'status' => 'submitter',
                                    'cohort' => $cohort,
                                    'enterprise_id' => $enterprise_id,
                                    'organization_id' => $organization_id,
                                    'role' => $role,
                                    'deletion_status' => 'active',
                                    'updated_at' => date('Y-m-d H:i:s')
                                ], 'id = ?', [$existingUser['id']]);
                                
                                $_SESSION['success'] = "Registration updated! You will receive an invitation email for the new cohort.";
                                header('Location: register.php');
                                exit;
                            }
                        }
                    }
                } elseif ($isCurrentCohort && in_array($existingStatus, ['active', 'enrollee', 'completer'])) {
                    // Redirect to course access page
                    $_SESSION['redirect_info'] = [
                        'cohort' => $existingCohort,
                        'status' => $existingStatus
                    ];
                    header('Location: course_access.php');
                    exit;
                } elseif ($isCurrentCohort && in_array($existingStatus, ['submitter', 'reenrolled', 'earner'])) {
                    // Check if user has actually accepted invite
                    $canvasUser = getCanvasUserByCohort($existingUser['canvas_user_id'], $existingUser['course_id']);
                    if ($canvasUser && isset($canvasUser['login_id'])) {
                        // User has accepted invite, update status
                        $newStatus = $existingStatus === 'earner' ? 'review' : 'active';
                        $db->update('registrations', [
                            'status' => $newStatus,
                            'updated_at' => date('Y-m-d H:i:s')
                        ], 'id = ?', [$existingUser['id']]);
                        
                        $_SESSION['redirect_info'] = [
                            'cohort' => $existingCohort,
                            'status' => $newStatus
                        ];
                        header('Location: course_access.php');
                        exit;
                    } else {
                        // Resend invitation
                        $canvasUserId = $existingUser['canvas_user_id'];
                        if ($canvasUserId) {
                            // Set to inactive then invited to trigger new email
                            $inactiveSuccess = setEnrollmentState($canvasUserId, 'inactive');
                            if ($inactiveSuccess) {
                                $inviteSuccess = setEnrollmentState($canvasUserId, 'invited');
                                if ($inviteSuccess) {
                                    $_SESSION['success'] = "Invitation email has been resent. Please check your email.";
                                    header('Location: register.php');
                                    exit;
                                }
                            }
                        }
                    }
                } elseif ($existingStatus === 'expired') {
                    // Redirect to alumni page for re-enrollment
                    header('Location: alumni_re_enroll.php');
                    exit;
                }
            }

            // If we get here, either no existing registration or none of the above conditions matched
            // Proceed with new registration
            $data = [
                'name' => $name,
                'email' => $email,
                'cohort' => $cohort,
                'enterprise_id' => $enterprise_id,
                'organization_id' => $organization_id,
                'role' => $role,
                'alumni' => 0,
                'certificate' => 0,
                'status' => 'submitter',
                'deletion_status' => 'active',
                'quiz_completion_status' => null,
                'missing_quizzes' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Insert into main registrations table
            $registrationId = $db->insert('registrations', $data);
            
            // Insert into backup submissions table with registration_id
            $submissionData = [
                'registration_id' => $registrationId,
                'name' => $name,
                'email' => $email,
                'cohort' => $cohort,
                'enterprise_id' => $enterprise_id,
                'organization_id' => $organization_id,
                'role' => $role
            ];
            $db->insert('registration_submissions', $submissionData);
            
            // --- Canvas User Creation and Enrollment with Robust Error Handling ---
            function logError($message, $data = null) {
                $logFile = '/var/websites/webaim/logs/canvas_api.log';
                $date = date('Y-m-d H:i:s');
                $logMessage = "[$date] $message";
                if ($data !== null) {
                    $logMessage .= "\nData: " . print_r($data, true);
                }
                $logMessage .= "\n";
                file_put_contents($logFile, $logMessage, FILE_APPEND);
            }

            function getCanvasUserIdByEmail($email) {
                global $apiUrl, $accessToken;
                $endpoint = "{$apiUrl}/accounts/240/users?search_term=" . urlencode($email);
                
                logError("Searching for Canvas user with email: $email", ['endpoint' => $endpoint]);
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $endpoint);
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $accessToken
                ]);
                
                $result = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                
                if ($result === false) {
                    $error = curl_error($ch);
                    logError("Canvas API (get user) cURL error", [
                        'error' => $error,
                        'email' => $email
                    ]);
                    curl_close($ch);
                    return null;
                }
                
                $json = json_decode($result, true);
                curl_close($ch);
                
                if ($httpCode !== 200) {
                    logError("Canvas API (get user) HTTP error", [
                        'httpCode' => $httpCode,
                        'response' => $result,
                        'email' => $email
                    ]);
                    return null;
                }
                
                logError("Canvas API response for user search", [
                    'email' => $email,
                    'response' => $json
                ]);
                
                if (is_array($json) && count($json) > 0 && isset($json[0]['id'])) {
                    return $json[0]['id'];
                }
                
                return null;
            }

            function createCanvasUser($name, $email) {
                global $apiUrl, $accessToken;
                
                logError("Attempting to create Canvas user", [
                    'name' => $name,
                    'email' => $email
                ]);
                
                $endpoint = "{$apiUrl}/accounts/240/users";
                $data = [
                    'user' => [
                        'name' => $name,
                        'terms_of_use' => true,
                        'skip_registration' => true
                    ],
                    'pseudonym' => [
                        'unique_id' => $email,
                        'send_confirmation' => false
                    ],
                    'communication_channel' => [
                        'type' => 'email',
                        'address' => $email,
                        'skip_confirmation' => true
                    ]
                ];
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $endpoint);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $accessToken
                ]);
                
                $result = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                
                if ($result === false) {
                    $error = curl_error($ch);
                    logError("Canvas API (create user) cURL error", [
                        'error' => $error,
                        'name' => $name,
                        'email' => $email
                    ]);
                    curl_close($ch);
                    return null;
                }
                
                $json = json_decode($result, true);
                curl_close($ch);
                
                if ($httpCode !== 200) {
                    logError("Canvas API (create user) HTTP error", [
                        'httpCode' => $httpCode,
                        'response' => $result,
                        'name' => $name,
                        'email' => $email
                    ]);
                    return null;
                }
                
                logError("Canvas API user creation response", [
                    'name' => $name,
                    'email' => $email,
                    'response' => $json
                ]);
                
                if (isset($json['id'])) {
                    return $json['id'];
                }
                
                return null;
            }

            function enrollCanvasUser($canvasUserId, $courseId = '2681') {
                global $apiUrl, $accessToken;
                
                logError("Attempting to enroll user in Canvas", [
                    'canvasUserId' => $canvasUserId,
                    'courseId' => $courseId
                ]);
                
                $endpoint = "{$apiUrl}/courses/{$courseId}/enrollments";
                $data = [
                    'enrollment' => [
                        'user_id' => $canvasUserId,
                        'type' => 'StudentEnrollment',
                        'enrollment_state' => 'invited',
                        'notify' => true
                    ]
                ];
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $endpoint);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $accessToken
                ]);
                
                $result = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                
                if ($result === false) {
                    $error = curl_error($ch);
                    logError("Canvas API (enroll) cURL error", [
                        'error' => $error,
                        'userId' => $canvasUserId
                    ]);
                    curl_close($ch);
                    return false;
                }
                
                $json = json_decode($result, true);
                curl_close($ch);
                
                if ($httpCode !== 200) {
                    logError("Canvas API (enroll) HTTP error", [
                        'httpCode' => $httpCode,
                        'response' => $result,
                        'userId' => $canvasUserId
                    ]);
                    return false;
                }
                
                logError("Canvas API enrollment response", [
                    'userId' => $canvasUserId,
                    'response' => $json
                ]);
                
                return true;
            }

            function setEnrollmentState($canvasUserId, $state) {
                global $apiUrl, $accessToken;
                
                $endpoint = "{$apiUrl}/courses/2681/enrollments";
                $data = [
                    'enrollment' => [
                        "user_id" => $canvasUserId,
                        "enrollment_state" => $state
                    ]
                ];
                $fields = json_encode($data);
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $endpoint);
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $accessToken
                ]);
                
                $result = curl_exec($ch);
                if ($result === false) {
                    logError("Canvas API (set enrollment state) cURL error: " . curl_error($ch));
                    curl_close($ch);
                    return false;
                }
                
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                return $httpCode === 200 || $httpCode === 201;
            }

            function getCanvasUserByCohort($canvasUserId, $courseId) {
                global $apiUrl, $accessToken;
                
                $endpoint = "{$apiUrl}/courses/{$courseId}/users/{$canvasUserId}";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $endpoint);
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $accessToken
                ]);
                
                $result = curl_exec($ch);
                if ($result === false) {
                    logError("Canvas API (get user by cohort) cURL error: " . curl_error($ch));
                    curl_close($ch);
                    return null;
                }
                
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $json = json_decode($result, true);
                curl_close($ch);
                
                if ($httpCode === 200 && is_array($json)) {
                    return $json;
                }
                
                return null;
            }

            // Check if user exists in Canvas, otherwise create
            $canvasUserId = getCanvasUserIdByEmail($email);
            
            if (!$canvasUserId) {
                logError("No existing Canvas user found, attempting to create one", ['email' => $email]);
                $canvasUserId = createCanvasUser($name, $email);
                
                if (!$canvasUserId) {
                    logError("Registration failed: Could not create Canvas user", ['email' => $email]);
                    throw new Exception("Failed to create Canvas user account. Please try again later.");
                }
            }
            
            // Now enroll the user
            $enrollSuccess = enrollCanvasUser($canvasUserId, $course['course_id']);
            if (!$enrollSuccess) {
                logError("Registration failed: Could not enroll Canvas user", [
                    'userId' => $canvasUserId,
                    'email' => $email
                ]);
                throw new Exception("Failed to enroll in the course. Please try again later.");
            }
            
            // Update the registration with the Canvas user ID and selected course ID
            $db->update('registrations', [
                'canvas_user_id' => $canvasUserId,
                'course_id' => $selectedCourseId
            ], 'id = ?', [$registrationId]);
            
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

// Initialize variables for form display
$selectedCourseId = $_POST['course_id'] ?? '';

// Organizations are now loaded dynamically via JavaScript
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php echo getHtmlHead('User Registration'); ?>
    <style>
        .dropdown-menu {
            border: 1px solid #ced4da;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .dropdown-item {
            padding: 8px 16px;
            cursor: pointer;
        }
        .dropdown-item:hover {
            background-color: #f8f9fa;
        }
        .search-highlight {
            background-color: #fff3cd;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1>User Registration</h1>
        
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
                <div class="col-md-12 mb-3">
                    <label for="name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="name" name="name" required
                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label for="email" class="form-label">Email (.edu address required)</label>
                <input type="email" class="form-control" id="email" name="email" required
                       pattern=".+\.edu$"
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
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
                    $selectedCohort = $_POST['cohort'] ?? '';
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
                                    <?php echo ($_POST['enterprise_id'] ?? '') == $enterprise['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($enterprise['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="organization_search" class="form-label">Organization</label>
                    <div class="position-relative">
                        <input type="text" class="form-control" id="organization_search" 
                               placeholder="Start typing organization name..." 
                               autocomplete="off" required
                               value="<?php 
                                   if (!empty($_POST['organization_id'])) {
                                       $orgName = $db->select("SELECT name FROM organizations WHERE id = ?", [$_POST['organization_id']]);
                                       echo htmlspecialchars($orgName[0]['name'] ?? '');
                                   }
                               ?>">
                        <div id="organization_results" class="dropdown-menu w-100" style="display: none; max-height: 200px; overflow-y: auto;">
                            <!-- Search results will appear here -->
                        </div>
                    </div>
                    <input type="hidden" id="organization_id" name="organization_id" required>
                    <div class="form-text">Type to search for your specific institution</div>
                </div>
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
                <label for="course_search" class="form-label">Course</label>
                <div class="position-relative">
                    <input type="text" class="form-control" id="course_search" 
                           placeholder="Start typing course name or cohort..." 
                           autocomplete="off" required
                           value="<?php 
                               if (!empty($selectedCourseId)) {
                                   $courseData = $db->select("SELECT course_title, cohort FROM courses WHERE id = ?", [$selectedCourseId]);
                                   if (!empty($courseData)) {
                                       echo htmlspecialchars($courseData[0]['course_title'] . " (Cohort: " . $courseData[0]['cohort'] . ")");
                                   }
                               }
                           ?>">
                    <div id="course_results" class="dropdown-menu w-100" style="display: none; max-height: 200px; overflow-y: auto;">
                        <!-- Search results will appear here -->
                    </div>
                </div>
                <input type="hidden" id="course_id" name="course_id" required>
                <div class="form-text">Type to search for available courses</div>
            </div>
            
            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Register</button>
                <a href="registrations.php" class="btn btn-secondary">View Registrations</a>
            </div>
        </form>
    </div>
    
    <?php echo getJsIncludes(); ?>
    
    <script>
        // Global variables for searchable organization dropdown
        let organizations = [];
        let selectedEnterpriseId = null;
        
        // Initialize the form
        document.addEventListener('DOMContentLoaded', function() {
            const enterpriseSelect = document.getElementById('enterprise_id');
            const organizationSearch = document.getElementById('organization_search');
            const organizationResults = document.getElementById('organization_results');
            const organizationIdInput = document.getElementById('organization_id');
            
            // Enterprise selection change
            enterpriseSelect.addEventListener('change', function() {
                selectedEnterpriseId = this.value;
                if (selectedEnterpriseId) {
                    loadOrganizations(selectedEnterpriseId);
                } else {
                    organizations = [];
                    organizationSearch.value = '';
                    organizationIdInput.value = '';
                }
            });
            
            // Organization search input
            organizationSearch.addEventListener('input', function() {
                const query = this.value.toLowerCase();
                if (query.length < 2) {
                    organizationResults.style.display = 'none';
                    return;
                }
                
                const filtered = organizations.filter(org => 
                    org.name.toLowerCase().includes(query)
                );
                
                displaySearchResults(filtered, query);
            });
            
            // Focus events
            organizationSearch.addEventListener('focus', function() {
                if (this.value.length >= 2) {
                    const query = this.value.toLowerCase();
                    const filtered = organizations.filter(org => 
                        org.name.toLowerCase().includes(query)
                    );
                    displaySearchResults(filtered, query);
                }
            });
            
            // Click outside to close dropdown
            document.addEventListener('click', function(e) {
                if (!organizationSearch.contains(e.target) && !organizationResults.contains(e.target)) {
                    organizationResults.style.display = 'none';
                }
            });
            
            // Load organizations if enterprise is pre-selected
            if (enterpriseSelect.value) {
                selectedEnterpriseId = enterpriseSelect.value;
                loadOrganizations(selectedEnterpriseId);
            }
        });
        
        // Load organizations for selected enterprise
        function loadOrganizations(enterpriseId) {
            fetch(`get_organizations.php?enterprise_id=${enterpriseId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        organizations = data.organizations || [];
                        console.log(`Loaded ${organizations.length} organizations for enterprise ${enterpriseId}`);
                    } else {
                        console.error('API error:', data.error);
                        organizations = [];
                    }
                })
                .catch(error => {
                    console.error('Error loading organizations:', error);
                    organizations = [];
                });
        }
        
        // Display search results
        function displaySearchResults(results, query) {
            const organizationResults = document.getElementById('organization_results');
            
            if (results.length === 0) {
                organizationResults.innerHTML = '<div class="dropdown-item text-muted">No organizations found</div>';
                organizationResults.style.display = 'block';
                return;
            }
            
            let html = '';
            results.forEach(org => {
                const highlightedName = highlightSearchTerm(org.name, query);
                html += `
                    <div class="dropdown-item" data-org-id="${org.id}" data-org-name="${org.name}">
                        ${highlightedName}
                    </div>
                `;
            });
            
            organizationResults.innerHTML = html;
            organizationResults.style.display = 'block';
            
            // Add click handlers
            organizationResults.querySelectorAll('.dropdown-item').forEach(item => {
                item.addEventListener('click', function() {
                    const orgId = this.dataset.orgId;
                    const orgName = this.dataset.orgName;
                    
                    document.getElementById('organization_search').value = orgName;
                    document.getElementById('organization_id').value = orgId;
                    organizationResults.style.display = 'none';
                });
            });
        }
        
        // Highlight search terms in results
        function highlightSearchTerm(text, query) {
            if (!query) return text;
            const regex = new RegExp(`(${query})`, 'gi');
            return text.replace(regex, '<span class="search-highlight">$1</span>');
        }
        
        // Course search functionality
        let courses = [];
        
        // Load courses on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadCourses();
        });
        
        // Load courses from API
        function loadCourses() {
            fetch('get_courses.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        courses = data.courses || [];
                        console.log(`Loaded ${courses.length} courses`);
                    } else {
                        console.error('API error:', data.error);
                        courses = [];
                    }
                })
                .catch(error => {
                    console.error('Error loading courses:', error);
                    courses = [];
                });
        }
        
        // Course search input
        document.addEventListener('DOMContentLoaded', function() {
            const courseSearch = document.getElementById('course_search');
            const courseResults = document.getElementById('course_results');
            const courseIdInput = document.getElementById('course_id');
            
            courseSearch.addEventListener('input', function() {
                const query = this.value.toLowerCase();
                if (query.length < 2) {
                    courseResults.style.display = 'none';
                    return;
                }
                
                const filtered = courses.filter(course => 
                    course.course_title.toLowerCase().includes(query) ||
                    course.cohort.toLowerCase().includes(query)
                );
                
                displayCourseResults(filtered, query);
            });
            
            // Focus events
            courseSearch.addEventListener('focus', function() {
                if (this.value.length >= 2) {
                    const query = this.value.toLowerCase();
                    const filtered = courses.filter(course => 
                        course.course_title.toLowerCase().includes(query) ||
                        course.cohort.toLowerCase().includes(query)
                    );
                    displayCourseResults(filtered, query);
                }
            });
            
            // Click outside to close dropdown
            document.addEventListener('click', function(e) {
                if (!courseSearch.contains(e.target) && !courseResults.contains(e.target)) {
                    courseResults.style.display = 'none';
                }
            });
        });
        
        // Display course search results
        function displayCourseResults(results, query) {
            const courseResults = document.getElementById('course_results');
            
            if (results.length === 0) {
                courseResults.innerHTML = '<div class="dropdown-item text-muted">No courses found</div>';
                courseResults.style.display = 'block';
                return;
            }
            
            let html = '';
            results.forEach(course => {
                const highlightedTitle = highlightSearchTerm(course.course_title, query);
                const highlightedCohort = highlightSearchTerm(course.cohort, query);
                const statusClass = course.status === 'active' ? 'text-success' : course.status === 'upcoming' ? 'text-warning' : 'text-muted';
                const statusText = course.status === 'active' ? 'Active' : course.status === 'upcoming' ? 'Upcoming' : 'Closed';
                
                html += `
                    <div class="dropdown-item" data-course-id="${course.id}" data-course-title="${course.course_title}" data-course-cohort="${course.cohort}">
                        <div><strong>${highlightedTitle}</strong></div>
                        <div class="text-muted">Cohort: ${highlightedCohort}</div>
                        <div class="${statusClass}"><small>${statusText}</small></div>
                    </div>
                `;
            });
            
            courseResults.innerHTML = html;
            courseResults.style.display = 'block';
            
            // Add click handlers
            courseResults.querySelectorAll('.dropdown-item').forEach(item => {
                item.addEventListener('click', function() {
                    const courseId = this.dataset.courseId;
                    const courseTitle = this.dataset.courseTitle;
                    const courseCohort = this.dataset.courseCohort;
                    
                    document.getElementById('course_search').value = `${courseTitle} (Cohort: ${courseCohort})`;
                    document.getElementById('course_id').value = courseId;
                    courseResults.style.display = 'none';
                });
            });
        }
    </script>
</body>
</html> 