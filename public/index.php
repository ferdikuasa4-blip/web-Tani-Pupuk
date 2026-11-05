<?php
session_start();
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../app/controllers/Controller.php';
$page=$_GET['page']??'home';
try{(new Controller())->dispatch($page);}catch(PDOException $e){http_response_code(500);echo '<h2>Kesalahan database</h2><p>Pastikan database tani_pupuk sudah diimport dan konfigurasi database benar.</p><pre>'.htmlspecialchars($e->getMessage()).'</pre>';}catch(Throwable $e){http_response_code(500);echo '<h2>Terjadi kesalahan</h2><pre>'.htmlspecialchars($e->getMessage()).'</pre>';}
