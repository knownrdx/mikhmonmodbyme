<?php
if (!isset($_SESSION["mikhmon"])) { header("Location:../admin.php?id=login"); exit; }
$isSuperAdmin = ($_SESSION['user_role'] == 'superadmin');
$isAdmin = ($_SESSION['user_role'] == 'admin');
if (!$isSuperAdmin && !$isAdmin) { echo "<script>window.location='./admin.php?id=sessions'</script>"; exit; }

if ($isSuperAdmin) {
    $listUsers = dbGetAllNonSuper();
} else {
    $listUsers = dbGetChildUsers($_SESSION['user_id']);
    $myData = dbGetUserById($_SESSION['user_id']);
}
?>

<div class="row">
<div class="col-12">
<div class="card">
<div class="card-header">
  <h3 class="card-title"><i class="fa fa-users"></i> 
  <?= $isSuperAdmin ? 'User Management' : 'My Users ('.count($listUsers).'/'.$myData['max_users'].')'; ?>
  <?php if (isset($_SESSION['logged_in_as'])): ?>
    &nbsp;|&nbsp; <a href="./admin.php?id=switch-back" class="text-green"><i class="fa fa-arrow-left"></i> Switch Back</a>
  <?php endif; ?>
  </h3>
</div>
<div class="card-body">
<div class="row">

<div class="<?= $isSuperAdmin ? 'col-8' : 'col-7'; ?>">
<div class="card">
<div class="card-header"><h3 class="card-title"><i class="fa fa-list"></i> <?= $isSuperAdmin ? 'All Admins & Users' : 'My Users'; ?> (<?= count($listUsers); ?>)</h3></div>
<div class="card-body">
<div class="overflow box-bordered" style="max-height:70vh">
<table class="table table-bordered table-hover text-nowrap">
<thead><tr>
  <th>#</th><th>Username</th><th>Type</th>
  <?php if ($isSuperAdmin): ?>
    <th>Subscription</th><th>Days</th><th>Routers</th><th>Users</th><th>Extend</th><th>Set Limits</th>
  <?php endif; ?>
  <th>Action</th>
</tr></thead>
<tbody>
<?php foreach ($listUsers as $i => $u): ?>
<tr>
  <td><?= $i+1; ?></td>
  <td><i class="fa fa-user"></i> <?= htmlspecialchars($u['username']); ?></td>
  <td>
    <?php if ($u['role'] == 'admin'): ?>
      <span class="text-green"><i class="fa fa-star"></i> Admin</span>
    <?php else: ?>
      <span class="text-blue"><i class="fa fa-user"></i> User</span>
    <?php endif; ?>
  </td>

  <?php if ($isSuperAdmin): ?>
  <td>
    <?php if (empty($u['sub_expires'])): ?>
      <span class="text-red">Not Set</span>
    <?php elseif (dbIsSubActive($u)): ?>
      <span class="text-green"><?= $u['sub_expires']; ?></span>
    <?php else: ?>
      <span class="text-red"><?= $u['sub_expires']; ?></span>
    <?php endif; ?>
  </td>
  <td>
    <?php $dl = dbGetSubDaysLeft($u);
    if ($dl > 7): ?><span class="text-green"><?= $dl; ?>d</span>
    <?php elseif ($dl > 0): ?><span class="text-orange"><?= $dl; ?>d</span>
    <?php else: ?><span class="text-red">Exp</span><?php endif; ?>
  </td>
  <td>
    <?php if ($u['role'] == 'admin'): ?>
      <?= dbGetAdminTotalRouterCount($u['id']); ?>/<?= $u['max_routers']; ?>
    <?php else: ?>
      <?= dbGetRouterCount($u['id']); ?>/<?= $u['max_routers']; ?>
    <?php endif; ?>
  </td>
  <td>
    <?php if ($u['role'] == 'admin'): ?>
      <?= dbGetChildUserCount($u['id']); ?>/<?= $u['max_users']; ?>
    <?php else: ?>-<?php endif; ?>
  </td>
  <td>
    <form method="post" action="" style="display:inline;">
      <input type="hidden" name="sub_user_id" value="<?= $u['id']; ?>">
      <select name="submonths" style="width:55px;" class="form-control">
        <option value="1">1m</option><option value="2">2m</option><option value="3">3m</option>
        <option value="6">6m</option><option value="12">12m</option>
      </select>
      <button type="submit" name="updatesub" class="btn bg-green" style="padding:2px 6px;"><i class="fa fa-plus"></i></button>
    </form>
  </td>
  <td>
    <form method="post" action="" style="display:inline;">
      <input type="hidden" name="limit_user_id" value="<?= $u['id']; ?>">
      R:<input type="number" name="nr" value="<?= $u['max_routers']; ?>" min="1" max="9999" style="width:50px;" class="form-control">
      <?php if ($u['role'] == 'admin'): ?>
        U:<input type="number" name="nu" value="<?= $u['max_users']; ?>" min="0" max="9999" style="width:50px;" class="form-control">
      <?php else: ?>
        <input type="hidden" name="nu" value="0">
      <?php endif; ?>
      <button type="submit" name="updatelimits" class="btn bg-blue" style="padding:2px 6px;"><i class="fa fa-save"></i></button>
    </form>
  </td>
  <?php endif; ?>

  <td>
    <a href="./admin.php?id=users&login-as-user=<?= $u['id']; ?>" class="text-blue" title="Login as"><i class="fa fa-sign-in"></i></a>
    &nbsp;
    <a href="javascript:void(0)" onclick="if(confirm('Delete <?= htmlspecialchars($u['username']); ?>?')){window.location='./admin.php?id=users&delete-user=<?= $u['id']; ?>'}" class="text-danger"><i class="fa fa-trash"></i></a>
  </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div></div></div></div>

<div class="<?= $isSuperAdmin ? 'col-4' : 'col-5'; ?>">
<div class="card">
<div class="card-header"><h3 class="card-title"><i class="fa fa-user-plus"></i> 
  <?php if ($isSuperAdmin): ?>Add Admin / User<?php else: ?>Add User<?php endif; ?>
</h3></div>
<div class="card-body">
<form method="post" action="" autocomplete="off">
<table class="table">
  <tr><td>Username</td><td><input class="form-control" type="text" name="newuser" required></td></tr>
  <tr><td>Password</td><td><input class="form-control" type="password" name="newpass" required></td></tr>
  <?php if ($isSuperAdmin): ?>
  <tr><td>Type</td><td>
    <select class="form-control" name="newrole" id="newrole" onchange="toggleAdminFields()">
      <option value="user">User (pays monthly, own routers)</option>
      <option value="admin">Admin (pays monthly, can create users)</option>
    </select>
  </td></tr>
  <tr><td>Subscription</td><td>
    <select class="form-control" name="submonths">
      <option value="1">1 Month</option><option value="2">2 Months</option><option value="3">3 Months</option>
      <option value="6">6 Months</option><option value="12">12 Months</option>
    </select>
  </td></tr>
  <tr><td>Max Routers</td><td><input class="form-control" type="number" name="maxrouters" value="5" min="1" max="9999" id="maxrouters"></td></tr>
  <tr id="maxusersrow" style="display:none;"><td>Max Users</td><td><input class="form-control" type="number" name="maxusers" value="10" min="0" max="9999"></td></tr>
  <?php endif; ?>
  <tr><td></td><td><button type="submit" name="adduser" class="btn bg-primary"><i class="fa fa-plus"></i> Add</button></td></tr>
</table>
</form>
<?php if ($isSuperAdmin): ?>
<script>
function toggleAdminFields(){
  var r=document.getElementById('newrole').value;
  document.getElementById('maxusersrow').style.display=(r=='admin')?'table-row':'none';
  document.getElementById('maxrouters').value=(r=='admin')?'100':'5';
}
</script>
<?php endif; ?>
</div></div></div>

</div></div></div></div></div>
