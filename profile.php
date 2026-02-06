<?php
/*
 * Paste $v3.3 2025/10/24 https://github.com/boxlabss/PASTE
 * demo: https://paste.boxlabs.uk/
 *
 * https://phpaste.sourceforge.io/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License in LICENCE for more details.
 */

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

// UTF-8
header('Content-Type: text/html; charset=utf-8');

$date = date('Y-m-d H:i:s');
$ip   = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

global $pdo;

try {
    // Site info
    $stmt = $pdo->query("SELECT * FROM site_info WHERE id = '1'");
    $row = $stmt->fetch() ?: [];
    $title      = trim((string)($row['title'] ?? 'Paste'));
    $des        = trim((string)($row['des'] ?? ''));
    $baseurl    = trim((string)($row['baseurl'] ?? ''));
    $keyword    = trim((string)($row['keyword'] ?? ''));
    $site_name  = trim((string)($row['site_name'] ?? 'Paste'));
    $email      = trim((string)($row['email'] ?? ''));
    $twit       = trim((string)($row['twit'] ?? ''));
    $face       = trim((string)($row['face'] ?? ''));
    $gplus      = trim((string)($row['gplus'] ?? ''));
    $ga         = trim((string)($row['ga'] ?? ''));
    $additional_scripts = trim((string)($row['additional_scripts'] ?? ''));

    // Theme & language
    $stmt = $pdo->query("SELECT * FROM interface WHERE id = '1'");
    $row = $stmt->fetch() ?: [];
    $default_lang  = trim((string)($row['lang'] ?? 'en.php'));
    $default_theme = trim((string)($row['theme'] ?? 'default'));
    require_once("langs/$default_lang");

    $p_title = $lang['myprofile'] ?? 'My Profile';

    // IP ban
    if (is_banned($pdo, $ip)) {
        die($lang['banned'] ?? 'You are banned from this site.');
    }

    // Site permissions
    $stmt = $pdo->query("SELECT * FROM site_permissions WHERE id = '1'");
    $row = $stmt->fetch() ?: [];
    $siteprivate = trim((string)($row['siteprivate'] ?? 'off'));
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $siteprivate === "on") {
        $privatesite = "on";
    }

    // Must be logged in
    if (!isset($_SESSION['token'])) {
        header("Location: ./login.php");
        exit;
    }

    // Logout
    if (isset($_GET['logout'])) {
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? $baseurl));
        unset($_SESSION['token'], $_SESSION['oauth_uid'], $_SESSION['username']);
        session_destroy();
        exit;
    }

    // Load current user record
    $sessionUsername = trim((string)($_SESSION['username'] ?? ''));
    $stmt = $pdo->prepare("SELECT id, oauth_uid, email_id, full_name, platform, verified, date, ip, password, username_locked FROM users WHERE username = ?");
    $stmt->execute([$sessionUsername]);
    $row = $stmt->fetch();

    if (!$row) {
        // Session user vanished; log them out
        header("Location: ./login.php?action=logout");
        exit;
    }

    $user_id          = (int)$row['id'];
    $user_oauth_uid   = $row['oauth_uid'] == '0' ? "None" : (string)$row['oauth_uid'];
    $user_email_id    = (string)$row['email_id'];
    $user_full_name   = (string)$row['full_name'];
    $user_platform    = trim((string)$row['platform']);  // 'Direct', 'Google', 'Facebook', ...
    $user_verified    = (string)$row['verified'];
    $user_date        = (string)$row['date'];
    $user_ip          = (string)$row['ip'];
    $user_password    = (string)$row['password'];
    $username_locked  = (int)($row['username_locked'] ?? 1);

    // Expose username separately (raw)
    $user_username = $sessionUsername;

    // OAuth users can change username once
    $can_edit_username = (strcasecmp($user_platform, 'Direct') !== 0) && ($username_locked === 0);

    // Handle one-time username change
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_username_once'])) {
        // CSRF
        if (!hash_equals($_SESSION['csrf_token'] ?? '', (string)($_POST['csrf_token'] ?? ''))) {
            $error = $lang['wentwrong'] ?? 'Something went wrong.';
        } elseif (!$can_edit_username) {
            $error = $lang['usernotvalid'] ?? 'Username not allowed to change.';
        } else {
            $new = trim((string)($_POST['new_username'] ?? ''));
            if ($new === '' || !isValidUsername($new)) {
                $error = $lang['usrinvalid'] ?? 'Invalid username.';
            } else {
                // unique?
                $stmt = $pdo->prepare("SELECT 1 FROM users WHERE username = ?");
                $stmt->execute([$new]);
                if ($stmt->fetch()) {
                    $error = $lang['userexists'] ?? 'Username already exists.';
                } else {
                    $old = $user_username;
                    $pdo->beginTransaction();
                    try {
                        // Update user + lock username
                        $stmt = $pdo->prepare("UPDATE users SET username = ?, username_locked = 1 WHERE id = ?");
                        $stmt->execute([$new, $user_id]);

                        // Reassign pastes to new username
                        $stmt = $pdo->prepare("UPDATE pastes SET member = ? WHERE member = ?");
                        $stmt->execute([$new, $old]);

                        // Update session + view vars
                        $_SESSION['username'] = $new;
                        $user_username = $new;
                        $can_edit_username = false;

                        $pdo->commit();
                        $success = $lang['userchanged'] ?? 'Username changed successfully.';
                    } catch (Throwable $e) {
                        $pdo->rollBack();
                        error_log("profile.php: username change failed: ".$e->getMessage());
                        $error = $lang['wentwrong'] ?? 'Something went wrong.';
                    }
                }
            }
        }
    }

    // Handle profile changes (full name + password)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cpassword']) && empty($_POST['set_username_once'])) {
        // Keep current full name by default if field not present in form
        $user_new_full = isset($_POST['full']) ? trim((string)$_POST['full']) : $user_full_name;

        $user_old_pass = (string)($_POST['old_password'] ?? '');
        $user_new_pass = (string)($_POST['password'] ?? '');

        if ($user_new_pass === '') {
            // full_name only (no password change)
            $stmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE username = ?");
            $stmt->execute([$user_new_full, $user_username]);
            $user_full_name = $user_new_full;
            $success = $lang['profileupdated'] ?? 'Profile updated.';
        } else {
            if (password_verify($user_old_pass, $user_password)) {
                $user_new_cpass = password_hash($user_new_pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, password = ? WHERE username = ?");
                $stmt->execute([$user_new_full, $user_new_cpass, $user_username]);
                $user_full_name = $user_new_full;
                $success = $lang['profileupdated'] ?? 'Profile updated.';
            } else {
                $error = $lang['oldpasswrong'] ?? 'Old password is wrong.';
            }
        }
    }

    // Handle account deletion (AJAX or normal POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account']) && $_POST['delete_account'] === '1') {
        $is_ajax = (isset($_POST['ajax']) && $_POST['ajax'] === '1')
            || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        // CSRF
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', (string)$_POST['csrf_token'])) {
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['ok' => false, 'error' => ($lang['invalidtoken'] ?? 'Invalid CSRF token.')]);
                exit;
            }
            $error = $lang['invalidtoken'] ?? 'Invalid CSRF token.';
        } else {
            try {
                $pdo->beginTransaction();

                // Delete user's pastes
                $stmt = $pdo->prepare("DELETE FROM pastes WHERE member = ?");
                $stmt->execute([$user_username]);

                // Delete user
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id]);

                $pdo->commit();

                // End session
                session_unset();
                session_destroy();

                $redirectUrl = rtrim($baseurl, '/') . '/accountdeleted.php';

                if ($is_ajax) {
                    header('Content-Type: application/json');
                    echo json_encode(['ok' => true, 'redirect' => $redirectUrl]);
                    exit;
                }

                header('Location: ' . $redirectUrl);
                exit;

            } catch (Throwable $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                error_log("GDPR delete_account failed for {$user_username}: " . $e->getMessage());

                if ($is_ajax) {
                    header('Content-Type: application/json');
                    echo json_encode(['ok' => false, 'error' => ($lang['wentwrong'] ?? 'Something went wrong while deleting your account.')]);
                    exit;
                }

                $error = $lang['wentwrong'] ?? 'Something went wrong while deleting your account.';
            }
        }
    }

    // Page views
    $dateYmd = date('Y-m-d');
    $ipToday = $_SERVER['REMOTE_ADDR'] ?? '';
    try {
        $stmt = $pdo->prepare("SELECT id, tpage, tvisit FROM page_view WHERE date = ?");
        $stmt->execute([$dateYmd]);
        $pv = $stmt->fetch();

        if ($pv) {
            $page_view_id = (int)$pv['id'];
            $tpage  = (int)$pv['tpage'] + 1;
            $tvisit = (int)$pv['tvisit'];

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM visitor_ips WHERE ip = ? AND visit_date = ?");
            $stmt->execute([$ipToday, $dateYmd]);
            if ((int)$stmt->fetchColumn() === 0) {
                $tvisit += 1;
                $stmt = $pdo->prepare("INSERT INTO visitor_ips (ip, visit_date) VALUES (?, ?)");
                $stmt->execute([$ipToday, $dateYmd]);
            }

            $stmt = $pdo->prepare("UPDATE page_view SET tpage = ?, tvisit = ? WHERE id = ?");
            $stmt->execute([$tpage, $tvisit, $page_view_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO page_view (date, tpage, tvisit) VALUES (?, ?, ?)");
            $stmt->execute([$dateYmd, 1, 1]);
            $stmt = $pdo->prepare("INSERT INTO visitor_ips (ip, visit_date) VALUES (?, ?)");
            $stmt->execute([$ipToday, $dateYmd]);
        }
    } catch (PDOException $e) {
        error_log("Page view tracking error: " . $e->getMessage());
    }

    $total_pastes = getTotalPastes($pdo, $user_username);

    // ========================================================================
    // API Key Management
    // ========================================================================
    
    // Check if API is enabled
    $api_enabled = true;
    $api_keys_per_user = 5;
    try {
        $stmt = $pdo->query("SELECT option_name, option_value FROM paste_options WHERE option_name IN ('api_enabled', 'api_keys_per_user')");
        while ($row = $stmt->fetch()) {
            if ($row['option_name'] === 'api_enabled') {
                $api_enabled = $row['option_value'] !== '0';
            } elseif ($row['option_name'] === 'api_keys_per_user') {
                $api_keys_per_user = (int)$row['option_value'];
            }
        }
    } catch (PDOException $e) {
        // Use defaults
    }
    
    // Handle API key creation
    $newly_created_key = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_api_key']) && $api_enabled) {
        if (!hash_equals($_SESSION['csrf_token'] ?? '', (string)($_POST['csrf_token'] ?? ''))) {
            $error = $lang['invalidtoken'] ?? 'Invalid CSRF token.';
        } else {
            // Check key limit
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM api_keys WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $keyCount = (int)$stmt->fetchColumn();
            
            if ($keyCount >= $api_keys_per_user) {
                $error = "You can only have $api_keys_per_user API keys. Please delete one first.";
            } else {
                $keyName = trim($_POST['key_name'] ?? 'Default');
                if (empty($keyName)) $keyName = 'Default';
                
                // Generate API key
                $apiKey = bin2hex(random_bytes(32));
                
                $stmt = $pdo->prepare("INSERT INTO api_keys (user_id, api_key, name) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $apiKey, $keyName]);
                
                // Store the full key temporarily so we can show it once
                $newly_created_key = $apiKey;
                $success = "API key created successfully!";
            }
        }
    }
    
    // Handle API key deletion
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_api_key'])) {
        if (!hash_equals($_SESSION['csrf_token'] ?? '', (string)($_POST['csrf_token'] ?? ''))) {
            $error = $lang['invalidtoken'] ?? 'Invalid CSRF token.';
        } else {
            $keyId = (int)$_POST['delete_api_key'];
            $stmt = $pdo->prepare("DELETE FROM api_keys WHERE id = ? AND user_id = ?");
            $stmt->execute([$keyId, $user_id]);
            if ($stmt->rowCount() > 0) {
                $success = "API key deleted.";
            }
        }
    }
    
    // Handle API key regeneration
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['regenerate_api_key'])) {
        if (!hash_equals($_SESSION['csrf_token'] ?? '', (string)($_POST['csrf_token'] ?? ''))) {
            $error = $lang['invalidtoken'] ?? 'Invalid CSRF token.';
        } else {
            $keyId = (int)$_POST['regenerate_api_key'];
            // Verify ownership
            $stmt = $pdo->prepare("SELECT id FROM api_keys WHERE id = ? AND user_id = ?");
            $stmt->execute([$keyId, $user_id]);
            if ($stmt->fetch()) {
                // Generate new key
                $newApiKey = bin2hex(random_bytes(32));
                $stmt = $pdo->prepare("UPDATE api_keys SET api_key = ?, last_used_at = NULL WHERE id = ?");
                $stmt->execute([$newApiKey, $keyId]);
                $newly_created_key = $newApiKey;
                $success = "API key regenerated successfully!";
            }
        }
    }
    
    // Get user's API keys
    $user_api_keys = [];
    if ($api_enabled) {
        try {
            $stmt = $pdo->prepare("SELECT id, name, LEFT(api_key, 8) as key_prefix, created_at, last_used_at, is_active FROM api_keys WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$user_id]);
            $user_api_keys = $stmt->fetchAll();
        } catch (PDOException $e) {
            // Table might not exist
        }
    }

    // Ads
    $stmt = $pdo->query("SELECT * FROM ads WHERE id = '1'");
    $row = $stmt->fetch() ?: [];
    $text_ads = trim((string)($row['text_ads'] ?? ''));
    $ads_1    = trim((string)($row['ads_1'] ?? ''));
    $ads_2    = trim((string)($row['ads_2'] ?? ''));

    // Render theme
    require_once('theme/' . $default_theme . '/header.php');
    require_once('theme/' . $default_theme . '/profile.php'); // uses $user_* and $can_edit_username
    require_once('theme/' . $default_theme . '/footer.php');

} catch (PDOException $e) {
    die("Database error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
