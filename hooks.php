<?php
/**
 * ███████╗███╗   ██╗██████╗ ███████╗██████╗     ██╗  ██╗██╗   ██╗███████╗
 * ██╔════╝████╗  ██║██╔══██╗██╔════╝██╔══██╗    ██║ ██╔╝██║   ██║██╔════╝
 * █████╗  ██╔██╗ ██║██║  ██║█████╗  ██████╔╝    █████╔╝ ██║   ██║███████╗
 * ██╔══╝  ██║╚██╗██║██║  ██║██╔══╝  ██╔══██╗    ██╔═██╗ ██║   ██║╚════██║
 * ███████╗██║ ╚████║██████╔╝███████╗██║  ██║    ██║  ██╗╚██████╔╝███████║
 * ╚══════╝╚═╝  ╚═══╝╚═════╝ ╚══════╝╚═╝  ╚═╝    ╚═╝  ╚═╝ ╚═════╝ ╚══════╝
 *
 * Login Notification Module for WHMCS
 * Version: 1.0 BETA
 * 
 * @author Ender KUS <ender@enderkus.com.tr>
 * @copyright 2024
 * @license MIT
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;
use WHMCS\User\Client;
use WHMCS\Authentication\CurrentUser;
use WHMCS\Session;

add_hook('UserLogin', 1, function($vars) {
    try {
        // Get current session
        $session = new Session();
        $sessionKey = 'login_notification_sent_' . md5(session_id());

        // Check if notification already sent in this session
        if ($session->get($sessionKey)) {
            return;
        }

        // Get user ID from the correct variable
        $userId = isset($vars['user']['id']) ? $vars['user']['id'] : 
                (isset($vars['id']) ? $vars['id'] : 
                (isset($vars['client_id']) ? $vars['client_id'] : null));

        if (!$userId) {
            logActivity("Login Notification - No user ID found in hook variables");
            return;
        }

        // Check if addon is enabled
        $moduleEnabled = Capsule::table('tbladdonmodules')
            ->where('module', 'loginnotification')
            ->where('setting', 'enabled')
            ->value('value');

        // If module is not enabled, return
        if (!$moduleEnabled || $moduleEnabled !== 'on') {
            return;
        }

        // Get client information
        $client = Client::find($userId);
        if (!$client) {
            return;
        }

        // Get IP information
        $ip = $_SERVER['REMOTE_ADDR'];
        $ipInfo = json_decode(file_get_contents("http://ip-api.com/json/{$ip}"), true);

        // Log the login notification
        try {
            $location = null;
            $isp = null;
            if ($ipInfo && $ipInfo['status'] === 'success') {
                $location = $ipInfo['city'] . ', ' . $ipInfo['country'];
                $isp = $ipInfo['isp'];
            }

            $insertData = [
                'user_id' => $userId,
                'ip_address' => $ip,
                'location' => $location,
                'isp' => $isp,
                'login_time' => date('Y-m-d H:i:s'),
            ];

            Capsule::table('mod_loginnotification_logs')->insert($insertData);
        } catch (\Exception $e) {
            logActivity('Login Notification Database Error: ' . $e->getMessage());
            return;
        }

        try {
            // Get email template
            $template = Capsule::table('tblemailtemplates')
                ->where('name', 'Login Notification Email')
                ->first();

            if (!$template) {
                return;
            }

            // Prepare merge fields
            $mergeFields = [
                'client_name' => $client->firstname . ' ' . $client->lastname,
                'login_time' => date('Y-m-d H:i:s'),
                'ip_address' => $ip,
                'location' => $location ?? 'Unknown',
                'isp' => $isp ?? 'Unknown',
                'company_name' => WHMCS\Config\Setting::getValue('CompanyName'),
            ];

            // Send email using WHMCS API
            $command = 'SendEmail';
            $postData = [
                'messagename' => 'Login Notification Email',
                'id' => $userId,
                'customtype' => 'general',
                'customsubject' => $template->subject,
                'custommessage' => $template->message,
                'customvars' => base64_encode(serialize($mergeFields)),
            ];

            $results = localAPI($command, $postData, 'admin');

            if ($results['result'] == 'success') {
                // Mark notification as sent in this session
                $session->set($sessionKey, true);
                
                // Log success but only for debugging
                if (WHMCS\Config\Setting::getValue('Debug')) {
                    logActivity("Login Notification Email Sent - User ID: {$userId}");
                }
            }
        } catch (\Exception $e) {
            // Log error only if debug mode is enabled
            if (WHMCS\Config\Setting::getValue('Debug')) {
                logActivity("Login Notification Error - " . $e->getMessage());
            }
        }
    } catch (\Exception $e) {
        // Log critical errors only
        logActivity("Login Notification Critical Error: " . $e->getMessage());
    }
});
