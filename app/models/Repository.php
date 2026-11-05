<?php
class Repository {
 private PDO $db;
 function __construct(){ $this->db=db(); }
 function rows($sql,$args=[]){$s=$this->db->prepare($sql);$s->execute($args);return $s->fetchAll();}
 function row($sql,$args=[]){$s=$this->db->prepare($sql);$s->execute($args);return $s->fetch()?:null;}
 function run($sql,$args=[]){$s=$this->db->prepare($sql);$s->execute($args);return $this->db->lastInsertId();}
 function count($table){return (int)$this->db->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();}
 function petaniList($q='',$page=1){$like="%$q%";$total=(int)$this->row("SELECT COUNT(*) n FROM petani WHERE nama_petani LIKE ? OR nik LIKE ?",[$like,$like])['n'];$pages=max(1,ceil($total/10));$page=min($page,$pages);return ['rows'=>$this->rows("SELECT * FROM petani WHERE nama_petani LIKE ? OR nik LIKE ? ORDER BY id_petani DESC LIMIT 10 OFFSET ".(($page-1)*10),[$like,$like]),'total'=>$total,'page'=>$page,'pages'=>$pages];}
 function pupukList($q='',$page=1){$like="%$q%";$total=(int)$this->row("SELECT COUNT(*) n FROM pupuk p JOIN kategori_pupuk k ON k.id_kategori=p.id_kategori WHERE p.nama_pupuk LIKE ? OR k.nama_kategori LIKE ?",[$like,$like])['n'];$pages=max(1,ceil($total/10));$page=min($page,$pages);return ['rows'=>$this->rows("SELECT p.*,k.nama_kategori FROM pupuk p JOIN kategori_pupuk k ON k.id_kategori=p.id_kategori WHERE p.nama_pupuk LIKE ? OR k.nama_kategori LIKE ? ORDER BY p.id_pupuk DESC LIMIT 10 OFFSET ".(($page-1)*10),[$like,$like]),'total'=>$total,'page'=>$page,'pages'=>$pages];}
 function orders($q='',$page=1,$farmerId=null){$like="%$q%";$where="WHERE (o.kode_pemesanan LIKE ? OR p.nama_petani LIKE ?)";$args=[$like,$like];if($farmerId){$where.=" AND o.id_petani=?";$args[]=$farmerId;}$total=(int)$this->row("SELECT COUNT(*) n FROM pemesanan o JOIN petani p ON p.id_petani=o.id_petani $where",$args)['n'];$pages=max(1,ceil($total/10));$page=min($page,$pages);return ['rows'=>$this->rows("SELECT o.*,p.nama_petani,u.nama_pupuk,u.satuan FROM pemesanan o JOIN petani p ON p.id_petani=o.id_petani JOIN pupuk u ON u.id_pupuk=o.id_pupuk $where ORDER BY o.id_pemesanan DESC LIMIT 10 OFFSET ".(($page-1)*10),$args),'total'=>$total,'page'=>$page,'pages'=>$pages];}
}