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

function loginnotification_config() {
    return [
        'name' => 'Login Notification',
        'description' => 'Sends login notifications to users.',
        'version' => '1.0',
        'author' => 'Ender KUS',
        'fields' => [
            'enabled' => [
                'FriendlyName' => 'Enable/Disable',
                'Type' => 'yesno',
                'Description' => 'Enable or disable the addon',
                'Default' => 'yes',
            ],
        ],
    ];
}

function loginnotification_activate() {
    try {
        // Create database table
        if (!Capsule::schema()->hasTable('mod_loginnotification_logs')) {
            Capsule::schema()->create('mod_loginnotification_logs', function ($table) {
                $table->increments('id');
                $table->integer('user_id');
                $table->string('ip_address', 45);
                $table->string('location')->nullable();
                $table->string('isp')->nullable();
                $table->timestamp('login_time');
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // Create email template
        createLoginNotificationEmailTemplate();

        // Copy hooks file to includes/hooks directory
        $moduleDir = dirname(__FILE__);
        $sourceHooksFile = $moduleDir . '/hooks.php';
        $targetHooksFile = ROOTDIR . '/includes/hooks/loginnotification.php';

        if (!copy($sourceHooksFile, $targetHooksFile)) {
            throw new Exception('Could not copy hooks file to includes/hooks directory');
        }

        // Automatically set enabled setting to 'on'
        Capsule::table('tbladdonmodules')
            ->updateOrInsert(
                [
                    'module' => 'loginnotification',
                    'setting' => 'enabled'
                ],
                [
                    'value' => 'on'
                ]
            );

        return [
            'status' => 'success',
            'description' => 'Login Notification has been activated successfully.',
        ];
    } catch (\Exception $e) {
        return [
            'status' => 'error',
            'description' => 'Could not activate Login Notification: ' . $e->getMessage(),
        ];
    }
}

function loginnotification_deactivate() {
    try {
        // Remove hooks file from includes/hooks directory
        $hooksFile = ROOTDIR . '/includes/hooks/loginnotification.php';
        if (file_exists($hooksFile)) {
            unlink($hooksFile);
        }

        // Set enabled setting to 'off'
        Capsule::table('tbladdonmodules')
            ->where('module', 'loginnotification')
            ->where('setting', 'enabled')
            ->update(['value' => 'off']);

        return [
            'status' => 'success',
            'description' => 'Login Notification has been deactivated successfully.',
        ];
    } catch (\Exception $e) {
        return [
            'status' => 'error',
            'description' => 'Could not deactivate Login Notification: ' . $e->getMessage(),
        ];
    }
}

function loginnotification_output($vars) {
    // Get module status
    $moduleStatus = Capsule::table('tbladdonmodules')
        ->where('module', 'loginnotification')
        ->where('setting', 'enabled')
        ->value('value');

    // Get page parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    // Get search parameters
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    // Build query
    $query = Capsule::table('mod_loginnotification_logs')
        ->select([
            'mod_loginnotification_logs.*',
            'tblclients.firstname',
            'tblclients.lastname',
            'tblclients.email'
        ])
        ->join('tblclients', 'tblclients.id', '=', 'mod_loginnotification_logs.user_id');

    // Apply search if provided
    if ($search) {
        $query->where(function($q) use ($search) {
            $q->where('tblclients.firstname', 'like', "%{$search}%")
              ->orWhere('tblclients.lastname', 'like', "%{$search}%")
              ->orWhere('tblclients.email', 'like', "%{$search}%")
              ->orWhere('mod_loginnotification_logs.ip_address', 'like', "%{$search}%")
              ->orWhere('mod_loginnotification_logs.location', 'like', "%{$search}%");
        });
    }

    // Get total count for pagination
    $totalRecords = $query->count();
    $totalPages = ceil($totalRecords / $limit);

    // Get records for current page
    $logs = $query->orderBy('login_time', 'desc')
                 ->offset($offset)
                 ->limit($limit)
                 ->get();

    // Custom CSS
    echo '<style>
        .status-card {
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        .search-container {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .search-row {
            display: flex;
            align-items: center;
            max-width: 600px;
            margin: 0 auto;
        }
        .search-input {
            flex: 1;
            margin-right: 10px;
        }
        .search-button {
            min-width: 120px;
        }
        .table-container {
            background-color: white;
            padding: 20px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .status-badge {
            font-size: 14px;
            padding: 8px 16px;
            border-radius: 20px;
            color: white;
            font-weight: 500;
            margin: 10px 0;
            display: inline-block;
        }
        .status-badge.active {
            background-color: #28a745;
        }
        .status-badge.inactive {
            background-color: #dc3545;
        }
        .table th {
            background-color: #f8f9fa;
        }
        .pagination {
            margin-top: 20px;
        }
        .card-header {
            padding: 1rem;
        }
    </style>';

    // Start output
    echo '<div class="card">';
    echo '<div class="card-header d-flex justify-content-between align-items-center">';
    echo '<h3 class="card-title m-0">Login Notification Logs</h3>';
    echo '<span class="status-badge ' . ($moduleStatus === 'on' ? 'active' : 'inactive') . '">';
    echo 'Module ' . ($moduleStatus === 'on' ? 'Active' : 'Inactive');
    echo '</span>';
    echo '</div>';
    
    echo '<div class="card-body">';
    
    // Search form
    echo '<div class="search-container">';
    echo '<form method="get" action="addonmodules.php">';
    echo '<div class="search-row">';
    echo '<input type="hidden" name="module" value="loginnotification">';
    echo '<input type="text" name="search" class="form-control search-input" placeholder="Search by name, email, IP or location..." value="' . htmlspecialchars($search) . '">';
    echo '<button type="submit" class="btn btn-primary search-button">Search</button>';
    echo '</div>';
    echo '</form>';
    echo '</div>';

    // Table
    echo '<div class="table-container">';
    if ($totalRecords > 0) {
        echo '<div class="table-responsive">';
        echo '<table class="table table-bordered table-striped table-hover">';
        echo '<thead class="thead-light">';
        echo '<tr>';
        echo '<th width="5%">ID</th>';
        echo '<th width="15%">User</th>';
        echo '<th width="20%">Email</th>';
        echo '<th width="15%">IP Address</th>';
        echo '<th width="20%">Location</th>';
        echo '<th width="15%">ISP</th>';
        echo '<th width="10%">Login Time</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        foreach ($logs as $log) {
            echo '<tr>';
            echo '<td>' . $log->id . '</td>';
            echo '<td>' . htmlspecialchars($log->firstname . ' ' . $log->lastname) . '</td>';
            echo '<td>' . htmlspecialchars($log->email) . '</td>';
            echo '<td>' . htmlspecialchars($log->ip_address) . '</td>';
            echo '<td>' . htmlspecialchars($log->location) . '</td>';
            echo '<td>' . htmlspecialchars($log->isp) . '</td>';
            echo '<td>' . date('Y-m-d H:i', strtotime($log->login_time)) . '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
        echo '</div>';

        // Pagination
        if ($totalPages > 1) {
            echo '<div class="text-center">';
            echo '<ul class="pagination justify-content-center">';
            
            // Previous page
            if ($page > 1) {
                echo '<li class="page-item"><a class="page-link" href="?module=loginnotification&page=' . ($page - 1) . '&search=' . urlencode($search) . '">&laquo;</a></li>';
            }

            // Page numbers
            for ($i = 1; $i <= $totalPages; $i++) {
                echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">';
                echo '<a class="page-link" href="?module=loginnotification&page=' . $i . '&search=' . urlencode($search) . '">' . $i . '</a>';
                echo '</li>';
            }

            // Next page
            if ($page < $totalPages) {
                echo '<li class="page-item"><a class="page-link" href="?module=loginnotification&page=' . ($page + 1) . '&search=' . urlencode($search) . '">&raquo;</a></li>';
            }

            echo '</ul>';
            echo '</div>';
        }
    } else {
        echo '<div class="alert alert-info">No records found.</div>';
    }
    echo '</div>'; // table-container

    echo '</div>'; // card-body
    echo '</div>'; // card
}

function createLoginNotificationEmailTemplate() {
    // Check if template already exists
    $existing = Capsule::table('tblemailtemplates')
        ->where('name', 'Login Notification Email')
        ->first();

    if (!$existing) {
        // Create new template
        Capsule::table('tblemailtemplates')->insert([
            'type' => 'general',
            'name' => 'Login Notification Email',
            'subject' => 'New Login Notification',
            'message' => <<<'EOT'
<p>Dear {$client_name},</p>

<p>A new login has been detected on your account.</p>

<p><strong>Login Details:</strong><br>
Date/Time: {$login_time}<br>
IP Address: {$ip_address}<br>
Location: {$location}<br>
ISP: {$isp}</p>

<p>If this login was not initiated by you, please contact us immediately.</p>

<p>Best regards,<br>
{$company_name}</p>
EOT
            ,
            'plaintext' => 0,
            'custom' => 1,
            'language' => '',
            'disabled' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
} 