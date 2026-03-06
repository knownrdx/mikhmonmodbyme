<?php
/*
 * Mikhmon Multi-User Database System
 * Uses SQLite for user accounts and router sessions
 */

if(substr($_SERVER["REQUEST_URI"], -12) == "database.php"){header("Location:./");}

// Database path - persistent storage
define('DB_PATH', __DIR__ . '/../data/mikhmon.db');

function getDB() {
    $dbDir = dirname(DB_PATH);
    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0755, true);
    }
    
    $db = new SQLite3(DB_PATH);
    $db->busyTimeout(5000);
    $db->exec('PRAGMA journal_mode=WAL');
    $db->exec('PRAGMA foreign_keys=ON');
    
    // Create tables if not exist
    $db->exec('CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        role TEXT DEFAULT "user",
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )');
    
    $db->exec('CREATE TABLE IF NOT EXISTS routers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        session_name TEXT NOT NULL,
        ip_host TEXT DEFAULT "",
        user_host TEXT DEFAULT "",
        passwd_host TEXT DEFAULT "",
        hotspot_name TEXT DEFAULT "",
        dns_name TEXT DEFAULT "",
        currency TEXT DEFAULT "Rp",
        auto_reload INTEGER DEFAULT 10,
        iface TEXT DEFAULT "1",
        info_lp TEXT DEFAULT "",
        idle_timeout TEXT DEFAULT "10",
        live_report TEXT DEFAULT "disable",
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE(user_id, session_name)
    )');
    
    return $db;
}

// Migrate existing config.php data to database (one-time)
function migrateOldConfig() {
    $configFile = __DIR__ . '/config.php';
    $migrationFlag = dirname(DB_PATH) . '/.migrated';
    
    if (file_exists($migrationFlag)) return;
    if (!file_exists($configFile)) return;
    
    // Source the old config
    $data = array();
    include($configFile);
    
    if (empty($data)) return;
    
    $db = getDB();
    
    // Migrate admin user
    if (isset($data['mikhmon'])) {
        $oldUser = explode('<|<', $data['mikhmon'][1])[1];
        $oldPass = explode('>|>', $data['mikhmon'][2])[1]; // encrypted
        
        if (!empty($oldUser)) {
            // Decrypt old password
            include_once(__DIR__ . '/../lib/routeros_api.class.php');
            $plainPass = decrypt($oldPass);
            $hashedPass = password_hash($plainPass, PASSWORD_DEFAULT);
            
            $stmt = $db->prepare('INSERT OR IGNORE INTO users (username, password, role) VALUES (:user, :pass, "admin")');
            $stmt->bindValue(':user', $oldUser, SQLITE3_TEXT);
            $stmt->bindValue(':pass', $hashedPass, SQLITE3_TEXT);
            $stmt->execute();
            
            $userId = $db->querySingle("SELECT id FROM users WHERE username = " . $db->escapeString("'" . $oldUser . "'"));
            if (!$userId) {
                $userId = $db->querySingle("SELECT id FROM users WHERE username = '" . $db->escapeString($oldUser) . "'");
            }
            
            // Migrate router sessions
            if ($userId) {
                foreach ($data as $key => $val) {
                    if ($key == 'mikhmon' || $key == '') continue;
                    
                    $iphost = explode('!', $val[1])[1];
                    $userhost = explode('@|@', $val[2])[1];
                    $passwdhost = explode('#|#', $val[3])[1];
                    $hotspotname = explode('%', $val[4])[1];
                    $dnsname = explode('^', $val[5])[1];
                    $currency = explode('&', $val[6])[1];
                    $areload = explode('*', $val[7])[1];
                    $iface = explode('(', $val[8])[1];
                    $infolp = explode(')', $val[9])[1];
                    $idleto = explode('=', $val[10])[1];
                    $livereport = isset($val[11]) ? explode('@!@', $val[11])[1] : 'disable';
                    
                    $stmt = $db->prepare('INSERT OR IGNORE INTO routers 
                        (user_id, session_name, ip_host, user_host, passwd_host, hotspot_name, dns_name, currency, auto_reload, iface, info_lp, idle_timeout, live_report)
                        VALUES (:uid, :sess, :ip, :user, :pass, :hname, :dns, :cur, :reload, :iface, :info, :idle, :live)');
                    $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
                    $stmt->bindValue(':sess', $key, SQLITE3_TEXT);
                    $stmt->bindValue(':ip', $iphost, SQLITE3_TEXT);
                    $stmt->bindValue(':user', $userhost, SQLITE3_TEXT);
                    $stmt->bindValue(':pass', $passwdhost, SQLITE3_TEXT);
                    $stmt->bindValue(':hname', $hotspotname, SQLITE3_TEXT);
                    $stmt->bindValue(':dns', $dnsname, SQLITE3_TEXT);
                    $stmt->bindValue(':cur', $currency, SQLITE3_TEXT);
                    $stmt->bindValue(':reload', (int)$areload, SQLITE3_INTEGER);
                    $stmt->bindValue(':iface', $iface, SQLITE3_TEXT);
                    $stmt->bindValue(':info', $infolp, SQLITE3_TEXT);
                    $stmt->bindValue(':idle', $idleto, SQLITE3_TEXT);
                    $stmt->bindValue(':live', $livereport, SQLITE3_TEXT);
                    $stmt->execute();
                }
            }
        }
    }
    
    // Mark migration as done
    file_put_contents($migrationFlag, date('Y-m-d H:i:s'));
    $db->close();
}

// User functions
function dbCreateUser($username, $password, $role = 'user') {
    $db = getDB();
    $hashedPass = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO users (username, password, role) VALUES (:user, :pass, :role)');
    $stmt->bindValue(':user', $username, SQLITE3_TEXT);
    $stmt->bindValue(':pass', $hashedPass, SQLITE3_TEXT);
    $stmt->bindValue(':role', $role, SQLITE3_TEXT);
    $result = $stmt->execute();
    $id = $result ? $db->lastInsertRowID() : false;
    $db->close();
    return $id;
}

function dbAuthUser($username, $password) {
    $db = getDB();
    $stmt = $db->prepare('SELECT id, username, password, role FROM users WHERE username = :user');
    $stmt->bindValue(':user', $username, SQLITE3_TEXT);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);
    $db->close();
    
    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }
    return false;
}

function dbUpdatePassword($userId, $newPassword) {
    $db = getDB();
    $hashedPass = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $db->prepare('UPDATE users SET password = :pass WHERE id = :id');
    $stmt->bindValue(':pass', $hashedPass, SQLITE3_TEXT);
    $stmt->bindValue(':id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $db->close();
    return $result ? true : false;
}

function dbGetAllUsers() {
    $db = getDB();
    $results = $db->query('SELECT id, username, role, created_at FROM users ORDER BY id');
    $users = array();
    while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
        $users[] = $row;
    }
    $db->close();
    return $users;
}

function dbDeleteUser($userId) {
    $db = getDB();
    $stmt = $db->prepare('DELETE FROM users WHERE id = :id AND role != "admin"');
    $stmt->bindValue(':id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $changes = $db->changes();
    $db->close();
    return $changes > 0;
}

function dbUserCount() {
    $db = getDB();
    $count = $db->querySingle('SELECT COUNT(*) FROM users');
    $db->close();
    return (int)$count;
}

// Router functions
function dbGetRouters($userId) {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM routers WHERE user_id = :uid ORDER BY session_name');
    $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $routers = array();
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $routers[] = $row;
    }
    $db->close();
    return $routers;
}

function dbGetRouter($userId, $sessionName) {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM routers WHERE user_id = :uid AND session_name = :sess');
    $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':sess', $sessionName, SQLITE3_TEXT);
    $result = $stmt->execute();
    $router = $result->fetchArray(SQLITE3_ASSOC);
    $db->close();
    return $router;
}

function dbSaveRouter($userId, $sessionName, $data) {
    $db = getDB();
    
    // Check if exists
    $existing = dbGetRouter($userId, $sessionName);
    
    if ($existing) {
        $stmt = $db->prepare('UPDATE routers SET 
            session_name = :newsess,
            ip_host = :ip, user_host = :user, passwd_host = :pass, 
            hotspot_name = :hname, dns_name = :dns, currency = :cur,
            auto_reload = :reload, iface = :iface, info_lp = :info,
            idle_timeout = :idle, live_report = :live
            WHERE user_id = :uid AND session_name = :sess');
        $stmt->bindValue(':newsess', isset($data['new_session_name']) ? $data['new_session_name'] : $sessionName, SQLITE3_TEXT);
    } else {
        $stmt = $db->prepare('INSERT INTO routers 
            (user_id, session_name, ip_host, user_host, passwd_host, hotspot_name, dns_name, currency, auto_reload, iface, info_lp, idle_timeout, live_report)
            VALUES (:uid, :newsess, :ip, :user, :pass, :hname, :dns, :cur, :reload, :iface, :info, :idle, :live)');
        $stmt->bindValue(':newsess', isset($data['new_session_name']) ? $data['new_session_name'] : $sessionName, SQLITE3_TEXT);
    }
    
    $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':sess', $sessionName, SQLITE3_TEXT);
    $stmt->bindValue(':ip', isset($data['ip_host']) ? $data['ip_host'] : '', SQLITE3_TEXT);
    $stmt->bindValue(':user', isset($data['user_host']) ? $data['user_host'] : '', SQLITE3_TEXT);
    $stmt->bindValue(':pass', isset($data['passwd_host']) ? $data['passwd_host'] : '', SQLITE3_TEXT);
    $stmt->bindValue(':hname', isset($data['hotspot_name']) ? $data['hotspot_name'] : '', SQLITE3_TEXT);
    $stmt->bindValue(':dns', isset($data['dns_name']) ? $data['dns_name'] : '', SQLITE3_TEXT);
    $stmt->bindValue(':cur', isset($data['currency']) ? $data['currency'] : 'Rp', SQLITE3_TEXT);
    $stmt->bindValue(':reload', isset($data['auto_reload']) ? (int)$data['auto_reload'] : 10, SQLITE3_INTEGER);
    $stmt->bindValue(':iface', isset($data['iface']) ? $data['iface'] : '1', SQLITE3_TEXT);
    $stmt->bindValue(':info', isset($data['info_lp']) ? $data['info_lp'] : '', SQLITE3_TEXT);
    $stmt->bindValue(':idle', isset($data['idle_timeout']) ? $data['idle_timeout'] : '10', SQLITE3_TEXT);
    $stmt->bindValue(':live', isset($data['live_report']) ? $data['live_report'] : 'disable', SQLITE3_TEXT);
    
    $result = $stmt->execute();
    $db->close();
    return $result ? true : false;
}

function dbDeleteRouter($userId, $sessionName) {
    $db = getDB();
    $stmt = $db->prepare('DELETE FROM routers WHERE user_id = :uid AND session_name = :sess');
    $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':sess', $sessionName, SQLITE3_TEXT);
    $result = $stmt->execute();
    $changes = $db->changes();
    $db->close();
    return $changes > 0;
}

// Auto-create first admin if no users exist
function ensureAdminExists() {
    if (dbUserCount() == 0) {
        dbCreateUser('mikhmon', '1234', 'admin');
    }
}

// Run migration and ensure admin
migrateOldConfig();
ensureAdminExists();
