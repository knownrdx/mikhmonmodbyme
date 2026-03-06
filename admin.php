<?php
session_start();
error_reporting(0);
ob_start("ob_gzhandler");

$url = $_SERVER['REQUEST_URI'];
$session = $_GET['session'];
$id = $_GET['id'];
$c = $_GET['c'];
$router = $_GET['router'];
$logo = $_GET['logo'];

$ids = array("editor","uplogo","settings");

include('./lang/isocodelang.php');
include('./include/lang.php');
include('./lang/'.$langid.'.php');
include('./include/quickbt.php');
include('./include/theme.php');
include('./settings/settheme.php');
include('./settings/setlang.php');
if ($_SESSION['theme'] == "") {
    $theme = $theme;
    $themecolor = $themecolor;
} else {
    $theme = $_SESSION['theme'];
    $themecolor = $_SESSION['themecolor'];
}

include_once('./include/headhtml.php');
include('./include/config.php');
include('./include/readcfg.php');
include_once('./lib/routeros_api.class.php');
include_once('./lib/formatbytesbites.php');
?>

<?php
// ==================== LOGIN AS USER (token-based) ====================
if ($id == "login-as" && isset($_GET['token'])) {
  $tokenData = dbConsumeLoginToken($_GET['token']);
  if ($tokenData) {
    $targetUser = dbGetUserById($tokenData['target_user_id']);
    if ($targetUser) {
      $_SESSION["mikhmon"] = $targetUser['username'];
      $_SESSION["user_id"] = $targetUser['id'];
      $_SESSION["user_role"] = $targetUser['role'];
      $_SESSION["logged_in_as"] = true;
      $_SESSION["original_admin_id"] = $tokenData['admin_id'];
      echo "<script>window.location='./admin.php?id=sessions'</script>";
    } else {
      echo "<script>alert('User not found');window.location='./admin.php?id=login'</script>";
    }
  } else {
    echo "<script>alert('Invalid or expired token');window.location='./admin.php?id=login'</script>";
  }

// ==================== SWITCH BACK TO ADMIN ====================
} elseif ($id == "switch-back" && isset($_SESSION['original_admin_id'])) {
  $adminUser = dbGetUserById($_SESSION['original_admin_id']);
  if ($adminUser && $adminUser['role'] == 'admin') {
    $_SESSION["mikhmon"] = $adminUser['username'];
    $_SESSION["user_id"] = $adminUser['id'];
    $_SESSION["user_role"] = 'admin';
    unset($_SESSION["logged_in_as"]);
    unset($_SESSION["original_admin_id"]);
  }
  echo "<script>window.location='./admin.php?id=users'</script>";

// ==================== LOGIN ====================
} elseif ($id == "login" || substr($url, -1) == "p") {

  if (isset($_POST['login'])) {
    $user = $_POST['user'];
    $pass = $_POST['pass'];
    
    $authUser = dbAuthUser($user, $pass);
    if ($authUser) {
      // Check subscription for non-admin users
      if ($authUser['role'] != 'admin' && !dbIsSubActive($authUser)) {
        $error = '<div style="width: 100%; padding:5px 0px 5px 0px; border-radius:5px;" class="bg-danger"><i class="fa fa-ban"></i> Your subscription has expired. Please contact admin.</div>';
      } else {
        $_SESSION["mikhmon"] = $authUser['username'];
        $_SESSION["user_id"] = $authUser['id'];
        $_SESSION["user_role"] = $authUser['role'];
        unset($_SESSION["logged_in_as"]);
        unset($_SESSION["original_admin_id"]);
        echo "<script>window.location='./admin.php?id=sessions'</script>";
      }
    } else {
      $error = '<div style="width: 100%; padding:5px 0px 5px 0px; border-radius:5px;" class="bg-danger"><i class="fa fa-ban"></i> Alert!<br>Invalid username or password.</div>';
    }
  }

  include_once('./include/login.php');

// ==================== REGISTER ====================
} elseif ($id == "register") {

  if (isset($_POST['register'])) {
    $newuser = trim($_POST['user']);
    $newpass = $_POST['pass'];
    $newpass2 = $_POST['pass2'];
    
    if (strlen($newuser) < 3) {
      $error = '<div style="width: 100%; padding:5px 0px 5px 0px; border-radius:5px;" class="bg-danger"><i class="fa fa-ban"></i> Username minimum 3 characters.</div>';
    } elseif (strlen($newpass) < 4) {
      $error = '<div style="width: 100%; padding:5px 0px 5px 0px; border-radius:5px;" class="bg-danger"><i class="fa fa-ban"></i> Password minimum 4 characters.</div>';
    } elseif ($newpass !== $newpass2) {
      $error = '<div style="width: 100%; padding:5px 0px 5px 0px; border-radius:5px;" class="bg-danger"><i class="fa fa-ban"></i> Passwords do not match.</div>';
    } else {
      $result = dbCreateUser($newuser, $newpass, 'user', '', 0);
      if ($result) {
        $success = '<div style="width: 100%; padding:5px 0px 5px 0px; border-radius:5px;" class="bg-success"><i class="fa fa-check"></i> Registration successful! Please contact admin to activate your subscription.</div>';
      } else {
        $error = '<div style="width: 100%; padding:5px 0px 5px 0px; border-radius:5px;" class="bg-danger"><i class="fa fa-ban"></i> Username already exists.</div>';
      }
    }
  }

  include_once('./include/register.php');

// ==================== USER MANAGEMENT (admin + sub-admin) ====================
} elseif ($id == "users" && isset($_SESSION["mikhmon"]) && ($_SESSION["user_role"] == "admin" || $_SESSION["user_role"] == "user")) {
  
  // Generate login-as token
  if (isset($_GET['login-as-user']) && $_GET['login-as-user'] != '') {
    $targetId = (int)$_GET['login-as-user'];
    // Sub-admin can only login-as their own subusers
    if ($_SESSION['user_role'] == 'user') {
      $targetUser = dbGetUserById($targetId);
      if (!$targetUser || $targetUser['created_by'] != $_SESSION['user_id']) {
        echo "<script>alert('Access denied');window.location='./admin.php?id=users'</script>";
        exit;
      }
    }
    $token = dbGenerateLoginToken($_SESSION['user_id'], $targetId);
    echo "<script>window.location='./admin.php?id=login-as&token=" . $token . "'</script>";
    exit;
  }
  
  // Delete user
  if (isset($_GET['delete-user']) && $_GET['delete-user'] != '') {
    $delId = (int)$_GET['delete-user'];
    // Sub-admin can only delete their own subusers
    if ($_SESSION['user_role'] == 'user') {
      $targetUser = dbGetUserById($delId);
      if (!$targetUser || $targetUser['created_by'] != $_SESSION['user_id']) {
        echo "<script>alert('Access denied');window.location='./admin.php?id=users'</script>";
        exit;
      }
    }
    dbDeleteUser($delId);
    echo "<script>window.location='./admin.php?id=users'</script>";
  }
  
  // Add user
  if (isset($_POST['adduser'])) {
    $newuser = trim($_POST['newuser']);
    $newpass = $_POST['newpass'];
    if (!empty($newuser) && !empty($newpass)) {
      if ($_SESSION['user_role'] == 'admin') {
        // Admin adds sub-admin (role=user) with subscription
        $subMonths = (int)$_POST['submonths'];
        if ($subMonths < 1) $subMonths = 1;
        $subExpires = date('Y-m-d', strtotime("+{$subMonths} months"));
        $maxRouters = (int)$_POST['maxrouters'];
        if ($maxRouters < 1) $maxRouters = 4;
        dbCreateUser($newuser, $newpass, 'user', $subExpires, $_SESSION['user_id'], $maxRouters);
      } else {
        // Sub-admin adds subuser (role=subuser), no subscription needed, default 4 routers
        dbCreateUser($newuser, $newpass, 'subuser', '', $_SESSION['user_id'], 4);
      }
    }
    echo "<script>window.location='./admin.php?id=users'</script>";
  }

  // Update subscription (admin only)
  if (isset($_POST['updatesub']) && $_SESSION['user_role'] == 'admin') {
    $subUserId = (int)$_POST['sub_user_id'];
    $subMonths = (int)$_POST['submonths'];
    if ($subMonths < 1) $subMonths = 1;
    $currentUser = dbGetUserById($subUserId);
    if ($currentUser) {
      $baseDate = (!empty($currentUser['sub_expires']) && strtotime($currentUser['sub_expires']) > time()) ? $currentUser['sub_expires'] : date('Y-m-d');
      $newExpiry = date('Y-m-d', strtotime($baseDate . " +{$subMonths} months"));
      dbUpdateSubscription($subUserId, $newExpiry);
    }
    echo "<script>window.location='./admin.php?id=users'</script>";
  }

  // Update max routers (admin only)
  if (isset($_POST['updaterouters']) && $_SESSION['user_role'] == 'admin') {
    $routerUserId = (int)$_POST['router_user_id'];
    $newMax = (int)$_POST['newmaxrouters'];
    if ($newMax < 1) $newMax = 1;
    dbUpdateMaxRouters($routerUserId, $newMax);
    echo "<script>window.location='./admin.php?id=users'</script>";
  }
  
  include_once('./include/menu.php');
  include_once('./include/usermanage.php');

} elseif (!isset($_SESSION["mikhmon"])) {
  echo "<script>window.location='./admin.php?id=login'</script>";

} elseif (substr($url, -1) == "/" || substr($url, -4) == ".php") {
  echo "<script>window.location='./admin.php?id=sessions'</script>";

} elseif ($id == "sessions") {
  // Check subscription
  if ($_SESSION['user_role'] != 'admin') {
    $currentUserData = dbGetUserById($_SESSION['user_id']);
    if (!dbIsSubActive($currentUserData)) {
      echo "<script>alert('Your subscription has expired. Please contact admin.');window.location='./admin.php?id=logout'</script>";
      exit;
    }
  }
  $_SESSION["connect"] = "";
  include_once('./include/menu.php');
  include_once('./settings/sessions.php');

} elseif ($id == "settings" && !empty($session) || $id == "settings" && !empty($router)) {
  include_once('./include/menu.php');
  include_once('./settings/settings.php');
  echo '
  <script type="text/javascript">
    document.getElementById("sessname").onkeypress = function(e) {
    var chr = String.fromCharCode(e.which);
    if (" _!@#$%^&*()+=;|?,~".indexOf(chr) >= 0)
        return false;
    };
    </script>';

} elseif ($id == "connect" && !empty($session)) {
  ini_set("max_execution_time",5);  
  include_once('./include/menu.php');
  $API = new RouterosAPI();
  $API->debug = false;
  if ($API->connect($iphost, $userhost, decrypt($passwdhost))){
    $_SESSION["connect"] = "<b class='text-green'>Connected</b>";
    echo "<script>window.location='./?session=" . $session . "'</script>";
  } else {
    $_SESSION["connect"] = "<b class='text-red'>Not Connected</b>";
    $nl = '\n';
    echo "<script>alert('Not connected!".$nl."Please check IP, User, Password and API port.')</script>";
    if($c == "settings"){
      echo "<script>window.location='./admin.php?id=settings&session=" . $session . "'</script>";
    }else{
      echo "<script>window.location='./admin.php?id=sessions'</script>";
    }
  }

} elseif ($id == "uplogo" && !empty($session)) {
  include_once('./include/menu.php');
  include_once('./settings/uplogo.php');
} elseif ($id == "reboot" && !empty($session)) {
  include_once('./process/reboot.php');
} elseif ($id == "shutdown" && !empty($session)) {
  include_once('./process/shutdown.php');
} elseif ($id == "remove-session" && $session != "") {
  include_once('./include/menu.php');
  if (isset($_SESSION['user_id'])) {
    dbDeleteRouter($_SESSION['user_id'], $session);
  }
  echo "<script>window.location='./admin.php?id=sessions'</script>";
} elseif ($id == "about") {
  include_once('./include/menu.php');
  include_once('./include/about.php');
} elseif ($id == "logout") {
  include_once('./include/menu.php');
  echo "<b class='cl-w'><i class='fa fa-circle-o-notch fa-spin' style='font-size:24px'></i> Logout...</b>";
  session_destroy();
  echo "<script>window.location='./admin.php?id=login'</script>";
} elseif ($id == "remove-logo" && $logo != "" && !empty($session)) {
  include_once('./include/menu.php');
  $logopath = "./img/";
  unlink($logopath . $logo);
  echo "<script>window.location='./admin.php?id=uplogo&session=" . $session . "'</script>";
} elseif ($id == "editor" && !empty($session)) {
  include_once('./include/menu.php');
  include_once('./settings/vouchereditor.php');
} elseif (empty($id)) {
  echo "<script>window.location='./admin.php?id=sessions'</script>";
} elseif(in_array($id, $ids) && empty($session)){
  echo "<script>window.location='./admin.php?id=sessions'</script>";
}
?>
<script src="js/mikhmon-ui.<?= $theme; ?>.min.js"></script>
<script src="js/mikhmon.js?t=<?= str_replace(" ","_",date("Y-m-d H:i:s")); ?>"></script>
<?php @include('./include/info.php'); ?>
</body>
</html>
