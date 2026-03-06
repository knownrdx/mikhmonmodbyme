<?php
/*
 *  Mikhmon Multi-User Settings
 *  Based on Mikhmon V3 by Laksamadi Guko (GPL v2)
 */

error_reporting(0);

if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {

  // New router - create empty entry in database
  if ($id == "settings" && explode("-",$router)[0] == "new") {
    if (isset($_SESSION['user_id'])) {
      dbSaveRouter($_SESSION['user_id'], $router, array(
        'ip_host' => '',
        'user_host' => '',
        'passwd_host' => '',
        'hotspot_name' => '',
        'dns_name' => '',
        'currency' => 'Rp',
        'auto_reload' => 10,
        'iface' => '1',
        'info_lp' => '',
        'idle_timeout' => '10',
        'live_report' => 'disable',
      ));
    }
    echo "<script>window.location='./admin.php?id=settings&session=" . $router . "'</script>";
  }

  // Save router settings
  if (isset($_POST['save'])) {
    $siphost = preg_replace('/\s+/', '', $_POST['ipmik']);
    $suserhost = $_POST['usermik'];
    $spasswdhost = encrypt($_POST['passmik']);
    $shotspotname = str_replace("'", "", $_POST['hotspotname']);
    $sdnsname = $_POST['dnsname'];
    $scurrency = $_POST['currency'];
    $sreload = $_POST['areload'];
    if ($sreload < 10) { $sreload = 10; }
    $siface = $_POST['iface'];
    $sinfolp = implode(unpack("H*", $_POST['infolp']));
    $sidleto = $_POST['idleto'];
    $sesname = preg_replace('/\s+/', '-', $_POST['sessname']);
    $slivereport = $_POST['livereport'];

    if (isset($_SESSION['user_id'])) {
      dbSaveRouter($_SESSION['user_id'], $session, array(
        'new_session_name' => $sesname,
        'ip_host' => $siphost,
        'user_host' => $suserhost,
        'passwd_host' => $spasswdhost,
        'hotspot_name' => $shotspotname,
        'dns_name' => $sdnsname,
        'currency' => $scurrency,
        'auto_reload' => $sreload,
        'iface' => $siface,
        'info_lp' => $sinfolp,
        'idle_timeout' => $sidleto,
        'live_report' => $slivereport,
      ));
    }

    $_SESSION["connect"] = "";
    echo "<script>window.location='./admin.php?id=settings&session=" . $sesname . "'</script>";
  }

  if ($currency == "") {
    echo "<script>window.location='./admin.php?id=settings&session=" . $session . "'</script>";
  }
}
?>
<script>
  function PassMk(){
    var x = document.getElementById('passmk');
    if (x.type === 'password') {
    x.type = 'text';
    } else {
    x.type = 'password';
    }}
    function PassAdm(){
    var x = document.getElementById('passadm');
    if (x.type === 'password') {
    x.type = 'text';
    } else {
    x.type = 'password';
  }}
  
</script>

<form autocomplete="off" method="post" action="" name="settings">  
<div class="row">
  <div class="col-12">
      <div class="card" >
        <div class="card-header">
          <h3 class="card-title"><i class="fa fa-gear"></i> <?= $_session_settings ?> &nbsp; | &nbsp;&nbsp;<i onclick="location.reload();" class="fa fa-refresh pointer " title="Reload data"></i></h3>
        </div>
        <div class="card-body">
         <div class="row">
           <div class="col-6">
            <div class="col-12">
              <div class="card">
                <div class="card-header">
                  <h3 class="card-title"><?= $_session ?></h3>
                </div>
                <div class="card-body">
                  <table class="table">
                    <tr>
                      <td><?= $_session_name ?></td>
                      <td><input class="form-control" id="sessname" type="text" name="sessname" title="Session Name" value="<?php if (explode("-",$session)[0] == "new") {
                                                                                                                              echo "";
                                                                                                                            } else {
                                                                                                                              echo $session;
                                                                                                                            } ?>" required="1"/></td>
                    </tr>
                  </table>
                </div>
              </div>
            </div>
            <div class="col-12">
              <div class="card">
               <div class="card-header">
                 <h3 class="card-title">MikroTik <?= $_SESSION["connect"]; ?></h3>
               </div>
               <div class="card-body">
          <table class="table table-sm">
            <tr>
              <td class="align-middle">IP MikroTik </td><td><input class="form-control" type="text" size="15" name="ipmik" title="IP MikroTik / IP Cloud MikroTik" value="<?= $iphost; ?>" required="1"/></td>
            </tr>
            <tr>
              <td class="align-middle">Username  </td><td><input class="form-control" id="usermk" type="text" size="10" name="usermik" title="User MikroTik" value="<?= $userhost; ?>" required="1"/></td>
            </tr>
            <tr>
              <td class="align-middle">Password  </td><td>
                <div class="input-group">
                  <div class="input-group-11 col-box-10">
                  <input class="group-item group-item-l" id="passmk" type="password" name="passmik" title="Password MikroTik" value="<?= decrypt($passwdhost); ?>" required="1"/>
                  </div>
                    <div class="input-group-1 col-box-2">
                      <div class="group-item group-item-r pd-2p5 text-center align-middle">
                          <input title="Show/Hide Password" type="checkbox" onclick="PassMk()">
                      </div>
                    </div>
                </div>
              </td>
            </tr>
            <tr>
              <td colspan="2">
                  <div class="input-group-4">
                    <input class="group-item group-item-md" type="submit" style="cursor: pointer;" name="save" value="Save"/>
                  </div>
                  <div class="input-group-4"> 
                  <span class="connect pointer group-item group-item-md pd-2p5 text-center align-middle" id="<?= $session; ?>&c=settings">Connect</span>
                  </div>
                  <div class="input-group-3"> 
                  <span class="pointer group-item group-item-md pd-2p5 text-center align-middle" id="ping_test">Ping</span>
                </div>
                <div class="input-group-1"> 
                    <div style="cursor: pointer;" class="group-item group-item-r pd-2p5 text-center" onclick="location.reload();" title="Reload Data"><i class="fa fa-refresh"></i></div>
                  </div>
                </div> 
                </td>
              </tr>
          </table>
        </div>
    </div>   
    <div id="ping">
    </div> 
  </div>
</div>
<div class="col-6">
<div class="col-12">
  <div class="card">
        <div class="card-header">
            <h3 class="card-title">Mikhmon Data</h3>
        </div>
    <div class="card-body">    
  <table class="table table-sm">
  <tr>
  <td class="align-middle"><?= $_hotspot_name ?>  </td><td><input class="form-control" type="text" size="15" maxlength="50" name="hotspotname" title="Hotspot Name" value="<?= $hotspotname; ?>" required="1"/></td>
  </tr>
  <tr>
  <td class="align-middle"><?= $_dns_name ?>  </td><td><input class="form-control" type="text" size="15" maxlength="500" name="dnsname" title="DNS Name [IP->Hotspot->Server Profiles->DNS Name]" value="<?= $dnsname; ?>" required="1"/></td>
  </tr>
  <tr>
  <td class="align-middle"><?= $_currency ?>  </td><td><input class="form-control" type="text" size="3" maxlength="4" name="currency" title="currency" value="<?= $currency; ?>" required="1"/></td>
  </tr>
  <tr> 
  <td class="align-middle"><?= $_auto_reload ?></td><td>
  <div class="input-group">
    <div class="input-group-10">
        <input class="group-item group-item-l" type="number" min="10" max="3600" name="areload" title="Auto Reload in sec [min 10]" value="<?= $areload; ?>" required="1"/>
    </div>
            <div class="input-group-2">
                <span class="group-item group-item-r pd-2p5 text-center align-middle"><?= $_sec ?></span>
            </div>
        </div>
  </td>
  </tr>
  <tr>
  <td class="align-middle"><?= $_idle_timeout ?></td>
  <td>
  <div class="input-group">
  <div class="input-group-9">
      <select class="group-item group-item-l" name="idleto" required="1">
          <option value="<?= $idleto; ?>"><?= $idleto; ?></option>
            <option value="5">5</option>
          <option value="10">10</option>
          <option value="30">30</option>
          <option value="60">60</option>
          <option value="disable">disable</option>
      </select>
  </div>
  <div class="input-group-3">
                <span class="group-item group-item-r pd-3p5 text-center align-middle"><?= $_min ?></span>
            </div>
        </div>
    </td>
  </tr>
  <tr>
  <td class="align-middle"><?= $_traffic_interface ?></td><td><input class="form-control" type="number" min="1" max="99" name="iface" title="Traffic Interface" value="<?= $iface; ?>" required="1"/></td>
  </tr>
  <?php if (empty($livereport)) {
  } else { ?>
  <tr>
    <td><?= $_live_report ?></td>
    <td>
      <select class="form-control" name="livereport" >
          <option value="<?= $livereport; ?>"><?= ucfirst($livereport); ?></option>
            <option value="enable">Enable</option>
            <option value="disable">Disable</option>
        </select>
    </td>
  </tr>
  <?php 
} ?>
</table>
</div>
</div>
</div>
</div>
</div>
</form>
<script type="text/javascript">
var sesname=document.settings.sessname;
function chksname(){
  if(sesname.value=="mikhmon"||sesname.value=="MIKHMON"||sesname.value=="Mikhmon"){
    alert("You cannot use "+sesname.value+" as a session name.");
    sesname.value="";
    window.location.reload();
  }
}
sesname.onkeyup=chksname;
sesname.onchange=chksname;

var hname=window.location.hostname;
var dom=hname.split(".")[1]+"."+hname.split(".")[2];
function pingTest(s){
  $("#ping").load("./status/ping-test.php?ping&session="+s);
}
var sessX=document.getElementById("sessname").value;
document.getElementById("ping_test").onclick=function(){pingTest(sessX)};
function closeX(){$("#pingX").hide();}
</script>
