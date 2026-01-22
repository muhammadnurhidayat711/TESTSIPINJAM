<script>
/* ===========================================
   SIPINJAM UI JS (Reusable)
   =========================================== */
window.SIPINJAM = window.SIPINJAM || {};

SIPINJAM.openWhatsAppSequential = function(urls, names, doneRedirect){
  if(!Array.isArray(urls) || urls.length===0){
    if (doneRedirect) window.location.href = doneRedirect;
    return;
  }
  function openAt(i){
    if(i >= urls.length){
      alert('Notifikasi WA dibuka untuk ' + (names ? names.length : urls.length) + ' PIC.');
      if (doneRedirect) window.location.href = doneRedirect;
      return;
    }
    window.open(urls[i], '_blank');
    setTimeout(function(){ openAt(i+1); }, 1200);
  }
  setTimeout(function(){ openAt(0); }, 400);
};
</script>
