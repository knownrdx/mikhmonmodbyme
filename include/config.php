<?php 
if(substr($_SERVER["REQUEST_URI"], -10) == "config.php"){header("Location:./");}

// Load database system
include_once(__DIR__ . '/database.php');

// Build $data array from database for backward compatibility
$data = array();

// Admin placeholder
$data['mikhmon'] = array('1' => 'mikhmon<|<' . (isset($_SESSION['mikhmon']) ? $_SESSION['mikhmon'] : ''), 'mikhmon>|>placeholder');

// Load routers for current user
if (isset($_SESSION['user_id'])) {
    $dbRouters = dbGetRouters($_SESSION['user_id']);
    foreach ($dbRouters as $r) {
        $sess = $r['session_name'];
        $data[$sess] = array(
            '1' => $sess . '!' . $r['ip_host'],
            '2' => $sess . '@|@' . $r['user_host'],
            '3' => $sess . '#|#' . $r['passwd_host'],
            '4' => $sess . '%' . $r['hotspot_name'],
            '5' => $sess . '^' . $r['dns_name'],
            '6' => $sess . '&' . $r['currency'],
            '7' => $sess . '*' . $r['auto_reload'],
            '8' => $sess . '(' . $r['iface'],
            '9' => $sess . ')' . $r['info_lp'],
            '10' => $sess . '=' . $r['idle_timeout'],
            '11' => $sess . '@!@' . $r['live_report'],
        );
    }
}
