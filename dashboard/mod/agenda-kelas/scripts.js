/**
 * Agenda Kelas - Scripts (Jadwal + Agenda Input)
 */
$(document).ready(function () {
    var basePath = window.location.pathname.replace(/\/dashboard\/.*$/, '') + '/dashboard/mod/agenda-kelas/proses.php';

    // === MODAL HELPER (handles BS4+BS5 dual-load conflict) ===
    function showModal(selector) {
        var el = document.querySelector(selector);
        if (!el) return;
        // Try BS5 native first
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var inst = bootstrap.Modal.getOrCreateInstance(el, { backdrop: 'static', keyboard: false });
            inst.show();
        } else {
            $(selector).modal('show');
        }
    }
    function hideModal(selector) {
        var el = document.querySelector(selector);
        if (!el) return;
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var inst = bootstrap.Modal.getInstance(el);
            if (inst) inst.hide();
        } else {
            $(selector).modal('hide');
        }
    }

    // Close modal via .btn-close-modal buttons
    $(document).on('click', '.btn-close-modal', function () {
        hideModal('#modalAgenda');
    });

    // Reset button when modal fully hidden (both BS events)
    $(document).on('hidden.bs.modal', '#modalAgenda', function () {
        $('#btn-submit-agenda').prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Simpan Agenda');
    });

    // === TAB: JADWAL ===
    // Day selector — also show first panel on page load
    $('#jadwal-hari').on('change', function () {
        var hari = $(this).val();
        $('.jadwal-day-panel').hide();
        $('#panel-' + hari).show();
    }).trigger('change');

    // Auto-fill guru info when mapel selected
    $(document).on('change', '.jadwal-mapel-select', function () {
        var hari = $(this).data('hari');
        var jam = $(this).data('jam');
        var selected = $(this).find(':selected');
        var guru = selected.data('guru') || '';
        var namaMapel = selected.data('nama-mapel') || '';
        if (namaMapel) {
            $('#guru-' + hari + '-' + jam).html(namaMapel + (guru ? ' &middot; ' + guru : ''));
        } else {
            $('#guru-' + hari + '-' + jam).html('');
        }
    });

    // Edit Jadwal (unlock selects)
    $(document).on('click', '.btn-edit-jadwal', function () {
        var hari = $(this).data('hari');
        var panel = $('#panel-' + hari);
        panel.find('.jadwal-mapel-select').prop('disabled', false);
        $(this).hide();
        panel.find('.btn-simpan-jadwal').show();
    });

    // Simpan Jadwal
    $(document).on('click', '.btn-simpan-jadwal', function () {
        var hari = $(this).data('hari');
        var panel = $('#panel-' + hari);
        var items = [];
        panel.find('.jadwal-mapel-select').each(function () {
            var mapel_id = $(this).val();
            if (mapel_id) {
                items.push({ jam_ke: $(this).data('jam'), mapel_id: mapel_id });
            }
        });

        if (items.length === 0) {
            swal({ title: 'Oops!', text: 'Tidak ada jadwal yang diisi.', icon: 'warning', timer: 2500 });
            return;
        }

        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Menyimpan...');

        $.ajax({
            url: basePath,
            type: 'POST',
            dataType: 'json',
            data: { action: 'simpan_jadwal', hari: hari, items: JSON.stringify(items) },
            success: function (res) {
                swal({ title: res.status === 'success' ? 'Berhasil!' : 'Gagal!', text: res.message, icon: res.status === 'success' ? 'success' : 'error', timer: 2000, buttons: false });
                if (res.status === 'success') {
                    // Lock jadwal after save
                    panel.find('.jadwal-mapel-select').prop('disabled', true);
                    btn.hide();
                    // Show edit button (create if not exists)
                    var editBtn = panel.find('.btn-edit-jadwal');
                    if (editBtn.length) {
                        editBtn.show();
                    } else {
                        btn.before('<button class="btn btn-sm btn-warning btn-edit-jadwal mr-2" data-hari="' + hari + '"><i class="fas fa-edit mr-1"></i>Edit Jadwal ' + hari + '</button>');
                    }
                    setTimeout(function () { location.reload(); }, 2000);
                }
                btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Simpan Jadwal ' + hari);
            },
            error: function () {
                swal({ title: 'Error!', text: 'Terjadi kesalahan server', icon: 'error' });
                btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Simpan Jadwal ' + hari);
            }
        });
    });

    // === TAB: AGENDA ===
    // Load date
    $(document).on('click', '#btn-load-agenda', function () {
        var tanggal = $('#tanggal-agenda').val();
        if (!tanggal) return;
        window.location.href = './agenda-kelas?tanggal=' + tanggal;
    });

    // ---- Kehadiran Siswa Radio ----
    $('input[name="mode_kehadiran"]').on('change', function () {
        var mode = $(this).val();
        if (mode === 'ada_tidak_hadir') {
            $('#panel-siswa-tidak-hadir').slideDown(200);
        } else {
            $('#panel-siswa-tidak-hadir').slideUp(200);
            $('.cb-siswa-absen').prop('checked', false);
            updateSiswaCount();
        }
    });

    $(document).on('change', '.cb-siswa-absen', function () {
        updateSiswaCount();
    });

    function updateSiswaCount() {
        var tidakHadir = $('.cb-siswa-absen:checked').length;
        var hadir = SISWA_TOTAL - tidakHadir;
        $('#count-tidak-hadir').text(tidakHadir);
        $('#hid_siswa_hadir').val(hadir);
        $('#hid_siswa_tidak').val(tidakHadir);
    }

    // Open agenda input modal (grouped per mapel)
    $(document).on('click', '.btn-isi-agenda', function () {
        var btn = $(this);
        var jamList = btn.data('jam-list'); // array of jam_ke
        var guruId = btn.data('guru-id') || 0;
        var guruName = btn.data('guru') || '';

        $('#agenda_jam_list').val(JSON.stringify(jamList));
        $('#agenda_mapel_id').val(btn.data('mapel-id'));
        $('#agenda_guru_id').val(guruId);
        $('#agenda_tanggal').val($('#tanggal-agenda').val());

        $('#formAgenda')[0].reset();
        $('#agenda_mapel_nama').val(btn.data('mapel'));
        $('#agenda_guru_nama').val(guruName);

        // Hide guru field if no guru
        if (!guruId || guruId == 0) {
            $('#group_guru_nama').hide();
            $('#group_kehadiran_guru').hide();
            $('#agenda_kehadiran').val('Hadir');
        } else {
            $('#group_guru_nama').show();
            $('#group_kehadiran_guru').show();
        }

        // Reset student attendance
        $('input[name="mode_kehadiran"][value="semua_hadir"]').prop('checked', true).trigger('change');
        $('#hid_siswa_hadir').val(SISWA_TOTAL);
        $('#hid_siswa_tidak').val(0);

        $('#foto-preview').hide();
        showModal('#modalAgenda');
    });

    // Photo preview
    $(document).on('change', '#agenda_foto', function () {
        var file = this.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $('#foto-preview-img').attr('src', e.target.result);
                $('#foto-preview').show();
            };
            reader.readAsDataURL(file);
        } else {
            $('#foto-preview').hide();
        }
    });

    // Submit Agenda (supports multiple jam_ke)
    $(document).on('submit', '#formAgenda', function (e) {
        e.preventDefault();
        e.stopImmediatePropagation(); // prevent global form handler in footer
        var form = this;
        var formData = new FormData();

        // Manually add only the needed fields (avoid duplicate hidden inputs)
        formData.append('action', 'simpan_agenda');
        formData.append('tanggal', $('#agenda_tanggal').val());
        formData.append('jam_list', $('#agenda_jam_list').val());
        formData.append('mapel_id', $('#agenda_mapel_id').val());
        formData.append('guru_id', $('#agenda_guru_id').val());
        formData.append('kehadiran_guru', $('#agenda_kehadiran').val());
        formData.append('jumlah_siswa_hadir', $('#hid_siswa_hadir').val());
        formData.append('jumlah_siswa_tidak_hadir', $('#hid_siswa_tidak').val());
        formData.append('keterangan_materi', $(form).find('[name="keterangan_materi"]').val());

        // Collect absent student IDs
        var siswaAbsen = [];
        $('.cb-siswa-absen:checked').each(function () {
            siswaAbsen.push($(this).val());
        });
        formData.append('siswa_tidak_hadir_list', JSON.stringify(siswaAbsen));

        // Add photo if selected
        var fotoInput = document.getElementById('agenda_foto');
        if (fotoInput && fotoInput.files.length > 0) {
            formData.append('foto_bukti', fotoInput.files[0]);
        }

        var btn = $('#btn-submit-agenda');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Loading...');

        $.ajax({
            url: basePath,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            timeout: 60000,
            success: function (res) {
                if (res.status === 'success') {
                    hideModal('#modalAgenda');
                    try { swal({ title: 'Berhasil!', text: res.message, icon: 'success', timer: 2000, buttons: false }); } catch(e) { alert(res.message); }
                    setTimeout(function () { location.reload(); }, 2000);
                } else {
                    try { swal({ title: 'Gagal!', text: res.message, icon: 'error' }); } catch(e) { alert('Gagal: ' + res.message); }
                    btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Simpan Agenda');
                }
            },
            error: function (xhr, status, err) {
                var msg = 'Terjadi kesalahan server';
                if (status === 'timeout') msg = 'Request timeout, coba lagi';
                try { swal({ title: 'Error!', text: msg, icon: 'error' }); } catch(e) { alert(msg); }
                btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Simpan Agenda');
            }
        });
    });

    // Request Edit Agenda
    $(document).on('click', '.btn-request-edit-agenda', function () {
        var agendaId = $(this).data('agenda-id');
        swal({
            title: 'Minta Izin Edit Agenda',
            text: 'Tulis alasan kenapa ingin mengedit agenda:',
            content: {
                element: 'input',
                attributes: { placeholder: 'Contoh: Salah input materi', type: 'text' }
            },
            buttons: { cancel: 'Batal', confirm: { text: 'Kirim Permintaan', closeModal: false } }
        }).then(function (catatan) {
            if (!catatan && catatan !== '') return;
            $.ajax({
                url: basePath,
                type: 'POST',
                dataType: 'json',
                data: { action: 'request_edit_agenda', agenda_id: agendaId, catatan: catatan || '' },
                success: function (res) {
                    swal({ title: res.status === 'success' ? 'Terkirim!' : 'Gagal!', text: res.message, icon: res.status === 'success' ? 'success' : 'error', timer: 2000, buttons: false });
                    if (res.status === 'success') setTimeout(function () { location.reload(); }, 2000);
                },
                error: function () {
                    swal({ title: 'Error!', text: 'Terjadi kesalahan server', icon: 'error' });
                }
            });
        });
    });
});
