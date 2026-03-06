<?php
if (!isset($_SESSION["mikhmon"]) || $_SESSION["user_role"] != "admin") {
  header("Location:../admin.php?id=login");
  exit;
}

$allUsers = dbGetAllUsers();
?>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title"><i class="fa fa-users"></i> User Management</h3>
      </div>
      <div class="card-body">
        <div class="row">
          <!-- User List -->
          <div class="col-7">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title"><i class="fa fa-list"></i> All Users (<?= count($allUsers); ?>)</h3>
              </div>
              <div class="card-body">
                <div class="overflow box-bordered" style="max-height: 60vh">
                <table class="table table-bordered table-hover">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Username</th>
                      <th>Role</th>
                      <th>Created</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($allUsers as $i => $u): ?>
                    <tr>
                      <td><?= $i + 1; ?></td>
                      <td><i class="fa fa-user"></i> <?= htmlspecialchars($u['username']); ?></td>
                      <td>
                        <?php if ($u['role'] == 'admin'): ?>
                          <span class="text-green"><i class="fa fa-star"></i> Admin</span>
                        <?php else: ?>
                          <span class="text-blue"><i class="fa fa-user"></i> User</span>
                        <?php endif; ?>
                      </td>
                      <td><?= $u['created_at']; ?></td>
                      <td>
                        <?php if ($u['role'] != 'admin'): ?>
                          <a href="javascript:void(0)" onclick="if(confirm('Delete user <?= htmlspecialchars($u['username']); ?>?')){window.location='./admin.php?id=users&delete-user=<?= $u['id']; ?>'}" class="text-danger"><i class="fa fa-trash"></i> Delete</a>
                        <?php else: ?>
                          <span class="text-muted">-</span>
                        <?php endif; ?>
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
          <div class="col-5">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title"><i class="fa fa-user-plus"></i> Add New User</h3>
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
                    <tr>
                      <td>Role</td>
                      <td>
                        <select class="form-control" name="newrole">
                          <option value="user">User</option>
                          <option value="admin">Admin</option>
                        </select>
                      </td>
                    </tr>
                    <tr>
                      <td></td>
                      <td>
                        <button type="submit" name="adduser" class="btn bg-primary"><i class="fa fa-plus"></i> Add User</button>
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
