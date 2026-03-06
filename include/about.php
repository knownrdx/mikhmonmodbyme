<?php
session_start();
error_reporting(0);
if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {
}
?>
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h3><i class="fa fa-info-circle"></i> About</h3>
      </div>
      <div class="card-body">
        <h3>MIKHMON V<?= $_SESSION['v']; ?></h3>
        <p>MikroTik Hotspot Monitor - Multi-User Edition</p>
      </div>
    </div>
  </div>
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h3><i class="fa fa-info-circle"></i> Changelog</h3>
      </div>
      <div class="card-body">
        <p>Multi-User Mod (2025) - SQLite database, multi-user registration, RouterOS 7.x date format fix, Coolify/Docker deployment.</p>
        <p>V3.20 (06-30-2021) - Fix typo script profile on-login.</p>
        <p>V3.19 (09-08-2020) - Voucher count in user list.</p>
        <p>V3.18 (08-16-2019) - Selling price added.</p>
      </div>
    </div>
  </div>
</div>
