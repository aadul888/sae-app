'use strict';
var skEd = null;

$(function () {
  if ($.fn.DataTable && $('.surat-keluar-table').length) {
    $('.surat-keluar-table').DataTable({
      iDisplayLength: 5, aLengthMenu: [[5,10,25,-1],[5,10,25,'All']],
      aaSorting: [[0,'desc']],
      language: { search:'Cari:', lengthMenu:'_MENU_',
        info:'_START_-_END_ dari _TOTAL_', infoEmpty:'0',
        paginate: { previous:'<i class="fas fa-angle-left">', next:'<i class="fas fa-angle-right">' }
      },
      columnDefs: [{ orderable:false, targets:[5] }],
      ajax: { url:'./mod/surat-keluar/proses.php?action=datatable', dataSrc:function(j){return j.data||[]} }
    });
  }
});

/* ====== PILIH INDEKS ====== */
$(document).on('change', '#slcIndeks', function () {
  var o = $(this).find(':selected');
  if (!o.val()) return;
  var indeks = o.data('indeks') || '';
  var perihal = o.data('perihal') || '';
  var tpl = o.data('template') || '';

  SK.perihal = perihal;
  $('#fKategori').val(o.data('kategori')||'Surat Keluar');

  $.get('./mod/surat-keluar/proses.php?action=gen_nomor&indeks=' + encodeURIComponent(indeks), function (d) {
    var no = (d||'').trim();
    $('#fNoSurat').val(no);
    $('#fNoSuratDisplay').val(no);
    SK.noSurat = no;
    SK.tujuan = $('#formBuatSurat [name="tujuan"]').val() || SK.tujuan;

    var html = tplSurat();
    if (tpl) {
      $.get('./mod/surat-keluar/proses.php?action=load_template&file=' + encodeURIComponent(tpl), function(dt) {
        showEditor(dt && dt.trim() ? dt : html);
      }).fail(function(){ showEditor(html); });
    } else { showEditor(html); }
  });
});

function tplSurat() {
  var s = SK;
  var tgl = function(){var m=['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];var d=new Date();return d.getDate()+' '+m[d.getMonth()]+' '+d.getFullYear();}();
  return '<div style="font-family:Times New Roman,serif;font-size:12pt;line-height:1.5;">'
    + '<p style="text-align:center;font-size:14pt;margin:0 0 2px"><strong>' + s.ns + '</strong></p>'
    + '<p style="text-align:center;font-size:9pt;margin:0 0 2px;color:#444">' + s.a + '</p>'
    + '<p style="text-align:center;font-size:9pt;margin:0 0 4px;color:#444">Telp. ' + s.ts + ' | Email: ' + s.es + '</p>'
    + '<hr style="border-top:2px solid #000;margin:4px 0 10px">'
    + '<table style="width:100%;font-size:12pt;border-collapse:collapse">'
    + '<tr><td style="width:90px">Nomor</td><td style="width:15px">:</td><td>' + s.noSurat + '</td></tr>'
    + '<tr><td>Lampiran</td><td>:</td><td>-</td></tr>'
    + '<tr><td>Perihal</td><td>:</td><td><strong>' + s.perihal + '</strong></td></tr>'
    + '</table><br>'
    + '<p style="text-align:right;font-size:12pt">' + s.ks + ', ' + tgl + '</p><br>'
    + '<p style="font-size:12pt">Kepada Yth.<br>' + s.tujuan + '<br>di -<br>Tempat</p><br>'
    + '<p style="font-size:12pt">Dengan hormat,</p><br>'
    + '<p style="font-size:12pt">Yang bertanda tangan di bawah ini:</p>'
    + '<table style="width:100%;font-size:12pt;border-collapse:collapse">'
    + '<tr><td style="width:100px">Nama</td><td style="width:15px">:</td><td>' + s.nk + '</td></tr>'
    + '<tr><td>NIP</td><td>:</td><td>' + s.nip + '</td></tr>'
    + '<tr><td>Jabatan</td><td>:</td><td>Kepala ' + s.ns + '</td></tr>'
    + '</table><br>'
    + '<p style="font-size:12pt">menerangkan bahwa:</p><br>'
    + '<p style="font-size:12pt">.............................................................................</p><br>'
    + '<p style="font-size:12pt">Demikian surat ini dibuat untuk dipergunakan sebagaimana mestinya.</p><br><br>'
    + '<table style="width:100%;font-size:12pt">'
    + '<tr><td style="text-align:center">' + s.ks + ', ' + tgl + '</td></tr>'
    + '<tr><td style="text-align:center">Kepala Sekolah,</td></tr>'
    + '<tr><td style="height:70px"></td></tr>'
    + '<tr><td style="text-align:center;text-decoration:underline"><strong>' + s.nk + '</strong></td></tr>'
    + '<tr><td style="text-align:center;font-size:11pt">NIP. ' + s.nip + '</td></tr>'
    + '</table></div>';
}

function showEditor(html) {
  $('#skPlaceholder').hide();
  $('#skEditorWrap').show();

  if (skEd) { skEd.setContent(html); wc(); return; }

  if (typeof tinymce !== 'undefined') {
    tinymce.init({
      selector: '#fIsiSurat',
      height: 700,
      menubar: false,
      plugins: 'lists link hr print fullscreen',
      toolbar: 'undo redo | bold italic underline | fontselect fontsizeselect | alignleft aligncenter alignright | bullist numlist | link | removeformat fullscreen',
      font_formats: 'Times New Roman=times new roman,times,serif; Arial=arial,helvetica,sans-serif; Calibri=calibri,sans-serif',
      fontsize_formats: '9pt 10pt 11pt 12pt 14pt 16pt 18pt 20pt 24pt',
      branding: false, elementpath: false, resize: false,
      content_style: 'body{font-family:Times New Roman,serif;font-size:12pt;line-height:1.5;margin:50px;color:#222} p{margin:0 0 6px 0}',
      setup: function (ed) {
        skEd = ed;
        ed.on('init', function () { ed.setContent(html); wc(); });
        ed.on('keyup change', function () { wc(); });
      }
    });
  }
}

function wc() {
  if (!skEd) return;
  var t = (skEd.getContent({format:'text'})||'').trim();
  $('#skWordCount').text((t ? t.split(/\s+/).length : 0) + ' kata');
}

/* ====== SIMPAN ====== */
$(document).on('submit', '#formBuatSurat', function (e) {
  e.preventDefault();
  if (skEd) skEd.save();
  $.ajax({
    url: './mod/surat-keluar/proses.php?action=buat',
    type: 'POST', data: new FormData(this),
    processData: false, contentType: false,
    success: function (data) {
      try {
        var r = typeof data === 'object' ? data : JSON.parse(data);
        if (r.status === 'success') {
          swal({ title:'Berhasil!', text:'Surat tersimpan.', icon:'success', timer:1500 });
          if (r.download_url) window.open(r.download_url, '_blank');
          setTimeout(function(){location.reload()},1500);
        } else { swal({ title:'Gagal!', text:r.message||data, icon:'error' }); }
      } catch(e) { swal({ title:'Error!', text:data, icon:'error' }); }
    },
    error: function(xhr) {
      var m = 'Koneksi gagal.';
      if (xhr && xhr.responseText) m = xhr.responseText.replace(/<[^>]*>/g,' ').trim().substring(0,200);
      swal({ title:'Error!', text:m, icon:'error' });
    }
  });
});

$(document).on('click', '.btn-delete-keluar', function () {
  var id = $(this).data('id');
  swal({ title:'Hapus?', text:'Surat akan dihapus.', icon:'warning', buttons:['Batal','Ya'], dangerMode:true })
  .then(function(ok){ if(!ok)return;
    $.post('./mod/surat-keluar/proses.php?action=delete',{id:id},function(data){
      if (data.trim()==='success'){ swal({title:'Berhasil!',text:'Surat dihapus.',icon:'success',timer:1500});setTimeout(function(){location.reload()},1200); }
      else { swal({title:'Gagal!',text:data,icon:'error'}); }
    });
  });
});

$(document).on('click', '.btn-cetak-surat', function () { window.open('./mod/surat-keluar/proses.php?action=cetak&id='+$(this).data('id'), '_blank'); });
$(document).on('click', '.btn-export-surat-keluar', function () { location.href = './mod/surat-keluar/proses.php?action=export_excel'; });
