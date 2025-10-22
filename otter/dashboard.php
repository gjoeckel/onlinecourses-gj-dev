<?php
// Start session first
require_once __DIR__ . '/lib/session.php';
initializeSession();

require_once __DIR__ . '/lib/direct_link.php';
require_once __DIR__ . '/lib/unified_database.php';
require_once __DIR__ . '/lib/password_utils.php';
require_once __DIR__ . '/lib/canvas_data_service.php';
require_once __DIR__ . '/lib/new_database_service.php';
// STANDARDIZED: Uses UnifiedEnterpriseConfig for enterprise detection and config access
require_once __DIR__ . '/lib/unified_enterprise_config.php';

$db = new UnifiedDatabase();

// Get organization code (password) from multiple sources
$organizationCode = '';

// Priority order for password detection:
// 1. Query parameter: ?org={password}
if (isset($_GET['org']) && preg_match('/^\d{4}$/', $_GET['org'])) {
    $organizationCode = $_GET['org'];
}
// 2. PATH_INFO: /dashboard.php/{password}
elseif (isset($_SERVER['PATH_INFO']) && !empty($_SERVER['PATH_INFO'])) {
    $parts = explode('/', trim($_SERVER['PATH_INFO'], '/'));
    $password = $parts[0] ?? '';
    if (preg_match('/^\d{4}$/', $password)) {
        $organizationCode = $password;
    }
}
// 3. Legacy parameter: ?organization={password}
elseif (isset($_GET['organization']) && preg_match('/^\d{4}$/', $_GET['organization'])) {
    $organizationCode = $_GET['organization'];
}
// 4. Legacy parameter: ?password={password}
elseif (isset($_GET['password']) && preg_match('/^\d{4}$/', $_GET['password'])) {
    $organizationCode = $_GET['password'];
}

// Get organization data from database
$org = null;
$enterprise_code = null;
$valid = false;

if ($organizationCode) {
    // If specific organization name is provided, use it
    if (isset($_GET['org_name'])) {
        $org_name = $_GET['org_name'];
        $all_orgs = $db->getAllOrganizations();
        foreach ($all_orgs as $org_check) {
            if ($org_check['name'] === $org_name && 
                ($org_check['password'] === $organizationCode || password_verify($organizationCode, $org_check['password']))) {
                $org = $org_name;
                $enterprise_code = $org_check['enterprise'];
                $valid = true;
                break;
            }
        }
    } else {
        // Fallback to original method
        $orgData = $db->getOrganizationByPassword($organizationCode);
        if ($orgData) {
            $org = $orgData['name'];
            $enterprise_code = $orgData['enterprise'];
            $valid = true;
        }
    }
}

// If we have an organization, display the dashboard
if ($valid && $org) {
    // Initialize enterprise configuration
    UnifiedEnterpriseConfig::init($enterprise_code);
    
    // Initialize Canvas Data Service
    $canvasService = null;
    $canvasAvailable = false;
    
    try {
        $canvasService = new CanvasDataService();
        $canvasAvailable = true;
    } catch (Exception $e) {
        // Canvas not available, continue without it
        $canvasAvailable = false;
    }
    
    // Get Canvas data based on enterprise type
    $summary = [];
    $enrolled = [];
    $invited = [];
    $showGenericError = false;
    $newSectionData = [];
    
    // Load registrations data from database
    $dbService = new NewDatabaseService();
    $dbConnection = $dbService->getDbConnection();
    
    if ($dbConnection) {
        try {
            // Get enterprise ID from enterprise code
            $dbConnection->query("SELECT id FROM enterprises WHERE name = ?", $enterprise_code);
            $enterpriseResult = $dbConnection->fetchArray();
            
            if ($enterpriseResult) {
                $enterpriseId = $enterpriseResult['id'];
                
                // Load registrations data from database by enterprise_id
                $dbConnection->query("
                    SELECT r.*, o.name as org_name 
                    FROM registrations r 
                    LEFT JOIN organizations o ON r.organization_id = o.id 
                    WHERE r.enterprise_id = ? AND r.deletion_status = 'active'
                    ORDER BY r.created_at DESC
                ", $enterpriseId);
                $registrationsData = $dbConnection->fetchAll();
            } else {
                // Enterprise not found, try fallback by organization name
                $orgName = strtoupper($enterprise_code);
                $dbConnection->query("
                    SELECT r.*, r.organization as org_name 
                    FROM registrations r 
                    WHERE r.organization = ? AND r.deletion_status = 'active'
                    ORDER BY r.created_at DESC
                ", $orgName);
                $registrationsData = $dbConnection->fetchAll();
                
                // If still no data, try broader search
                if (empty($registrationsData)) {
                    $dbConnection->query("
                        SELECT r.*, r.organization as org_name 
                        FROM registrations r 
                        WHERE r.organization LIKE ? AND r.deletion_status = 'active'
                        ORDER BY r.created_at DESC
                    ", "%{$orgName}%");
                    $registrationsData = $dbConnection->fetchAll();
                }
            }
            
            // Process registrations data for dashboard display
            $summary = [];
            $enrolled = [];
            $invited = [];
            $newSectionData = [];
            
            // Group by cohort and year for summary
            $grouped = [];
            foreach ($registrationsData as $reg) {
                $cohort = $reg['cohort'] ?? 'Unknown';
                $year = $reg['year'] ?? date('Y');
                $key = $cohort . '-' . $year;
                
                if (!isset($grouped[$key])) {
                    $grouped[$key] = [
                        'cohort' => $cohort,
                        'year' => $year,
                        'enrollments' => 0,
                        'completed' => 0,
                        'certificates' => 0
                    ];
                }
                
                // Count enrollments
                if ($reg['enrolled'] == 1) {
                    $grouped[$key]['enrollments']++;
                }
                
                // Count completions (status = 'earner')
                if ($reg['status'] === 'earner') {
                    $grouped[$key]['completed']++;
                }
                
                // Count certificates
                if ($reg['certificate'] == 1) {
                    $grouped[$key]['certificates']++;
                }
                
                // Add to enrolled list
                if ($reg['enrolled'] == 1) {
                    $enrolled[] = [
                        'first' => explode(' ', $reg['name'])[0] ?? '',
                        'last' => substr($reg['name'], strpos($reg['name'], ' ') + 1) ?? '',
                        'email' => $reg['email'] ?? '',
                        'cohort' => $cohort,
                        'year' => $year,
                        'enrolled' => 'Yes',
                        'completed' => $reg['status'] === 'earner' ? 'Yes' : 'No',
                        'certificate' => $reg['certificate'] == 1 ? 'Yes' : 'No',
                        'organization' => $reg['org_name'] ?? $reg['organization'] ?? ''
                    ];
                }
                
                // Add to invited list (if they have an invitation date)
                if (!empty($reg['invited'])) {
                    $invited[] = [
                        'invited' => $reg['invited'],
                        'cohort' => $cohort,
                        'year' => $year,
                        'first' => explode(' ', $reg['name'])[0] ?? '',
                        'last' => substr($reg['name'], strpos($reg['name'], ' ') + 1) ?? '',
                        'email' => $reg['email'] ?? ''
                    ];
                }
                
                // Add to certificates earned
                if ($reg['certificate'] == 1) {
                    $newSectionData[] = [
                        'cohort' => $cohort,
                        'year' => $year,
                        'first' => explode(' ', $reg['name'])[0] ?? '',
                        'last' => substr($reg['name'], strpos($reg['name'], ' ') + 1) ?? '',
                        'email' => $reg['email'] ?? ''
                    ];
                }
            }
            
            // Convert grouped data to array
            foreach ($grouped as $row) {
                $summary[] = $row;
            }
            
            // Debug: Log what we found
            error_log("Dashboard Debug - Enterprise: {$enterprise_code}, Found " . count($registrationsData) . " registrations");
            if (count($registrationsData) > 0) {
                $sample = $registrationsData[0];
                error_log("Sample registration: " . json_encode($sample));
            }
        } catch (Exception $e) {
            error_log("Error loading registrations data: " . $e->getMessage());
            // Continue with empty data
        }
    }
    
    if ($canvasAvailable) {
        try {
            // Get enrollments based on enterprise type
            $enrollments = [];
            switch ($enterprise_code) {
                case 'csu':
                    $enrollments = $canvasService->getCSUEnrollments();
                    break;
                case 'ccc':
                    $enrollments = $canvasService->getCCCEnrollments();
                    break;
                case 'astho':
                    $enrollments = $canvasService->getASTHOEnrollments();
                    break;
                case 'demo':
                    $enrollments = $canvasService->getDemoEnrollments();
                    break;
                default:
                    // For admin or unknown enterprise, get all enrollments
                    $enrollments = $canvasService->getAllEnrollments();
                    break;
            }
            
            if (isset($enrollments['error'])) {
                $showGenericError = true;
            } else {
                // Process Canvas enrollment data
                $grouped = [];
                foreach ($enrollments as $enrollment) {
                    $course_name = $enrollment['Organization'] ?? '';
                    $status = $enrollment['Status'] ?? '';
                    $completed = $enrollment['Completed'] ?? 'No';
                    $certificate = $enrollment['Certificate'] ?? '-';
                    
                    // Extract year from course name or use current year
                    $year = date('Y');
                    if (preg_match('/(\d{4})/', $course_name, $matches)) {
                        $year = $matches[1];
                    }
                    
                    // Extract cohort from course name
                    $cohort = 'Canvas Course';
                    if (preg_match('/([A-Za-z]+ \d{4})/', $course_name, $matches)) {
                        $cohort = $matches[1];
                    }
                    
                    $key = $cohort . '-' . $year;
                    if (!isset($grouped[$key])) {
                        $grouped[$key] = [
                            'cohort' => $cohort,
                            'year' => $year,
                            'enrollments' => 0,
                            'completed' => 0,
                            'certificates' => 0
                        ];
                    }
                    
                    // Count enrollments
                    if ($status === 'active') {
                        $grouped[$key]['enrollments']++;
                    }
                    
                    // Count completions
                    if ($completed === 'Yes') {
                        $grouped[$key]['completed']++;
                    }
                    
                    // Count certificates
                    if ($certificate === 'Yes') {
                        $grouped[$key]['certificates']++;
                    }
                    
                    // Add to enrolled list
                    if ($status === 'active') {
                        $enrolled[] = [
                            'first' => $enrollment['Name'] ?? '',
                            'last' => '',
                            'email' => $enrollment['Email'] ?? '',
                            'cohort' => $cohort,
                            'year' => $year,
                            'enrolled' => 'Yes',
                            'completed' => $completed,
                            'certificate' => $certificate,
                            'organization' => $course_name
                        ];
                    }
                }
                
                // Convert grouped data to array
                foreach ($grouped as $row) {
                    $summary[] = $row;
                }
                
                // Get course statistics for certificates
                $courseStats = $canvasService->getCourseStatistics();
                if (!isset($courseStats['error'])) {
                    foreach ($courseStats as $course) {
                        if ($course['Status'] === 'completed') {
                            $newSectionData[] = [
                                'Name' => $course['Course Name'] ?? '',
                                'CourseID' => $course['Course ID'] ?? '',
                                'Students' => $course['Students'] ?? 0,
                                'Status' => $course['Status'] ?? ''
                            ];
                        }
                    }
                }
            }
            
        } catch (Exception $e) {
            $showGenericError = true;
        }
    } else {
        // Canvas not available, show no data message
        $summary = [];
        $enrolled = [];
        $newSectionData = [];
    }

    // Sort data
    uasort($summary, function($a, $b) {
        $yearDiff = strcmp($b['year'], $a['year']);
        if ($yearDiff !== 0) return $yearDiff;
        return strcmp($b['cohort'], $a['cohort']);
    });

    usort($enrolled, function($a, $b) {
        $yearDiff = strcmp($b['year'] ?? '', $a['year'] ?? '');
        if ($yearDiff !== 0) return $yearDiff;
        $cohortDiff = strcmp($b['cohort'] ?? '', $a['cohort'] ?? '');
        if ($cohortDiff !== 0) return $cohortDiff;
        $lastDiff = strcmp($a['last'] ?? '', $b['last'] ?? '');
        if ($lastDiff !== 0) return $lastDiff;
        return strcmp($a['first'] ?? '', $b['first'] ?? '');
    });

    usort($invited, function($a, $b) {
        $dateA = null;
        $dateB = null;
        if (!empty($a['invited']) && ($dt = DateTime::createFromFormat('m-d-y', $a['invited']))) {
            $dateA = $dt->getTimestamp();
        }
        if (!empty($b['invited']) && ($dt = DateTime::createFromFormat('m-d-y', $b['invited']))) {
            $dateB = $dt->getTimestamp();
        }
        if ($dateA !== null && $dateB !== null) {
            if ($dateA != $dateB) {
                return $dateB <=> $dateA;
            }
        } elseif ($dateA !== null) {
            return -1;
        } elseif ($dateB !== null) {
            return 1;
        } else {
            $dateStrDiff = strcmp($b['invited'] ?? '', $a['invited'] ?? '');
            if ($dateStrDiff !== 0) return $dateStrDiff;
        }
        $lastDiff = strcmp($a['last'] ?? '', $b['last'] ?? '');
        if ($lastDiff !== 0) return $lastDiff;
        return strcmp($a['first'] ?? '', $b['first'] ?? '');
    });
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($org); ?> Dashboard</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="icon" type="image/svg+xml" href="lib/otter.svg">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <script src="config/config.js"></script>

</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>
<?php if ($valid && $org): ?>
    <header class="dashboard-header">
        <div class="header-center">
            <h1><?php echo htmlspecialchars($org); ?> Dashboard</h1>
            <?php if ($canvasAvailable): ?>
                <p class="last-updated">Data from Canvas LMS and Database</p>
            <?php else: ?>
                <p class="last-updated">Data from Database</p>
            <?php endif; ?>
        </div>
    </header>
    <main id="main-content">
        <?php if ($showGenericError): ?>
            <p class="error-message" role="alert">An error occurred while retrieving data. Please try again later or contact support.</p>
        <?php else: ?>
            <div id="global-toggle-controls">
                <button type="button" id="dismiss-info-button" class="close-button" aria-label="Hide master toggle switch">&times;</button>
                <p>Use this button
                    <button type="button" id="toggle-all-button" aria-expanded="false" aria-label="Show or hide data rows on all tables."></button>
                to show/hide the data rows on <strong>all</strong> tables. Use the buttons on each of the four tables to show/hide its data rows.</p>
            </div>
            <!-- Enrollment Summary -->
            <section>
                <div class="table-responsive">
                    <table class="enrollment-summary" id="enrollment-summary">
                        <caption>
                            Enrollment Summary
                            <button type="button" class="table-toggle-button" aria-expanded="false" aria-label="Show or hide enrollment summary data rows."></button>
                        </caption>
                        <thead>
                            <tr>
                                <th scope="col">Cohort</th>
                                <th scope="col">Year</th>
                                <th scope="col">Enrollments</th>
                                <th scope="col">Completed</th>
                                <th scope="col">Certificates</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($summary)): ?>
                                <tr><td colspan="5">No enrollment summary data available</td></tr>
                            <?php else: ?>
                                <?php foreach ($summary as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['cohort']); ?></td>
                                        <td><?php echo htmlspecialchars($row['year']); ?></td>
                                        <td><?php echo htmlspecialchars($row['enrollments']); ?></td>
                                        <td><?php echo htmlspecialchars($row['completed']); ?></td>
                                        <td><?php echo htmlspecialchars($row['certificates']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <?php
            // Calculate enrollments sum for Enrolled Participants caption
            $enrollmentsSum = 0;
            foreach ($summary as $row) {
                $enrollmentsSum += isset($row['enrollments']) ? intval($row['enrollments']) : 0;
            }
            ?>
            <!-- Enrolled Participants -->
            <section>
                <div class="table-responsive">
                    <table class="enrolled-participants" id="enrolled-participants">
                        <caption>
                            Enrolled Participants | <span class="caption-count"><?php echo $enrollmentsSum; ?></span>
                            <button type="button" class="table-toggle-button" aria-expanded="false" aria-label="Show or hide enrolled participants data rows."></button>
                        </caption>
                        <thead>
                            <tr>
                                <th scope="col">Days to Close</th>
                                <th scope="col">Cohort</th>
                                <th scope="col">Year</th>
                                <th scope="col">First</th>
                                <th scope="col">Last</th>
                                <th scope="col">Email</th>
                                <th scope="col">Completed</th>
                                <th scope="col">Earned</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($enrolled)): ?>
                                <tr><td colspan="8">No enrolled participants data available</td></tr>
                            <?php else: ?>
                                <?php foreach ($enrolled as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['daystoclose'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($row['cohort'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($row['year'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($row['first'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($row['last'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($row['completed'] ?? '0'); ?></td>
                                        <td><?php echo htmlspecialchars($row['certificate'] ?? '0'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <!-- Invited Participants -->
            <section>
                <div class="table-responsive">
                    <table class="invited-participants" id="invited-participants">
                        <caption>
                            Invited Participants | <span class="caption-count"><?php echo count($invited); ?></span>
                            <button type="button" class="table-toggle-button" aria-expanded="false" aria-label="Show or hide invited participants data rows."></button>
                        </caption>
                        <thead>
                            <tr>
                                <th scope="col">Invited</th>
                                <th scope="col">Cohort</th>
                                <th scope="col">Year</th>
                                <th scope="col">First</th>
                                <th scope="col">Last</th>
                                <th scope="col">Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($invited)): ?>
                                <tr><td colspan="6">No invited participants data available</td></tr>
                            <?php else: ?>
                                <?php foreach ($invited as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['invited']); ?></td>
                                        <td><?php echo htmlspecialchars($row['cohort']); ?></td>
                                        <td><?php echo htmlspecialchars($row['year']); ?></td>
                                        <td><?php echo htmlspecialchars($row['first']); ?></td>
                                        <td><?php echo htmlspecialchars($row['last']); ?></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <!-- New Section Placeholder -->
            <section>
                <div class="table-responsive">
                    <table class="certificates-earned" id="certificates-earned">
                        <caption>
                            Certificates Earned | <span class="caption-count"><?php echo isset($newSectionData) ? count($newSectionData) : 0; ?></span>
                            <button type="button" class="table-toggle-button" aria-expanded="false" aria-label="Show or hide new section data rows."></button>
                        </caption>
                        <thead>
                            <tr>
                                <th scope="col">Cohort</th>
                                <th scope="col">Year</th>
                                <th scope="col">First</th>
                                <th scope="col">Last</th>
                                <th scope="col">Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($newSectionData)): ?>
                                <tr><td colspan="5">No certificates earned data available</td></tr>
                            <?php else: ?>
                                <?php foreach ($newSectionData as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['cohort']); ?></td>
                                        <td><?php echo htmlspecialchars($row['year']); ?></td>
                                        <td><?php echo htmlspecialchars($row['first']); ?></td>
                                        <td><?php echo htmlspecialchars($row['last']); ?></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    </main>
    <script src="lib/table-interaction.js"></script>
<?php else: ?>
    <main style="max-width:600px;margin:40px auto;padding:2rem;background:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.08);">
        <h1 style="text-align:center;">Organization Dashboard Access</h1>
        <p style="text-align:center; color: red;">
        Invalid organization or password.<br>
        <span style="font-size:0.9em;">Please check your link or contact support.</span>
        </p>
    </main>
<?php endif; ?>
</body>
</html>