<?php

class SecurePass_Encryption {
    private $encryption_key;

    public function __construct() {
        $this->encryption_key = defined('SECUREPASS_KEY') ? SECUREPASS_KEY : wp_salt('secure_auth');
    }

    public function encrypt($data) {
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $this->encryption_key, 0, substr($this->encryption_key, 0, 16));
        return base64_encode($encrypted);
    }

    public function decrypt($data) {
        $decrypted = openssl_decrypt(base64_decode($data), 'AES-256-CBC', $this->encryption_key, 0, substr($this->encryption_key, 0, 16));
        return $decrypted;
    }

    public static function generate_password($length = 16, $include_symbols = true) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        if ($include_symbols) {
            $chars .= '!@#$%^&*()_+-=[]{}|;:,.<>?';
        }
        
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }

    public static function check_password_strength($password) {
        $strength = 0;
        if (strlen($password) >= 8) $strength++;
        if (strlen($password) >= 12) $strength++;
        if (preg_match('/[a-z]/', $password)) $strength++;
        if (preg_match('/[A-Z]/', $password)) $strength++;
        if (preg_match('/[0-9]/', $password)) $strength++;
        if (preg_match('/[^a-zA-Z0-9]/', $password)) $strength++;
        
        $levels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong', 'Very Strong'];
        return $levels[$strength] ?? 'Weak';
    }
}
