/** Portal GTK - Enhanced with Responsive Features */

// Responsive handling
$(document).ready(function() {
  
  // Initialize responsive features
  initResponsiveFeatures();
  
  // Handle window resize
  $(window).on('resize', debounce(function() {
    handleResponsiveResize();
  }, 250));
  
});

// Responsive initialization
function initResponsiveFeatures() {
  // Optimize for mobile
  if ($(window).width() < 768) {
    $('body').addClass('mobile-view');
    optimizeForMobile();
  } else {
    $('body').removeClass('mobile-view');
  }
  
  // Handle card heights
  equalizeCardHeights();
}

// Handle responsive resize
function handleResponsiveResize() {
  const windowWidth = $(window).width();
  
  // Toggle mobile class
  if (windowWidth < 768) {
    $('body').addClass('mobile-view');
    optimizeForMobile();
  } else {
    $('body').removeClass('mobile-view');
  }
  
  // Re-equalize heights
  equalizeCardHeights();
  
  // Close dropdowns on mobile orientation change
  if (windowWidth < 768) {
    $('.dropdown-menu.show').removeClass('show');
  }
}

// Mobile optimizations
function optimizeForMobile() {
  // Lazy load images on mobile
  $('.custom-app-icon').each(function() {
    if (!$(this).attr('data-loaded')) {
      const $img = $(this);
      $img.attr('data-loaded', 'true');
    }
  });
  
  // Optimize touch interactions
  $('.portal-app-card').off('touchstart touchend');
  $('.portal-app-card').on('touchstart', function() {
    $(this).addClass('touch-active');
  }).on('touchend', function() {
    $(this).removeClass('touch-active');
  });
}

// Equalize card heights in same row
function equalizeCardHeights() {
  const $cards = $('.portal-app-card');
  
  // Reset heights
  $cards.css('height', 'auto');
  
  // Only equalize on desktop
  if ($(window).width() >= 768) {
    let maxHeight = 0;
    $cards.each(function() {
      const height = $(this).outerHeight();
      if (height > maxHeight) {
        maxHeight = height;
      }
    });
    
    if (maxHeight > 0) {
      $cards.css('min-height', maxHeight + 'px');
    }
  }
}

// Debounce utility
function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

/** Portal GTK - Card Click Disabled */
// Card click dinonaktifkan - hanya bisa akses melalui tombol AKSES
$(document).on("click", ".portal-app-card", function (e) {
  // Biarkan tombol-tombol tertentu berfungsi
  if ($(e.target).closest('.btn-manage-credential, .btn-access-app, .app-actions, .dropdown').length > 0) {
    return; // Biarkan event berlanjut untuk tombol-tombol ini
  }
  
  // Mencegah card click, hanya tombol yang bisa diklik
  e.preventDefault();
  e.stopPropagation();
});

// Portal GTK Functions

/** Helper function to open credential modal */
function openCredentialModal(appName, appId, credentialId) {
  
  // Cast credentialId to integer
  credentialId = parseInt(credentialId) || 0;
  
  // Cari app_id jika tidak ada
  if (!appId) {
    const $appCard = $(`.portal-app-card[data-app="${appName}"]`);
    
    if ($appCard.length > 0) {
      const $credBtn = $appCard.find('.btn-manage-credential');
      
      if ($credBtn.length > 0) {
        appId = $credBtn.data('id');
        credentialId = parseInt($credBtn.data('credential-id')) || 0;
      }
    }
    
    // Fallback: cari dari semua tombol credential
    if (!appId) {
      const $allCredBtns = $('.btn-manage-credential');
      
      $allCredBtns.each(function() {
        if ($(this).data('name') === appName) {
          appId = $(this).data('id');
          credentialId = parseInt($(this).data('credential-id')) || 0;
          return false; // break
        }
      });
    }
  }
  
  if (!appId) {
    console.error('Cannot find app_id for:', appName);
    try {
      swal({
        title: 'Error!',
        text: 'Tidak dapat menemukan data aplikasi',
        icon: 'error',
        button: 'OK'
      });
    } catch(e) {
      alert('Error: Tidak dapat menemukan data aplikasi');
    }
    return;
  }
  
  // Set form values
  $('#credential_app_id').val(appId);
  $('#credential_app_name').val(appName);
  $('#credential_id').val(credentialId);
  
  // Reset form
  $('#formCredential')[0].reset();
  $('#formCredential').removeClass('was-validated');
  $('#credential_app_id').val(appId);
  $('#credential_app_name').val(appName);
  $('#credential_id').val(credentialId);
  
  if (credentialId > 0) {
    // Load existing credential
    $('#modalCredentialLabel').text('Edit Kredensial - ' + appName);
    $('#btnDeleteCredential').show();
    loadCredential(appId);
  } else {
    // New credential
    $('#modalCredentialLabel').text('Tambah Kredensial - ' + appName);
    $('#btnDeleteCredential').hide();
  }
  
  // Show modal
  const $modal = $('#modalCredential');
  
  if ($modal.length === 0) {
    console.error('FATAL: Modal #modalCredential tidak ditemukan di DOM!');
    alert('Error: Modal kredensial tidak ditemukan!');
    return;
  }
  
  try {
    // Coba metode Bootstrap dulu
    if (typeof $.fn.modal !== 'undefined') {
      $modal.off('shown.bs.modal.credential').on('shown.bs.modal.credential', function() {
        $('#app_username').focus();
      });
      
      $modal.off('hidden.bs.modal.credential').on('hidden.bs.modal.credential', function() {
      });
      
      $modal.modal('show');
      
      // Check if modal appeared after 1 second
      setTimeout(function() {
        if (!$modal.hasClass('show')) {
          console.warn('⚠️ Bootstrap modal failed, trying manual method...');
          showModalManually($modal);
        } else {
        }
      }, 1000);
      
    } else {
      console.warn('Bootstrap modal not available, using manual method...');
      showModalManually($modal);
    }
    
  } catch (error) {
    console.error('❌ Error showing modal:', error);
    showModalManually($modal);
  }
}

// Smooth modal animations handled by Bootstrap and CSS

/** Copy Account Field Function - Enhanced for Modal */
function copyAccountField(value, fieldType) {
  copyToClipboard(value);
  
  // Enhanced visual feedback
  const toast = $(`<div class="toast-notification success">✅ ${fieldType} tersalin!</div>`);
  $('body').append(toast);
  toast.addClass('show');
  
  setTimeout(function() {
    toast.remove();
  }, 2500);
}

/** Toggle Modal Password Visibility */
function toggleModalPassword() {
  const passwordField = document.getElementById('modalPasswordField');
  const toggleIcon = document.getElementById('modalPasswordToggle');
  
  if (passwordField && toggleIcon) {
    if (passwordField.type === 'password') {
      passwordField.type = 'text';
      toggleIcon.className = 'fas fa-eye-slash';
    } else {
      passwordField.type = 'password';
      toggleIcon.className = 'fas fa-eye';
    }
  }
}

/** Copy to Clipboard Function */
function copyToClipboard(text) {
  navigator.clipboard.writeText(text).then(function() {
    // Tampilkan notifikasi sukses
    const toast = $('<div class="toast-notification">Disalin ke clipboard!</div>');
    $('body').append(toast);
    toast.addClass('show');
    
    setTimeout(function() {
      toast.remove();
    }, 2000);
  }).catch(function() {
    // Fallback untuk browser lama
    const textArea = document.createElement('textarea');
    textArea.value = text;
    document.body.appendChild(textArea);
    textArea.select();
    document.execCommand('copy');
    document.body.removeChild(textArea);
    
    const toast = $('<div class="toast-notification">Disalin ke clipboard!</div>');
    $('body').append(toast);
    toast.addClass('show');
    
    setTimeout(function() {
      toast.remove();
    }, 2000);
  });
}

/** Search Apps Function */
function searchApps() {
  const searchTerm = $('#app-search').val().toLowerCase();
  
  $('.portal-app-card').each(function() {
    const appName = $(this).data('app').toLowerCase();
    const appDesc = $(this).find('.app-description').text().toLowerCase();
    
    if (appName.includes(searchTerm) || appDesc.includes(searchTerm)) {
      $(this).parent().show();
    } else {
      $(this).parent().hide();
    }
  });
}

// CRUD Operations

/** Form App Submission */
$(document).on('submit', '#formApp', function(e) {
  e.preventDefault();
  
  if (!this.checkValidity()) {
    e.stopPropagation();
    $(this).addClass('was-validated');
    return;
  }
  
  const formData = new FormData(this);
  const isEdit = $('#app_id').val() !== '';
  
  // Cek apakah ada file yang benar-benar dipilih untuk upload
  const fileInput = document.getElementById('icon_file');
  const hasValidFile = fileInput && fileInput.files && fileInput.files.length > 0 && fileInput.files[0].size > 0;
  
  // Jika tidak ada file valid tapi icon_type adalah upload, hapus file dari FormData
  if (!hasValidFile && formData.has('icon_file')) {
    formData.delete('icon_file');
  }
  
  // Debug: pastikan icon_type ter-set dengan benar
  const iconType = $('#icon_type').val();
  if (!iconType) {
    // Fallback jika icon_type kosong
    const checkedOption = $('input[name="icon_option"]:checked');
    if (checkedOption.length > 0) {
      const optionValue = checkedOption.val();
      $('#icon_type').val(optionValue);
      formData.set('icon_type', optionValue);
    } else {
      // Default ke font jika tidak ada yang checked
      $('#icon_type').val('font');
      formData.set('icon_type', 'font');
    }
  }
  
  $('#btnSubmit').addClass('loading').prop('disabled', true);
  
  // Add upload progress feedback hanya jika benar-benar ada file baru
  const hasFileUpload = hasValidFile && formData.has('icon_file') && formData.get('icon_file').size > 0;
  if (hasFileUpload) {
    swal({
      title: 'Mengupload...',
      text: 'Sedang mengupload icon, mohon tunggu...',
      icon: 'info',
      buttons: false,
      closeOnClickOutside: false,
      closeOnEsc: false
    });
  }
  
  $.ajax({
    url: 'mod/portal-gtk/proses.php?action=app-save',
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    cache: false,  // Tambah cache: false seperti user module
    timeout: 30000, // 30 detik timeout
    success: function(response) {
      // Tutup loading dialog jika ada
      try {
        swal.close();
      } catch(e) {}
      
      // Parse response dengan error handling yang aman
      let data;
      try {
        if (typeof response === 'string') {
          // Bersihkan response dari whitespace dan karakter tidak valid
          const cleanResponse = response.trim();
          data = JSON.parse(cleanResponse);
        } else if (typeof response === 'object' && response !== null) {
          data = response;
        } else {
          throw new Error('Invalid response format');
        }
      } catch(e) {
        // Jika parsing gagal, cek apakah mungkin sukses tapi format salah
        const responseStr = String(response).trim();
        if (responseStr.includes('success') || responseStr.includes('berhasil')) {
          data = { status: 'success', message: 'Data berhasil disimpan' };
        } else {
          data = { status: 'error', message: 'Server response error' };
        }
      }
      
      // Handle success response
      if (data.status === 'success') {
        try {
          swal({
            title: 'Berhasil!',
            text: data.message || 'Data berhasil disimpan',
            icon: 'success',
            button: 'OK'
          }).then(function() {
            try {
              $('#modalAddApp').modal('hide');
            } catch(e) {}
            // Delay reload sedikit untuk memastikan modal tertutup
            setTimeout(function() {
              location.reload();
            }, 500);
          });
        } catch(e) {
          // Fallback jika SweetAlert bermasalah
          alert('Data berhasil disimpan');
          $('#modalAddApp').modal('hide');
          location.reload();
        }
      } else {
        // Handle error response
        try {
          const errorMessage = data.message || 'Terjadi kesalahan yang tidak diketahui';
          swal({
            title: 'Upload Gagal!',
            text: errorMessage,
            icon: 'error',
            button: 'OK'
          });
        } catch(e) {
          // Fallback jika SweetAlert bermasalah
          alert('Upload gagal: ' + (data.message || 'Error tidak diketahui'));
        }
      }
    },
    error: function(xhr, status, error) {
      // Tutup loading dialog jika ada
      try {
        swal.close();
      } catch(e) {}
      
      let errorMessage = 'Terjadi kesalahan sistem';
      
      if (status === 'timeout') {
        errorMessage = 'Upload timeout. Coba file yang lebih kecil atau periksa koneksi internet.';
      } else if (xhr.responseText) {
        try {
          const response = JSON.parse(xhr.responseText);
          errorMessage = response.message || errorMessage;
        } catch (e) {
          // If not JSON, show first 200 chars of response
          errorMessage += ':\n' + xhr.responseText.substring(0, 200);
        }
      }
      
      try {
        swal({
          title: 'Error Upload!',
          text: errorMessage,
          icon: 'error',
          button: 'OK'
        });
      } catch(e) {
        // Fallback jika SweetAlert bermasalah
        alert('Error Upload: ' + errorMessage);
      }
      
      console.error('Upload Error:', {
        status: status,
        error: error,
        response: xhr.responseText
      });
    },
    complete: function() {
      $('#btnSubmit').removeClass('loading').prop('disabled', false);
    }
  });
});

/** Edit App */
$(document).on('click', '.btn-edit-app', function(e) {
  e.preventDefault();
  e.stopPropagation();
  
  const data = $(this).data();
  
  $('#modalTitle').text('Edit Aplikasi Portal');
  $('#app_id').val(data.id);
  $('#app_name').val(data.name);
  $('#app_url').val(data.url);
  $('#app_icon').val(data.icon);
  $('#app_description').val(data.description);
  $('#app_category').val(data.category);
  
  // Handle custom icon dengan system baru - Enhanced Debug
  if (data.customIcon && data.customIcon.trim && data.customIcon.trim().length > 0) {
    // Set upload option as checked
    $('#iconOptionUpload').prop('checked', true);
    $('#icon_type').val('upload');
    $('#current_icon_file').val(data.customIcon);
    
    // Update visual states
    $('.icon-option').removeClass('active');
    $('#iconOptionUpload').closest('.icon-option').addClass('active');
    $('#fontIconGroup').hide();
    $('#uploadIconGroup').show();
    
    // Set preview dengan path yang benar untuk icon-apps
    const previewPath = '../content/icon-apps/' + data.customIcon;
    
    // Show preview immediately
    $('#uploadPreview').show();
    
    // Clean up previous events first
    $('#previewImg').off('error load');
    
    $('#previewImg').attr('src', previewPath).on('error', function() {
      console.warn('❌ Error loading icon preview:', previewPath);
      // Jika gagal load preview, tetap di upload tab tapi tanpa preview
      $('#previewImg').attr('src', '').hide();
    }).on('load', function() {
      $('#previewImg').show();
      
      // Add replace button for existing icon
      if (!$('#replaceIconBtn').length) {
        $('#uploadPreview').append(`
          <button type="button" class="btn btn-sm btn-warning ml-2" id="replaceIconBtn" title="Ganti dengan icon baru">
            <i class="fas fa-sync-alt"></i> Ganti
          </button>
        `);
      }
    });
    
    // Set custom file label to show current icon filename
    $('.custom-file-label').html('Current: ' + data.customIcon);
    
    // IMPORTANT: Reset file input untuk edit - tidak ada file baru dipilih
    $('#icon_file').val('');
    
  } else {
    // Set font option as checked
    $('#iconOptionFont').prop('checked', true);
    $('#icon_type').val('font');
    
    // Update visual states
    $('.icon-option').removeClass('active');
    $('#iconOptionFont').closest('.icon-option').addClass('active');
    $('#fontIconGroup').show();
    $('#uploadIconGroup').hide();
    $('#uploadPreview').hide();
    
    // Reset file input untuk mode font
    $('#icon_file').val('');
    $('.custom-file-label').html('Pilih file icon (PNG/JPG, max 1MB)');
    
    // Update icon preview with saved icon
    updateIconPreview(data.icon || 'fas fa-globe');
  }
  
  $('#modalAddApp').modal('show');
});

/** Delete App */
$(document).on('click', '.btn-delete-app', function(e) {
  e.preventDefault();
  e.stopPropagation();
  
  const id = $(this).data('id');
  const name = $(this).data('name');
  const customIcon = $(this).data('custom-icon') || '';
  
  const deleteText = customIcon ? 
    `Aplikasi "${name}" dan file icon-nya akan dihapus permanent.` : 
    `Aplikasi "${name}" akan dihapus permanent.`;
  
  swal({
    title: 'Hapus Aplikasi?',
    text: deleteText,
    icon: 'warning',
    buttons: ['Batal', 'Hapus'],
    dangerMode: true
  }).then((willDelete) => {
    if (willDelete) {
      $.ajax({
        url: 'mod/portal-gtk/proses.php?action=app-delete',
        type: 'POST',
        data: { 
          app_id: id,
          custom_icon: customIcon
        },
        dataType: 'json',
        success: function(response) {
          if (response.status === 'success') {
            try {
              swal({
                title: 'Berhasil!',
                text: response.message || 'Data berhasil dihapus',
                icon: 'success',
                button: 'OK'
              }).then(function() {
                location.reload();
              });
            } catch(e) {
              alert('Data berhasil dihapus');
              location.reload();
            }
          } else {
            try {
              swal({
                title: 'Error!',
                text: response.message || 'Terjadi kesalahan',
                icon: 'error',
                button: 'OK'
              });
            } catch(e) {
              alert('Error: ' + (response.message || 'Terjadi kesalahan'));
            }
          }
        },
        error: function() {
          try {
            swal({
              title: 'Error!',
              text: 'Terjadi kesalahan sistem',
              icon: 'error',
              button: 'OK'
            });
          } catch(e) {
            alert('Error: Terjadi kesalahan sistem');
          }
        }
      });
    }
  });
});

/** Toggle App Status */
$(document).on('click', '.btn-toggle-app', function(e) {
  e.preventDefault();
  e.stopPropagation();
  
  const id = $(this).data('id');
  const status = $(this).data('status');
  const actionText = status === 'Y' ? 'mengaktifkan' : 'menonaktifkan';
  
  swal({
    title: 'Konfirmasi',
    text: `Yakin ingin ${actionText} aplikasi ini?`,
    icon: 'info',
    buttons: ['Batal', 'Ya'],
  }).then((willToggle) => {
    if (willToggle) {
      $.ajax({
        url: 'mod/portal-gtk/proses.php?action=app-toggle',
        type: 'POST',
        data: { 
          app_id: id,
          status: status
        },
        dataType: 'json',
        success: function(response) {
          if (response.status === 'success') {
            try {
              swal({
                title: 'Berhasil!',
                text: response.message || 'Status berhasil diubah',
                icon: 'success',
                button: 'OK'
              }).then(function() {
                location.reload();
              });
            } catch(e) {
              alert('Status berhasil diubah');
              location.reload();
            }
          } else {
            try {
              swal({
                title: 'Error!',
                text: response.message || 'Terjadi kesalahan',
                icon: 'error',
                button: 'OK'
              });
            } catch(e) {
              alert('Error: ' + (response.message || 'Terjadi kesalahan'));
            }
          }
        },
        error: function() {
          try {
            swal({
              title: 'Error!',
              text: 'Terjadi kesalahan sistem',
              icon: 'error',
              button: 'OK'
            });
          } catch(e) {
            alert('Error: Terjadi kesalahan sistem');
          }
        }
      });
    }
  });
});

/** Icon Tab Handlers - Updated for Radio Button */
$(document).on('change', 'input[name="icon_option"]', function() {
  const selectedValue = $(this).val();
  
  // Update icon_type hidden field
  $('#icon_type').val(selectedValue);
  
  // Update visual states
  $('.icon-option').removeClass('active');
  $(this).closest('.icon-option').addClass('active');
  
  // Show/hide appropriate input groups
  if (selectedValue === 'font') {
    $('#fontIconGroup').show();
    $('#uploadIconGroup').hide();
    
    // Clear upload data when switching to font (only if not in edit mode)
    if (!$('#app_id').val()) {
      $('#icon_file').val('');
      $('#uploadPreview').hide();
      $('.custom-file-label').html('Pilih file icon (PNG/JPG, max 1MB)');
      $('#current_icon_file').val('');
    }
    
  } else {
    $('#fontIconGroup').hide();
    $('#uploadIconGroup').show();
    
    // Clear font icon when switching to upload (only if not in edit mode)  
    if (!$('#app_id').val()) {
      $('#app_icon').val('fas fa-globe');
      updateIconPreview('fas fa-globe');
    }
  }
});

// Initialize icon option state on page load
$(document).ready(function() {
  // Set initial active state
  $('input[name="icon_option"]:checked').closest('.icon-option').addClass('active');
  
  // Add change handler for file input to show replacement message
  $(document).on('change', '#icon_file', function() {
    const currentIcon = $('#current_icon_file').val();
    if (currentIcon && currentIcon.length > 0) {
      swal({
        title: 'Ganti Icon?',
        text: 'Icon lama akan digantikan dengan icon baru yang Anda upload.',
        icon: 'info',
        buttons: ['Batal', 'Ganti'],
      }).then((willReplace) => {
        if (!willReplace) {
          // Reset file input if user cancels
          $(this).val('');
          $('.custom-file-label').html('Pilih file icon (PNG/JPG, max 1MB)');
        }
      });
    }
  });
});

/** File Upload Preview */
$(document).on('change', '#icon_file', function(e) {
  const file = e.target.files[0];
  if (file) {
    // Validasi file
    if (!file.type.match('image.*')) {
      try {
        swal({
          title: 'Error!',
          text: 'File harus berupa gambar',
          icon: 'error',
          button: 'OK'
        });
      } catch(e) {
        alert('Error: File harus berupa gambar');
      }
      this.value = '';
      return;
    }
    
    if (file.size > 1048576) { // 1MB
      try {
        swal({
          title: 'Error!',
          text: 'Icon terlalu besar! Maksimal 1MB.',
          icon: 'error',
          button: 'OK'
        });
      } catch(e) {
        alert('Error: Icon terlalu besar! Maksimal 1MB.');
      }
      this.value = '';
      return;
    }
    
    // Show preview
    const reader = new FileReader();
    reader.onload = function(e) {
      $('#previewImg').attr('src', e.target.result);
      $('#uploadPreview').show();
    };
    reader.readAsDataURL(file);
    
    // Update file label
    const fileName = file.name;
    $(this).next('.custom-file-label').html(fileName);
  }
});

/** Remove Preview */
$(document).on('click', '#removePreview', function() {
  $('#icon_file').val('');
  $('#uploadPreview').hide();
  $('.custom-file-label').html('Pilih file icon (PNG/JPG, max 1MB)');
  $('#current_icon_file').val('');
});

/** Replace Icon Button */
$(document).on('click', '#replaceIconBtn', function() {
  swal({
    title: 'Ganti Icon?',
    text: 'Pilih icon baru untuk menggantikan icon yang sudah ada.',
    icon: 'info',
    buttons: ['Batal', 'Pilih Icon Baru'],
  }).then((willReplace) => {
    if (willReplace) {
      $('#icon_file').trigger('click');
    }
  });
});

/** Icon Preview Update */
$(document).on('input', '#app_icon', function() {
  updateIconPreview($(this).val());
});

function updateIconPreview(iconClass) {
  const preview = $('#iconPreview i');
  preview.attr('class', iconClass || 'fas fa-globe');
}

/** Reset Modal */
$('#modalAddApp').on('hidden.bs.modal', function() {
  $('#formApp')[0].reset();
  $('#formApp').removeClass('was-validated');
  $('#modalTitle').text('Tambah Aplikasi Portal');
  $('#app_id').val('');
  $('#icon_type').val('font');
  $('#current_icon_file').val('');
  $('#uploadPreview').hide();
  $('#previewImg').attr('src', '').off('error load'); // Clean up image events
  $('.custom-file-label').html('Pilih file icon (PNG/JPG, max 1MB)');
  
  // Remove replace button if exists
  $('#replaceIconBtn').remove();
  
  // Reset icon options to font by default
  $('#iconOptionFont').prop('checked', true);
  $('.icon-option').removeClass('active');
  $('#iconOptionFont').closest('.icon-option').addClass('active');
  $('#fontIconGroup').show();
  $('#uploadIconGroup').hide();
  
  // Remove replace button if exists
  $('#replaceIconBtn').remove();
  
  // Reset radio buttons
  $('#iconOptionFont').prop('checked', true);
  $('#iconOptionUpload').prop('checked', false);
  $('.icon-option').removeClass('active');
  $('#iconOptionFont').closest('.icon-option').addClass('active');
  $('#fontIconGroup').show();
  $('#uploadIconGroup').hide();
  
  updateIconPreview('fas fa-globe');
});

/** Reset Credential Modal */
$('#modalCredential').on('hidden.bs.modal', function() {
    // Reset form dengan smooth transition
    const $form = $('#formCredential');
    const $inputs = $form.find('.form-control');
    
    // Add fade out effect
    $inputs.addClass('fade-out');
    
    setTimeout(() => {
      $form[0].reset();
      $form.removeClass('was-validated');
      $inputs.prop('disabled', false).removeClass('loading-input fade-out');
      
      // Clear hidden fields
      $('#credential_id').val('');
      $('#credential_app_id').val('');
      $('#credential_app_name').val('');
      
      // Hide delete button
      $('#btnDeleteCredential').hide();
      
      // Reset password field type
      $('#app_password').attr('type', 'password');
      $('#togglePassword i').removeClass('fa-eye-slash').addClass('fa-eye');
      
      // Re-enable form submission with smooth animation
      const $submitBtn = $('#btnSaveCredential');
      $submitBtn.prop('disabled', false)
                .removeClass('btn-loading')
                .html('<i class="fas fa-save"></i> Simpan');
      
      // Reset modal title
      $('#modalCredentialLabel').text('Kelola Kredensial');
    }, 150);
});

/** Initialize Portal */
$(document).ready(function() {
  // Debug: cek apakah tombol-tombol ada
  setTimeout(() => {
    // Debug dropdown visibility
    $('.app-actions').each(function(index) {
      // Check visibility for debugging
    });
    
    // Force dropdown visibility for admin
    $('.app-actions').css({
      'opacity': '1 !important',
      'visibility': 'visible !important',
      'display': 'flex !important'
    });
    
    // Initialize Bootstrap dropdowns jika belum ter-initialize
    if (typeof $.fn.dropdown !== 'undefined') {
      $('.dropdown-toggle').dropdown();
    } else {
      console.warn('Bootstrap dropdown not available');
    }
    
    // Test dropdown functionality
    $('.dropdown-toggle').on('shown.bs.dropdown', function() {
      // Dropdown shown
    });
    
    $('.dropdown-toggle').on('hidden.bs.dropdown', function() {
      // Dropdown hidden
    });
  }, 1000);
  
  // Tambahkan search box jika ada aplikasi
  if ($('.portal-app-card').length > 0) {
    $('.portal-apps-grid').before(`
      <div class="search-box mb-4">
        <div class="input-group">
          <div class="input-group-prepend">
            <span class="input-group-text"><i class="fas fa-search"></i></span>
          </div>
          <input type="text" class="form-control" id="app-search" placeholder="Cari aplikasi...">
        </div>
      </div>
    `);
    
    // Event listener untuk search
    $(document).on('input', '#app-search', searchApps);
  }
  
  // Hover effect untuk app cards - disabled karena card tidak clickable
  $(document).on('mouseenter', '.portal-app-card', function() {
    // Card hover disabled - only buttons are interactive
    // $(this).addClass('hover');
  }).on('mouseleave', '.portal-app-card', function() {
    // $(this).removeClass('hover');
  });
  
  // Stop propagation untuk action buttons container tapi biarkan dropdown berfungsi
  $(document).on('click', '.app-actions', function(e) {
    // Jangan stop propagation untuk dropdown toggle dan dropdown menu
    if (!$(e.target).closest('.dropdown-toggle, .dropdown-menu').length) {
      e.stopPropagation();
    }
  });
  
  // Stop propagation untuk user action buttons
  $(document).on('click', '.user-actions', function(e) {
    e.stopPropagation();
  });
  
  // Stop propagation hanya untuk tombol tertentu, bukan dropdown elements  
  $(document).on('click', '.btn-access-app', function(e) {
    e.stopPropagation();
  });
  
  // Jangan stop propagation untuk btn-manage-credential agar modal bisa muncul
  // $(document).on('click', '.btn-manage-credential', function(e) {
  //   e.stopPropagation();
  // });
  
  // Ensure dropdown can work properly
  $(document).on('click', '.dropdown-toggle', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    // Fallback manual dropdown jika Bootstrap tidak berfungsi
    const $dropdown = $(this).next('.dropdown-menu');
    const $allDropdowns = $('.dropdown-menu');
    
    // Tutup semua dropdown lain
    $allDropdowns.not($dropdown).removeClass('show');
    
    // Hitung posisi dropdown untuk fixed positioning
    const $button = $(this);
    const buttonRect = $button[0].getBoundingClientRect();
    const windowHeight = window.innerHeight;
    
    // Tentukan posisi vertikal
    let top;
    if (buttonRect.bottom + 150 > windowHeight) {
      // Tampilkan di atas jika tidak cukup ruang di bawah
      top = (buttonRect.top + window.scrollY - 150);
    } else {
      // Tampilkan di bawah
      top = (buttonRect.bottom + window.scrollY + 5);
    }
    
    // Set posisi dropdown menggunakan fixed positioning
    $dropdown.css({
      'position': 'fixed',
      'top': top + 'px',
      'left': (buttonRect.right - 140) + 'px', // Align kanan dengan tombol
      'z-index': '999999',
      'min-width': '140px'
    });
    
    // Toggle dropdown ini
    $dropdown.toggleClass('show');
  });
  
  // Tutup dropdown saat klik di luar
  $(document).on('click', function(e) {
    if (!$(e.target).closest('.dropdown').length) {
      $('.dropdown-menu').removeClass('show');
    }
  });

  // Credential Management

  /** Manage Credential Button */
  $(document).on('click', '.btn-manage-credential', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const appId = $(this).data('id');
    const appName = $(this).data('name');
    const credentialId = parseInt($(this).data('credential-id')) || 0;
    
    // Validasi data
    if (!appId || !appName) {
      try {
        swal({
          title: 'Error!',
          text: 'Data aplikasi tidak lengkap',
          icon: 'error',
          button: 'OK'
        });
      } catch(e) {
        alert('Error: Data aplikasi tidak lengkap');
      }
      return;
    }
    
    const $modal = $('#modalCredential');
    
    if ($modal.length === 0) {
      try {
        swal({
          title: 'Error!',
          text: 'Modal kredensial tidak ditemukan',
          icon: 'error',
          button: 'OK'
        });
      } catch(e) {
        alert('Error: Modal kredensial tidak ditemukan');
      }
      return;
    }
    
    // Set modal title dan form values
    $('#modalCredentialLabel').text(credentialId > 0 ? 'Edit Kredensial - ' + appName : 'Tambah Kredensial - ' + appName);
    $('#credential_app_id').val(appId);
    $('#credential_app_name').val(appName);
    $('#credential_id').val(credentialId);
    
    // Reset form dengan smooth transition
    $('#formCredential')[0].reset();
    $('#formCredential').removeClass('was-validated');
    $('#credential_app_id').val(appId);
    $('#credential_app_name').val(appName);
    $('#credential_id').val(credentialId);
    
    // Show/hide delete button dan load data
    if (credentialId > 0) {
      $('#btnDeleteCredential').show();
      loadCredential(appId);
    } else {
      $('#btnDeleteCredential').hide();
    }
    
    // Show modal dengan smooth animation
    $modal.modal({
      backdrop: 'static',
      keyboard: true,
      focus: true
    });
    
    // Focus on first input after modal is fully shown
    $modal.one('shown.bs.modal', function() {
      setTimeout(() => $('#app_username').focus(), 100);
    });
  });

  /** Load Credential Data */
  function loadCredential(appId) {
    if (!appId) return;
    
    // Show loading state dengan smooth animation
    const $form = $('#formCredential');
    const $inputs = $form.find('.form-control');
    
    $inputs.prop('disabled', true).addClass('loading-input');
    $('#app_username, #app_password, #notes').val('Memuat...');
    
    $.ajax({
      url: 'mod/portal-gtk/proses.php?action=credential-get',
      type: 'POST',
      data: { app_id: appId },
      dataType: 'json',
      success: function(response) {
        if (response.status === 'success' && response.data) {
          const data = response.data;
          $('#credential_id').val(data.id);
          $('#app_username').val(data.app_username);
          $('#app_password').val(data.app_password);
          $('#notes').val(data.notes || '');
        } else {
          // Clear if no data
          $('#app_username, #app_password, #notes').val('');
          if (response.message) {
            try {
              swal({
                title: 'Info',
                text: response.message,
                icon: 'info',
                button: 'OK'
              });
            } catch(e) {
              alert('Info: ' + response.message);
            }
          }
        }
      },
      error: function() {
        $('#app_username, #app_password, #notes').val('');
        try {
          swal({
            title: 'Error!',
            text: 'Gagal memuat data kredensial',
            icon: 'error',
            button: 'OK'
          });
        } catch(e) {
          alert('Error: Gagal memuat data kredensial');
        }
      },
      complete: function() {
        // Re-enable form dengan delay untuk smooth transition
        setTimeout(() => {
          $inputs.prop('disabled', false).removeClass('loading-input');
        }, 300);
      }
    });
  }

  /** Save Credential Form */
  $(document).on('submit', '#formCredential', function(e) {
    e.preventDefault();
    
    if (!this.checkValidity()) {
      e.stopPropagation();
      $(this).addClass('was-validated');
      return;
    }
    
    const formData = new FormData(this);
    const credentialId = $('#credential_id').val();
    const isEdit = credentialId && credentialId > 0;
    
    // Show loading state dengan smooth animation
    const $submitBtn = $('#btnSaveCredential');
    $submitBtn.prop('disabled', true)
             .html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...')
             .addClass('btn-loading');
    
    $.ajax({
      url: 'mod/portal-gtk/proses.php?action=credential-save',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      dataType: 'json',
      success: function(response) {
        // Parse response dengan error handling yang aman
        let data;
        try {
          if (typeof response === 'string') {
            const cleanResponse = response.trim();
            data = JSON.parse(cleanResponse);
          } else if (typeof response === 'object' && response !== null) {
            data = response;
          } else {
            throw new Error('Invalid response format');
          }
        } catch(e) {
          // Jika parsing gagal, cek apakah mungkin sukses
          const responseStr = String(response).trim();
          if (responseStr.includes('success') || responseStr.includes('berhasil')) {
            data = { status: 'success', message: 'Data berhasil disimpan' };
          } else {
            data = { status: 'error', message: 'Server response error' };
          }
        }
        
        if (data.status === 'success') {
          try {
            swal({
              title: 'Berhasil!',
              text: data.message || 'Kredensial berhasil disimpan',
              icon: 'success',
              button: 'OK'
            }).then(function() {
              try {
                $('#modalCredential').modal('hide');
                
                // Update button data tanpa reload
                const appId = $('#credential_app_id').val();
                const newCredentialId = data.credential_id || credentialId || 1;
                
                // Update credential badge and button data
                const $appCard = $(`[data-app-id="${appId}"]`);
                
                if ($appCard.length > 0) {
                  // Update credential badge
                  const $badge = $appCard.find('.credential-badge');
                  $badge.removeClass('no-credential')
                        .addClass('credential-exists')
                        .html('<i class="fas fa-key"></i> Tersimpan');
                  
                  // Update button data
                  const $credentialBtn = $appCard.find('.btn-manage-credential');
                  $credentialBtn.data('credential-id', newCredentialId)
                               .attr('data-credential-id', newCredentialId);
                } else {
                  // Alternative selector
                  const $altCard = $(`.portal-app-card[data-app-id="${appId}"]`);
                  if ($altCard.length > 0) {
                    const $badge = $altCard.find('.credential-badge');
                    $badge.removeClass('no-credential').addClass('credential-exists').html('<i class="fas fa-key"></i> Tersimpan');
                    
                    const $credentialBtn = $altCard.find('.btn-manage-credential');
                    $credentialBtn.data('credential-id', newCredentialId).attr('data-credential-id', newCredentialId);
                  }
                }
              } catch(e) {
                // Jika ada error dalam update UI, reload halaman
                location.reload();
              }
            });
          } catch(e) {
            // Fallback jika SweetAlert bermasalah
            alert('Kredensial berhasil disimpan');
            $('#modalCredential').modal('hide');
          }
        } else {
          try {
            swal({
              title: 'Error!',
              text: data.message || 'Terjadi kesalahan',
              icon: 'error',
              button: 'OK'
            });
          } catch(e) {
            alert('Error: ' + (data.message || 'Terjadi kesalahan'));
          }
        }
      },
      error: function(xhr, status, error) {
        try {
          swal({
            title: 'Error!',
            text: 'Terjadi kesalahan sistem',
            icon: 'error',
            button: 'OK'
          });
        } catch(e) {
          alert('Error: Terjadi kesalahan sistem');
        }
      },
      complete: function() {
        $submitBtn.prop('disabled', false)
                 .html('<i class="fas fa-save"></i> Simpan')
                 .removeClass('btn-loading');
      }
    });
  });

  /** Delete Credential */
  $(document).on('click', '#btnDeleteCredential', function() {
    const credentialId = $('#credential_id').val();
    const appName = $('#credential_app_name').val();
    
    swal({
      title: 'Hapus Kredensial?',
      text: 'Kredensial untuk aplikasi "' + appName + '" akan dihapus permanen!',
      icon: 'warning',
      buttons: {
        cancel: {
          text: 'Batal',
          visible: true,
          className: 'btn btn-secondary'
        },
        confirm: {
          text: 'Hapus',
          className: 'btn btn-danger'
        }
      }
    }).then((willDelete) => {
      if (willDelete) {
        $.ajax({
          url: 'mod/portal-gtk/proses.php?action=credential-delete',
          type: 'POST',
          data: { credential_id: credentialId },
          dataType: 'json',
          success: function(response) {
            if (response.status === 'success') {
              try {
                swal({
                  title: 'Dihapus!',
                  text: response.message || 'Kredensial berhasil dihapus',
                  icon: 'success',
                  button: 'OK'
                }).then(function() {
                  $('#modalCredential').modal('hide');
                  
                  // Update UI tanpa reload
                  const appId = $('#credential_app_id').val();
                  const $appCard = $(`.portal-app-card[data-app-id="${appId}"]`);
                  if ($appCard.length > 0) {
                    // Update credential badge
                    const $badge = $appCard.find('.credential-badge');
                    $badge.removeClass('credential-exists')
                          .addClass('no-credential')
                          .html('<i class="fas fa-key-slash"></i> Belum disimpan');
                    
                    // Update button data
                    const $credentialBtn = $appCard.find('.btn-manage-credential');
                    $credentialBtn.data('credential-id', '0')
                                 .attr('data-credential-id', '0');
                  }
                });
              } catch(e) {
                alert('Kredensial berhasil dihapus');
                $('#modalCredential').modal('hide');
              }
            } else {
              try {
                swal({
                  title: 'Error!',
                  text: response.message || 'Terjadi kesalahan',
                  icon: 'error',
                  button: 'OK'
                });
              } catch(e) {
                alert('Error: ' + (response.message || 'Terjadi kesalahan'));
              }
            }
          },
          error: function() {
            try {
              swal({
                title: 'Error!',
                text: 'Terjadi kesalahan sistem',
                icon: 'error',
                button: 'OK'
              });
            } catch(e) {
              alert('Error: Terjadi kesalahan sistem');
            }
          }
        });
      }
    });
  });

  /** Toggle Password Visibility */
  $(document).on('click', '#togglePassword', function() {
    const passwordField = $('#app_password');
    const icon = $(this).find('i');
    
    if (passwordField.attr('type') === 'password') {
      passwordField.attr('type', 'text');
      icon.removeClass('fa-eye').addClass('fa-eye-slash');
    } else {
      passwordField.attr('type', 'password');
      icon.removeClass('fa-eye-slash').addClass('fa-eye');
    }
  });

  /** Enhanced Access with Credential */
  $(document).on('click', '.btn-access-app', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const url = $(this).data('url');
    const appId = $(this).data('id');
    const appName = $(this).data('name');
    
    // Validasi data
    if (!url || !appName) {
      swal("Error!", "Data aplikasi tidak lengkap", "error");
      return;
    }
    
    // Tampilkan loading
    swal({
      title: "Mengakses " + appName,
      text: "Memuat informasi akun...",
      icon: "info",
      buttons: false,
      closeOnClickOutside: false,
      closeOnEsc: false,
    });
    
    // Request data akun
    $.ajax({
      url: "mod/portal-gtk/proses.php?action=portal-access",
      type: "POST",
      data: { 
        app_name: appName,
        app_url: url
      },
      dataType: "json",
      timeout: 10000, // 10 second timeout
      success: function (res) {
        try {
          if (res.status === "success") {
            // Tampilkan informasi akun
            swal({
              title: "🔑 Informasi Akun - " + res.app_name,
              content: {
                element: "div",
                attributes: {
                  innerHTML: `
                    <div class="account-info-modal">
                      <div class="credentials-container">
                        <div class="credential-item">
                          <div class="credential-header">
                            <i class="fas fa-user text-info"></i>
                            <label>Username / Email</label>
                          </div>
                          <div class="credential-content">
                            <div class="credential-value" id="username-display">
                              <input type="text" class="form-control" value="${res.username}" readonly>
                            </div>
                            <div class="credential-actions">
                              <button type="button" class="btn btn-outline-primary btn-sm" onclick="copyAccountField('${res.username}', 'Username')" title="Salin username">
                                <i class="fas fa-copy"></i>
                              </button>
                            </div>
                          </div>
                        </div>
                        
                        <div class="credential-item mt-3">
                          <div class="credential-header">
                            <i class="fas fa-lock text-warning"></i>
                            <label>Password</label>
                          </div>
                          <div class="credential-content">
                            <div class="credential-value" id="password-display">
                              <input type="password" class="form-control" value="${res.password}" readonly id="modalPasswordField">
                            </div>
                            <div class="credential-actions">
                              <button type="button" class="btn btn-outline-secondary btn-sm mr-1" onclick="toggleModalPassword()" title="Tampilkan/Sembunyikan password">
                                <i class="fas fa-eye" id="modalPasswordToggle"></i>
                              </button>
                              <button type="button" class="btn btn-outline-primary btn-sm" onclick="copyAccountField('${res.password}', 'Password')" title="Salin password">
                                <i class="fas fa-copy"></i>
                              </button>
                            </div>
                          </div>
                        </div>
                        
                        ${res.notes ? `
                        <div class="credential-notes mt-3">
                          <div class="alert alert-light">
                            <i class="fas fa-sticky-note text-muted mr-2"></i>
                            <strong>Catatan:</strong> ${res.notes}
                          </div>
                        </div>
                        ` : ''}
                      </div>
                      
                      <div class="access-instruction mt-4">
                        <div class="alert alert-info">
                          <div class="row align-items-center">
                            <div class="col-auto">
                              <i class="fas fa-info-circle fa-lg"></i>
                            </div>
                            <div class="col">
                              <strong>Cara menggunakan:</strong><br>
                              <small>Salin email/password di atas untuk login ke aplikasi</small>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    
                    <style>
                      .account-info-modal {
                        text-align: left;
                        max-width: 500px;
                        margin: 0 auto;
                      }
                      
                      .credentials-container {
                        background: #ffffff;
                        border-radius: 12px;
                        padding: 20px;
                        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
                        border: 1px solid #e2e8f0;
                      }
                      
                      .credential-item {
                        margin-bottom: 0;
                      }
                      
                      .credential-header {
                        display: flex;
                        align-items: center;
                        margin-bottom: 8px;
                      }
                      
                      .credential-header i {
                        margin-right: 8px;
                        font-size: 14px;
                      }
                      
                      .credential-header label {
                        font-weight: 600;
                        color: #4a5568;
                        margin: 0;
                        font-size: 14px;
                      }
                      
                      .credential-content {
                        display: flex;
                        align-items: center;
                        gap: 10px;
                      }
                      
                      .credential-value {
                        flex: 1;
                      }
                      
                      .credential-value input {
                        font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
                        font-size: 14px;
                        background: #f8f9fa;
                        border: 1px solid #e2e8f0;
                        border-radius: 6px;
                        padding: 10px 12px;
                        color: #2d3748;
                      }
                      
                      .credential-actions {
                        display: flex;
                        gap: 5px;
                      }
                      
                      .credential-actions .btn {
                        border-radius: 6px;
                        padding: 8px 12px;
                        font-size: 12px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                      }
                      
                      .credential-notes .alert {
                        border-radius: 8px;
                        font-size: 13px;
                        padding: 12px 15px;
                      }
                      
                      .access-instruction .alert {
                        border-radius: 10px;
                        border: none;
                        background: linear-gradient(135deg, #dbeafe, #bfdbfe);
                        color: #1e40af;
                      }
                      
                      .swal-button {
                        border-radius: 8px !important;
                        font-weight: 600 !important;
                        text-transform: uppercase !important;
                        letter-spacing: 0.5px !important;
                        padding: 12px 24px !important;
                        font-size: 13px !important;
                      }
                      
                      .swal-button--confirm {
                        background: linear-gradient(135deg, #5e72e4, #4f46e5) !important;
                        box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4) !important;
                      }
                      
                      .swal-button--cancel {
                        background: #6c757d !important;
                      }
                    </style>
                  `
                }
              },
              buttons: {
                cancel: {
                  text: "❌ Batal",
                  visible: true,
                  className: "btn btn-secondary"
                },
                confirm: {
                  text: "🚀 Buka Aplikasi",
                  value: true,
                  className: "btn btn-primary"
                }
              }
            }).then((willOpen) => {
              if (willOpen) {
                // Show loading state
                const loadingToast = $('<div class="toast-notification loading">Membuka aplikasi...</div>');
                $('body').append(loadingToast);
                loadingToast.addClass('show');
                
                setTimeout(() => {
                  loadingToast.remove();
                  window.open(res.app_url, '_blank');
                }, 500);
              }
            });
          } else if (res.status === "info" && res.action === "setup_credential") {
            // Kredensial belum disimpan
            swal({
              title: "Kredensial Belum Disimpan",
              text: `Kredensial untuk aplikasi ${res.app_name} belum disimpan. Anda dapat menyimpan kredensial atau langsung membuka aplikasi.`,
              icon: "info",
              buttons: {
                credential: {
                  text: "Kelola Kredensial",
                  value: "credential",
                  className: "btn btn-success"
                },
                open: {
                  text: "Buka Aplikasi",
                  value: "open", 
                  className: "btn btn-primary"
                },
                cancel: {
                  text: "Batal",
                  value: false,
                  className: "btn btn-secondary"
                }
              }
            }).then((action) => {
              if (action === "open") {
                window.open(res.app_url, '_blank');
              } else if (action === "credential") {
                $('.swal-modal').remove();
                $('.swal-overlay').remove();
                
                setTimeout(() => {
                  const $credentialBtn = $(`.btn-manage-credential[data-name="${res.app_name}"]`);
                  if ($credentialBtn.length > 0) {
                    $credentialBtn.trigger('click');
                  } else {
                    openCredentialModal(res.app_name, null, 0);
                  }
                }, 200);
              }
            });
          } else {
            swal("Error!", res.message || "Response tidak dikenal", "error");
          }
        } catch (err) {
          console.error('Error processing response:', err);
          swal("Error!", "Terjadi kesalahan memproses response", "error");
        }
      },
      error: function (xhr, status, error) {
        console.error('Portal access AJAX Error:', {
          status: xhr.status,
          statusText: xhr.statusText,
          responseText: xhr.responseText,
          ajaxStatus: status,
          error: error,
          url: "mod/portal-gtk/proses.php?action=portal-access",
          data: { app_name: appName, app_url: url }
        });
        
        let errorMessage = "Gagal mengakses informasi aplikasi";
        let debugInfo = "";
        
        if (xhr.status === 404) {
          errorMessage = "File proses.php tidak ditemukan";
          debugInfo = "Path: mod/portal-gtk/proses.php";
        } else if (xhr.status === 500) {
          errorMessage = "Terjadi kesalahan server";
          debugInfo = "Response: " + (xhr.responseText || "No response");
        } else if (xhr.status === 0) {
          errorMessage = "Tidak dapat terhubung ke server";
          debugInfo = "Kemungkinan: CORS, Network, atau Server tidak running";
        } else if (status === 'timeout') {
          errorMessage = "Request timeout (>10 detik)";
          debugInfo = "Server terlalu lambat merespons";
        } else if (status === 'parsererror') {
          errorMessage = "Response bukan JSON valid";
          debugInfo = "Response: " + (xhr.responseText || "Empty");
        }
        
        console.error('Error details:', errorMessage, debugInfo);
        
        swal("Error!", errorMessage + (debugInfo ? "\\n\\n" + debugInfo : ""), "error");
      }
    });
  });
});
