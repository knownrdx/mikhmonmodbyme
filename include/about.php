<?php
/*
 *  Copyright (C) 2018 Laksamadi Guko.
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
session_start();
// hide all error
error_reporting(0);
if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {
}
?>
<style>
.iFWrapper {
	position: relative;
	padding-bottom: 56.25%; /* 16:9 */
	padding-top: 25px;
	height: 0;
}
.iFWrapper iframe {
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
  height: 100%;
  border :none;
}
</style>
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h3><i class="fa fa-info-circle"></i> About</h3>
      </div>
      <div class="card-body">
        <h3>MIKHMON V<?= $_SESSION['v']; ?></h3>
<p>
  Aplikasi ini dipersembahkan untuk pengusaha hotspot di manapun Anda berada.
  Semoga makin sukses.
</p>
<p>
  <ul>
    <li>
      Author : Laksamadi Guko
    </li>
    <li>
      Licence : GPLv2
    </li>
    <li>
      API Class : routeros-api (BenMenking)
    </li>
  </ul>
</p>
<p>
  Terima kasih untuk semua yang telah mendukung pengembangan MIKHMON.
</p>
<div>
    <i>Copyright &copy; <i> 2018 Laksamadi Guko</i></i>
</div>
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
  <p>V3.20 (06-30-2021) - Perbaikan typo script profile on-login.</p>
  <p>V3.19 (09-08-2020) - Penambahan jumlah sisa voucher.</p>
  <p>V3.18 (08-16-2019) - Penambahan harga jual.</p>
  </div>
</div>
</div>
</div>
