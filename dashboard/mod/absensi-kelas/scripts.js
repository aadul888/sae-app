/**
 * Absensi Kelas - Scripts with Lock/Edit/Token flow
 */
$(document).ready(function () {

    var basePath = window.location.pathname.replace(/\/dashboard\/.*$/, '') + '/dashboard/mod/absensi-kelas/proses.php';

    // Live clock
    setInterval(function () {
        var now = new Date();
        var h = String(now.getHours()).padStart(2, '0');
        var m = String(now.getMinutes()).padStart(2, '0');
        var s = String(now.getSeconds()).padStart(2, '0');
        $('#live-clock').text(h + ':' + m + ':' + s);
    }, 1000);

    // Radio pill visual highlight
    $(document).on('change', '.student-radios input[type="radio"]', function () {
        var $radios = $(this).closest('.student-radios');
        $radios.find('.radio-pill').removeClass('active');
        $(this).closest('.radio-pill').addClass('active');
    });

    // Hadir Semua
    $(document).on('click', '#btn-hadir-semua', function () {
        $('.student-radios').each(function () {
            var $radios = $(this);
            if ($radios.find('input[type="radio"]').prop('disabled')) return;
            var $hadir = $radios.find('input[value="Hadir"]');
            $hadir.prop('checked', true);
            $radios.find('.radio-pill').removeClass('active');
            $radios.find('.pill-hadir').addClass('active');
        });
    });

    // Pembelajaran Daring - otomatis hadir semua + jam sesuai jadwal
    $(document).on('click', '#btn-daring', function () {
        swal({
            title: 'Pembelajaran Daring',
            text: 'Semua siswa akan diabsen Hadir dengan jam masuk & pulang sesuai jadwal, serta keterangan "Pembelajaran Daring". Lanjutkan?',
            icon: 'info',
            buttons: {
                cancel: 'Batal',
                confirm: { text: 'Ya, Absen Daring', closeModal: false }
            }
        }).then(function (confirm) {
            if (!confirm) return;

            var tanggal = $('#tanggal-absensi').val() || new Date().toISOString().slice(0, 10);

            $.ajax({
                url: basePath,
                type: 'POST',
                dataType: 'json',
                data: { action: 'absen_daring', tanggal: tanggal },
                success: function (res) {
                    if (res.status === 'success') {
                        swal({ title: 'Berhasil!', text: res.message, icon: 'success', timer: 2500, buttons: false });
                        setTimeout(function () { location.reload(); }, 2500);
                    } else {
                        swal({ title: 'Gagal!', text: res.message, icon: 'error' });
                    }
                },
                error: function () {
                    swal({ title: 'Error!', text: 'Terjadi kesalahan server', icon: 'error' });
                }
            });
        });
    });

    // Simpan batch
    $(document).on('click', '#btn-simpan-absensi', function () {
        var tanggal = $('#tanggal-absensi').val() || new Date().toISOString().slice(0, 10);
        var items = [];

        $('.student-radios').each(function () {
            var userId = $(this).data('user-id');
            var checked = $(this).find('input[type="radio"]:checked').val();
            if (checked) {
                items.push({ user_id: userId, status: checked });
            }
        });

        if (items.length === 0) {
            swal({ title: 'Oops!', text: 'Belum ada siswa yang dipilih.', icon: 'warning', timer: 2500 });
            return;
        }

        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Menyimpan...');

        $.ajax({
            url: basePath,
            type: 'POST',
            dataType: 'json',
            data: { action: 'absen_batch', tanggal: tanggal, items: JSON.stringify(items) },
            success: function (res) {
                if (res.status === 'success') {
                    swal({ title: 'Berhasil!', text: res.message, icon: 'success', timer: 2000, buttons: false });
                    setTimeout(function () { location.reload(); }, 2000);
                } else {
                    swal({ title: 'Gagal!', text: res.message, icon: 'error' });
                    btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Simpan');
                }
            },
            error: function () {
                swal({ title: 'Error!', text: 'Terjadi kesalahan server', icon: 'error' });
                btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Simpan');
            }
        });
    });

    // Request edit permission from admin
    $(document).on('click', '#btn-request-edit', function () {
        swal({
            title: 'Minta Izin Edit',
            text: 'Tulis alasan kenapa ingin mengedit absensi:',
            content: {
                element: 'input',
                attributes: {
                    placeholder: 'Contoh: Ada siswa yang salah tercatat alpha',
                    type: 'text'
                }
            },
            buttons: { cancel: 'Batal', confirm: { text: 'Kirim Permintaan', closeModal: false } }
        }).then(function (catatan) {
            if (!catatan && catatan !== '') return;

            var tanggal = $('#tanggal-absensi').val() || new Date().toISOString().slice(0, 10);

            $.ajax({
                url: basePath,
                type: 'POST',
                dataType: 'json',
                data: { action: 'request_edit', tanggal: tanggal, catatan: catatan || '' },
                success: function (res) {
                    if (res.status === 'success') {
                        swal({ title: 'Terkirim!', text: res.message, icon: 'success', timer: 2000, buttons: false });
                        setTimeout(function () { location.reload(); }, 2000);
                    } else {
                        swal({ title: 'Gagal!', text: res.message, icon: 'error' });
                    }
                },
                error: function () {
                    swal({ title: 'Error!', text: 'Terjadi kesalahan server', icon: 'error' });
                }
            });
        });
    });

    // Load date
    $(document).on('click', '#btn-load-date', function () {
        var tanggal = $('#tanggal-absensi').val();
        if (!tanggal) return;
        window.location.href = './absensi-kelas?tanggal=' + tanggal;
    });

});