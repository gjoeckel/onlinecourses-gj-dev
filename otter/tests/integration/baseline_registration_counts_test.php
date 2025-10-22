<?php
/**
 * Baseline Registration Counts Test
 * 
 * This test documents current registration counts for all enterprises
 * before implementing the registrations refactor. This serves as a
 * baseline for comparison after the refactor is complete.
 */

require_once __DIR__ . '/../../lib/data_processor.php';
require_once __DIR__ . '/../../lib/enterprise_data_service.php';
require_once __DIR__ . '/../../lib/unified_enterprise_config.php';

class BaselineRegistrationCountsTest {
    private $enterprises = ['ccc', 'csu', 'demo'];
    private $dateRanges = [
        'recent' => ['start' => '01-01-25', 'end' => '12-31-25'],
        'last_month' => ['start' => '12-01-24', 'end' => '12-31-24'],
        'last_quarter' => ['start' => '10-01-24', 'end' => '12-31-24']
    ];
    
    public function runBaselineTest() {
        echo "=== BASELINE REGISTRATION COUNTS TEST ===\n";
        echo "Date: " . date('Y-m-d H:i:s') . "\n";
        echo "Purpose: Document current registration counts before refactor\n\n";
        
        $results = [];
        
        foreach ($this->enterprises as $enterprise) {
            echo "Testing Enterprise: {$enterprise}\n";
            echo str_repeat("-", 50) . "\n";
            
            $results[$enterprise] = $this->testEnterprise($enterprise);
            
            echo "\n";
        }
        
        // Save results to file
        $this->saveResults($results);
        
        echo "=== BASELINE TEST COMPLETE ===\n";
        echo "Results saved to: tests/integration/baseline_results.json\n";
    }
    
    private function testEnterprise($enterprise) {
        $results = [];
        
        try {
            // Set enterprise environment
            $_ENV['ENTERPRISE'] = $enterprise;
            
            // Get configuration
            $config = UnifiedEnterpriseConfig::getFullConfig();
            
            // Test each date range
            foreach ($this->dateRanges as $rangeName => $range) {
                echo "  Date Range: {$rangeName} ({$range['start']} to {$range['end']})\n";
                
                $rangeResults = $this->testDateRange($config, $range['start'], $range['end']);
                $results[$rangeName] = $rangeResults;
                
                echo "    Registrations: {$rangeResults['registrations']}\n";
                echo "    Enrollments: {$rangeResults['enrollments']}\n";
                echo "    Certificates: {$rangeResults['certificates']}\n";
                echo "    Submissions: {$rangeResults['submissions']}\n";
                echo "\n";
            }
            
        } catch (Exception $e) {
            echo "  ERROR: " . $e->getMessage() . "\n";
            $results['error'] = $e->getMessage();
        }
        
        return $results;
    }
    
    private function testDateRange($config, $start, $end) {
        $results = [
            'registrations' => 0,
            'enrollments' => 0,
            'certificates' => 0,
            'submissions' => 0
        ];
        
        try {
            // Test registrations (current logic)
            if (isset($config['registrants'])) {
                $registrantsData = $this->fetchRegistrantsData($config);
                if (is_array($registrantsData)) {
                    $processedData = DataProcessor::processInvitationsData($registrantsData, $start, $end);
                    $results['registrations'] = count($processedData['invitations']);
                    $results['enrollments'] = count($processedData['enrollments']);
                    $results['certificates'] = count($processedData['certificates']);
                }
            }
            
            // Test submissions (future registration logic)
            if (isset($config['submissions'])) {
                $submissionsData = $this->fetchSubmissionsData($config);
                if (is_array($submissionsData)) {
                    $submissions = DataProcessor::processSubmissionsData($submissionsData, $start, $end);
                    $results['submissions'] = count($submissions);
                }
            }
            
        } catch (Exception $e) {
            echo "    ERROR in date range test: " . $e->getMessage() . "\n";
        }
        
        return $results;
    }
    
    private function fetchRegistrantsData($config) {
        $registrantsConfig = $config['registrants'];
        $cacheFile = "cache/{$_ENV['ENTERPRISE']}/all-registrants-data.json";
        
        if (file_exists($cacheFile)) {
            $json = json_decode(file_get_contents($cacheFile), true);
            return isset($json['data']) ? $json['data'] : [];
        }
        
        return [];
    }
    
    private function fetchSubmissionsData($config) {
        $submissionsConfig = $config['submissions'];
        $cacheFile = "cache/{$_ENV['ENTERPRISE']}/all-submissions-data.json";
        
        if (file_exists($cacheFile)) {
            $json = json_decode(file_get_contents($cacheFile), true);
            return isset($json['data']) ? $json['data'] : [];
        }
        
        return [];
    }
    
    private function saveResults($results) {
        $output = [
            'timestamp' => date('Y-m-d H:i:s'),
            'purpose' => 'Baseline registration counts before refactor',
            'enterprises' => $results
        ];
        
        $outputFile = __DIR__ . '/baseline_results.json';
        file_put_contents($outputFile, json_encode($output, JSON_PRETTY_PRINT));
    }
}

// Run the test if called directly
if (php_sapi_name() === 'cli' || isset($_GET['run'])) {
    $test = new BaselineRegistrationCountsTest();
    $test->runBaselineTest();
}
?> 