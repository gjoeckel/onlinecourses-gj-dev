<?php
/**
 * Enterprise Data Service
 * Groups Canvas data by enterprise and provides aggregate statistics
 */

class EnterpriseDataService {
    private $canvas_service;
    private $db;
    
    public function __construct() {
        require_once __DIR__ . '/canvas_data_service.php';
        require_once __DIR__ . '/unified_database.php';
        
        $this->canvas_service = new CanvasDataService();
        $this->db = new UnifiedDatabase();
    }
    
    /**
     * Get enterprise summary data
     */
    public function getEnterpriseSummary($enterprise_code) {
        try {
            // Get all organizations for this enterprise
            $organizations = $this->getOrganizationsByEnterprise($enterprise_code);
            
            if (empty($organizations)) {
                return ['error' => 'No organizations found for enterprise: ' . $enterprise_code];
            }
            
            // Get Canvas enrollments for this enterprise
            $enrollments = $this->getEnterpriseEnrollments($enterprise_code);
            
            if (isset($enrollments['error'])) {
                return $enrollments;
            }
            
            // Group data by organization
            $org_data = [];
            $total_enrollments = 0;
            $total_completed = 0;
            $total_certificates = 0;
            
            foreach ($organizations as $org) {
                $org_name = $org['name'];
                $org_enrollments = $this->filterEnrollmentsByOrganization($enrollments, $org_name);
                
                $enrollment_count = count($org_enrollments);
                $completed_count = 0;
                $certificate_count = 0;
                
                foreach ($org_enrollments as $enrollment) {
                    // Fix: Properly interpret Canvas completion status
                    $completed_status = $enrollment['Completed'] ?? '';
                    if ($this->isCompleted($completed_status)) {
                        $completed_count++;
                    }
                    
                    // Fix: Properly interpret Canvas certificate status
                    $certificate_status = $enrollment['Certificate'] ?? '';
                    if ($this->hasCertificate($certificate_status)) {
                        $certificate_count++;
                    }
                }
                
                $org_data[] = [
                    'name' => $org_name,
                    'enrollments' => $enrollment_count,
                    'completed' => $completed_count,
                    'certificates' => $certificate_count,
                    'enrollment_details' => $org_enrollments
                ];
                
                $total_enrollments += $enrollment_count;
                $total_completed += $completed_count;
                $total_certificates += $certificate_count;
            }
            
            return [
                'enterprise' => $enterprise_code,
                'enterprise_name' => $this->getEnterpriseDisplayName($enterprise_code),
                'organizations' => $org_data,
                'totals' => [
                    'organizations' => count($organizations),
                    'enrollments' => $total_enrollments,
                    'completed' => $total_completed,
                    'certificates' => $total_certificates
                ],
                'all_enrollments' => $enrollments
            ];
            
        } catch (Exception $e) {
            return ['error' => 'Enterprise data error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get organizations by enterprise code
     */
    private function getOrganizationsByEnterprise($enterprise_code) {
        $all_orgs = $this->db->getAllOrganizations();
        $enterprise_orgs = [];
        
        foreach ($all_orgs as $org) {
            if ($org['enterprise'] === $enterprise_code) {
                $enterprise_orgs[] = $org;
            }
        }
        
        return $enterprise_orgs;
    }
    
    /**
     * Get enterprise enrollments based on enterprise type
     */
    private function getEnterpriseEnrollments($enterprise_code) {
        // Get ALL enrollments first, then filter by organization mapping
        $all_enrollments = $this->canvas_service->getAllEnrollments();
        
        if (isset($all_enrollments['error'])) {
            return $all_enrollments;
        }
        
        // For now, return all enrollments and let the organization filtering handle the mapping
        // This is more flexible than keyword-based filtering
        return $all_enrollments;
    }
    
    /**
     * Filter enrollments by organization name - IMPROVED VERSION
     */
    private function filterEnrollmentsByOrganization($enrollments, $org_name) {
        $filtered = [];
        
        foreach ($enrollments as $enrollment) {
            $course_name = $enrollment['Organization'] ?? '';
            
            // More flexible matching - check multiple patterns
            if ($this->isCourseRelatedToOrg($course_name, $org_name)) {
                $filtered[] = $enrollment;
            }
        }
        
        return $filtered;
    }
    
    /**
     * Check if an enrollment is completed based on Canvas data
     */
    private function isCompleted($status) {
        if (empty($status)) return false;
        
        $status_lower = strtolower(trim($status));
        
        // Canvas uses various values for completion
        $completed_values = ['yes', 'true', '1', 'completed', 'finished', 'done'];
        $not_completed_values = ['no', 'false', '0', 'incomplete', 'pending', 'in progress', '-'];
        
        if (in_array($status_lower, $completed_values)) {
            return true;
        }
        
        if (in_array($status_lower, $not_completed_values)) {
            return false;
        }
        
        // Default to false for unknown values
        return false;
    }
    
    /**
     * Check if an enrollment has a certificate based on Canvas data
     */
    private function hasCertificate($status) {
        if (empty($status)) return false;
        
        $status_lower = strtolower(trim($status));
        
        // Canvas uses various values for certificates
        $certificate_values = ['yes', 'true', '1', 'certificate', 'issued', 'awarded'];
        $no_certificate_values = ['no', 'false', '0', 'none', 'pending', '-', ''];
        
        if (in_array($status_lower, $certificate_values)) {
            return true;
        }
        
        if (in_array($status_lower, $no_certificate_values)) {
            return false;
        }
        
        // Default to false for unknown values
        return false;
    }
    
    /**
     * Get manual course-to-organization mappings
     */
    private function getManualCourseMappings() {
        // Manual mappings for courses that don't match automatically
        return [
            // CPD courses - these are likely general training courses
            'CPD | Accessible Documents: Word, PowerPoint, & PDF' => ['ADMIN', 'Bakersfield College', 'American River College'],
            'July 2018 | Accessible Documents' => ['ADMIN', 'Bakersfield College', 'American River College'],
            'Aug 2018 | Accessible Documents' => ['ADMIN', 'Bakersfield College', 'American River College'],
            'Sept 2018 | Accessible Documents' => ['ADMIN', 'Bakersfield College', 'American River College'],
            'Oct 2018 | Accessible Documents' => ['ADMIN', 'Bakersfield College', 'American River College'],
            'Nov 2018 | Accessible Documents' => ['ADMIN', 'Bakersfield College', 'American River College'],
            'IOTI Accessibility Training' => ['ADMIN', 'Bakersfield College', 'American River College'],
            'DEV | Accessible Docs | v 1.0' => ['ADMIN', 'Bakersfield College', 'American River College'],
            'Preview of Accessible Documents: Word, PowerPoint, & PDF' => ['ADMIN', 'Bakersfield College', 'American River College'],
            'Beta Testers | Accessible Documents: Word, PowerPoint, & PDF' => ['ADMIN', 'Bakersfield College', 'American River College']
        ];
    }
    
    /**
     * Check if a course is related to an organization - IMPROVED VERSION
     */
    private function isCourseRelatedToOrg($course_name, $org_name) {
        // First check manual mappings
        $manual_mappings = $this->getManualCourseMappings();
        if (isset($manual_mappings[$course_name])) {
            return in_array($org_name, $manual_mappings[$course_name]);
        }
        
        // Convert to lowercase for comparison
        $course_lower = strtolower(trim($course_name));
        $org_lower = strtolower(trim($org_name));
        
        // Method 1: Direct name match
        if ($course_lower === $org_lower) {
            return true;
        }
        
        // Method 2: Course contains organization name
        if (strpos($course_lower, $org_lower) !== false) {
            return true;
        }
        
        // Method 3: Organization contains course name
        if (strpos($org_lower, $course_lower) !== false) {
            return true;
        }
        
        // Method 4: Check for common abbreviations and variations
        $org_words = explode(' ', $org_name);
        foreach ($org_words as $word) {
            $word = trim($word);
            if (strlen($word) > 2) { // Skip very short words
                $word_lower = strtolower($word);
                
                // Check if course contains this word
                if (strpos($course_lower, $word_lower) !== false) {
                    return true;
                }
                
                // Check for common abbreviations
                if ($word_lower === 'college' && strpos($course_lower, 'col') !== false) {
                    return true;
                }
                if ($word_lower === 'university' && strpos($course_lower, 'univ') !== false) {
                    return true;
                }
                if ($word_lower === 'community' && strpos($course_lower, 'comm') !== false) {
                    return true;
                }
            }
        }
        
        // Method 5: Check for special cases and patterns
        $special_patterns = [
            'bakersfield' => ['bakersfield', 'bakers'],
            'american river' => ['american river', 'american', 'river'],
            'antelope valley' => ['antelope valley', 'antelope', 'valley'],
            'berkeley' => ['berkeley', 'berk'],
            'butte' => ['butte'],
            'cabrillo' => ['cabrillo'],
            'calbright' => ['calbright'],
            'canada' => ['canada'],
            'cerritos' => ['cerritos'],
            'cerro coso' => ['cerro coso', 'cerro', 'coso'],
            'chabot' => ['chabot'],
            'chaffey' => ['chaffey'],
            'citrus' => ['citrus'],
            'city college of san francisco' => ['city college', 'san francisco', 'sf'],
            'clovis' => ['clovis'],
            'coast' => ['coast'],
            'coastline' => ['coastline'],
            'college of alameda' => ['alameda'],
            'college of marin' => ['marin'],
            'college of san mateo' => ['san mateo'],
            'college of the canyons' => ['canyons'],
            'college of the desert' => ['desert'],
            'college of the redwoods' => ['redwoods'],
            'college of the sequoias' => ['sequoias'],
            'college of the siskiyous' => ['siskiyou'],
            'columbia' => ['columbia'],
            'compton' => ['compton'],
            'contra costa' => ['contra costa', 'contra', 'costa'],
            'copper mountain' => ['copper mountain', 'copper', 'mountain'],
            'cosumnes river' => ['cosumnes river', 'cosumnes', 'river'],
            'crafton hills' => ['crafton hills', 'crafton', 'hills'],
            'cuesta' => ['cuesta'],
            'cuyamaca' => ['cuyamaca'],
            'cypress' => ['cypress'],
            'de anza' => ['de anza', 'anza'],
            'diablo valley' => ['diablo valley', 'diablo', 'valley'],
            'east los angeles' => ['east los angeles', 'east la', 'los angeles'],
            'el camino' => ['el camino', 'camino'],
            'evergreen valley' => ['evergreen valley', 'evergreen', 'valley'],
            'feather river' => ['feather river', 'feather', 'river'],
            'folsom lake' => ['folsom lake', 'folsom', 'lake'],
            'foothill' => ['foothill'],
            'fresno city' => ['fresno city', 'fresno'],
            'fullerton' => ['fullerton'],
            'gavilan' => ['gavilan'],
            'glendale' => ['glendale'],
            'golden west' => ['golden west', 'golden', 'west'],
            'grossmont' => ['grossmont'],
            'hartnell' => ['hartnell'],
            'imperial valley' => ['imperial valley', 'imperial', 'valley'],
            'irvine valley' => ['irvine valley', 'irvine', 'valley'],
            'lake tahoe' => ['lake tahoe', 'tahoe'],
            'laney' => ['laney'],
            'las positas' => ['las positas', 'positas'],
            'lassen' => ['lassen'],
            'long beach city' => ['long beach city', 'long beach', 'beach'],
            'los angeles city' => ['los angeles city', 'los angeles', 'la'],
            'los angeles harbor' => ['los angeles harbor', 'la harbor', 'harbor'],
            'los angeles mission' => ['los angeles mission', 'la mission', 'mission'],
            'los angeles pierce' => ['los angeles pierce', 'la pierce', 'pierce'],
            'los angeles southwest' => ['los angeles southwest', 'la southwest', 'southwest'],
            'los angeles trade-tech' => ['los angeles trade-tech', 'la trade-tech', 'trade-tech'],
            'los angeles valley' => ['los angeles valley', 'la valley', 'valley'],
            'los medanos' => ['los medanos', 'medanos'],
            'madera' => ['madera'],
            'mendocino' => ['mendocino'],
            'merced' => ['merced'],
            'merritt' => ['merritt'],
            'miracosta' => ['miracosta'],
            'mission' => ['mission'],
            'modesto junior' => ['modesto junior', 'modesto'],
            'monterey peninsula' => ['monterey peninsula', 'monterey', 'peninsula'],
            'moorpark' => ['moorpark'],
            'moreno valley' => ['moreno valley', 'moreno', 'valley'],
            'mt. san antonio' => ['mt. san antonio', 'mt san antonio', 'san antonio', 'antonio'],
            'mt. san jacinto' => ['mt. san jacinto', 'mt san jacinto', 'san jacinto', 'jacinto'],
            'napa valley' => ['napa valley', 'napa', 'valley'],
            'norco' => ['norco'],
            'north orange' => ['north orange', 'orange'],
            'ohlone' => ['ohlone'],
            'orange coast' => ['orange coast', 'orange', 'coast'],
            'oxnard' => ['oxnard'],
            'palo verde' => ['palo verde', 'palo', 'verde'],
            'palomar' => ['palomar'],
            'pasadena city' => ['pasadena city', 'pasadena'],
            'peralta' => ['peralta'],
            'porterville' => ['porterville'],
            'rancho santiago' => ['rancho santiago', 'rancho', 'santiago'],
            'redwoods' => ['redwoods'],
            'reedley' => ['reedley'],
            'rio hondo' => ['rio hondo', 'rio', 'hondo'],
            'riverside city' => ['riverside city', 'riverside'],
            'sacramento city' => ['sacramento city', 'sacramento'],
            'saddleback' => ['saddleback'],
            'san bernardino valley' => ['san bernardino valley', 'san bernardino', 'bernardino'],
            'san diego city' => ['san diego city', 'san diego', 'diego'],
            'san diego college of continuing education' => ['san diego continuing', 'continuing education', 'continuing'],
            'san diego mesa' => ['san diego mesa', 'mesa'],
            'san diego miramar' => ['san diego miramar', 'miramar'],
            'san francisco' => ['san francisco', 'sf'],
            'san joaquin delta' => ['san joaquin delta', 'san joaquin', 'delta'],
            'san jose city' => ['san jose city', 'san jose', 'jose'],
            'san luis obispo' => ['san luis obispo', 'obispo'],
            'san mateo' => ['san mateo', 'mateo'],
            'santa ana' => ['santa ana', 'ana'],
            'santa barbara city' => ['santa barbara city', 'santa barbara', 'barbara'],
            'santa clarita' => ['santa clarita', 'clarita'],
            'santa monica' => ['santa monica', 'monica'],
            'santa rosa junior' => ['santa rosa junior', 'santa rosa', 'rosa'],
            'santiago canyon' => ['santiago canyon', 'santiago', 'canyon'],
            'sequoias' => ['sequoias'],
            'shasta' => ['shasta'],
            'sierra' => ['sierra'],
            'siskiyou' => ['siskiyou'],
            'skyline' => ['skyline'],
            'solano' => ['solano'],
            'sonoma county' => ['sonoma county', 'sonoma'],
            'south orange' => ['south orange', 'orange'],
            'southwestern' => ['southwestern'],
            'state center' => ['state center', 'state', 'center'],
            'taft' => ['taft'],
            'ventura' => ['ventura'],
            'victor valley' => ['victor valley', 'victor', 'valley'],
            'west hills' => ['west hills', 'hills'],
            'west kern' => ['west kern', 'kern'],
            'west los angeles' => ['west los angeles', 'west la', 'los angeles'],
            'west valley' => ['west valley', 'valley'],
            'woodland' => ['woodland'],
            'yosemite' => ['yosemite'],
            'yuba' => ['yuba']
        ];
        
        // Check if this organization has special patterns
        foreach ($special_patterns as $pattern_org => $patterns) {
            if (stripos($org_lower, $pattern_org) !== false) {
                foreach ($patterns as $pattern) {
                    if (strpos($course_lower, $pattern) !== false) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get enterprise display name
     */
    private function getEnterpriseDisplayName($enterprise_code) {
        $names = [
            'csu' => 'California State University',
            'ccc' => 'California Community Colleges',
            'demo' => 'Demo Organizations',
            'super' => 'Super Admin',
            'admin' => 'Administrative'
        ];
        
        return $names[$enterprise_code] ?? ucfirst($enterprise_code);
    }
    
    /**
     * Get enterprise statistics
     */
    public function getEnterpriseStats($enterprise_code) {
        $summary = $this->getEnterpriseSummary($enterprise_code);
        
        if (isset($summary['error'])) {
            return $summary;
        }
        
        return [
            'enterprise' => $summary['enterprise'],
            'enterprise_name' => $summary['enterprise_name'],
            'total_organizations' => $summary['totals']['organizations'],
            'total_enrollments' => $summary['totals']['enrollments'],
            'total_completed' => $summary['totals']['completed'],
            'total_certificates' => $summary['totals']['certificates'],
            'organizations' => array_map(function($org) {
                return [
                    'name' => $org['name'],
                    'enrollments' => $org['enrollments'],
                    'completed' => $org['completed'],
                    'certificates' => $org['certificates']
                ];
            }, $summary['organizations'])
        ];
    }
}
?>