<?php
require_once __DIR__.'/../models/Repository.php';
class Controller {
 private Repository $r;
 function __construct(){ $this->r=new Repository(); }
 private function esc($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
 private function go($page,$params=[]){header('Location: index.php?'.http_build_query(array_merge(['page'=>$page],$params)));exit;}
 private function flash($msg,$type='success'){$_SESSION['flash']=['msg'=>$msg,'type'=>$type];}
 private function csrf(){if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(24));return $_SESSION['csrf'];}
 private function check(){if(!hash_equals($_SESSION['csrf']??'',$_POST['csrf']??'')){http_response_code(419);exit('Token keamanan tidak valid.');}}
 private function need($roles=[]){if(empty($_SESSION['user']))$this->go('login');if($roles&&!in_array($_SESSION['user']['role'],$roles,true)){http_response_code(403);exit('Akses ditolak.');}}
 private function render($title,$content,$public=false){
  $flash=$_SESSION['flash']??null;unset($_SESSION['flash']);
  $u=$_SESSION['user']??null;$role=$u['role']??'';
  $landing=match($role){'Admin'=>'dashboard','Petani'=>'farmer_dashboard','Petugas'=>'petugas_dashboard',default=>'home'};
  $menus=match($role){'Admin'=>[['dashboard','Dashboard'],['admin_petani','Data Petani'],['admin_pupuk','Data Pupuk'],['admin_kategori','Kategori Pupuk'],['admin_stok','Manajemen Stok'],['admin_pesanan','Transaksi'],['admin_laporan','Laporan'],['admin_users','Akun Pengguna'],['pengaturan','Pengaturan']], 'Petani'=>[['farmer_dashboard','Dashboard'],['profil','Profil'],['katalog','Katalog Pupuk'],['pesanan_saya','Pesanan Saya'],['pembayaran','Pembayaran'],['riwayat','Riwayat Transaksi']], 'Petugas'=>[['petugas_dashboard','Dashboard'],['scan_qr','Scan QR Transaksi'],['validasi','Validasi Transaksi']],default=>[]};
  $currentPage=$_GET['page']??'';
  require __DIR__.'/../views/partials/head.php';
  if(!$public && $u){require __DIR__.'/../views/partials/topbar.php';require __DIR__.'/../views/partials/sidebar.php';echo '<main class="main">';}
  else {require __DIR__.'/../views/partials/public_nav.php';echo '<main class="public-main">';}
  if($flash)echo '<div class="alert alert-'.$this->esc($flash['type']).'">'.$this->esc($flash['msg']).'</div>';
  echo $content;
  require __DIR__.'/../views/partials/footer.php';
 }
 private function table($headers,$rows,$actions=''){ $h='<div class="table-responsive"><table class="table align-middle"><thead><tr>';foreach($headers as $v)$h.='<th>'.$v.'</th>';$h.=$actions?'<th>Aksi</th>':'';$h.='</tr></thead><tbody>'.$rows.'</tbody></table></div>';return $h; }
 private function pager($data,$page){$s='<nav><ul class="pagination pagination-sm">';for($i=1;$i<=$data['pages'];$i++)$s.='<li class="page-item '.($i==$data['page']?'active':'').'"><a class="page-link" href="index.php?page='.$page.'&p='.$i.'&q='.urlencode($_GET['q']??'').'">'.$i.'</a></li>';$s.='</ul></nav>';return $s;}
 private function search($page){return '<form class="d-flex gap-2 mb-3" method="get"><input type="hidden" name="page" value="'.$page.'"><input class="form-control" name="q" value="'.$this->esc($_GET['q']??'').'" placeholder="Cari data..."><button class="btn btn-outline-success">Cari</button><a class="btn btn-light" href="index.php?page='.$page.'">Reset</a></form>';}
 private function form($page,$fields,$id=0){$html='<div class="panel form-panel"><form method="post" action="index.php?page='.$page.'_save"><input type="hidden" name="csrf" value="'.$this->csrf().'"><input type="hidden" name="id" value="'.$id.'">';foreach($fields as $f){$html.='<div class="mb-3"><label class="form-label">'.$f[1].'</label>';if($f[0]==='textarea')$html.='<textarea class="form-control" name="'.$f[2].'" required>'.$this->esc($f[3]??'').'</textarea>';elseif($f[0]==='select'){$html.='<select class="form-select" name="'.$f[2].'">';foreach($f[4] as $k=>$v)$html.='<option value="'.$k.'" '.(($f[3]??'')==$k?'selected':'').'>'.$this->esc($v).'</option>';$html.='</select>';}else $html.='<input class="form-control" type="'.$f[0].'" name="'.$f[2].'" value="'.$this->esc($f[3]??'').'" required>'; $html.='</div>';}$html.='<button class="btn btn-success">Simpan</button> <a class="btn btn-light" href="javascript:history.back()">Batal</a></form></div>';return $html;}
 private function productCards($limit=0){
  $items=$this->r->rows('SELECT p.*,k.nama_kategori FROM pupuk p JOIN kategori_pupuk k USING(id_kategori) ORDER BY p.id_pupuk'.($limit?' LIMIT '.(int)$limit:''));
  $html='<div class="row g-3 product-grid">';
  foreach($items as $p){
   $key=strtolower($p['nama_pupuk']);
   $img=str_contains($key,'urea')?'UREA.jpeg':(str_contains($key,'npk')?'NPK.jpeg':(str_contains($key,'za')?'ZA.jpeg':'ORGANIK.jpeg'));
   $html.='<div class="col-6 col-md-3"><div class="product-card"><img src="assets/png/'.$img.'" alt="Karung '.$this->esc($p['nama_pupuk']).'"><h6>'.$this->esc($p['nama_pupuk']).'</h6><small>Rp '.number_format($p['harga'],0,',','.').' / sak</small><small>Stok: '.$p['stok'].' '.$this->esc($p['satuan']).'</small><a class="btn btn-success btn-sm" href="index.php?page=katalog">Pilih</a></div></div>';
  }
  $html.='</div>';
  return $html;
 }
 
 function dispatch($page){
  $r=$this->r;
  if($page==='logout'){session_destroy();$this->go('');}
  if($page==='login' && $_SERVER['REQUEST_METHOD']==='POST'){
      $this->check();
      $u=$r->row('SELECT * FROM users WHERE username=?',[trim($_POST['username']??'')]);
      $p=$_POST['password']??'';
      if($u && (password_verify($p, $u['password']) || hash_equals((string)$u['password'], (string)$p))){
          if(!password_get_info($u['password'])['algo']){ $r->run('UPDATE users SET password=? WHERE id_user=?',[password_hash($p,PASSWORD_DEFAULT),$u['id_user']]); }
          session_regenerate_id(true);
          $_SESSION['user']=['id'=>$u['id_user'],'nama'=>$u['nama_lengkap'],'role'=>$u['role'],'petani_id'=>$u['id_petani']];
          $this->go(match($u['role']){'Admin'=>'dashboard','Petani'=>'farmer_dashboard','Petugas'=>'petugas_dashboard',default=>'login'});
      } 
      $this->flash('Username atau password salah.','danger');
      $this->go('login');
  }
  if($page==='register'&&$_SERVER['REQUEST_METHOD']==='POST'){
      $this->check();
      try{
          $id=$r->run('INSERT INTO petani(nik,nama_petani,alamat,no_hp,status) VALUES(?,?,?,?,?)',[$_POST['nik'],$_POST['nama'],$_POST['alamat'],$_POST['hp'],'Menunggu']);
          $r->run('INSERT INTO users(nama_lengkap,username,password,role,id_petani) VALUES(?,?,?,"Petani",?)',[$_POST['nama'],$_POST['nik'],password_hash($_POST['password'],PASSWORD_DEFAULT),$id]);
          $this->flash('Registrasi berhasil. Silakan login setelah verifikasi admin.');
      }catch(Throwable $e){
          $this->flash('Registrasi gagal. Periksa NIK dan data yang dimasukkan.','danger');
      }
      $this->go('login');
  }
  
  if(in_array($page,['','home','informasi','detail_pupuk','login','register'],true)){
   if($page==='login'){
        $c='<div class="auth-wrap"><div class="login-card"><h3>🌱 Masuk ke Akun Anda</h3><form method="post"><input type="hidden" name="csrf" value="'.$this->csrf().'"><label>Username / NIK</label><input class="form-control mb-3" name="username" required><label>Password</label><input class="form-control mb-3" type="password" name="password" required><button class="btn btn-success w-100">Login</button></form><p class="mt-3 text-center">Belum punya akun? <a href="index.php?page=register">Daftar di sini</a></p></div></div>';
   }elseif($page==='register'){
        $c='<div class="auth-wrap"><div class="login-card"><h3>Daftar Akun Petani</h3><form method="post"><input type="hidden" name="csrf" value="'.$this->csrf().'"><label>Nama lengkap</label><input class="form-control mb-2" name="nama" required><label>NIK</label><input class="form-control mb-2" name="nik" required><label>Nomor HP</label><input class="form-control mb-2" name="hp"><label>Alamat</label><textarea class="form-control mb-2" name="alamat" required></textarea><label>Password</label><input class="form-control mb-3" type="password" name="password" minlength="6" required><button class="btn btn-success w-100">Daftar</button></form></div></div>';
   }elseif($page==='informasi'){
        $items=$r->rows('SELECT p.*,k.nama_kategori FROM pupuk p JOIN kategori_pupuk k USING(id_kategori)');
        $c='<div class="page-heading"><div><h2>Informasi Pupuk</h2><p>Kenali jenis pupuk dan manfaatnya untuk pertanian Anda.</p></div></div><div class="row g-3 product-grid">';
        foreach($items as $p){
            $key=strtolower($p['nama_pupuk']);
            $img=str_contains($key,'urea')?'UREA.jpeg':(str_contains($key,'npk')?'NPK.jpeg':(str_contains($key,'za')?'ZA.jpeg':'ORGANIK.jpeg'));
            $c.='<div class="col-6 col-md-3"><div class="product-card"><img src="assets/png/'.$img.'" alt="Karung '.$this->esc($p['nama_pupuk']).'"><h6>'.$this->esc($p['nama_pupuk']).'</h6><small>'.$this->esc($p['nama_kategori']).'</small><strong>Rp '.number_format($p['harga'],0,',','.').' / sak</strong><small>Stok: '.$p['stok'].' '.$this->esc($p['satuan']).'</small><a class="btn btn-success btn-sm mt-2" href="index.php?page=detail_pupuk&id='.$p['id_pupuk'].'">Lihat Detail</a></div></div>';
        }
        $c.='</div>';
   }elseif($page==='detail_pupuk'){
        $id=(int)($_GET['id']??0);
        $p=$r->row('SELECT p.*,k.nama_kategori FROM pupuk p JOIN kategori_pupuk k USING(id_kategori) WHERE p.id_pupuk=?',[$id]);
        if(!$p){
            $c='<div class="container mt-4"><h2>Data Pupuk Tidak Ditemukan</h2><a class="btn btn-success mt-3" href="index.php?page=informasi">← Kembali</a></div>';
        } else {
            $key=strtolower($p['nama_pupuk']);
            $img=str_contains($key,'urea')?'UREA.jpeg':(str_contains($key,'npk')?'NPK.jpeg':(str_contains($key,'za')?'ZA.jpeg':'ORGANIK.jpeg'));
            
            if(str_contains($key, 'urea')) {
                $kandungan = 'Nitrogen (N) 46%';
                $manfaat = 'Memacu pertumbuhan vegetatif daun dan anakan padi agar lebih hijau pekat dan subur.';
                $dosis = '150 – 200 kg / Ha pada fase awal tanam';
            } elseif(str_contains($key, 'npk')) {
                $kandungan = 'N 15%, P 15%, K 15%';
                $manfaat = 'Formula lengkap seimbang untuk merangsang akar kokoh, pembungaan lebat, dan bulir padat.';
                $dosis = '250 – 300 kg / Ha';
            } elseif(str_contains($key, 'za')) {
                $kandungan = 'Nitrogen 21%, Sulfur 24%';
                $manfaat = 'Kandungan belerang tinggi meningkatkan aroma, rasa gurih, dan daya simpan panen bawang dan cabai.';
                $dosis = '100 – 150 kg / Ha';
            } else {
                $kandungan = 'C-Organik > 15%';
                $manfaat = 'Menggemburkan struktur tanah yang padat, memelihara mikroba alami tanah yang menguntungkan.';
                $dosis = '500 – 1000 kg / Ha';
            }

            $c='<div class="container mt-5" style="max-width:650px;"><div class="panel shadow-sm bg-white p-4 rounded-4 position-relative"><a href="index.php?page=informasi" class="btn-close position-absolute top-0 end-0 m-4" aria-label="Close"></a><h3 class="fw-bold mb-4">'.$this->esc($p['nama_pupuk']).'</h3><div class="row align-items-center mb-4"><div class="col-md-4 text-center"><img src="assets/png/'.$img.'" alt="'.$this->esc($p['nama_pupuk']).'" style="max-width:140px;" class="img-fluid"></div><div class="col-md-8"><p class="mb-1">Harga: <strong class="text-success fs-5">Rp '.number_format($p['harga'],0,',','.').' / sak</strong></p><p class="mb-1">Stok: <strong>'.$p['stok'].' sak</strong></p><p class="mb-1">Kemasan: <strong>50 Kg</strong></p><p class="mb-1">Kandungan: <strong>'.$kandungan.'</strong></p></div></div><div class="p-3 bg-light rounded-3 mb-4"><p class="mb-1 fw-bold">Manfaat Utama:</p><p class="text-secondary small mb-2">'.$manfaat.'</p><p class="mb-1 fw-bold">Anjuran Dosis:</p><p class="text-secondary small mb-0">'.$dosis.'</p></div><div class="text-end"><a href="index.php?page=informasi" class="btn btn-secondary px-4">Tutup</a></div></div></div>';
        }
   } else {
        $c='<section class="hero"><div><h1>Mudah, Cepat dan Transparan</h1><p>Pemesanan dan penebusan pupuk pertanian kini bisa dilakukan secara online.</p><a class="btn btn-success" href="index.php?page=register">Mulai Sekarang →</a></div></section><div class="row g-3 mt-3"><div class="col-md-3"><div class="panel text-center">🌱<br><b>Pemesanan Online</b></div></div><div class="col-md-3"><div class="panel text-center">🛡️<br><b>Verifikasi Data</b></div></div><div class="col-md-3"><div class="panel text-center">💳<br><b>Pembayaran Aman</b></div></div><div class="col-md-3"><div class="panel text-center">🚜<br><b>Ambil di Lokasi</b></div></div></div>';
   }
   $this->render(ucfirst(str_replace('_',' ',$page?:'Beranda')),$c,true);
   return;
 }
 
 $role=$_SESSION['user']['role']??null;
 $admin=['dashboard','admin_petani','admin_petani_form','admin_petani_save','admin_petani_delete','admin_pupuk','admin_pupuk_form','admin_pupuk_save','admin_pupuk_delete','admin_kategori','admin_kategori_form','admin_kategori_save','admin_kategori_delete','admin_stok','admin_pesanan','admin_laporan','admin_laporan_print','admin_users','admin_users_form','admin_users_save','admin_users_delete','pengaturan','pengaturan_save'];
 $farmer=['farmer_dashboard','profil','profil_save','katalog','buat_pesanan','pesanan_saya','pembayaran','bayar','riwayat'];
 $staff=['petugas_dashboard','scan_qr','validasi','konfirmasi_ambil'];
 $this->need(in_array($page,$admin)?['Admin']:(in_array($page,$farmer)?['Petani']:(in_array($page,$staff)?['Petugas']:[])));
 if($page==='dashboard' && $role!=='Admin')$this->go(match($role){'Petani'=>'farmer_dashboard','Petugas'=>'petugas_dashboard',default=>'login'});
 if($page==='farmer_dashboard' && $role!=='Petani')$this->go(match($role){'Admin'=>'dashboard','Petugas'=>'petugas_dashboard',default=>'login'});
 if($page==='petugas_dashboard' && $role!=='Petugas')$this->go(match($role){'Admin'=>'dashboard','Petani'=>'farmer_dashboard',default=>'login'});
 
 $c='';
 if($page==='dashboard'){
   $c='<div class="page-heading"><div><h2>Dashboard Admin</h2><p>Kelola data dan pantau transaksi pupuk.</p></div><div class="user-chip">👤 '.$this->esc($_SESSION['user']['nama']).'<small>Administrator</small></div></div><div class="row g-3 stat-row">';
   $stats=[['♟','Total Petani',$r->count('petani'),'#e8f1ff'],['▣','Pemesanan',$r->count('pemesanan'),'#fff4df'],['▤','Total Sak',(int)($r->row('SELECT COALESCE(SUM(jumlah),0) n FROM pemesanan')['n']??0),'#e5f7ed'],['▧','Nilai Transaksi','Rp '.number_format((float)($r->row('SELECT COALESCE(SUM(total_harga),0) n FROM pemesanan')['n']??0),0,',','.'),'#fff0e6']];
   foreach($stats as $s)$c.='<div class="col-md-3"><div class="stat-card"><span class="stat-icon" style="background:'.$s[3].'">'.$s[0].'</span><div class="stat-label">'.$s[1].'</div><strong>'.$s[2].'</strong></div></div>';
   $recent=$r->rows('SELECT o.kode_pemesanan,p.nama_petani,o.tanggal_pemesanan,o.total_harga,o.status_pemesanan FROM pemesanan o JOIN petani p USING(id_petani) ORDER BY o.id_pemesanan DESC LIMIT 5');$rr='';foreach($recent as $x)$rr.='<tr><td>'.$this->esc($x['kode_pemesanan']).'</td><td>'.$this->esc($x['nama_petani']).'</td><td>'.date('d-m-Y',strtotime($x['tanggal_pemesanan'])).'</td><td>Rp '.number_format($x['total_harga'],0,',','.').'</td><td><span class="status-pill">'.$this->esc($x['status_pemesanan']).'</span></td></tr>';
   $c.='</div><div class="panel mt-4"><div class="panel-heading"><h5>Pemesanan Terbaru</h5><a href="index.php?page=admin_pesanan">Lihat semua</a></div>'.$this->table(['Kode','Nama Petani','Tanggal','Total','Status'],$rr).'</div>';
 }
 elseif($page==='farmer_dashboard'){
   $pid=(int)$_SESSION['user']['petani_id'];$n=$r->row('SELECT COUNT(*) jumlah,COALESCE(SUM(total_harga),0) total FROM pemesanan WHERE id_petani=?',[$pid]);
   $c='<div class="farmer-welcome"><div><small>Selamat datang,</small><h2>'.$this->esc($_SESSION['user']['nama']).'!</h2><p>Data Anda sedang siap digunakan untuk pemesanan pupuk.</p><a class="btn btn-light btn-sm" href="index.php?page=katalog">Mulai Pesan Pupuk</a></div><img src="assets/png/hero-pertanian.jpg" alt="Petani dan lahan pertanian"></div><div class="row g-3 stat-row mt-1"><div class="col-md-4"><div class="stat-card"><span class="stat-icon">▣</span><div class="stat-label">Pesanan Aktif</div><strong>'.$n['jumlah'].' Pesanan</strong></div></div><div class="col-md-4"><div class="stat-card"><span class="stat-icon">▤</span><div class="stat-label">Total Transaksi</div><strong>Rp '.number_format($n['total'],0,',','.').'</strong></div></div><div class="col-md-4"><div class="stat-card"><span class="stat-icon">✓</span><div class="stat-label">Status Data</div><strong>Terverifikasi</strong></div></div></div><div class="section-title"><h5>Pupuk Tersedia</h5><a href="index.php?page=katalog">Lihat Semua →</a></div>'.$this->productCards(4);
 }
 elseif($page==='petugas_dashboard'){
   $pending=$r->row('SELECT COUNT(*) jumlah FROM pemesanan WHERE status_pemesanan="Menunggu Pengambilan"');$done=$r->row('SELECT COUNT(*) jumlah FROM pemesanan WHERE status_pemesanan="Sudah Diambil"');
   $c='<h2>Dashboard Petugas Kios</h2><p class="text-secondary">Kelola pemeriksaan bukti transaksi dan pengambilan pupuk.</p><div class="row g-3"><div class="col-md-4"><div class="panel">Menunggu Pengambilan<h2>'.$pending['jumlah'].'</h2></div></div><div class="col-md-4"><div class="panel">Sudah Diambil<h2>'.$done['jumlah'].'</h2></div></div></div><div class="panel mt-4"><h5>Pemeriksaan Transaksi</h5><a class="btn btn-success" href="index.php?page=scan_qr">Scan QR Transaksi</a></div>';
 }
 elseif($page==='admin_petani'){
   $d=$r->petaniList($_GET['q']??'',(int)($_GET['p']??1));$rows='';foreach($d['rows'] as $x)$rows.='<tr><td>'.$x['nama_petani'].'</td><td>'.$x['nik'].'</td><td>'.$x['no_hp'].'</td><td>'.$x['status'].'</td><td><a class="btn btn-sm btn-outline-primary" href="index.php?page=admin_petani_form&id='.$x['id_petani'].'">Ubah</a> <form class="d-inline" method="post" action="index.php?page=admin_petani_status"><input type="hidden" name="csrf" value="'.$this->csrf().'"><input type="hidden" name="id" value="'.$x['id_petani'].'"><input type="hidden" name="status" value="'.($x['status']==='Aktif'?'Ditolak':'Aktif').'"><button class="btn btn-sm btn-outline-success">'.($x['status']==='Aktif'?'Nonaktifkan':'Verifikasi').'</button></form> <form class="d-inline" method="post" action="index.php?page=admin_petani_delete" onsubmit="return confirm(\'Hapus data?\')"><input type="hidden" name="csrf" value="'.$this->csrf().'"><input type="hidden" name="id" value="'.$x['id_petani'].'"><button class="btn btn-sm btn-outline-danger">Hapus</button></form></td></tr>';
   $c='<h2>Data Petani</h2><a class="btn btn-success mb-3" href="index.php?page=admin_petani_form">+ Tambah Data</a>'.$this->search('admin_petani').$this->table(['Nama','NIK','No. HP','Status'],$rows,'aksi').$this->pager($d,'admin_petani');
 }
 elseif($page==='admin_petani_form'){
   $x=isset($_GET['id'])?$r->row('SELECT * FROM petani WHERE id_petani=?',[(int)$_GET['id']]):[];
   $c='<h2>Form Data Petani</h2>'.$this->form('admin_petani',[
    ['text','NIK','nik',$x['nik']??''],
    ['text','Nama Petani','nama_petani',$x['nama_petani']??''],
    ['text','Alamat','alamat',$x['alamat']??''],
    ['text','Nomor HP','no_hp',$x['no_hp']??''],
    ['select','Status Verifikasi','status',$x['status']??'Menunggu',['Menunggu'=>'Menunggu','Aktif'=>'Aktif','Ditolak'=>'Ditolak']]
   ],(int)($_GET['id']??0));
 }
 elseif($page==='admin_petani_save'){ 
   $this->check();$id=(int)$_POST['id'];
   $args=[$_POST['nik'],$_POST['nama_petani']??$_POST['nik'],$_POST['alamat']??'-',$_POST['no_hp']??'',$_POST['status']??'Menunggu'];
   if($id){$args[]=$id;$r->run('UPDATE petani SET nik=?,nama_petani=?,alamat=?,no_hp=?,status=? WHERE id_petani=?',$args);}
   else{$r->run('INSERT INTO petani(nik,nama_petani,alamat,no_hp,status) VALUES(?,?,?,?,?)',$args);}
   $this->go('admin_petani');
 }
 elseif($page==='admin_petani_status'){
   $this->check();$status=$_POST['status']==='Aktif'?'Aktif':'Ditolak';
   $r->run('UPDATE petani SET status=? WHERE id_petani=?',[$status,(int)$_POST['id']]);
   $this->flash('Status verifikasi petani diperbarui.');$this->go('admin_petani');
 }
 elseif($page==='admin_petani_delete'){
   $this->check();
   try{$r->run('DELETE FROM petani WHERE id_petani=?',[(int)$_POST['id']]);$this->flash('Data petani dihapus.');}
   catch(Throwable $e){$this->flash('Petani masih terkait transaksi sehingga tidak dapat dihapus.','warning');}
   $this->go('admin_petani');
 }
 elseif($page==='admin_pupuk'){
   $d=$r->pupukList($_GET['q']??'',(int)($_GET['p']??1));$rows='';foreach($d['rows'] as $x)$rows.='<tr><td>'.$x['nama_pupuk'].'</td><td>'.$x['nama_kategori'].'</td><td>Rp '.number_format($x['harga'],0,',','.').'</td><td>'.$x['stok'].' '.$x['satuan'].'</td><td><a class="btn btn-sm btn-outline-primary" href="index.php?page=admin_pupuk_form&id='.$x['id_pupuk'].'">Ubah</a> <form class="d-inline" method="post" action="index.php?page=admin_pupuk_delete" onsubmit="return confirm(\'Hapus pupuk?\')"><input type="hidden" name="csrf" value="'.$this->csrf().'"><input type="hidden" name="id" value="'.$x['id_pupuk'].'"><button class="btn btn-sm btn-outline-danger">Hapus</button></form></td></tr>';
   $c='<h2>Data Pupuk</h2><a class="btn btn-success mb-3" href="index.php?page=admin_pupuk_form">+ Tambah Pupuk</a>'.$this->search('admin_pupuk').$this->table(['Jenis Pupuk','Kategori','Harga','Stok'],$rows,'aksi').$this->pager($d,'admin_pupuk');
 }
 elseif($page==='admin_pupuk_form'){
   $x=isset($_GET['id'])?$r->row('SELECT * FROM pupuk WHERE id_pupuk=?',[(int)$_GET['id']]):[];
   $cats=$r->rows('SELECT * FROM kategori_pupuk');$opts=[];foreach($cats as $cat)$opts[$cat['id_kategori']]=$cat['nama_kategori'];
   $c='<h2>Form Pupuk</h2>'.$this->form('admin_pupuk',[['select','Kategori','id_kategori',$x['id_kategori']??'', $opts],['text','Nama Pupuk','nama_pupuk',$x['nama_pupuk']??''],['number','Harga','harga',$x['harga']??0],['number','Stok','stok',$x['stok']??0]],(int)($_GET['id']??0));
 }
 elseif($page==='admin_pupuk_save'){
   $this->check();$id=(int)$_POST['id'];$a=[$_POST['id_kategori'],$_POST['nama_pupuk'],$_POST['harga'],$_POST['stok']];
   if($id){$a[]=$id;$r->run('UPDATE pupuk SET id_kategori=?,nama_pupuk=?,harga=?,stok=? WHERE id_pupuk=?',$a);}
   else{$r->run('INSERT INTO pupuk(id_kategori,nama_pupuk,harga,stok) VALUES(?,?,?,?)',$a);}
   $this->go('admin_pupuk');
 }
 elseif($page==='admin_pupuk_delete'){
   $this->check();
   try{$r->run('DELETE FROM pupuk WHERE id_pupuk=?',[(int)$_POST['id']]);$this->flash('Pupuk dihapus.');}
   catch(Throwable $e){$this->flash('Pupuk masih digunakan transaksi.','warning');}
   $this->go('admin_pupuk');
 }
 elseif($page==='admin_kategori'){
   $rows='';foreach($r->rows('SELECT * FROM kategori_pupuk ORDER BY id_kategori DESC') as $x){
    $rows.='<tr><td>'.$this->esc($x['nama_kategori']).'</td><td>'.$this->esc($x['keterangan']).'</td><td><a class="btn btn-sm btn-outline-primary" href="index.php?page=admin_kategori_form&id='.$x['id_kategori'].'">Ubah</a> <form class="d-inline" method="post" action="index.php?page=admin_kategori_delete" onsubmit="return confirm(\'Hapus kategori ini?\')"><input type="hidden" name="csrf" value="'.$this->csrf().'"><input type="hidden" name="id" value="'.$x['id_kategori'].'"><button class="btn btn-sm btn-outline-danger">Hapus</button></form></td></tr>';
   }
   $c='<h2>Kategori Pupuk</h2><a class="btn btn-success mb-3" href="index.php?page=admin_kategori_form">+ Tambah Kategori</a>'.$this->table(['Nama Kategori','Keterangan'],$rows,'aksi');
 }
 elseif($page==='admin_kategori_form'){
   $x=isset($_GET['id'])?$r->row('SELECT * FROM kategori_pupuk WHERE id_kategori=?',[(int)$_GET['id']]):[];
   $c='<h2>Form Kategori Pupuk</h2>'.$this->form('admin_kategori',[['text','Nama Kategori','nama_kategori',$x['nama_kategori']??''],['text','Keterangan','keterangan',$x['keterangan']??'']],(int)($_GET['id']??0));
 }
 elseif($page==='admin_kategori_save'){
   $this->check();$id=(int)($_POST['id']??0);$nama=trim($_POST['nama_kategori']??'');$ket=trim($_POST['keterangan']??'');
   if($id){$r->run('UPDATE kategori_pupuk SET nama_kategori=?,keterangan=? WHERE id_kategori=?',[$nama,$ket,$id]);$this->flash('Kategori berhasil diperbarui.');}
   else{$r->run('INSERT INTO kategori_pupuk(nama_kategori,keterangan) VALUES(?,?)',[$nama,$ket]);$this->flash('Kategori berhasil ditambahkan.');}
   $this->go('admin_kategori');
 }
 elseif($page==='admin_kategori_delete'){
   $this->check();try{$r->run('DELETE FROM kategori_pupuk WHERE id_kategori=?',[(int)$_POST['id']]);$this->flash('Kategori berhasil dihapus.');}
   catch(Throwable $e){$this->flash('Kategori masih digunakan oleh data pupuk sehingga tidak dapat dihapus.','warning');}
   $this->go('admin_kategori');
 }
 elseif($page==='admin_stok'){
   $rows='';foreach($r->rows('SELECT * FROM pupuk ORDER BY nama_pupuk') as $x)$rows.='<tr><td>'.$x['nama_pupuk'].'</td><td>'.$x['stok'].' '.$x['satuan'].'</td><td><form method="post" action="index.php?page=admin_stok_save" class="d-flex gap-2"><input type="hidden" name="csrf" value="'.$this->csrf().'"><input type="hidden" name="id" value="'.$x['id_pupuk'].'"><input class="form-control" style="max-width:110px" type="number" min="0" name="stok" value="'.$x['stok'].'"><button class="btn btn-success btn-sm">Simpan</button></form></td></tr>';
   $c='<h2>Manajemen Stok</h2>'.$this->table(['Pupuk','Stok Saat Ini','Perbarui Stok'],$rows);
 }
 elseif($page==='admin_stok_save'){
   $this->check();$r->run('UPDATE pupuk SET stok=? WHERE id_pupuk=?',[(int)$_POST['stok'],(int)$_POST['id']]);$this->go('admin_stok');
 }
 elseif($page==='admin_laporan'){
   $total=$r->row('SELECT COALESCE(SUM(total_harga),0) n FROM pemesanan')['n']??0;
   $rows='';
   foreach($r->rows('SELECT o.kode_pemesanan, p.nama_petani, u.nama_pupuk, o.jumlah, o.total_harga, o.tanggal_pemesanan, o.status_pemesanan FROM pemesanan o JOIN petani p USING(id_petani) JOIN pupuk u USING(id_pupuk) ORDER BY o.id_pemesanan DESC') as $x){
    $rows.='<tr><td>'.$this->esc($x['kode_pemesanan']).'</td><td>'.$this->esc($x['nama_petani']).'</td><td>'.$this->esc($x['nama_pupuk']).'</td><td>'.$x['jumlah'].'</td><td>Rp '.number_format($x['total_harga'],0,',','.').'</td><td>'.date('d-m-Y', strtotime($x['tanggal_pemesanan'])).'</td><td><span class="status-pill">'.$this->esc($x['status_pemesanan']).'</span></td></tr>';
   }
   $c='<div class="page-heading"><div><h2>Laporan Transaksi</h2><p>Rekapitulasi seluruh data pemesanan pupuk.</p></div><a href="index.php?page=admin_laporan_print" target="_blank" class="btn btn-success">🖨️ Cetak Laporan</a></div><div class="row g-3 stat-row mb-4"><div class="col-md-4"><div class="stat-card"><span class="stat-icon" style="background:#e5f7ed">💰</span><div class="stat-label">Total Pendapatan</div><strong>Rp '.number_format($total,0,',','.').'</strong></div></div></div><div class="panel">'.$this->table(['Kode','Petani','Pupuk','Jumlah','Total','Tanggal','Status'],$rows).'</div>';
 }
 elseif($page==='admin_laporan_print'){
   $items=$r->rows('SELECT o.kode_pemesanan, p.nama_petani, u.nama_pupuk, o.jumlah, o.total_harga, o.tanggal_pemesanan, o.status_pemesanan FROM pemesanan o JOIN petani p USING(id_petani) JOIN pupuk u USING(id_pupuk) ORDER BY o.id_pemesanan DESC');
   $total=$r->row('SELECT COALESCE(SUM(total_harga),0) n FROM pemesanan')['n']??0;
   echo '<div style="padding:20px; font-family:sans-serif;"><h2>Laporan Rekapitulasi Transaksi Tani Pupuk</h2><p>Tanggal Cetak: '.date('d-m-Y H:i').'</p><table border="1" cellspacing="0" cellpadding="8" style="width:100%; border-collapse:collapse; margin-top:15px;"><thead><tr style="background:#f2f2f2"><th>Kode</th><th>Petani</th><th>Pupuk</th><th>Jumlah</th><th>Total</th><th>Tanggal</th><th>Status</th></tr></thead><tbody>';
   foreach($items as $x){
       echo '<tr><td>'.$this->esc($x['kode_pemesanan']).'</td><td>'.$this->esc($x['nama_petani']).'</td><td>'.$this->esc($x['nama_pupuk']).'</td><td>'.$x['jumlah'].'</td><td>Rp '.number_format($x['total_harga'],0,',','.').'</td><td>'.date('d-m-Y',strtotime($x['tanggal_pemesanan'])).'</td><td>'.$this->esc($x['status_pemesanan']).'</td></tr>';
   }
   echo '</tbody></table><h3 style="margin-top:20px;">Total Pendapatan: Rp '.number_format($total,0,',','.').'</h3></div><script>window.print();</script>';
   echo '<script>
        window.print();
        window.onafterprint = function() {
            window.location.href = "index.php?page=admin_laporan";
        };
        // Fallback untuk beberapa browser yang tidak mendukung onafterprint dengan sempurna
        setTimeout(function() {
            window.location.href = "index.php?page=admin_laporan";
        }, 1000);
   </script>';
   exit;
 }
 elseif($page==='admin_users'){
   $rows='';
   foreach($r->rows('SELECT * FROM users ORDER BY id_user DESC') as $x){
    $rows.='<tr><td>'.$this->esc($x['nama_lengkap']).'</td><td>'.$this->esc($x['username']).'</td><td><span class="badge bg-secondary">'.$this->esc($x['role']).'</span></td><td><a class="btn btn-sm btn-outline-primary" href="index.php?page=admin_users_form&id='.$x['id_user'].'">Ubah</a> <form class="d-inline" method="post" action="index.php?page=admin_users_delete" onsubmit="return confirm(\'Hapus akun ini?\')"><input type="hidden" name="csrf" value="'.$this->csrf().'"><input type="hidden" name="id" value="'.$x['id_user'].'"><button class="btn btn-sm btn-outline-danger">Hapus</button></form></td></tr>';
   }
   $c='<h2>Akun Pengguna</h2><a class="btn btn-success mb-3" href="index.php?page=admin_users_form">+ Tambah Akun</a>'.$this->table(['Nama Lengkap','Username','Role'],$rows,'aksi');
 }
 elseif($page==='admin_users_form'){
   $x=isset($_GET['id'])?$r->row('SELECT * FROM users WHERE id_user=?',[(int)$_GET['id']]):[];
   $c='<h2>Form Akun Pengguna</h2>'.$this->form('admin_users',[['text','Nama Lengkap','nama_lengkap',$x['nama_lengkap']??''],['text','Username','username',$x['username']??''],['select','Role','role',$x['role']??'Admin',['Admin'=>'Admin','Petugas'=>'Petugas','Petani'=>'Petani']],['text','Password Baru (Kosongkan jika tidak diubah)','password','']],(int)($_GET['id']??0));
 }
 elseif($page==='admin_users_save'){
   $this->check();$id=(int)$_POST['id'];
   $nama=$_POST['nama_lengkap']; $username=$_POST['username']; $role=$_POST['role']; $pass=$_POST['password']??'';
   if($id){
       if(!empty($pass)){
           $hash=password_hash($pass, PASSWORD_DEFAULT);
           $r->run('UPDATE users SET nama_lengkap=?, username=?, role=?, password=? WHERE id_user=?',[$nama,$username,$role,$hash,$id]);
       } else {
           $r->run('UPDATE users SET nama_lengkap=?, username=?, role=? WHERE id_user=?',[$nama,$username,$role,$id]);
       }
       $this->flash('Akun pengguna berhasil diperbarui.');
   } else {
       $hash=password_hash(!empty($pass)?$pass:'123456', PASSWORD_DEFAULT);
       $r->run('INSERT INTO users(nama_lengkap,username,password,role) VALUES(?,?,?,?)',[$nama,$username,$hash,$role]);
       $this->flash('Akun pengguna baru berhasil ditambahkan.');
   }
   $this->go('admin_users');
 }
 elseif($page==='admin_users_delete'){
   $this->check();
   $r->run('DELETE FROM users WHERE id_user=?',[(int)$_POST['id']]);
   $this->flash('Akun pengguna berhasil dihapus.');
   $this->go('admin_users');
 }
elseif($page==='pengaturan'){
   $appName = $_SESSION['app_name'] ?? 'TANI PUPUK';
   $appDesc = $_SESSION['app_desc'] ?? 'Sistem Pengelolaan dan Pemesanan Pupuk Pertanian';
   
   $c='<h2>Pengaturan Sistem</h2><div class="panel"><form method="post" action="index.php?page=pengaturan_save"><input type="hidden" name="csrf" value="'.$this->csrf().'"><div class="mb-3"><label class="form-label">Nama Aplikasi</label><input type="text" class="form-control" name="app_name" value="'.$this->esc($appName).'" required></div><div class="mb-3"><label class="form-label">Deskripsi Sistem</label><textarea class="form-control" name="app_desc" required>'.$this->esc($appDesc).'</textarea></div><button class="btn btn-success">Simpan Pengaturan</button></form></div>';
 }
 elseif($page==='pengaturan_save'){
   $this->check();
   $_SESSION['app_name'] = trim($_POST['app_name'] ?? 'TANI PUPUK');
   $_SESSION['app_desc'] = trim($_POST['app_desc'] ?? 'Sistem Pengelolaan dan Pemesanan Pupuk Pertanian');
   
   $this->flash('Pengaturan sistem berhasil disimpan.');
   $this->go('pengaturan');
 }
 elseif($page==='profil'){
   $pid=(int)$_SESSION['user']['petani_id'];
   $x=$r->row('SELECT * FROM petani WHERE id_petani=?',[$pid]);
   $c='<h2>Profil Petani</h2><div class="panel" style="max-width:600px;"><form method="post" action="index.php?page=profil_save"><input type="hidden" name="csrf" value="'.$this->csrf().'"><div class="mb-3"><label class="form-label">NIK</label><input type="text" class="form-control" name="nik" value="'.$this->esc($x['nik']??'').'" required></div><div class="mb-3"><label class="form-label">Nama Petani</label><input type="text" class="form-control" name="nama_petani" value="'.$this->esc($x['nama_petani']??'').'" required></div><div class="mb-3"><label class="form-label">Nomor HP</label><input type="text" class="form-control" name="no_hp" value="'.$this->esc($x['no_hp']??'').'"></div><div class="mb-3"><label class="form-label">Alamat</label><textarea class="form-control" name="alamat" required>'.$this->esc($x['alamat']??'').'</textarea></div><button class="btn btn-success">Simpan Perubahan</button></form></div>';
 }
 elseif($page==='profil_save'){
   $this->check();
   $pid=(int)$_SESSION['user']['petani_id'];
   $namaBaru = trim($_POST['nama_petani'] ?? '');
   
   // 1. Update data pada tabel petani
   $r->run('UPDATE petani SET nik=?, nama_petani=?, no_hp=?, alamat=? WHERE id_petani=?',[$_POST['nik'], $namaBaru, $_POST['no_hp'], $_POST['alamat'], $pid]);
   
   // 2. Update juga data nama pada tabel users agar dashboard dan topbar ikut berubah
   $r->run('UPDATE users SET nama_lengkap=? WHERE id_petani=?', [$namaBaru, $pid]);
   
   // 3. Perbarui session aktif agar perubahan langsung terasa tanpa harus login ulang
   $_SESSION['user']['nama'] = $namaBaru;
   
   $this->flash('Profil dan nama akun berhasil diperbarui.');
   $this->go('profil');
 }
 elseif(in_array($page,['admin_pesanan','pesanan_saya','riwayat'])){
   $farmer=$role==='Petani'?$_SESSION['user']['petani_id']:null;
   $d=$r->orders($_GET['q']??'',(int)($_GET['p']??1),$farmer);$rows='';
   foreach($d['rows'] as $x)$rows.='<tr><td>'.$x['kode_pemesanan'].'</td><td>'.$x['nama_petani'].'</td><td>'.$x['nama_pupuk'].'</td><td>'.$x['jumlah'].'</td><td>Rp '.number_format($x['total_harga'],0,',','.').'</td><td>'.$x['status_pemesanan'].'</td><td><a class="btn btn-sm btn-outline-success" href="index.php?page=detail_pesanan&id='.$x['id_pemesanan'].'">Detail</a></td></tr>';
   $c='<h2>'.($page==='admin_pesanan'?'Transaksi':($page==='riwayat'?'Riwayat Transaksi':'Pesanan Saya')).'</h2>'.$this->search($page).$this->table(['Kode','Petani','Pupuk','Jumlah','Total','Status'],$rows,'detail').$this->pager($d,$page);
   if($page==='pesanan_saya')$c='<a class="btn btn-success mb-3" href="index.php?page=katalog">Pesan Pupuk</a>'.$c;
 }
 elseif($page==='katalog'){
   $items=$r->rows('SELECT p.*,k.nama_kategori FROM pupuk p JOIN kategori_pupuk k USING(id_kategori)');
   $c='<div class="page-heading"><div><h2>Katalog Pupuk</h2><p>Pilih pupuk sesuai kebutuhan pertanian Anda.</p></div></div><div class="row g-3 product-grid">';
   foreach($items as $p){
        $key=strtolower($p['nama_pupuk']);
        $img=str_contains($key,'urea')?'UREA.jpeg':(str_contains($key,'npk')?'NPK.jpeg':(str_contains($key,'za')?'ZA.jpeg':'ORGANIK.jpeg'));
        $c.='<div class="col-6 col-md-3"><div class="product-card"><img src="assets/png/'.$img.'" alt="Karung '.$this->esc($p['nama_pupuk']).'"><h6>'.$this->esc($p['nama_pupuk']).'</h6><small>'.$this->esc($p['nama_kategori']).'</small><strong>Rp '.number_format($p['harga'],0,',','.').' / sak</strong><small>Stok: '.$p['stok'].' '.$this->esc($p['satuan']).'</small><form method="post" action="index.php?page=buat_pesanan"><input type="hidden" name="csrf" value="'.$this->csrf().'"><input type="hidden" name="id_pupuk" value="'.$p['id_pupuk'].'"><label>Jumlah</label><input type="number" class="form-control form-control-sm mb-2" name="jumlah" min="1" max="'.$p['stok'].'" value="1" required><button class="btn btn-success btn-sm w-100" '.($p['stok']<1?'disabled':'').'>Pesan</button></form></div></div>';
   }
   $c.='</div>';
 }
 elseif($page==='buat_pesanan'){
   $this->check();$farmer=$_SESSION['user']['petani_id'];
   $p=$r->row('SELECT * FROM pupuk WHERE id_pupuk=?',[(int)$_POST['id_pupuk']]);$qty=(int)$_POST['jumlah'];
   if(!$p||$qty<1||$qty>$p['stok']){$this->flash('Stok tidak mencukupi.','danger');$this->go('katalog');}
   $kode='TP'.date('ymdHis').random_int(10,99);$token=bin2hex(random_bytes(16));
   $id=$r->run('INSERT INTO pemesanan(kode_pemesanan,qr_token,id_petani,id_pupuk,tanggal_pemesanan,jumlah,total_harga,status_pemesanan) VALUES(?,?,?,?,?,?,?,?)',[$kode,$token,$farmer,$p['id_pupuk'],date('Y-m-d'),$qty,$qty*$p['harga'],'Menunggu Pembayaran']);
   $r->run('UPDATE pupuk SET stok=stok-? WHERE id_pupuk=?',[$qty,$p['id_pupuk']]);
   $r->run('INSERT INTO pembayaran(id_pemesanan,jumlah_bayar,status_pembayaran) VALUES(?,?,"Belum Dibayar")',[$id,$qty*$p['harga']]);
   $this->flash('Pesanan dibuat. Silakan lakukan pembayaran.');$this->go('pembayaran');
 }
 elseif($page==='detail_pesanan'){
   $x=$r->row('SELECT o.*,p.nama_petani,u.nama_pupuk FROM pemesanan o JOIN petani p USING(id_petani) JOIN pupuk u USING(id_pupuk) WHERE id_pemesanan=?',[(int)$_GET['id']]);
   if(!$x || ($role==='Petani' && (int)$x['id_petani']!==(int)$_SESSION['user']['petani_id'])){$this->flash('Pesanan tidak ditemukan atau tidak dapat diakses.','danger');$this->go($role==='Petani'?'pesanan_saya':'admin_pesanan');}
   $c='<h2>Detail Pesanan</h2><div class="panel"><p>Kode: <b>'.$x['kode_pemesanan'].'</b></p><p>Petani: '.$x['nama_petani'].'</p><p>Pupuk: '.$x['nama_pupuk'].'</p><p>Jumlah: '.$x['jumlah'].'</p><p>Total: Rp '.number_format($x['total_harga'],0,',','.').'</p><p>Status: '.$x['status_pemesanan'].'</p><p>QR Token: <code>'.$x['qr_token'].'</code></p></div>';
 }
 elseif($page==='pembayaran'){
   $farmer=$_SESSION['user']['petani_id'];$rows='';
   foreach($r->rows('SELECT b.*,o.kode_pemesanan FROM pembayaran b JOIN pemesanan o USING(id_pemesanan) WHERE o.id_petani=? ORDER BY b.id_pembayaran DESC',[$farmer]) as $x)
        $rows.='<tr><td>'.$x['kode_pemesanan'].'</td><td>Rp '.number_format($x['jumlah_bayar'],0,',','.').'</td><td>'.$x['status_pembayaran'].'</td><td>'.($x['status_pembayaran']==='Lunas'?'Lunas':'<form method="post" action="index.php?page=bayar"><input type="hidden" name="csrf" value="'.$this->csrf().'"><input type="hidden" name="id" value="'.$x['id_pembayaran'].'"><button class="btn btn-success btn-sm">Bayar (simulasi)</button></form>').'</td></tr>';
   $c='<h2>Pembayaran</h2>'.$this->table(['Kode Pesanan','Jumlah','Status','Aksi'],$rows);
 }
 elseif($page==='bayar'){
   $this->check();$id=(int)$_POST['id'];
   $r->run('UPDATE pembayaran SET status_pembayaran="Lunas",tanggal_bayar=CURDATE(),metode_pembayaran="Simulasi" WHERE id_pembayaran=?',[$id]);
   $r->run('UPDATE pemesanan o JOIN pembayaran b USING(id_pemesanan) SET o.status_pemesanan="Menunggu Pengambilan" WHERE b.id_pembayaran=?',[$id]);
   $this->flash('Pembayaran simulasi berhasil. Bukti QR siap diperiksa petugas.');$this->go('pembayaran');
 }
 elseif($page==='scan_qr'){
   $c='<h2>Scan QR Transaksi</h2><p>Arahkan kamera ke QR bukti pembayaran petani.</p><div class="panel"><div id="reader" style="max-width:460px"></div><form class="mt-3" method="get"><input type="hidden" name="page" value="validasi"><label>Atau masukkan kode transaksi</label><input class="form-control mb-2" name="kode" placeholder="Kode / token QR"><button class="btn btn-success">Validasi</button></form></div><script>window.startQr=true;</script>';
 }
 elseif($page==='validasi'){
   $code=trim($_GET['kode']??'');
   $x=$code?$r->row('SELECT o.*,p.nama_petani,u.nama_pupuk,b.status_pembayaran FROM pemesanan o JOIN petani p USING(id_petani) JOIN pupuk u USING(id_pupuk) JOIN pembayaran b USING(id_pemesanan) WHERE o.kode_pemesanan=? OR o.qr_token=?',[$code,$code]):null;
   if(!$x){
        $c='<h2>Validasi Transaksi</h2><div class="alert alert-danger">Transaksi atau kode QR tidak ditemukan.</div>';
   } else {
        $c='<h2>Validasi Transaksi</h2><div class="panel"><p>Kode: <b>'.$x['kode_pemesanan'].'</b></p><p>Petani: '.$x['nama_petani'].'</p><p>Pupuk: '.$x['nama_pupuk'].' ('.$x['jumlah'].' sak)</p><p>Total: Rp '.number_format($x['total_harga'],0,',','.').'</p><p>Status Pembayaran: <span class="badge bg-success">'.$x['status_pembayaran'].'</span></p><p>Status Pemesanan: <b>'.$x['status_pemesanan'].'</b></p>';
        if($x['status_pemesanan']==='Menunggu Pengambilan'){
            $c.='<form method="post" action="index.php?page=konfirmasi_ambil"><input type="hidden" name="csrf" value="'.$this->csrf().'"><input type="hidden" name="id" value="'.$x['id_pemesanan'].'"><button class="btn btn-success">Konfirmasi Pengambilan Selesai</button></form>';
        } else {
            $c.='<p class="text-muted">Transaksi ini sudah selesai atau diambil.</p>';
        }
        $c.='</div>';
   }
 }
 elseif($page==='konfirmasi_ambil'){
   $this->check();$id=(int)$_POST['id'];
   $r->run('UPDATE pemesanan SET status_pemesanan="Sudah Diambil" WHERE id_pemesanan=?',[$id]);
   $this->flash('Pengambilan pupuk berhasil dikonfirmasi.');$this->go('petugas_dashboard');
 }
 else {
   $c='<h2>Halaman Tidak Ditemukan</h2><p>Halaman yang Anda akses tidak tersedia.</p>';
 }
 $this->render(ucfirst(str_replace('_',' ',$page)),$c);
 }
}