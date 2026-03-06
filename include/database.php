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

    // roles:
    // superadmin = mikhmon (god mode, no limits)
    // admin = sub-admin (pays monthly, can create users, shared router pool)
    // user = end user (pays monthly independently, own routers only)
    $db->exec('CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        role TEXT DEFAULT "user",
        sub_expires TEXT DEFAULT "",
        max_routers INTEGER DEFAULT 5,
        max_users INTEGER DEFAULT 0,
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

    // Migration
    $cols = array();
    $res = $db->query("PRAGMA table_info(users)");
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) { $cols[] = $row['name']; }
    if (!in_array('sub_expires', $cols)) $db->exec('ALTER TABLE users ADD COLUMN sub_expires TEXT DEFAULT ""');
    if (!in_array('created_by', $cols)) $db->exec('ALTER TABLE users ADD COLUMN created_by INTEGER DEFAULT 0');
    if (!in_array('max_routers', $cols)) $db->exec('ALTER TABLE users ADD COLUMN max_routers INTEGER DEFAULT 5');
    if (!in_array('max_users', $cols)) $db->exec('ALTER TABLE users ADD COLUMN max_users INTEGER DEFAULT 0');

    return $db;
}

// ==================== USER CRUD ====================

function dbCreateUser($username, $password, $role='user', $subExpires='', $createdBy=0, $maxRouters=5, $maxUsers=0) {
    $db = getDB();
    $stmt = $db->prepare('INSERT INTO users (username,password,role,sub_expires,created_by,max_routers,max_users) VALUES (:u,:p,:r,:s,:c,:mr,:mu)');
    $stmt->bindValue(':u', $username, SQLITE3_TEXT);
    $stmt->bindValue(':p', password_hash($password, PASSWORD_DEFAULT), SQLITE3_TEXT);
    $stmt->bindValue(':r', $role, SQLITE3_TEXT);
    $stmt->bindValue(':s', $subExpires, SQLITE3_TEXT);
    $stmt->bindValue(':c', $createdBy, SQLITE3_INTEGER);
    $stmt->bindValue(':mr', $maxRouters, SQLITE3_INTEGER);
    $stmt->bindValue(':mu', $maxUsers, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $id = $result ? $db->lastInsertRowID() : false;
    $db->close();
    return $id;
}

function dbAuthUser($username, $password) {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE username = :u');
    $stmt->bindValue(':u', $username, SQLITE3_TEXT);
    $user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    $db->close();
    if ($user && password_verify($password, $user['password'])) return $user;
    return false;
}

function dbGetUserById($id) {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE id = :id');
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    $db->close();
    return $user;
}

function dbUpdatePassword($id, $pw) {
    $db = getDB();
    $stmt = $db->prepare('UPDATE users SET password=:p WHERE id=:id');
    $stmt->bindValue(':p', password_hash($pw, PASSWORD_DEFAULT), SQLITE3_TEXT);
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $stmt->execute(); $db->close();
}

function dbUpdateSubscription($id, $exp) {
    $db = getDB();
    $stmt = $db->prepare('UPDATE users SET sub_expires=:s WHERE id=:id');
    $stmt->bindValue(':s', $exp, SQLITE3_TEXT);
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $stmt->execute(); $db->close();
}

function dbUpdateLimits($id, $maxRouters, $maxUsers) {
    $db = getDB();
    $stmt = $db->prepare('UPDATE users SET max_routers=:mr, max_users=:mu WHERE id=:id');
    $stmt->bindValue(':mr', $maxRouters, SQLITE3_INTEGER);
    $stmt->bindValue(':mu', $maxUsers, SQLITE3_INTEGER);
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $stmt->execute(); $db->close();
}

function dbGetAllNonSuper() {
    $db = getDB();
    $r = $db->query('SELECT * FROM users WHERE role != "superadmin" ORDER BY role DESC, id');
    $a = array(); while ($row = $r->fetchArray(SQLITE3_ASSOC)) { $a[] = $row; } $db->close(); return $a;
}

// Get users created by an admin (sub-admin's users)
function dbGetChildUsers($parentId) {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE created_by=:pid ORDER BY id');
    $stmt->bindValue(':pid', $parentId, SQLITE3_INTEGER);
    $r = $stmt->execute();
    $a = array(); while ($row = $r->fetchArray(SQLITE3_ASSOC)) { $a[] = $row; } $db->close(); return $a;
}

function dbGetChildUserCount($parentId) {
    $db = getDB();
    $stmt = $db->prepare('SELECT COUNT(*) FROM users WHERE created_by=:pid');
    $stmt->bindValue(':pid', $parentId, SQLITE3_INTEGER);
    $c = $stmt->execute()->fetchArray()[0]; $db->close(); return (int)$c;
}

function dbDeleteUser($id) {
    $db = getDB();
    $stmt = $db->prepare('DELETE FROM users WHERE id=:id AND role!="superadmin"');
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $stmt->execute(); $c = $db->changes(); $db->close(); return $c > 0;
}

function dbUserCount() {
    $db = getDB();
    $c = $db->querySingle('SELECT COUNT(*) FROM users');
    $db->close(); return (int)$c;
}

// ==================== SUBSCRIPTION ====================

function dbIsSubActive($user) {
    if ($user['role'] == 'superadmin') return true;
    // admin's child user inherits parent subscription
    if (!empty($user['created_by']) && $user['created_by'] > 0) {
        $parent = dbGetUserById($user['created_by']);
        if ($parent && $parent['role'] == 'admin') return dbIsSubActive($parent);
    }
    if (empty($user['sub_expires'])) return false;
    return (strtotime($user['sub_expires']) >= strtotime(date('Y-m-d')));
}

function dbGetSubDaysLeft($user) {
    if ($user['role'] == 'superadmin') return -1;
    if (!empty($user['created_by']) && $user['created_by'] > 0) {
        $parent = dbGetUserById($user['created_by']);
        if ($parent && $parent['role'] == 'admin') return dbGetSubDaysLeft($parent);
    }
    if (empty($user['sub_expires'])) return 0;
    $d = strtotime($user['sub_expires']) - strtotime(date('Y-m-d'));
    return max(0, (int)floor($d / 86400));
}

// ==================== ROUTER FUNCTIONS ====================

function dbGetRouterCount($userId) {
    $db = getDB();
    $stmt = $db->prepare('SELECT COUNT(*) FROM routers WHERE user_id=:u');
    $stmt->bindValue(':u', $userId, SQLITE3_INTEGER);
    $c = $stmt->execute()->fetchArray()[0]; $db->close(); return (int)$c;
}

// Total routers used by admin + all their child users
function dbGetAdminTotalRouterCount($adminId) {
    $count = dbGetRouterCount($adminId);
    $children = dbGetChildUsers($adminId);
    foreach ($children as $ch) {
        $count += dbGetRouterCount($ch['id']);
    }
    return $count;
}

function dbCanAddRouter($userId) {
    $user = dbGetUserById($userId);
    if (!$user) return false;
    if ($user['role'] == 'superadmin') return true;

    // If user is a child of an admin, check admin's total pool
    if ($user['role'] == 'user' && !empty($user['created_by'])) {
        $parent = dbGetUserById($user['created_by']);
        if ($parent && $parent['role'] == 'admin') {
            return dbGetAdminTotalRouterCount($parent['id']) < $parent['max_routers'];
        }
    }

    // admin (sub-admin) — check total pool (self + children)
    if ($user['role'] == 'admin') {
        return dbGetAdminTotalRouterCount($userId) < $user['max_routers'];
    }

    // standalone user — check own count vs own limit
    return dbGetRouterCount($userId) < $user['max_routers'];
}

function dbCanAddUser($adminId) {
    $user = dbGetUserById($adminId);
    if (!$user) return false;
    if ($user['role'] == 'superadmin') return true;
    if ($user['role'] != 'admin') return false;
    return dbGetChildUserCount($adminId) < $user['max_users'];
}

function dbGetRouters($userId) {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM routers WHERE user_id=:u ORDER BY session_name');
    $stmt->bindValue(':u', $userId, SQLITE3_INTEGER);
    $r = $stmt->execute();
    $a = array(); while ($row = $r->fetchArray(SQLITE3_ASSOC)) { $a[] = $row; } $db->close(); return $a;
}

// Get all routers for admin + their child users (shared pool view)
function dbGetAdminAllRouters($adminId) {
    $all = dbGetRouters($adminId);
    $children = dbGetChildUsers($adminId);
    foreach ($children as $ch) {
        $childRouters = dbGetRouters($ch['id']);
        foreach ($childRouters as &$cr) {
            $cr['_owner'] = $ch['username'];
        }
        $all = array_merge($all, $childRouters);
    }
    return $all;
}

function dbGetRouter($userId, $sessionName) {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM routers WHERE user_id=:u AND session_name=:s');
    $stmt->bindValue(':u', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':s', $sessionName, SQLITE3_TEXT);
    $r = $stmt->execute()->fetchArray(SQLITE3_ASSOC); $db->close(); return $r;
}

function dbSaveRouter($userId, $sessionName, $data) {
    $db = getDB();
    $existing = dbGetRouter($userId, $sessionName);

    if ($existing) {
        $stmt = $db->prepare('UPDATE routers SET session_name=:ns, ip_host=:ip, user_host=:uh, passwd_host=:ph,
            hotspot_name=:hn, dns_name=:dn, currency=:cur, auto_reload=:ar, iface=:if, info_lp=:il,
            idle_timeout=:it, live_report=:lr WHERE user_id=:u AND session_name=:s');
    } else {
        if (!dbCanAddRouter($userId)) { $db->close(); return false; }
        $stmt = $db->prepare('INSERT INTO routers (user_id,session_name,ip_host,user_host,passwd_host,hotspot_name,
            dns_name,currency,auto_reload,iface,info_lp,idle_timeout,live_report)
            VALUES (:u,:ns,:ip,:uh,:ph,:hn,:dn,:cur,:ar,:if,:il,:it,:lr)');
    }

    $ns = isset($data['new_session_name']) ? $data['new_session_name'] : $sessionName;
    $stmt->bindValue(':ns', $ns, SQLITE3_TEXT);
    $stmt->bindValue(':u', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':s', $sessionName, SQLITE3_TEXT);
    $stmt->bindValue(':ip', @$data['ip_host'] ?: '', SQLITE3_TEXT);
    $stmt->bindValue(':uh', @$data['user_host'] ?: '', SQLITE3_TEXT);
    $stmt->bindValue(':ph', @$data['passwd_host'] ?: '', SQLITE3_TEXT);
    $stmt->bindValue(':hn', @$data['hotspot_name'] ?: '', SQLITE3_TEXT);
    $stmt->bindValue(':dn', @$data['dns_name'] ?: '', SQLITE3_TEXT);
    $stmt->bindValue(':cur', @$data['currency'] ?: 'Rp', SQLITE3_TEXT);
    $stmt->bindValue(':ar', @$data['auto_reload'] ? (int)$data['auto_reload'] : 10, SQLITE3_INTEGER);
    $stmt->bindValue(':if', @$data['iface'] ?: '1', SQLITE3_TEXT);
    $stmt->bindValue(':il', @$data['info_lp'] ?: '', SQLITE3_TEXT);
    $stmt->bindValue(':it', @$data['idle_timeout'] ?: '10', SQLITE3_TEXT);
    $stmt->bindValue(':lr', @$data['live_report'] ?: 'disable', SQLITE3_TEXT);

    $result = $stmt->execute(); $db->close();
    return $result ? true : false;
}

function dbDeleteRouter($userId, $sessionName) {
    $db = getDB();
    $stmt = $db->prepare('DELETE FROM routers WHERE user_id=:u AND session_name=:s');
    $stmt->bindValue(':u', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':s', $sessionName, SQLITE3_TEXT);
    $stmt->execute(); $c = $db->changes(); $db->close(); return $c > 0;
}

// ==================== LOGIN AS ====================

function dbGenerateLoginToken($fromId, $toId) {
    $t = bin2hex(random_bytes(32));
    $db = getDB();
    $db->exec("DELETE FROM login_tokens WHERE created_at < datetime('now','-5 minutes')");
    $stmt = $db->prepare('INSERT INTO login_tokens (token,admin_id,target_user_id) VALUES (:t,:a,:u)');
    $stmt->bindValue(':t', $t, SQLITE3_TEXT);
    $stmt->bindValue(':a', $fromId, SQLITE3_INTEGER);
    $stmt->bindValue(':u', $toId, SQLITE3_INTEGER);
    $stmt->execute(); $db->close(); return $t;
}

function dbConsumeLoginToken($token) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM login_tokens WHERE token=:t AND created_at > datetime('now','-5 minutes')");
    $stmt->bindValue(':t', $token, SQLITE3_TEXT);
    $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    if ($row) { $db->exec("DELETE FROM login_tokens WHERE token='".$db->escapeString($token)."'"); }
    $db->close(); return $row ?: false;
}

// ==================== INIT ====================
function ensureAdminExists() {
    if (dbUserCount() == 0) dbCreateUser('mikhmon', '1234', 'superadmin', '', 0, 999, 999);
}
function migrateOldConfig() {
    $f = dirname(DB_PATH).'/.migrated';
    if (file_exists($f)) return;
    file_put_contents($f, date('Y-m-d H:i:s'));
}
migrateOldConfig();
ensureAdminExists();
