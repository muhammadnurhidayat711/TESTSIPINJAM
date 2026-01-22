// assets/js/app.js
// Small helpers: confirm delete, auto-submit filters, and lazy image loading.
document.addEventListener('click', (e)=>{
  if(e.target.matches('[data-confirm]')){
    if(!confirm(e.target.getAttribute('data-confirm'))){
      e.preventDefault();
    }
  }
});
document.querySelectorAll('img[loading="lazy"]').forEach(img => {
  if('loading' in HTMLImageElement.prototype){ img.loading = 'lazy'; }
});