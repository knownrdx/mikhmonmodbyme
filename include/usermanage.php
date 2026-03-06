<?php
if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
  exit;
}

$isAdmin = ($_SESSION['user_role'] == 'admin');
$isUser = ($_SESSION['user_role'] == 'user');

if (!$isAdmin && !$isUser) {
  echo "<script>window.location='./admin.php?id=sessions'</script>";
  exit;
}

if ($isAdmin) {
    $listUsers = dbGetAllUsers();
} else {
    $listUsers = dbGetSubUsers($_SESSION['user_id']);
}
?>

<div class="row">
<div class="col-12">
<div class="card">
<div class="card-header">
  <h3 class="card-title"><i class="fa fa-users"></i> 
  <?php if ($isAdmin): ?>User Management<?php else: ?>My Users<?php endif; ?>
  <?php if (isset($_SESSION['logged_in_as'])): ?>
    &nbsp; | &nbsp; <a href="./admin.php?id=switch-back" class="text-green"><i class="fa fa-arrow-left"></i> Switch Back</a>
  <?php endif; ?>
  </h3>
</div>
<div class="card-body">
<div class="row">

<!-- User List -->
<div class="col-8">
<div class="card">
<div class="card-header">
  <h3 class="card-title"><i class="fa fa-list"></i> 
  <?php if ($isAdmin): ?>All Users<?php else: ?>My Users<?php endif; ?>
  (<?= count($listUsers); ?>)</h3>
</div>
<div class="card-body">
<div class="overflow box-bordered" style="max-height: 70vh">
<table class="table table-bordered table-hover text-nowrap">
<thead>
<tr>
  <th>#</th>
  <th>Username</th>
  <th>Role</th>
  <?php if ($isAdmin): ?>
  <th>Subscription</th>
  <th>Days Left</th>
  <th>Routers</th>
  <th>Extend Sub</th>
  <th>Set Routers</th>
  <?php endif; ?>
  <th>Action</th>
</tr>
</thead>
<tbody>
<?php foreach ($listUsers as $i => $u): ?>
<tr>
  <td><?= $i + 1; ?></td>
  <td><i class="fa fa-user"></i> <?= htmlspecialchars($u['username']); ?></td>
  <td>
    <?php if ($u['role'] == 'admin'): ?>
      <span class="text-green"><i class="fa fa-star"></i> Admin</span>
    <?php else: ?>
      <span class="text-blue"><i class="fa fa-user"></i> Admin</span>
    <?php endif; ?>
  </td>

  <?php if ($isAdmin): ?>
  <!-- Subscription column (admin view only) -->
  <td>
    <?php if ($u['role'] == 'admin'): ?>
      <span class="text-green">Unlimited</span>
    <?php elseif (empty($u['sub_expires'])): ?>
      <span class="text-red">Not Set</span>
    <?php elseif (dbIsSubActive($u)): ?>
      <span class="text-green"><?= $u['sub_expires']; ?></span>
    <?php else: ?>
      <span class="text-red"><?= $u['sub_expires']; ?> (Exp)</span>
    <?php endif; ?>
  </td>
  <td>
    <?php if ($u['role'] == 'admin'): ?>
      <span class="text-green">&infin;</span>
    <?php else:
      $dl = dbGetSubDaysLeft($u);
      if ($dl > 7): ?><span class="text-green"><?= $dl; ?>d</span>
      <?php elseif ($dl > 0): ?><span class="text-orange"><?= $dl; ?>d</span>
      <?php else: ?><span class="text-red">Expired</span>
    <?php endif; endif; ?>
  </td>
  <!-- Router limit -->
  <td>
    <?php if ($u['role'] == 'admin'): ?>
      &infin;
    <?php else: ?>
      <?= dbGetRouterCount($u['id']); ?>/<?= $u['max_routers']; ?>
    <?php endif; ?>
  </td>
  <!-- Extend subscription -->
  <td>
    <?php if ($u['role'] != 'admin'): ?>
    <form method="post" action="" style="display:inline;">
      <input type="hidden" name="sub_user_id" value="<?= $u['id']; ?>">
      <select name="submonths" style="width:55px;" class="form-control" style="display:inline;">
        <option value="1">1m</option>
        <option value="2">2m</option>
        <option value="3">3m</option>
        <option value="6">6m</option>
        <option value="12">12m</option>
      </select>
      <button type="submit" name="updatesub" class="btn bg-green" style="padding:2px 6px;" title="Extend"><i class="fa fa-plus"></i></button>
    </form>
    <?php else: ?>-<?php endif; ?>
  </td>
  <!-- Set max routers -->
  <td>
    <?php if ($u['role'] != 'admin'): ?>
    <form method="post" action="" style="display:inline;">
      <input type="hidden" name="router_user_id" value="<?= $u['id']; ?>">
      <input type="number" name="newmaxrouters" value="<?= $u['max_routers']; ?>" min="1" max="999" style="width:55px;" class="form-control" style="display:inline;">
      <button type="submit" name="updaterouters" class="btn bg-blue" style="padding:2px 6px;" title="Set"><i class="fa fa-save"></i></button>
    </form>
    <?php else: ?>-<?php endif; ?>
  </td>
  <?php endif; // end isAdmin columns ?>

  <!-- Actions -->
  <td>
    <?php if ($u['role'] != 'admin'): ?>
      <?php if ($isAdmin || $isUser): ?>
      <a href="./admin.php?id=users&login-as-user=<?= $u['id']; ?>" class="text-blue" title="Login as this user"><i class="fa fa-sign-in"></i></a>
      &nbsp;
      <?php endif; ?>
      <a href="javascript:void(0)" onclick="if(confirm('Delete <?= htmlspecialchars($u['username']); ?>?')){window.location='./admin.php?id=users&delete-user=<?= $u['id']; ?>'}" class="text-danger" title="Delete"><i class="fa fa-trash"></i></a>
    <?php else: ?>-<?php endif; ?>
  </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</div>
</div>

<!-- Add User Form -->
<div class="col-4">
<div class="card">
<div class="card-header">
  <h3 class="card-title"><i class="fa fa-user-plus"></i> 
  <?php if ($isAdmin): ?>Add Admin<?php else: ?>Add User<?php endif; ?>
  </h3>
</div>
<div class="card-body">
<form method="post" action="" autocomplete="off">
<table class="table">
  <tr>
    <td>Username</td>
    <td><input class="form-control" type="text" name="newuser" required placeholder="min 3 chars"></td>
  </tr>
  <tr>
    <td>Password</td>
    <td><input class="form-control" type="password" name="newpass" required placeholder="min 4 chars"></td>
  </tr>
  <?php if ($isAdmin): ?>
  <tr>
    <td>Subscription</td>
    <td>
      <select class="form-control" name="submonths">
        <option value="1">1 Month</option>
        <option value="2">2 Months</option>
        <option value="3">3 Months</option>
        <option value="6">6 Months</option>
        <option value="12">12 Months</option>
      </select>
    </td>
  </tr>
  <tr>
    <td>Max Routers</td>
    <td><input class="form-control" type="number" name="maxrouters" value="4" min="1" max="999"></td>
  </tr>
  <?php endif; ?>
  <tr>
    <td></td>
    <td>
      <button type="submit" name="adduser" class="btn bg-primary"><i class="fa fa-plus"></i> 
      <?php if ($isAdmin): ?>Add Admin<?php else: ?>Add User<?php endif; ?>
      </button>
    </td>
  </tr>
</table>
</form>
</div>
</div>
</div>

</div>
</div>
</div>
</div>
</div>
