<?php
/**
 * Get Organizations API Endpoint
 * 
 * This endpoint provides organizations filtered by enterprise
 * for use in the searchable dropdown on the registration form.
 * 
 * Usage: GET get_organizations.php?enterprise_id=1
 * Response: JSON with organizations array
 */

require_once 'config.php';
require_once 'db.php';

// Set JSON content type
header('Content-Type: application/json');

try {
    $db = new Database();
    
    // Get enterprise_id from query string
    $enterpriseId = $_GET['enterprise_id'] ?? null;
    
    if (!$enterpriseId) {
        http_response_code(400);
        echo json_encode([
            'error' => 'enterprise_id parameter is required',
            'example' => 'get_organizations.php?enterprise_id=1'
        ]);
        exit;
    }
    
    // Validate enterprise_id
    if (!in_array($enterpriseId, ['1', '2'])) {
        http_response_code(400);
        echo json_encode([
            'error' => 'enterprise_id must be 1 (CCC) or 2 (CSU)',
            'received' => $enterpriseId
        ]);
        exit;
    }
    
    // Get organizations for the specified enterprise
    $organizations = $db->select("
        SELECT id, name, type, enterprise_id
        FROM organizations 
        WHERE enterprise_id = ? 
        ORDER BY name
    ", [$enterpriseId]);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'enterprise_id' => $enterpriseId,
        'count' => count($organizations),
        'organizations' => $organizations
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 