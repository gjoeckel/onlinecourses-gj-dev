<?php
/**
 * Password Security Utilities
 * Handles secure password hashing and verification
 */

/**
 * Hash a password using bcrypt
 * @param string $password The plain text password
 * @return string The hashed password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify a password against its hash
 * @param string $password The plain text password to verify
 * @param string $hash The hashed password to check against
 * @return bool True if password matches hash
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Check if a password hash needs to be rehashed
 * @param string $hash The current password hash
 * @return bool True if hash should be rehashed
 */
function passwordNeedsRehash($hash) {
    return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Generate a secure random password
 * @param int $length Length of password (default: 4)
 * @return string Random password
 */
function generateSecurePassword($length = 4) {
    $chars = '0123456789';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
} 