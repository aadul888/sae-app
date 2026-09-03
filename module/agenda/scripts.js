/**
 * Agenda - Public Module Scripts
 * Tabs: Realtime, Per Guru, Per Kelas, Per Mapel
 */
$(document).ready(function () {

function getBasePath() {
  var p = window.location.pathname;
  if (p.indexOf('/module/agenda/') !== -1) return './';
  return './module/agenda/';
}
var BASE = getBasePath() + 'proses.php';

// Tab switching
$('.tab-btn').on('click', function () {
  var tab = $(this).data('tab');
  $('.tab-btn').removeClass('active');
  $(this).addClass('active');
  $('.tab-panel').removeClass('active');
  $('#tab-' + tab).addClass('active');
  if (tab === 'realtime') loadRealtime();
  else loadReport(tab);
});

$('#ag-tanggal').on('change', function () { loadRealtime(); });
$('#btn-filter-guru').on('click', function () { loadReport('guru'); });
$('#btn-filter-kelas').on('click', function () { loadReport('kelas'); });
$('#btn-filter-mapel').on('click', function () { loadReport('mapel'); });

loadRealtime();

function loadRealtime() {
  var tanggal = $('#ag-tanggal').val();
  $.getJSON(BASE + '?action=realtime&tanggal=' + tanggal, function (res) {
    if (res.status === 'success') {
      renderSummary(res.summary);
      renderGrid(res.data);
    }
  });
}

function renderSummary(s) {
  if (!s) { $('#ag-summary').html(''); return; }
  var total = (s.hadir || 0) + (s.tidak_hadir || 0) + (s.tugas || 0) + (s.belum || 0);
  var items = [
    { icon: 'fa-clock', accent: 'blue', label: 'Total Jam', val: total },
    { icon: 'fa-check-circle', accent: 'teal', label: 'Hadir', val: s.hadir || 0 },
    { icon: 'fa-times-circle', accent: 'orange', label: 'Tidak Hadir', val: s.tidak_hadir || 0 },
    { icon: 'fa-exclamation-triangle', accent: 'purple', label: 'Tugas', val: s.tugas || 0 },
    { icon: 'fa-minus-circle', accent: 'green', label: 'Belum Diisi', val: s.belum || 0 }
  ];
  var html = '';
  for (var i = 0; i < items.length; i++) {
    var it = items[i];
    html += '<div class="sae-kpi-card agenda-kpi-card">' +
      '<span class="sae-kpi-icon ' + it.accent + '"><i class="fas ' + it.icon + '"></i></span>' +
      '<div><div class="sae-kpi-value">' + it.val + '</div><p class="sae-kpi-label">' + it.label + '</p></div>' +
      '</div>';
  }
  $('#ag-summary').html(html);
}

function renderGrid(data) {
  if (!data || data.length === 0) {
    $('#kelas-grid').html('<div class="agenda-empty-state"><i class="fas fa-inbox me-2"></i>Tidak ada data jadwal</div>');
    return;
  }
  var colors = ['#5e72e4','#2dce89','#fb6340','#f5365c','#11cdef','#8965e0','#ffd600','#172b4d','#5603ad','#ff3709'];
  var html = '';
  for (var i = 0; i < data.length; i++) {
    var k = data[i];
    var color = colors[i % colors.length];
    html += '<div class="kelas-card"><div class="kelas-card-header" style="background:' + color + '">' +
      '<span>' + k.kelas_nama + '</span>' +
      '<span class="badge badge-light">' + (k.terisi || 0) + '/' + (k.total_jam || 0) + '</span></div><div class="kelas-card-body">';
    if (k.jadwal && k.jadwal.length) {
      for (var j = 0; j < k.jadwal.length; j++) {
        var jd = k.jadwal[j];
        var statusCls = 'belum', statusIcon = 'fa-minus';
        if (jd.kehadiran === 'Hadir') { statusCls = 'hadir'; statusIcon = 'fa-check'; }
        else if (jd.kehadiran === 'Tidak Hadir') { statusCls = 'tidak-hadir'; statusIcon = 'fa-times'; }
        else if (jd.kehadiran === 'Tidak Hadir + Tugas') { statusCls = 'tugas'; statusIcon = 'fa-exclamation'; }
        html += '<div class="jam-row"><span class="jam-num">' + jd.jam_ke + '</span>' +
          '<span class="jam-mapel">' + (jd.nama_mapel || '-') + '</span>' +
          '<span class="jam-guru">' + (jd.nama_guru || '-') + '</span>' +
          '<span class="jam-status ' + statusCls + '"><i class="fas ' + statusIcon + '"></i></span></div>';
      }
    } else {
      html += '<div class="agenda-empty-inline">Belum ada jadwal</div>';
    }
    html += '</div></div>';
  }
  $('#kelas-grid').html(html);
}

function loadReport(type) {
  var dari = $('#ag-dari-' + type).val();
  var sampai = $('#ag-sampai-' + type).val();
  $.getJSON(BASE + '?action=report&type=' + type + '&dari=' + dari + '&sampai=' + sampai, function (res) {
    if (res.status === 'success') {
      renderReport(type, res.data);
    }
  });
}

function renderReport(type, data) {
  var tbl = $('#tbl-' + type);
  if (!data || data.length === 0) {
    tbl.html('<tr><td class="text-center text-muted py-4" colspan="6"><i class="fas fa-inbox me-1"></i>Tidak ada data</td></tr>');
    return;
  }
  var label = type === 'guru' ? 'Guru' : type === 'kelas' ? 'Kelas' : 'Mata Pelajaran';
  var html = '<thead><tr><th>No</th><th>' + label + '</th><th>Hadir</th><th>Tidak Hadir</th><th>Tugas</th><th>% Hadir</th></tr></thead><tbody>';
  for (var i = 0; i < data.length; i++) {
    var r = data[i];
    var total = (r.hadir || 0) + (r.tidak_hadir || 0) + (r.tugas || 0);
    var pct = total > 0 ? Math.round(r.hadir / total * 100) : 0;
    var barColor = pct >= 80 ? '#2dce89' : pct >= 50 ? '#fb6340' : '#f5365c';
    html += '<tr><td>' + (i + 1) + '</td><td style="text-align:left;">' + (r.nama || '-') + '</td>' +
      '<td>' + (r.hadir || 0) + '</td><td>' + (r.tidak_hadir || 0) + '</td><td>' + (r.tugas || 0) + '</td>' +
      '<td>' + pct + '%<div class="pct-bar"><div class="pct-fill" style="width:' + pct + '%;background:' + barColor + '"></div></div></td></tr>';
  }
  html += '</tbody>';
  tbl.html(html);
}

setInterval(function () {
  if ($('.tab-btn.active').data('tab') === 'realtime') loadRealtime();
}, 180000);

});
