document.addEventListener('DOMContentLoaded',()=>{
 const qr=document.getElementById('qrcode');
 if(qr && window.QRCode){new QRCode(qr,{text:qr.dataset.token,width:180,height:180});}
 if(window.startQr && document.getElementById('reader') && window.Html5Qrcode){
   const scanner=new Html5Qrcode("reader");
   scanner.start({facingMode:"environment"},{fps:10,qrbox:{width:230,height:230}},
    decoded=>{scanner.stop().then(()=>{window.location.href='index.php?page=validasi&kode='+encodeURIComponent(decoded)});},
    ()=>{}
   ).catch(()=>{document.getElementById('reader').innerHTML='<div class="alert alert-warning">Kamera tidak dapat diakses. Gunakan kolom input kode transaksi.</div>';});
 }
});