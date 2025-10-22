<?php
// mailgun.php
// Helper to send email using Mailgun HTTP API

// Include the master Mailgun configuration
require_once '/var/websites/webaim/master_includes/mailgun.php';

/**
 * Get the API endpoint based on region
 * 
 * @param string $region The region (us or eu)
 * @return string The API endpoint
 */
function mg_api_get_region($region) {
    if ($region === 'eu') {
        return 'https://api.eu.mailgun.net/v3/';
    }
    return 'https://api.mailgun.net/v3/';
}

// Override the sendMailgun function to add email disabling functionality
if (!function_exists('sendMailgun')) {
    function sendMailgun($to, $from, $subject, $body, $replyto = null) {
        global $config;
        
        // Check if emails are disabled
        if ($config['email_disabled']) {
            error_log("EMAIL DISABLED: Would send to $to - Subject: $subject");
            return true; // Return success but don't actually send
        }
        
        $apiKey = $config['mailgun']['api_key'];
        $domain = $config['mailgun']['domain'];
        $region = $config['mailgun']['region'] ?? 'us';
        $endpoint = mg_api_get_region($region) . "$domain/messages";

        $postData = [
            'from' => $from,
            'to' => $to,
            'subject' => $subject,
            'text' => $body
        ];
        if ($replyto) {
            $postData['h:Reply-To'] = $replyto;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, 'api:' . $apiKey);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($httpCode >= 200 && $httpCode < 300);
    }
}
?>