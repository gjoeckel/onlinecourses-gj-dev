<?php

class UnifiedDatabase {
    private $passwordsFile;

    public function __construct() {
        $this->passwordsFile = __DIR__ . '/../config/passwords.json';

        if (!file_exists($this->passwordsFile)) {
            throw new Exception('Could not find passwords.json file.');
        }
    }

    private function loadPasswordsData() {
        $data = json_decode(file_get_contents($this->passwordsFile), true);
        if (!isset($data['organizations'])) {
            throw new Exception('Invalid passwords.json structure.');
        }
        return $data;
    }

    private function savePasswordsData($data) {
        file_put_contents($this->passwordsFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Validate login for any organization across all enterprises
     * @param string $password The 4-digit password
     * @return array|false Organization data if valid, false otherwise
     */
    public function validateLogin($password) {
        require_once __DIR__ . '/password_utils.php';
        
        $data = $this->loadPasswordsData();

        // First check admin_passwords section (more likely to be used, fewer entries)
        if (isset($data['admin_passwords'])) {
            foreach ($data['admin_passwords'] as $enterprise_code => $admin_password) {
                // Check if password is hashed or plain text
                if (strlen($admin_password) === 4 && is_numeric($admin_password)) {
                    // Plain text password (legacy)
                    if ($admin_password === $password) {
                        return [
                            'name' => 'ADMIN',
                            'password' => $admin_password,
                            'enterprise' => $enterprise_code,
                            'is_admin' => true
                        ];
                    }
                } else {
                    // Hashed password
                    if (verifyPassword($password, $admin_password)) {
                        return [
                            'name' => 'ADMIN',
                            'password' => $admin_password,
                            'enterprise' => $enterprise_code,
                            'is_admin' => true
                        ];
                    }
                }
            }
        }

        // Then check organizations array (only if admin check failed)
        foreach ($data['organizations'] as $org) {
            if (isset($org['password'])) {
                // Check if password is hashed or plain text
                if (strlen($org['password']) === 4 && is_numeric($org['password'])) {
                    // Plain text password (legacy)
                    if ($org['password'] === $password) {
                        return $org;
                    }
                } else {
                    // Hashed password
                    if (verifyPassword($password, $org['password'])) {
                        return $org;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Get organization by password
     * @param string $password The 4-digit password
     * @return array|false Organization data if found, false otherwise
     */
    public function getOrganizationByPassword($password) {
        return $this->validateLogin($password);
    }

    /**
     * Get all organizations for a specific enterprise
     * @param string $enterprise_code The enterprise code (e.g., 'csu', 'ccc')
     * @return array Array of organizations
     */
    public function getOrganizationsByEnterprise($enterprise_code) {
        $data = $this->loadPasswordsData();
        $organizations = [];

        foreach ($data['organizations'] as $org) {
            if (isset($org['enterprise']) && $org['enterprise'] === $enterprise_code) {
                $organizations[] = $org;
            }
        }

        return $organizations;
    }

    /**
     * Get all organizations across all enterprises
     * @return array Array of all organizations
     */
    public function getAllOrganizations() {
        $data = $this->loadPasswordsData();
        $organizations = [];

        // Add admin organizations first
        if (isset($data['admin_passwords'])) {
            foreach ($data['admin_passwords'] as $enterprise_code => $admin_password) {
                $organizations[] = [
                    'name' => 'ADMIN',
                    'password' => $admin_password,
                    'enterprise' => $enterprise_code,
                    'is_admin' => true
                ];
            }
        }

        // Add regular organizations
        foreach ($data['organizations'] as $org) {
            $organizations[] = $org;
        }

        return $organizations;
    }

    /**
     * Get admin password for a specific enterprise
     * @param string $enterprise_code The enterprise code
     * @return string|false Admin password if found, false otherwise
     */
    public function getAdminPassword($enterprise_code) {
        $data = $this->loadPasswordsData();
        if (isset($data['admin_passwords'][$enterprise_code])) {
            return $data['admin_passwords'][$enterprise_code];
        }
        return false;
    }

    /**
     * Get admin organization for a specific enterprise
     * @param string $enterprise_code The enterprise code
     * @return array|false Admin organization data if found, false otherwise
     */
    public function getAdminOrganization($enterprise_code) {
        $data = $this->loadPasswordsData();
        // Try to find in organizations array (legacy support)
        foreach ($data['organizations'] as $org) {
            if (isset($org['enterprise']) && $org['enterprise'] === $enterprise_code && isset($org['is_admin']) && $org['is_admin'] === true) {
                return $org;
            }
        }
        // If not found, synthesize from admin_passwords
        if (isset($data['admin_passwords'][$enterprise_code])) {
            return [
                'name' => 'ADMIN',
                'password' => $data['admin_passwords'][$enterprise_code],
                'enterprise' => $enterprise_code,
                'is_admin' => true
            ];
        }
        return false;
    }

    /**
     * Check if a password belongs to an admin organization
     * @param string $password The 4-digit password
     * @return bool True if admin, false otherwise
     */
    public function isAdminPassword($password) {
        $data = $this->loadPasswordsData();

        // Check organizations array first (legacy support)
        foreach ($data['organizations'] as $org) {
            if (isset($org['password']) && $org['password'] === $password &&
                isset($org['is_admin']) && $org['is_admin'] === true) {
                return true;
            }
        }

        // Check admin_passwords section
        if (isset($data['admin_passwords'])) {
            foreach ($data['admin_passwords'] as $enterprise_code => $admin_password) {
                if ($admin_password === $password) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get enterprise code for a given password
     * @param string $password The 4-digit password
     * @return string|false Enterprise code if found, false otherwise
     */
    public function getEnterpriseByPassword($password) {
        $org = $this->getOrganizationByPassword($password);
        return $org ? $org['enterprise'] : false;
    }

    /**
     * Update password for an organization
     * @param string $organization_name The organization name
     * @param string $newPassword The new 4-digit password
     * @param string|null $enterprise_code The enterprise code (required for ADMIN updates)
     * @return bool True if updated, false otherwise
     */
    public function updatePassword($organization_name, $newPassword, $enterprise_code = null) {
        $data = $this->loadPasswordsData();
        $updated = false;

        // First, find the current password for the organization
        $currentPassword = null;
        $is_admin = false;
        $found_enterprise_code = null;

        foreach ($data['organizations'] as $org) {
            if (isset($org['name']) && $org['name'] === $organization_name) {
                $currentPassword = $org['password'];
                $found_enterprise_code = $org['enterprise'] ?? null;
                $is_admin = isset($org['is_admin']) && $org['is_admin'] === true;
                break;
            }
        }

        // If not found in organizations, check if it's ADMIN
        if ($currentPassword === null && $organization_name === 'ADMIN') {
            // For ADMIN, we need the enterprise code to identify which admin to update
            if ($enterprise_code === null) {
                // Try to get enterprise code from current context
                if (class_exists('UnifiedEnterpriseConfig')) {
                    $enterprise_code = UnifiedEnterpriseConfig::getEnterpriseCode();
                }
            }

            if ($enterprise_code && isset($data['admin_passwords'][$enterprise_code])) {
                $currentPassword = $data['admin_passwords'][$enterprise_code];
                $found_enterprise_code = $enterprise_code;
                $is_admin = true;
            }
        }

        // Check if new password matches current password
        if ($currentPassword === $newPassword) {
            return false;
        }

        // Check for duplicate password in organizations section
        foreach ($data['organizations'] as $org) {
            if (isset($org['name']) && isset($org['password']) &&
                $org['name'] !== $organization_name && $org['password'] === $newPassword) {
                return false;
            }
        }

        // Check for duplicate password in admin_passwords section
        if (isset($data['admin_passwords'])) {
            foreach ($data['admin_passwords'] as $code => $admin_password) {
                // Only skip if updating this admin
                if ($is_admin && $found_enterprise_code === $code) continue;
                if ($admin_password === $newPassword) {
                    return false;
                }
            }
        }

        // Update password for organization
        foreach ($data['organizations'] as &$org) {
            if (isset($org['name']) && $org['name'] === $organization_name) {
                $org['password'] = $newPassword;
                $updated = true;
            }
        }

        // If updating ADMIN, update admin_passwords for the specific enterprise
        if ($is_admin && $found_enterprise_code && isset($data['admin_passwords'][$found_enterprise_code])) {
            $data['admin_passwords'][$found_enterprise_code] = $newPassword;
            $updated = true;
        }

        if ($updated) {
            $this->savePasswordsData($data);
            return true;
        }

        return false;
    }



    /**
     * Validate that a password exists
     * @param string $password The 4-digit password
     * @return bool True if valid, false otherwise
     */
    public function isValidPassword($password) {
        return $this->validateLogin($password) !== false;
    }

    /**
     * Get metadata about the passwords configuration
     * @return array Metadata information
     */
    public function getMetadata() {
        $data = $this->loadPasswordsData();
        return $data['metadata'] ?? [];
    }
}