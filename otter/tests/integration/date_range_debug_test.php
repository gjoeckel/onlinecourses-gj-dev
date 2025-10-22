<?php
/**
 * Date Range Debug Test
 * 
 * This test specifically investigates why submissions for 07-16-25 are not being included
 * in the registrations counts when date range is 07-01-25 to 07-16-25
 */

require_once __DIR__ . '/../../lib/data_processor.php';
require_once __DIR__ . '/../../lib/enterprise_data_service.php';
require_once __DIR__ . '/../../lib/unified_enterprise_config.php';

class DateRangeDebugTest {
    
    public function runDebugTest() {
        echo "=== DATE RANGE DEBUG TEST ===\n";
        echo "Testing why 07-16-25 submissions are not included in 07-01-25 to 07-16-25 range\n\n";
        
        // Test the inRange function directly
        $this->testInRangeFunction();
        
        // Test with actual data
        $this->testWithActualData();
    }
    
    private function testInRangeFunction() {
        echo "1. Testing inRange function directly:\n";
        
        $start = '07-01-25';
        $end = '07-16-25';
        $testDate = '07-16-25';
        
        echo "   Start date: $start\n";
        echo "   End date: $end\n";
        echo "   Test date: $testDate\n";
        
        // Use reflection to access private method
        $reflection = new ReflectionClass('DataProcessor');
        $inRangeMethod = $reflection->getMethod('inRange');
        $inRangeMethod->setAccessible(true);
        
        $result = $inRangeMethod->invoke(null, $testDate, $start, $end);
        echo "   inRange result: " . ($result ? 'TRUE' : 'FALSE') . "\n\n";
        
        // Test edge cases
        echo "   Edge case tests:\n";
        $edgeCases = [
            '07-01-25' => 'Start date',
            '07-16-25' => 'End date',
            '07-15-25' => 'Day before end',
            '07-17-25' => 'Day after end',
            '06-30-25' => 'Day before start',
            '07-02-25' => 'Day after start'
        ];
        
        foreach ($edgeCases as $date => $description) {
            $result = $inRangeMethod->invoke(null, $date, $start, $end);
            echo "     $date ($description): " . ($result ? 'IN RANGE' : 'OUT OF RANGE') . "\n";
        }
        echo "\n";
    }
    
    private function testWithActualData() {
        echo "2. Testing with actual submissions data:\n";
        
        // Test with CCC enterprise
        $_ENV['ENTERPRISE'] = 'ccc';
        $config = UnifiedEnterpriseConfig::getFullConfig();
        
        $cacheFile = "cache/ccc/all-submissions-data.json";
        
        if (!file_exists($cacheFile)) {
            echo "   ERROR: Submissions cache file not found: $cacheFile\n";
            echo "   Please ensure submissions data has been fetched first.\n";
            return;
        }
        
        $json = json_decode(file_get_contents($cacheFile), true);
        $submissionsData = isset($json['data']) ? $json['data'] : [];
        
        echo "   Total submissions records: " . count($submissionsData) . "\n";
        
        // Use hardcoded Google Sheets column indices for reliable data processing
        $submittedDateIdx = 15; // Google Sheets Column P (15)
        echo "   Submitted column index: $submittedDateIdx\n";
        
        // Print first 10 raw Submitted values to see what's in the data
        echo "   First 10 raw Submitted values:\n";
        for ($i = 0; $i < min(10, count($submissionsData)); $i++) {
            $row = $submissionsData[$i];
            $rawValue = isset($row[$submittedDateIdx]) ? $row[$submittedDateIdx] : 'EMPTY';
            echo "     Row $i: '$rawValue'\n";
        }
        
        // Check what dates are actually in the submissions data
        $allDates = [];
        foreach ($submissionsData as $row) {
            if (isset($row[$submittedDateIdx])) {
                $date = trim($row[$submittedDateIdx]);
                if (!empty($date) && preg_match('/^\d{2}-\d{2}-\d{2}$/', $date)) {
                    $allDates[] = $date;
                }
            }
        }
        $uniqueDates = array_unique($allDates);
        sort($uniqueDates);
        echo "   Unique dates in submissions data: " . implode(', ', array_slice($uniqueDates, 0, 10)) . "... (total: " . count($uniqueDates) . ")\n";
        
        $targetDate = '07-16-25';
        $matchingRecords = [];
        
        foreach ($submissionsData as $index => $row) {
            if (isset($row[$submittedDateIdx])) {
                $submittedDate = trim($row[$submittedDateIdx]);
                if ($submittedDate === $targetDate) {
                    $matchingRecords[] = [
                        'index' => $index,
                        'date' => $submittedDate,
                        'row' => $row
                    ];
                }
            }
        }
        
        echo "   Records with date $targetDate: " . count($matchingRecords) . "\n";
        
        if (count($matchingRecords) > 0) {
            echo "   Sample matching record:\n";
            $sample = $matchingRecords[0];
            echo "     Row index: " . $sample['index'] . "\n";
            echo "     Submitted date: " . $sample['date'] . "\n";
            echo "     First few columns: " . implode(', ', array_slice($sample['row'], 0, 5)) . "\n";
            // Print raw value and hex for all matching records
            echo "\n   Raw value and hex for all matching records:\n";
            foreach ($matchingRecords as $rec) {
                $raw = $rec['row'][$submittedDateIdx];
                $trimmed = trim($raw);
                $hex = bin2hex($raw);
                $trimmedHex = bin2hex($trimmed);
                $dt = DateTime::createFromFormat('m-d-y', $trimmed);
                $dtStatus = $dt ? 'OK' : 'FAIL';
                echo "     Raw: '$raw' | Hex: $hex | Trimmed: '$trimmed' | Trimmed Hex: $trimmedHex | DateTime: $dtStatus\n";
            }
        }
        
        // Test the processRegistrationsData method
        echo "\n3. Testing processRegistrationsData method:\n";
        
        $start = '07-01-25';
        $end = '07-16-25';
        
        $registrations = DataProcessor::processRegistrationsData($submissionsData, $start, $end);
        
        echo "   Date range: $start to $end\n";
        echo "   Registrations found: " . count($registrations) . "\n";
        
        // Check if any of the matching records are in the results
        $foundInResults = 0;
        foreach ($matchingRecords as $match) {
            foreach ($registrations as $reg) {
                if ($reg === $match['row']) {
                    $foundInResults++;
                    break;
                }
            }
        }
        
        echo "   Matching records found in results: $foundInResults\n";
        
        if (count($matchingRecords) > 0 && $foundInResults === 0) {
            echo "   ⚠️  WARNING: Records with date $targetDate exist but are not in results!\n";
            echo "   This suggests a problem with the date processing logic.\n";
        }
        
        // Check for the presence of the date value in the results by comparing Submitted field values
        echo "\n4. Checking for date value presence in results:\n";
        $dateValuesInResults = [];
        foreach ($registrations as $reg) {
            if (isset($reg[$submittedDateIdx])) {
                $dateValuesInResults[] = trim($reg[$submittedDateIdx]);
            }
        }
        $dateValuesInResults = array_unique($dateValuesInResults);
        sort($dateValuesInResults);
        
        echo "   Unique Submitted dates in results: " . implode(', ', $dateValuesInResults) . "\n";
        
        if (in_array($targetDate, $dateValuesInResults)) {
            echo "   ✅ Date $targetDate IS present in results!\n";
        } else {
            echo "   ❌ Date $targetDate is NOT present in results.\n";
        }
    }
}

// Run the test if called directly
if (php_sapi_name() === 'cli' || isset($_GET['run'])) {
    $test = new DateRangeDebugTest();
    $test->runDebugTest();
}
?> 