"use strict";

// === Loading Overlay ===
function showLoadingOverlay() {
  const overlay = document.getElementById("loadingOverlay");
  if (overlay) {
    overlay.style.display = "flex";
    document.body.style.overflow = "hidden";
    overlay._autoHideTimeout = setTimeout(hideLoadingOverlay, 7000);
  }
}
function hideLoadingOverlay() {
  const overlay = document.getElementById("loadingOverlay");
  if (overlay) {
    overlay.style.display = "none";
    document.body.style.overflow = "";
    if (overlay._autoHideTimeout) {
      clearTimeout(overlay._autoHideTimeout);
      overlay._autoHideTimeout = null;
    }
  }
}

// === AJAX Filter Absensi ===
function initAbsensiFilter() {
  const form = document.getElementById('absensiFilterForm');
  if (!form) return;
  const bulanSel = form.querySelector('select[name="bulan"]');
  const tahunSel = form.querySelector('select[name="tahun"]');
  const submitBtn = form.querySelector('button[type="submit"]');

  // Set UI loading state
  function setLoadingState(isLoading) {
    if (submitBtn) {
      if (isLoading) {
        submitBtn.dataset.origText = submitBtn.textContent;
        submitBtn.textContent = 'Loading...';
        submitBtn.disabled = true;
      } else {
        submitBtn.textContent = submitBtn.dataset.origText || submitBtn.textContent;
        submitBtn.disabled = false;
      }
    }
    if (bulanSel) bulanSel.disabled = isLoading;
    if (tahunSel) tahunSel.disabled = isLoading;
  }

  // Submit handler (AJAX)
  form.onsubmit = function(e) {
    e.preventDefault();
    const bulan = bulanSel ? bulanSel.value : '';
    const tahun = tahunSel ? tahunSel.value : '';
    filterAbsensi(bulan, tahun, setLoadingState);
  };

  // Auto submit saat select berubah
  if (bulanSel) bulanSel.onchange = () => form.requestSubmit ? form.requestSubmit() : form.submit();
  if (tahunSel) tahunSel.onchange = () => form.requestSubmit ? form.requestSubmit() : form.submit();
}


function filterAbsensi(bulan, tahun, setLoadingState) {
  // AJAX ke proses.php?action=filter
  // Gunakan path relatif dari halaman saat ini
  const currentPath = window.location.pathname;
  const basePath = currentPath.replace(/\/dashboard\/.*$/, '');
  const url = new URL(window.location.origin + basePath + '/dashboard/mod/absensi/proses.php');
  url.searchParams.set('action', 'filter');
  if (bulan) url.searchParams.set('bulan', String(bulan));
  if (tahun) url.searchParams.set('tahun', String(tahun));

  // debug: filter URL removed in production

  setLoadingState(true);
  showLoadingOverlay();

  fetch(url.toString(), { cache: 'no-store', credentials: 'same-origin' })
    .then(resp => {
      if (!resp.ok) throw new Error('Network response was not ok');
      return resp.text(); // Ambil text dulu untuk debug
    })
    .then(text => {
      // response debug removed in production
      try {
        return JSON.parse(text);
      } catch (e) {
        console.error('JSON Parse Error:', e);
        console.error('Response was:', text);
        throw new Error('Invalid JSON response');
      }
    })
    .then(data => {
      if (data.error) {
        alert('Error: ' + data.error);
        return;
      }
      
      // Data: { rows_html, stats_html, title_html }
      const curTbody = document.querySelector('table.table.table-bordered tbody');
      const curStats = document.querySelector('.card-body .mt-4.p-3.bg-light.rounded') || document.querySelector('.card-body .mt-3');
      const curTitle = document.querySelector('.absensi-card .card-header h3') || document.querySelector('.card-header h3');
      
      if (curTbody) {
        if (data.rows_html) {
          curTbody.innerHTML = data.rows_html;
        } else {
          // Jika tidak ada data
          curTbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-5"><i class="fas fa-inbox fa-3x mb-3 d-block"></i><h5>Belum Ada Riwayat Absensi</h5><p>Tidak ada data absensi untuk bulan yang dipilih</p></td></tr>';
        }
      }
      
      if (data.stats_html && curStats) {
        curStats.innerHTML = '<h5 class="mb-3">Ringkasan Kehadiran:</h5>' + data.stats_html;
      }
      
      if (data.title_html && curTitle) {
        curTitle.innerHTML = data.title_html;
      }
      
      // Update browser URL
      const newUrl = new URL(window.location.href);
      newUrl.searchParams.set('mod', 'absensi');
      newUrl.searchParams.set('bulan', bulan);
      newUrl.searchParams.set('tahun', tahun);
      window.history.pushState({}, '', newUrl.toString());
      
      if (window.absensi && typeof window.absensi.afterLoad === 'function') window.absensi.afterLoad();
    })
    .catch(err => {
      console.error('AJAX Error:', err);
      // Fallback reload penuh ke halaman absensi
      const currentPath = window.location.pathname;
      const search = window.location.search;
      const params = new URLSearchParams(search);
      params.set('mod', 'absensi');
      params.set('bulan', bulan);
      params.set('tahun', tahun);
      
      // Ambil base URL
      const baseUrl = currentPath.split('?')[0];
      window.location.href = baseUrl + '?' + params.toString();
    })
    .finally(() => {
      setLoadingState(false);
      hideLoadingOverlay();
    });
}

// === Inisialisasi saat DOM ready ===
document.addEventListener('DOMContentLoaded', function () {
  initAbsensiFilter();
});