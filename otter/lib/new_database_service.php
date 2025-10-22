<?php
/**
 * New Database Service for Otter Application
 * Works with the new location and uses canvas.php and db.php for connections
 */

class NewDatabaseService {
    private $db_connection = null;
    private $canvas_connection = null;
    private $cache_dir;
    private $cache_ttl = 3600; // 1 hour cache
    
    public function __construct() {
        $this->cache_dir = __DIR__ . '/../cache/';
        
        // Create cache directory if it doesn't exist
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
        
        $this->initializeConnections();
    }
    
    /**
     * Initialize database and canvas connections
     */
    private function initializeConnections() {
        try {
            // Try to include the db.php file from the includes directory
            $db_file_path = '/var/websites/webaim/htdocs/onlinecourses/includes/db.php';
            if (file_exists($db_file_path)) {
                require_once $db_file_path;
                
                // Try to get database credentials from master_includes
                $master_includes_path = '/var/websites/webaim/master_includes/onlinecourses_common.php';
                if (file_exists($master_includes_path)) {
                    // Include master_includes to get database variables
                    require_once $master_includes_path;
                    
                    // Debug: Check what variables are available
                    error_log("Master includes loaded. Available variables: " . print_r(get_defined_vars(), true));
                    
                    // Check if database variables are available (using the actual variable names from master_includes)
                    if (isset($dbhost) && isset($dbuser) && isset($dbname)) {
                        error_log("Database variables found: HOST=$dbhost, USER=$dbuser, NAME=$dbname");
                        try {
                            $this->db_connection = new db($dbhost, $dbuser, $dbpass ?? '', $dbname, 'utf8');
                            error_log("Database connection established successfully using master_includes");
                        } catch (Exception $e) {
                            error_log("Failed to create database connection: " . $e->getMessage());
                        }
                    } else {
                        error_log("Database credentials not found in master_includes. Available variables: " . implode(', ', array_keys(get_defined_vars())));
                    }
                } else {
                    error_log("Master includes file not found at: " . $master_includes_path);
                }
            } else {
                error_log("Database file not found at: " . $db_file_path);
            }
            
            // Try to include the canvas.php file from the includes directory
            $canvas_file_path = '/var/websites/webaim/htdocs/onlinecourses/includes/canvas.php';
            if (file_exists($canvas_file_path)) {
                require_once $canvas_file_path;
                // The canvas.php file provides $canvas_config array
                if (isset($GLOBALS['canvas_config'])) {
                    $this->canvas_connection = $GLOBALS['canvas_config'];
                    error_log("Canvas configuration loaded successfully");
                }
            } else {
                error_log("Canvas file not found at: " . $canvas_file_path);
            }
            
        } catch (Exception $e) {
            error_log("Error initializing connections: " . $e->getMessage());
        }
    }
    
    /**
     * Get enterprise summary data from database instead of Canvas API
     */
    public function getEnterpriseSummary($enterprise_code) {
        $cache_key = "db_enterprise_summary_{$enterprise_code}";
        
        // Check cache first
        $cached_data = $this->getCache($cache_key);
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        try {
            if (!$this->db_connection) {
                return ['error' => 'Database connection not available'];
            }
            
            // Get organizations for this enterprise
            $organizations = $this->getOrganizationsByEnterprise($enterprise_code);
            
            if (empty($organizations)) {
                return ['error' => 'No organizations found for enterprise: ' . $enterprise_code];
            }
            
            // Get summary statistics from database
            $summary = $this->getSummaryStatistics($enterprise_code, $organizations);
            
            // Cache the results
            $this->setCache($cache_key, $summary);
            
            return $summary;
            
        } catch (Exception $e) {
            return ['error' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get organizations by enterprise from database
     */
    private function getOrganizationsByEnterprise($enterprise_code) {
        try {
            if (!$this->db_connection) {
                return [];
            }
            
            // First get the enterprise ID from the enterprises table
            $enterprise_query = "SELECT id FROM enterprises WHERE name = ?";
            $this->db_connection->query($enterprise_query, $enterprise_code);
            $enterprise_result = $this->db_connection->fetchArray();
            
            if (!$enterprise_result) {
                error_log("Enterprise not found: " . $enterprise_code);
                return [];
            }
            
            $enterprise_id = $enterprise_result['id'];
            
            // Query to get organizations for this enterprise using enterprise_id
            $query = "SELECT id, name, enterprise_id FROM organizations WHERE enterprise_id = ?";
            
            // Execute query using the db class
            $this->db_connection->query($query, $enterprise_id);
            $results = $this->db_connection->fetchAll();
            
            return $results;
            
        } catch (Exception $e) {
            error_log("Error getting organizations: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get summary statistics from database
     */
    private function getSummaryStatistics($enterprise_code, $organizations) {
        try {
            if (!$this->db_connection) {
                return $this->getFallbackSummary($enterprise_code, $organizations);
            }
            
            $total_organizations = count($organizations);
            $total_enrollments = 0;
            $total_completed = 0;
            $total_certificates = 0;
            
            // Get summary data for each organization
            $org_summaries = [];
            
            foreach ($organizations as $org) {
                $org_id = $org['id'];
                $org_name = $org['name'];
                
                // Get summary stats for this organization
                $org_stats = $this->getOrganizationSummary($org_id, $enterprise_code);
                
                $org_summaries[] = [
                    'name' => $org_name,
                    'enrollments' => $org_stats['enrollments'],
                    'completed' => $org_stats['completed'],
                    'certificates' => $org_stats['certificates']
                ];
                
                $total_enrollments += $org_stats['enrollments'];
                $total_completed += $org_stats['completed'];
                $total_certificates += $org_stats['certificates'];
            }
            
            return [
                'enterprise' => $enterprise_code,
                'enterprise_name' => $this->getEnterpriseDisplayName($enterprise_code),
                'organizations' => $org_summaries,
                'totals' => [
                    'organizations' => $total_organizations,
                    'enrollments' => $total_enrollments,
                    'completed' => $total_completed,
                    'certificates' => $total_certificates
                ],
                'data_source' => 'Database (New Location)',
                'last_updated' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            error_log("Error getting summary statistics: " . $e->getMessage());
            return $this->getFallbackSummary($enterprise_code, $organizations);
        }
    }
    
    /**
     * Get organization summary from database
     */
    private function getOrganizationSummary($org_id, $enterprise_code) {
        try {
            if (!$this->db_connection) {
                return ['enrollments' => 0, 'completed' => 0, 'certificates' => 0];
            }
            
            // Query to get summary statistics for an organization
            // Using the registrations table with the correct column names
            $query = "
                SELECT 
                    COUNT(*) as total_enrollments,
                    SUM(CASE WHEN status = 'earner' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN certificate = 1 THEN 1 ELSE 0 END) as certificates
                FROM registrations 
                WHERE organization_id = ? AND enterprise_id = (
                    SELECT id FROM enterprises WHERE name = ?
                )
            ";
            
            $this->db_connection->query($query, $org_id, $enterprise_code);
            $result = $this->db_connection->fetchArray();
            
            return [
                'enrollments' => (int)($result['total_enrollments'] ?? 0),
                'completed' => (int)($result['completed'] ?? 0),
                'certificates' => (int)($result['certificates'] ?? 0)
            ];
            
        } catch (Exception $e) {
            error_log("Error getting organization summary: " . $e->getMessage());
            return ['enrollments' => 0, 'completed' => 0, 'certificates' => 0];
        }
    }
    
    /**
     * Fallback summary if database is not available
     */
    private function getFallbackSummary($enterprise_code, $organizations) {
        // Return basic summary without detailed statistics
        return [
            'enterprise' => $enterprise_code,
            'enterprise_name' => $this->getEnterpriseDisplayName($enterprise_code),
            'organizations' => array_map(function($org) {
                return [
                    'name' => $org['name'],
                    'enrollments' => 0,
                    'completed' => 0,
                    'certificates' => 0
                ];
            }, $organizations),
            'totals' => [
                'organizations' => count($organizations),
                'enrollments' => 0,
                'completed' => 0,
                'certificates' => 0
            ],
            'data_source' => 'Fallback (No Database)',
            'last_updated' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Get enterprise display name
     */
    public function getEnterpriseDisplayName($enterprise_code) {
        $enterprise_names = [
            'csu' => 'California State University',
            'ccc' => 'California Community Colleges',
            'demo' => 'Demonstration Enterprise'
        ];
        
        return $enterprise_names[$enterprise_code] ?? ucfirst($enterprise_code);
    }
    
    /**
     * Get the database connection object for direct queries
     */
    public function getDbConnection() {
        return $this->db_connection;
    }
    
    /**
     * Test database connection
     */
    public function testConnection() {
        try {
            if ($this->db_connection) {
                // Test the database connection using the db class
                $this->db_connection->query('SELECT 1 as test');
                $result = $this->db_connection->fetchArray();
                
                return [
                    'status' => 'connected',
                    'database' => 'Available',
                    'test_result' => $result['test'] ?? null
                ];
            } else {
                return [
                    'status' => 'error',
                    'database' => 'Not available',
                    'error' => 'Database connection not established'
                ];
            }
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'database' => 'Error',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Simple cache implementation
     */
    private function getCache($key) {
        $cache_file = $this->cache_dir . md5($key) . '.json';
        
        if (!file_exists($cache_file)) {
            return false;
        }
        
        $data = json_decode(file_get_contents($cache_file), true);
        if (!$data) {
            return false;
        }
        
        // Check if cache is expired
        if (time() - $data['timestamp'] > $this->cache_ttl) {
            unlink($cache_file);
            return false;
        }
        
        return $data['data'];
    }
    
    private function setCache($key, $data) {
        $cache_file = $this->cache_dir . md5($key) . '.json';
        
        $cache_data = [
            'timestamp' => time(),
            'data' => $data
        ];
        
        file_put_contents($cache_file, json_encode($cache_data));
    }
    
    /**
     * Clear cache for specific enterprise or all
     */
    public function clearCache($enterprise_code = null) {
        if ($enterprise_code) {
            $cache_key = "db_enterprise_{$enterprise_code}";
            $cache_file = $this->cache_dir . md5($cache_key) . '.json';
            if (file_exists($cache_file)) {
                unlink($cache_file);
            }
        } else {
            // Clear all cache files
            $files = glob($this->cache_dir . '*.json');
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }

    /**
     * Get enrollment counts per organization for an enterprise
     * This is the most efficient way to count enrollments per organization
     */
    public function getEnrollmentCountsPerOrganization($enterprise_code) {
        try {
            if (!$this->db_connection) {
                return [];
            }
            
            // Debug: Check what tables exist and their structure
            error_log("Debug: Checking database structure for enterprise: " . $enterprise_code);
            
            // First, let's see what tables exist
            $this->db_connection->query("SHOW TABLES");
            $tables = $this->db_connection->fetchAll();
            error_log("Debug: Available tables: " . json_encode($tables));
            
            // Check enterprises table
            $this->db_connection->query("SELECT * FROM enterprises WHERE name = ?", $enterprise_code);
            $enterprise = $this->db_connection->fetchArray();
            error_log("Debug: Enterprise lookup result: " . json_encode($enterprise));
            
            if (!$enterprise) {
                error_log("Debug: Enterprise not found, checking all enterprises");
                $this->db_connection->query("SELECT * FROM enterprises");
                $all_enterprises = $this->db_connection->fetchAll();
                error_log("Debug: All enterprises: " . json_encode($all_enterprises));
            }
            
            // Check organizations table structure
            $this->db_connection->query("DESCRIBE organizations");
            $org_structure = $this->db_connection->fetchAll();
            error_log("Debug: Organizations table structure: " . json_encode($org_structure));
            
            // Check registrations table structure
            $this->db_connection->query("DESCRIBE registrations");
            $reg_structure = $this->db_connection->fetchAll();
            error_log("Debug: Registrations table structure: " . json_encode($reg_structure));
            
            $query = "
                SELECT 
                    o.id as organization_id,
                    o.name as organization_name,
                    COUNT(r.id) as total_enrollments,
                    SUM(CASE WHEN r.status = 'enrollee' THEN 1 ELSE 0 END) as completed_enrollments,
                    SUM(CASE WHEN r.certificate = 1 THEN 1 ELSE 0 END) as certificates_issued
                FROM organizations o
                LEFT JOIN registrations r ON o.id = r.organization_id AND r.deletion_status = 'active'
                WHERE o.enterprise_id = (
                    SELECT id FROM enterprises WHERE name = ?
                )
                GROUP BY o.id, o.name
                ORDER BY total_enrollments DESC, o.name ASC
            ";
            
            error_log("Debug: Executing query: " . $query);
            $this->db_connection->query($query, $enterprise_code);
            $results = $this->db_connection->fetchAll();
            error_log("Debug: Query results: " . json_encode($results));
            
            // Format the results for easy consumption
            $formatted_results = [];
            foreach ($results as $row) {
                $formatted_results[] = [
                    'organization_id' => (int)$row['organization_id'],
                    'organization_name' => $row['organization_name'],
                    'total_enrollments' => (int)$row['total_enrollments'],
                    'completed_enrollments' => (int)$row['completed_enrollments'],
                    'certificates_issued' => (int)$row['certificates_issued'],
                    'completion_rate' => $row['total_enrollments'] > 0 
                        ? round(($row['completed_enrollments'] / $row['total_enrollments']) * 100, 1)
                        : 0
                ];
            }
            
            return $formatted_results;
            
        } catch (Exception $e) {
            error_log("Error getting enrollment counts per organization: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get detailed enrollment breakdown for a specific organization
     */
    public function getOrganizationEnrollmentDetails($organization_id, $enterprise_code) {
        try {
            if (!$this->db_connection) {
                return null;
            }
            
            // Get detailed enrollment information for a specific organization
            $query = "
                SELECT 
                    r.id,
                    r.status,
                    r.certificate,
                    r.created_at,
                    r.earnerdate as completed_at,
                    r.email,
                    r.name as full_name,
                    r.role,
                    r.organization,
                    r.college,
                    r.cohort
                FROM registrations r
                JOIN organizations o ON r.organization = o.name
                WHERE o.id = ? 
                AND o.enterprise_id = (
                    SELECT id FROM enterprises WHERE name = ?
                )
                AND r.deletion_status = 'active'
                ORDER BY r.created_at DESC
            ";
            
            $this->db_connection->query($query, $organization_id, $enterprise_code);
            $results = $this->db_connection->fetchAll();
            
            return $results;
            
        } catch (Exception $e) {
            error_log("Error getting organization enrollment details: " . $e->getMessage());
            return null;
        }
    }
}
