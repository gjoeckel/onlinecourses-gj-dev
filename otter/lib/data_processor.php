<?php
/**
 * Data Processor
 * Centralized data processing utilities for reports pages
 *
 * GOOGLE SHEETS COLUMN NUMBERING:
 * - Google Sheets uses 1-based column indexing (A=1, B=2, C=3, etc.)
 * - Array indices are 0-based (0, 1, 2, etc.)
 * - Mapping: Google Sheets Column A ΓåÆ Array Index 0, Column B ΓåÆ Array Index 1, etc.
 *
 * KEY COLUMN MAPPINGS (from config/csu.config):
 * - Registration Date: Google Sheets Column B (1) ΓåÆ Array Index 1
 * - Enrollment Status: Google Sheets Column C (2) ΓåÆ Array Index 2
 * - Organization: Google Sheets Column J (9) ΓåÆ Array Index 9
 * - Certificate Status: Google Sheets Column K (10) ΓåÆ Array Index 10
 * - Certificate Issued Date: Google Sheets Column L (11) ΓåÆ Array Index 11
 * - Submission Date (Registrants): Google Sheets Column P (15) ΓåÆ Array Index 15
 * - Submission Date (Submissions): Google Sheets Column P (15) ΓåÆ Array Index 15
 * - Status: Google Sheets Column Q (16) ΓåÆ Array Index 16
 *
 * ENROLLMENT LOGIC:
 * - Enrollments track registrations that are also enrolled
 * - Uses same date range logic as registrations (Invited date)
 * - Additional condition: Enrolled column must be "Yes"
 */

require_once __DIR__ . '/abbreviation_utils.php';

class DataProcessor {
    private static $orgIdx = 9; // Organization column index (Google Sheets Column J)



    /**
     * Process invitations data for date range (PRESERVED - processes "Invited" dates from registrants sheet)
     * This method processes invitation dates, not actual registrations. It is preserved for future use
     * and comparison with the new registration logic that uses submissions data.
     *
     * @param array $registrantsData - Raw registrants data
     * @param string $start - Start date in MM-DD-YY format
     * @param string $end - End date in MM-DD-YY format
     * @return array Processed data with invitations, enrollments, and certificates
     */
    public static function processInvitationsData($registrantsData, $start, $end) {
        // Use hardcoded Google Sheets column indices for reliable data processing
        $regDateIdx = 1;      // Google Sheets Column B (Invited)
        $enrolledIdx = 2;     // Google Sheets Column C (Enrolled)
        $certificateIdx = 10; // Google Sheets Column K (Certificate)
        $issuedDateIdx = 11;  // Google Sheets Column L (Issued)

        $invitations = [];
        $enrollments = [];
        $certificates = [];

        $processedCount = 0;
        $invitationCount = 0;
        $enrollmentCount = 0;
        $certificateCount = 0;

        foreach ($registrantsData as $rowIndex => $row) {
            $processedCount++;

            if (!is_array($row)) {
                continue;
            }

            // Use hardcoded Google Sheets column indices (data already trimmed at source)
            $regDate = isset($row[$regDateIdx]) ? $row[$regDateIdx] : '';
            $enrolled = isset($row[$enrolledIdx]) && $row[$enrolledIdx] === 'Yes';
            $certificate = isset($row[$certificateIdx]) && $row[$certificateIdx] === 'Yes';
            $issuedDate = isset($row[$issuedDateIdx]) ? $row[$issuedDateIdx] : '';

            // Note: Cohort and year are no longer used for enrollment calculation
            // Enrollments now use the same date range logic as registrations

            if (self::inRange($regDate, $start, $end)) {
                $invitations[] = $row;
                $invitationCount++;
            }

            // Enrollments: Track registrations that are also enrolled
            // Use same logic to determine registrations in date range, then add condition that Enrolled column must be "Yes"
            if (self::inRange($regDate, $start, $end) && $enrolled) {
                $enrollments[] = $row;
                $enrollmentCount++;
            }

            if ($certificate && self::inRange($issuedDate, $start, $end)) {
                $certificates[] = $row;
                $certificateCount++;
            }
        }

        return [
            'invitations' => $invitations,
            'enrollments' => $enrollments,
            'certificates' => $certificates
        ];
    }

    /**
     * Process registrations data for date range (NEW - uses submissions "Filtered" sheet)
     * This method processes actual registration submissions using the submissions sheet
     * "Submitted" column (index 15) to determine registrations in date range.
     *
     * @param array $submissionsData - Raw submissions data from "Filtered" sheet
     * @param string $start - Start date in MM-DD-YY format
     * @param string $end - End date in MM-DD-YY format
     * @return array Processed registrations data
     */
    public static function processRegistrationsData($submissionsData, $start, $end) {
        // Use hardcoded Google Sheets column indices for reliable data processing
        $submittedDateIdx = 15; // Google Sheets Column P (Submitted)

        $registrations = [];
        $processedCount = 0;
        $registrationCount = 0;

        foreach ($submissionsData as $rowIndex => $row) {
            $processedCount++;

            if (!is_array($row)) {
                continue;
            }

            // Use hardcoded Google Sheets column indices (data already trimmed at source)
            $submittedDate = isset($row[$submittedDateIdx]) ? $row[$submittedDateIdx] : '';

            if (self::inRange($submittedDate, $start, $end)) {
                $registrations[] = $row;
                $registrationCount++;
            }
        }

        return $registrations;
    }

    /**
     * Process enrollments data for date range (NEW - uses "Submitted" column from cached enrollments)
     * This method filters cached enrollments data using the "Submitted" column (index 15)
     * to determine enrollments in date range, instead of using "Invited" column.
     *
     * @param array $enrollmentsData - Cached enrollments data (all qualifying enrollments)
     * @param string $start - Start date in MM-DD-YY format
     * @param string $end - End date in MM-DD-YY format
     * @return array Filtered enrollments data for date range
     */
    public static function processEnrollmentsData($enrollmentsData, $start, $end) {
        // Use hardcoded Google Sheets column indices for reliable data processing
        $submittedDateIdx = 15; // Google Sheets Column P (15)

        $filteredEnrollments = [];
        $processedCount = 0;
        $enrollmentCount = 0;

        foreach ($enrollmentsData as $rowIndex => $row) {
            $processedCount++;

            if (!is_array($row)) {
                continue;
            }

            // Use hardcoded Google Sheets column indices (data already trimmed at source)
            $submittedDate = isset($row[$submittedDateIdx]) ? $row[$submittedDateIdx] : '';

            // Filter enrollments by Submitted date range
            if (self::inRange($submittedDate, $start, $end)) {
                $filteredEnrollments[] = $row;
                $enrollmentCount++;
            }
        }

        return $filteredEnrollments;
    }

    /**
     * Process submissions data for date range
     * @param array $submissionsData - Raw submissions data
     * @param string $start - Start date in MM-DD-YY format
     * @param string $end - End date in MM-DD-YY format
     * @return array Processed submissions data
     */
    public static function processSubmissionsData($submissionsData, $start, $end) {
        // Use hardcoded Google Sheets column indices for reliable data processing
        $submittedDateIdx = 15; // Google Sheets Column P (15)

        $submissions = [];
        $processedCount = 0;
        $submissionCount = 0;

        foreach ($submissionsData as $rowIndex => $row) {
            $processedCount++;

            if (!is_array($row)) {
                continue;
            }

            // Use hardcoded Google Sheets column indices (data already trimmed at source)
            $submittedDate = isset($row[$submittedDateIdx]) ? $row[$submittedDateIdx] : '';

            if (self::inRange($submittedDate, $start, $end)) {
                $submissions[] = $row;
                $submissionCount++;
            }
        }

        return $submissions;
    }

    /**
     * Process organization data from cached files
     * @param array $registrationsRows - Registration data
     * @param array $enrollmentsRows - Enrollment data
     * @param array $certificatesRows - Certificate data
     * @return array Processed organization data
     */
    public static function processOrganizationData($registrationsRows, $enrollmentsRows, $certificatesRows) {
        // Use hardcoded Google Sheets column indices for reliable data processing
        $orgIdx = 9; // Google Sheets Column J (9)

        // Get ALL organizations from config FIRST (not just those with data)
        $config = UnifiedEnterpriseConfig::getFullConfig();
        $configOrgs = $config['organizations'] ?? [];

        // Start with ALL organizations from config, ensuring they're all included
        $organizationSet = [];
        foreach ($configOrgs as $orgName) {
            $organizationSet[$orgName] = true;
        }

        // Also add any organizations found in the data (in case there are new ones not in config)
        foreach ($registrationsRows as $rowIndex => $row) {
            if (isset($row[$orgIdx])) {
                $organization = $row[$orgIdx];
                if ($organization !== '') {
                    $organizationSet[$organization] = true;
                }
            }
        }

        foreach ($enrollmentsRows as $rowIndex => $row) {
            if (isset($row[$orgIdx])) {
                $organization = $row[$orgIdx];
                if ($organization !== '') {
                    $organizationSet[$organization] = true;
                }
            }
        }

        foreach ($certificatesRows as $rowIndex => $row) {
            if (isset($row[$orgIdx])) {
                $organization = $row[$orgIdx];
                if ($organization !== '') {
                    $organizationSet[$organization] = true;
                }
            }
        }

        // Convert to array and sort
        $organizations = array_keys($organizationSet);
        sort($organizations);

        // Process each organization
        $organizationData = [];
        foreach ($organizations as $orgName) {
            $registrations = 0;
            $enrollments = 0;
            $certificates = 0;

            // Count registrations for this organization
            foreach ($registrationsRows as $row) {
                if (isset($row[$orgIdx]) && $row[$orgIdx] === $orgName) {
                    $registrations++;
                }
            }

            // Count enrollments for this organization
            foreach ($enrollmentsRows as $row) {
                if (isset($row[$orgIdx]) && $row[$orgIdx] === $orgName) {
                    $enrollments++;
                }
            }

            // Count certificates for this organization
            foreach ($certificatesRows as $row) {
                if (isset($row[$orgIdx]) && $row[$orgIdx] === $orgName) {
                    $certificates++;
                }
            }

            // Add organization data with abbreviated name
            // Note: Organizations with all zero values are included to support client-side data display options
            $organizationData[] = [
                'organization' => $orgName,
                'organization_display' => abbreviateOrganizationName($orgName),
                'registrations' => $registrations,
                'enrollments' => $enrollments,
                'certificates' => $certificates
            ];
        }

        return $organizationData;
    }

    /**
     * Check if date is within range
     * @param string $date - Date in MM-DD-YY format
     * @param string $start - Start date in MM-DD-YY format
     * @param string $end - End date in MM-DD-YY format
     * @return bool True if date is within range
     */
    private static function inRange($date, $start, $end) {
        if (empty($date)) {
            return false;
        }

        $d = DateTime::createFromFormat('m-d-y', $date);
        $s = DateTime::createFromFormat('m-d-y', $start);
        $e = DateTime::createFromFormat('m-d-y', $end);

        if (!$d || !$s || !$e) {
            return false;
        }

        return $d >= $s && $d <= $e;
    }


}
