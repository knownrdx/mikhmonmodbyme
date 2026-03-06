<?php

if(substr($_SERVER["REQUEST_URI"], -12) == "database.php"){header("Location:./");}

define('DB_PATH', __DIR__ . '/../data/mikhmon.db');

function getDB() {
    $dbDir = dirname(DB_PATH);
    if (!is_dir($dbDir)) { mkdir($dbDir, 0755, true); }
    
    $db = new SQLite3(DB_PATH);
    $db->busyTimeout(5000);
    $db->exec('PRAGMA journal_mode=WAL');
    $db->exec('PRAGMA foreign_keys=ON');
    
    // roles: admin = super admin (mikhmon), user = sub-admin (pays), subuser = end user (free, belongs to user)
    $db->exec('CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        role TEXT DEFAULT "subuser",
        sub_expires TEXT DEFAULT "",
        max_routers INTEGER DEFAULT 4,
        created_by INTEGER DEFAULT 0,
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

    $db->exec('CREATE TABLE IF NOT EXISTS login_tokens (
        token TEXT PRIMARY KEY,
        admin_id INTEGER NOT NULL,
        target_user_id INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )');

    // Migration: add new columns if missing
    $cols = array();
    $res = $db->query("PRAGMA table_info(users)");
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) { $cols[] = $row['name']; }
    if (!in_array('sub_expires', $cols)) $db->exec('ALTER TABLE users ADD COLUMN sub_expires TEXT DEFAULT ""');
    if (!in_array('created_by', $cols)) $db->exec('ALTER TABLE users ADD COLUMN created_by INTEGER DEFAULT 0');
    if (!in_array('max_routers', $cols)) $db->exec('ALTER TABLE users ADD COLUMN max_routers INTEGER DEFAULT 4');
    
    return $db;
}

// ==================== USER FUNCTIONS ====================

function dbCreateUser($username, $password, $role = 'subuser', $subExpires = '', $createdBy = 0, $maxRouters = 4) {
    $db = getDB();
    $hashedPass = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO users (username, password, role, sub_expires, created_by, max_routers) VALUES (:user, :pass, :role, :sub, :by, :mr)');
    $stmt->bindValue(':user', $username, SQLITE3_TEXT);
    $stmt->bindValue(':pass', $hashedPass, SQLITE3_TEXT);
    $stmt->bindValue(':role', $role, SQLITE3_TEXT);
    $stmt->bindValue(':sub', $subExpires, SQLITE3_TEXT);
    $stmt->bindValue(':by', $createdBy, SQLITE3_INTEGER);
    $stmt->bindValue(':mr', $maxRouters, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $id = $result ? $db->lastInsertRowID() : false;
    $db->close();
    return $id;
}

function dbAuthUser($username, $password) {
    $db = getDB();
    $stmt = $db->prepare('SELECT id, username, password, role, sub_expires, created_by, max_routers FROM users WHERE username = :user');
    $stmt->bindValue(':user', $username, SQLITE3_TEXT);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);
    $db->close();
    if ($user && password_verify($password, $user['password'])) return $user;
    return false;
}

function dbGetUserById($userId) {
    $db = getDB();
    $stmt = $db->prepare('SELECT id, username, role, sub_expires, created_by, max_routers, created_at FROM users WHERE id = :id');
    $stmt->bindValue(':id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);
    $db->close();
    return $user;
}

function dbUpdatePassword($userId, $newPassword) {
    $db = getDB();
    $hashedPass = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $db->prepare('UPDATE users SET password = :pass WHERE id = :id');
    $stmt->bindValue(':pass', $hashedPass, SQLITE3_TEXT);
    $stmt->bindValue(':id', $userId, SQLITE3_INTEGER);
    $stmt->execute();
    $db->close();
    return true;
}

function dbUpdateSubscription($userId, $subExpires) {
    $db = getDB();
    $stmt = $db->prepare('UPDATE users SET sub_expires = :sub WHERE id = :id');
    $stmt->bindValue(':sub', $subExpires, SQLITE3_TEXT);
    $stmt->bindValue(':id', $userId, SQLITE3_INTEGER);
    $stmt->execute();
    $db->close();
    return true;
}

function dbUpdateMaxRouters($userId, $maxRouters) {
    $db = getDB();
    $stmt = $db->prepare('UPDATE users SET max_routers = :mr WHERE id = :id');
    $stmt->bindValue(':mr', $maxRouters, SQLITE3_INTEGER);
    $stmt->bindValue(':id', $userId, SQLITE3_INTEGER);
    $stmt->execute();
    $db->close();
    return true;
}

function dbGetAllUsers() {
    $db = getDB();
    $results = $db->query('SELECT id, username, role, sub_expires, created_by, max_routers, created_at FROM users ORDER BY id');
    $users = array();
    while ($row = $results->fetchArray(SQLITE3_ASSOC)) { $users[] = $row; }
    $db->close();
    return $users;
}

// Get sub-admins (role=user) only — for admin panel
function dbGetSubAdmins() {
    $db = getDB();
    $results = $db->query('SELECT id, username, role, sub_expires, created_by, max_routers, created_at FROM users WHERE role = "user" ORDER BY id');
    $users = array();
    while ($row = $results->fetchArray(SQLITE3_ASSOC)) { $users[] = $row; }
    $db->close();
    return $users;
}

// Get subusers created by a specific user (sub-admin)
function dbGetSubUsers($parentId) {
    $db = getDB();
    $stmt = $db->prepare('SELECT id, username, role, sub_expires, created_by, max_routers, created_at FROM users WHERE created_by = :pid AND role = "subuser" ORDER BY id');
    $stmt->bindValue(':pid', $parentId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $users = array();
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) { $users[] = $row; }
    $db->close();
    return $users;
}

function dbDeleteUser($userId) {
    $db = getDB();
    // Don't delete admin
    $stmt = $db->prepare('DELETE FROM users WHERE id = :id AND role != "admin"');
    $stmt->bindValue(':id', $userId, SQLITE3_INTEGER);
    $stmt->execute();
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

// Subscription check — admin=always active, user=check sub_expires, subuser=check parent's sub_expires
function dbIsSubActive($user) {
    if ($user['role'] == 'admin') return true;
    if ($user['role'] == 'user') {
        if (empty($user['sub_expires'])) return false;
        return (strtotime($user['sub_expires']) >= strtotime(date('Y-m-d')));
    }
    // subuser — check parent's subscription
    if ($user['role'] == 'subuser' && $user['created_by'] > 0) {
        $parent = dbGetUserById($user['created_by']);
        if ($parent) return dbIsSubActive($parent);
    }
    return false;
}

function dbGetSubDaysLeft($user) {
    if ($user['role'] == 'admin') return -1;
    if ($user['role'] == 'subuser' && $user['created_by'] > 0) {
        $parent = dbGetUserById($user['created_by']);
        if ($parent) return dbGetSubDaysLeft($parent);
        return 0;
    }
    if (empty($user['sub_expires'])) return 0;
    $diff = strtotime($user['sub_expires']) - strtotime(date('Y-m-d'));
    return max(0, (int)floor($diff / 86400));
}

// ==================== ROUTER FUNCTIONS ====================

function dbGetRouterCount($userId) {
    $db = getDB();
    $stmt = $db->prepare('SELECT COUNT(*) FROM routers WHERE user_id = :uid');
    $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
    $count = $stmt->execute()->fetchArray()[0];
    $db->close();
    return (int)$count;
}

function dbGetMaxRouters($userId) {
    $user = dbGetUserById($userId);
    if (!$user) return 0;
    if ($user['role'] == 'admin') return 999;
    // subuser inherits parent's limit
    if ($user['role'] == 'subuser' && $user['created_by'] > 0) {
        return $user['max_routers']; // subuser has own limit set by parent
    }
    return (int)$user['max_routers'];
}

function dbCanAddRouter($userId) {
    return dbGetRouterCount($userId) < dbGetMaxRouters($userId);
}

function dbGetRouters($userId) {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM routers WHERE user_id = :uid ORDER BY session_name');
    $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $routers = array();
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) { $routers[] = $row; }
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
    $existing = dbGetRouter($userId, $sessionName);
    
    if ($existing) {
        $stmt = $db->prepare('UPDATE routers SET 
            session_name = :newsess, ip_host = :ip, user_host = :user, passwd_host = :pass, 
            hotspot_name = :hname, dns_name = :dns, currency = :cur, auto_reload = :reload, 
            iface = :iface, info_lp = :info, idle_timeout = :idle, live_report = :live
            WHERE user_id = :uid AND session_name = :sess');
    } else {
        // Check router limit before adding
        if (!dbCanAddRouter($userId)) return false;
        $stmt = $db->prepare('INSERT INTO routers 
            (user_id, session_name, ip_host, user_host, passwd_host, hotspot_name, dns_name, currency, auto_reload, iface, info_lp, idle_timeout, live_report)
            VALUES (:uid, :newsess, :ip, :user, :pass, :hname, :dns, :cur, :reload, :iface, :info, :idle, :live)');
    }
    
    $stmt->bindValue(':newsess', isset($data['new_session_name']) ? $data['new_session_name'] : $sessionName, SQLITE3_TEXT);
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
    $stmt->execute();
    $changes = $db->changes();
    $db->close();
    return $changes > 0;
}

// ==================== LOGIN AS USER ====================

function dbGenerateLoginToken($adminId, $targetUserId) {
    $token = bin2hex(random_bytes(32));
    $db = getDB();
    $db->exec("DELETE FROM login_tokens WHERE created_at < datetime('now', '-5 minutes')");
    $stmt = $db->prepare('INSERT INTO login_tokens (token, admin_id, target_user_id) VALUES (:token, :aid, :tid)');
    $stmt->bindValue(':token', $token, SQLITE3_TEXT);
    $stmt->bindValue(':aid', $adminId, SQLITE3_INTEGER);
    $stmt->bindValue(':tid', $targetUserId, SQLITE3_INTEGER);
    $stmt->execute();
    $db->close();
    return $token;
}

function dbConsumeLoginToken($token) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM login_tokens WHERE token = :token AND created_at > datetime('now', '-5 minutes')");
    $stmt->bindValue(':token', $token, SQLITE3_TEXT);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    if ($row) {
        $db->exec("DELETE FROM login_tokens WHERE token = '" . $db->escapeString($token) . "'");
        $db->close();
        return $row;
    }
    $db->close();
    return false;
}

// ==================== INIT ====================

function ensureAdminExists() {
    if (dbUserCount() == 0) {
        dbCreateUser('mikhmon', '1234', 'admin', '', 0, 999);
    }
}

function migrateOldConfig() {
    $migrationFlag = dirname(DB_PATH) . '/.migrated';
    if (file_exists($migrationFlag)) return;
    file_put_contents($migrationFlag, date('Y-m-d H:i:s'));
}

migrateOldConfig();
ensureAdminExists();
