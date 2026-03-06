<?php
/*
 *  Mikhmon Multi-User Sessions Page
 *  Based on Mikhmon V3 by Laksamadi Guko (GPL v2)
 */

error_reporting(0);
if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {

  $color = array('1' => 'bg-blue', 'bg-indigo', 'bg-purple', 'bg-pink', 'bg-red', 'bg-yellow', 'bg-green', 'bg-teal', 'bg-cyan', 'bg-grey', 'bg-light-blue');

  // Change password
  if (isset($_POST['save'])) {
    $newpass = $_POST['passadm'];
    $qrbt = $_POST['qrbt'];
    
    if (!empty($newpass) && isset($_SESSION['user_id'])) {
      dbUpdatePassword($_SESSION['user_id'], $newpass);
    }
    
    $gen = '<?php $qrbt="' . $qrbt . '";?>';
    $key = './include/quickbt.php';
    $handle = fopen($key, 'w') or die('Cannot open file:  ' . $key);
    fwrite($handle, $gen);
    fclose($handle);
    
    echo "<script>window.location='./admin.php?id=sessions'</script>";
  }

  // Get routers for current user
  $userRouters = array();
  if (isset($_SESSION['user_id'])) {
    $userRouters = dbGetRouters($_SESSION['user_id']);
  }
}
?>
<script>
  function Pass(id){
    var x = document.getElementById(id);
    if (x.type === 'password') {
    x.type = 'text';
    } else {
    x.type = 'password';
    }}
</script>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title"><i class="fa fa-gear"></i> <?= $_admin_settings ?> &nbsp; | &nbsp;&nbsp;<i onclick="location.reload();" class="fa fa-refresh pointer " title="Reload data"></i>
        &nbsp; | &nbsp; <span class="text-blue"><i class="fa fa-user"></i> <?= htmlspecialchars($_SESSION['mikhmon']); ?></span>
        <?php if ($_SESSION['user_role'] == 'admin'): ?>
          <span class="text-green">(Admin)</span>
          &nbsp; | &nbsp; <a href="./admin.php?id=users"><i class="fa fa-users"></i> Manage Users</a>
        <?php endif; ?>
        </h3>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-6">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title"><i class="fa fa-server"></i> <?= $_router_list ?> (<?= count($userRouters); ?>)</h3>
              </div>
            <div class="card-body">
            <div class="row">
              <?php
              foreach ($userRouters as $r) {
                $value = $r['session_name'];
                $hname = $r['hotspot_name'];
              ?>
                    <div class="col-12">
                        <div class="box bmh-75 box-bordered <?= $color[rand(1, 11)]; ?>">
                                <div class="box-group">
                                  
                                  <div class="box-group-icon">
                                    <span class="connect pointer" id="<?= $value; ?>">
                                    <i class="fa fa-server"></i>
                                    </span>
                                  </div>
                                
                                  <div class="box-group-area">
                                    <span>
                                      <?= $_hotspot_name ?> : <?= htmlspecialchars($hname); ?><br>
                                      <?= $_session_name ?> : <?= htmlspecialchars($value); ?><br>
                                      <span class="connect pointer"  id="<?= $value; ?>"><i class="fa fa-external-link"></i> <?= $_open ?></span>&nbsp;
                                      <a href="./admin.php?id=settings&session=<?= $value; ?>"><i class="fa fa-edit"></i> <?= $_edit ?></a>&nbsp;
                                      <a href="javascript:void(0)" onclick="if(confirm('Are you sure to delete data <?= $value . " (" . htmlspecialchars($hname) . ")"; ?>?')){loadpage('./admin.php?id=remove-session&session=<?= $value; ?>')}else{}"><i class="fa fa-remove"></i> <?= $_delete ?></a>
                                    </span>

                                  </div>
                                </div>
                              
                            </div>
                          </div>
              <?php
              }
              ?>
              </div>
            </div>
          </div>
        </div>
            <div class="col-6">
          <form autocomplete="off" method="post" action="">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title"><i class="fa fa-user-circle"></i> <?= $_admin ?> - <?= htmlspecialchars($_SESSION['mikhmon']); ?></h3>
              </div>
            <div class="card-body">
      <table class="table table-sm">
        <tr>
          <td class="align-middle"><?= $_user_name ?> </td>
          <td><input class="form-control" type="text" value="<?= htmlspecialchars($_SESSION['mikhmon']); ?>" disabled></td>
        </tr>
        <tr>
          <td class="align-middle">New <?= $_password ?> </td>
          <td>
          <div class="input-group">
          <div class="input-group-11 col-box-10">
                <input class="group-item group-item-l" id="passadm" type="password" size="10" name="passadm" title="New Password" placeholder="Leave empty to keep current" value=""/>
              </div>
                <div class="input-group-1 col-box-2">
                  <div class="group-item group-item-r pd-2p5 text-center align-middle">
                      <input title="Show/Hide Password" type="checkbox" onclick="Pass('passadm')">
                  </div>
                </div>
            </div>
          </td>
        </tr>
        <tr>
          <td class="align-middle"><?= $_quick_print ?> QR</td>
          <td>
            <select class="form-control" name="qrbt">
            <option><?= $qrbt ?></option>
              <option>enable</option>
              <option>disable</option>
            </select>
          </td>
        </tr>
        <tr>
          <td></td><td class="text-right">
              <div class="input-group-4">
                  <input class="group-item group-item-l" type="submit" style="cursor: pointer;" name="save" value="<?= $_save ?>"/>
                </div>
                <div class="input-group-2">
                  <div style="cursor: pointer;" class="group-item group-item-r pd-2p5 text-center" onclick="location.reload();" title="Reload Data"><i class="fa fa-refresh"></i></div>
                </div>
                </div>
          </td>
        </tr>
        
      </table>
      <div id="loadV">v<?= $_SESSION['v']; ?> </div>
      <div><b id="newVer" class="text-green"></b></div>
    </div>
    </div>
    </form>
  </div>
</div>
</div>
</div>
</div>
</div>
