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

// ── Close modal overlay ──
function closeModal() {
  const el = document.getElementById('bookingModal');
  if (el) el.classList.remove('show');
}

// ── Switch tabs ──
function switchTab(event, tabId) {
  document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  const target = document.getElementById(tabId);
  if (target) target.classList.add('active');
  if (event && event.target) event.target.classList.add('active');
}

// ── Search/filter table ──
function searchTable(inputId, tableId) {
  const input = document.getElementById(inputId);
  if (!input) return;
  const filter = input.value.toLowerCase();
  const table = document.getElementById(tableId);
  if (!table) return;
  const rows = table.querySelector('tbody')?.rows || [];
  for (let i = 0; i < rows.length; i++) {
    rows[i].style.display = rows[i].textContent.toLowerCase().indexOf(filter) > -1 ? '' : 'none';
  }
}

// ── Escape HTML ──
function escapeHtml(str) {
  if (!str) return '';
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}