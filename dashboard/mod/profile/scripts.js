"use strict";

// Profile Page Scripts
document.addEventListener("DOMContentLoaded", function () {
  // Initialize page animations
  initializeProfileAnimations();

  // Initialize form handling
  initializeProfileForms();

  // Initialize password strength checker
  initializePasswordStrength();
  
  // Initialize avatar upload
  initializeAvatarUpload();
});

// Page animations
function initializeProfileAnimations() {
  // Animate cards on load
  const cards = document.querySelectorAll(".card");
  cards.forEach((card, index) => {
    card.style.opacity = "0";
    card.style.transform = "translateY(20px)";

    setTimeout(() => {
      card.style.transition = "opacity 0.6s ease, transform 0.6s ease";
      card.style.opacity = "1";
      card.style.transform = "translateY(0)";
    }, index * 100);
  });
}

// Profile form handling
function initializeProfileForms() {
  // Store original form values
  storeOriginalFormValues();
  // Cache save button HTML/state on init so we can always restore it
  const saveBtn = document.getElementById("btnSaveProfile");
  if (saveBtn && savedSubmitHTML === null) {
    savedSubmitHTML = saveBtn.innerHTML;
    savedSubmitDisabled = saveBtn.disabled;
  }
  
  // Profile update form
  const profileForm = document.getElementById("formProfile");
  if (profileForm) {
    profileForm.addEventListener("submit", function (e) {
      e.preventDefault();
      updateProfile();
    });
  }

  // Change password form
  const passwordForm = document.getElementById("formChangePassword");
  if (passwordForm) {
    passwordForm.addEventListener("submit", function (e) {
      e.preventDefault();
      changePassword();
    });
  }
}

// Store original form values
let originalFormValues = {};
// Cache original submit button state to prevent persistent loading state
let savedSubmitHTML = null;
let savedSubmitDisabled = false;

function storeOriginalFormValues() {
  const emailField = document.getElementById("email");
  const telpField = document.getElementById("telp");
  
  if (emailField && telpField) {
    originalFormValues = {
      email: emailField.value,
      telp: telpField.value
    };
  }
}

// Toggle edit mode for profile
function toggleEditProfile() {
  const editableFields = ["email", "telp"];
  const isReadonly = document.getElementById("email").hasAttribute("readonly");
  
  editableFields.forEach(fieldId => {
    const field = document.getElementById(fieldId);
    if (isReadonly) {
      field.removeAttribute("readonly");
      field.classList.remove("form-control-alternative");
      field.classList.add("form-control");
    } else {
      field.setAttribute("readonly", true);
      field.classList.add("form-control-alternative");
      field.classList.remove("form-control");
    }
  });
  
  const editActions = document.getElementById("editActions");
  const btnEdit = document.getElementById("btnEditProfile");
  const whatsappVerifySection = document.getElementById("whatsappVerifySection");
  
  if (isReadonly) {
    // Mode Edit
    editActions.style.display = "block";
    btnEdit.innerHTML = '<i class="fas fa-times"></i> Batal';
    btnEdit.classList.remove("btn-primary");
    btnEdit.classList.add("btn-secondary");
    
    // Sembunyikan tombol verifikasi WhatsApp saat edit
    if (whatsappVerifySection) {
      whatsappVerifySection.style.display = "none";
    }
  } else {
    // Mode Normal (tidak edit)
    editActions.style.display = "none";
    btnEdit.innerHTML = '<i class="fas fa-edit"></i> Edit';
    btnEdit.classList.add("btn-primary");
    btnEdit.classList.remove("btn-secondary");
    
    // Tampilkan tombol verifikasi WhatsApp saat tidak edit
    if (whatsappVerifySection) {
      whatsappVerifySection.style.display = "block";
    }
  }
}

// Cancel edit profile
function cancelEditProfile() {
  // Restore original values
  if (originalFormValues.email !== undefined) {
    document.getElementById("email").value = originalFormValues.email;
  }
  if (originalFormValues.telp !== undefined) {
    document.getElementById("telp").value = originalFormValues.telp;
  }
  
  toggleEditProfile();
}

// Update profile
function updateProfile() {
  const form = document.getElementById("formProfile");
  const formData = new FormData(form);
  const submitBtn = document.getElementById("btnSaveProfile");
  
  // Email domain validation: jika email diisi, harus berakhiran @smk.belajar.id
  const emailField = document.getElementById('email');
  const emailValue = emailField ? emailField.value.trim() : '';
  if (emailValue.length > 0) {
    const domainRegex = /@smk\.belajar\.id$/i;
    if (!domainRegex.test(emailValue)) {
      showNotification('error', 'Email harus menggunakan domain @smk.belajar.id', true);
      // Pulihkan tombol menggunakan cache yang disimpan pada inisialisasi
      if (submitBtn) {
        submitBtn.disabled = savedSubmitDisabled === undefined ? false : savedSubmitDisabled;
        submitBtn.innerHTML = savedSubmitHTML !== null ? savedSubmitHTML : '<i class="fas fa-save"></i> Simpan';
        submitBtn.removeAttribute('aria-busy');
      }
      return;
    }
  }

  // Disable submit button
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...';
  
  fetch("mod/profile/proses.php?action=update_profile", {
    method: "POST",
    body: formData
  })
  .then(response => response.json())
    .then(data => {
    if (data.status === "success") {
      // Update stored original values dan kembalikan mode view
      storeOriginalFormValues();
      toggleEditProfile();
      
      showNotification("success", data.message, true);

      // Reload halaman untuk memastikan data sinkron (1.5 detik)
      setTimeout(() => {
        location.reload();
      }, 1500);
    } else {
      // Re-enable submit button before showing error
      submitBtn.disabled = false;
      submitBtn.innerHTML = '<i class="fas fa-save"></i> Simpan';
      showNotification("error", data.message, true);
    }
  })
  .catch(error => {
    console.error("Error:", error);
    showNotification("error", "Terjadi kesalahan sistem");
  })
  .finally(() => {
    // Auto reload will handle button state reset
  });
}

// Change password
function changePassword() {
  const form = document.getElementById("formChangePassword");
  const formData = new FormData(form);
  const submitBtn = document.getElementById("btnChangePassword");
  
  // Client-side validation - SEBELUM disable tombol
  const newPassword = document.getElementById("new_password") ? document.getElementById("new_password").value.trim() : "";
  const confirmPassword = document.getElementById("confirm_password") ? document.getElementById("confirm_password").value.trim() : "";
  
  // Prevent multiple submissions
  if (submitBtn.disabled) {
    return;
  }
  
  // Validasi field kosong
  if (!newPassword || !confirmPassword) {
    showNotification("error", "Password baru dan konfirmasi password harus diisi", true);
    return;
  }
  
  if (newPassword !== confirmPassword) {
    showNotification("error", "Konfirmasi password tidak sama", true);
    return;
  }
  
  // Password policy validation
  const policyRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{6,}$/;
  if (!policyRegex.test(newPassword)) {
    showNotification("error", "Password harus minimal 6 karakter dan mengandung huruf besar, huruf kecil, angka, dan simbol", true);
    return;
  }
  
  // Disable submit button - SETELAH semua validasi lolos
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Mengubah...';
  
  fetch("mod/profile/proses.php?action=change_password", {
    method: "POST",
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.status === "success") {
      showNotification("success", data.message, true);
      form.reset();
      document.getElementById("passwordStrength").style.display = "none";
    } else {
      // Reset button before showing error
      submitBtn.disabled = false;
      submitBtn.innerHTML = '<i class="fas fa-key"></i> Ubah Password';
      showNotification("error", data.message, true);
    }
  })
  .catch(error => {
    console.error("Error:", error);
    // Reset button before showing error
    submitBtn.disabled = false;
    submitBtn.innerHTML = '<i class="fas fa-key"></i> Ubah Password';
    showNotification("error", "Terjadi kesalahan sistem", true);
  })
  .finally(() => {
    // Only reset if still in loading state (success case only)
    if (submitBtn.disabled && submitBtn.innerHTML.includes('Mengubah')) {
      submitBtn.disabled = false;
      submitBtn.innerHTML = '<i class="fas fa-key"></i> Ubah Password';
    }
  });
}

// Toggle password visibility
function togglePassword(fieldId) {
  const field = document.getElementById(fieldId);
  const icon = document.getElementById(`toggle${fieldId.charAt(0).toUpperCase() + fieldId.slice(1).replace('_', '')}`);
  
  if (field.type === "password") {
    field.type = "text";
    icon.classList.remove("fa-eye");
    icon.classList.add("fa-eye-slash");
  } else {
    field.type = "password";
    icon.classList.remove("fa-eye-slash");
    icon.classList.add("fa-eye");
  }
}

// Password strength checker
function initializePasswordStrength() {
  const passwordField = document.getElementById("new_password");
  const strengthDiv = document.getElementById("passwordStrength");
  const progressBar = strengthDiv.querySelector(".progress-bar");
  
  if (passwordField) {
    passwordField.addEventListener("input", function () {
      const password = this.value;
      
      if (password.length > 0) {
        strengthDiv.style.display = "block";
        
        // Check password strength criteria
        const checks = {
          length: password.length >= 6,
          upper: /[A-Z]/.test(password),
          lower: /[a-z]/.test(password),
          digit: /\d/.test(password),
          symbol: /[^A-Za-z0-9]/.test(password)
        };
        
        // Update visual indicators
        Object.keys(checks).forEach(key => {
          const indicator = document.getElementById(`pw-${key}`);
          if (checks[key]) {
            indicator.className = "fas fa-check text-success";
          } else {
            indicator.className = "fas fa-times text-danger";
          }
        });
        
        // Calculate strength score
        const score = Object.values(checks).filter(Boolean).length;
        const percentage = (score / 5) * 100;
        
        progressBar.style.width = `${percentage}%`;
        progressBar.className = "progress-bar ";
        
        if (score < 3) {
          progressBar.classList.add("password-weak");
        } else if (score < 5) {
          progressBar.classList.add("password-medium");
        } else {
          progressBar.classList.add("password-strong");
        }
      } else {
        strengthDiv.style.display = "none";
      }
    });
  }
}

// Avatar upload
function initializeAvatarUpload() {
  const avatarInput = document.getElementById("avatarInput");
  if (avatarInput) {
    avatarInput.addEventListener("change", function (e) {
      uploadAvatar(this);
    });
  }
}

function uploadAvatar(input) {
  if (input.files && input.files[0]) {
    const file = input.files[0];
    
    // Validate file type
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    if (!allowedTypes.includes(file.type)) {
      showNotification("error", "Format file tidak didukung. Gunakan JPEG, JPG, atau PNG", true);
      return;
    }
    
    // Validate file size (5MB)
    const maxSize = 5 * 1024 * 1024;
    if (file.size > maxSize) {
      showNotification("error", "Ukuran file terlalu besar. Maksimal 5MB", true);
      return;
    }
    
    const formData = new FormData();
    formData.append('avatar', file);
    
    // Show loading
    const profileImage = document.getElementById("profileImage");
    const originalSrc = profileImage.src;
    
    fetch("mod/profile/proses.php?action=upload_avatar", {
      method: "POST",
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.status === "success") {
        showNotification("success", data.message);
        // Update profile image
        profileImage.src = data.avatar_url;
      } else {
        showNotification("error", data.message, true);
        profileImage.src = originalSrc;
      }
    })
    .catch(error => {
      console.error("Error:", error);
      showNotification("error", "Terjadi kesalahan sistem", true);
      profileImage.src = originalSrc;
    });
  }
}

// WhatsApp Verification Functions
function verifyWhatsApp() {
  const phoneField = document.getElementById('telp');
  const phoneNumber = phoneField.value.trim();
  
  if (!phoneNumber) {
    showNotification('error', 'Silakan isi nomor telepon terlebih dahulu', true);
    return;
  }
  
  // Validate phone number format (Indonesian)
  const phoneRegex = /^(\+62|62|0)[0-9]{9,13}$/;
  if (!phoneRegex.test(phoneNumber)) {
    showNotification('error', 'Format nomor telepon tidak valid. Gunakan format Indonesia (08xx atau +628xx)', true);
    return;
  }
  
  // Display phone number in modal
  document.getElementById('phoneNumberDisplay').textContent = phoneNumber;
  
  // Reset modal state
  resetVerificationModal();
  
  // Show modal with proper Bootstrap/jQuery method
  if (typeof $ !== 'undefined' && $('#whatsappVerificationModal').length) {
    $('#whatsappVerificationModal').modal('show');
  } else {
    const modal = document.getElementById('whatsappVerificationModal');
    if (modal && typeof bootstrap !== 'undefined') {
      const bsModal = new bootstrap.Modal(modal);
      bsModal.show();
    }
  }
}

function sendVerificationCode() {
  const phoneField = document.getElementById('telp');
  const phoneNumber = phoneField.value.trim();
  const sendBtn = document.getElementById('btnSendCode');
  const resendLink = document.getElementById('resendCode');
  
  // Disable button
  sendBtn.disabled = true;
  sendBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Mengirim...';
  resendLink.style.display = 'none';
  
  fetch('mod/profile/proses.php?action=send_whatsapp_verification', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'phone=' + encodeURIComponent(phoneNumber)
  })
  .then(response => response.json())
  .then(data => {
    if (data.status === 'success') {
      showNotification('success', data.message);
      
      // Switch to verification step
      document.getElementById('verificationStep1').style.display = 'none';
      document.getElementById('verificationStep2').style.display = 'block';
      document.getElementById('btnSendCode').style.display = 'none';
      document.getElementById('btnVerifyCode').style.display = 'inline-block';
      
      // Focus on verification code input
      setTimeout(() => {
        document.getElementById('verificationCode').focus();
      }, 500);
      
      // Start countdown for resend
      startResendCountdown();
      
    } else {
      // Re-enable button before showing error
      sendBtn.disabled = false;
      sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Kirim Kode';
      resendLink.style.display = 'inline';
      showNotification('error', data.message, true);
    }
  })
  .catch(error => {
    console.error('Error:', error);
    // Re-enable button before showing error
    sendBtn.disabled = false;
    sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Kirim Kode';
    resendLink.style.display = 'inline';
    showNotification('error', 'Terjadi kesalahan sistem', true);
  })
  .finally(() => {
    // Only reset button if it's still in loading state (success case)
    if (sendBtn.disabled && sendBtn.innerHTML.includes('Mengirim')) {
      sendBtn.disabled = false;
      sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Kirim Kode';
    }
  });
}

function verifyCode() {
  const codeInput = document.getElementById('verificationCode');
  const code = codeInput.value.trim();
  const verifyBtn = document.getElementById('btnVerifyCode');
  
  if (!code || code.length !== 6) {
    showNotification('error', 'Masukkan kode verifikasi 6 digit', true);
    codeInput.focus();
    return;
  }
  
  // Disable button
  verifyBtn.disabled = true;
  verifyBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Memverifikasi...';
  
  fetch('mod/profile/proses.php?action=verify_whatsapp_code', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'code=' + encodeURIComponent(code)
  })
  .then(response => response.json())
  .then(data => {
    if (data.status === 'success') {
      showNotification('success', data.message, true);
      closeVerificationModal();
      
      // Reload page to show verification status
      setTimeout(() => {
        location.reload();
      }, 1500);
      
    } else {
      // Re-enable button before showing error
      verifyBtn.disabled = false;
      verifyBtn.innerHTML = '<i class="fas fa-check"></i> Verifikasi';
      showNotification('error', data.message, true);
      codeInput.focus();
      codeInput.select();
    }
  })
  .catch(error => {
    console.error('Error:', error);
    // Re-enable button before showing error
    verifyBtn.disabled = false;
    verifyBtn.innerHTML = '<i class="fas fa-check"></i> Verifikasi';
    showNotification('error', 'Terjadi kesalahan sistem', true);
  })
  .finally(() => {
    // Only reset button if it's still in loading state (success case)
    if (verifyBtn.disabled && verifyBtn.innerHTML.includes('Memverifikasi')) {
      verifyBtn.disabled = false;
      verifyBtn.innerHTML = '<i class="fas fa-check"></i> Verifikasi';
    }
  });
}

function startResendCountdown() {
  let countdown = 60;
  const countdownDiv = document.getElementById('countdown');
  const countdownTime = document.getElementById('countdownTime');
  const resendLink = document.getElementById('resendCode');
  
  countdownDiv.style.display = 'block';
  resendLink.style.display = 'none';
  
  const timer = setInterval(() => {
    countdown--;
    countdownTime.textContent = countdown;
    
    if (countdown <= 0) {
      clearInterval(timer);
      countdownDiv.style.display = 'none';
      resendLink.style.display = 'inline';
    }
  }, 1000);
}

// Auto-format verification code input
document.addEventListener('DOMContentLoaded', function() {
  const codeInput = document.getElementById('verificationCode');
  if (codeInput) {
    codeInput.addEventListener('input', function(e) {
      // Only allow numbers
      this.value = this.value.replace(/[^0-9]/g, '');
      
      // Auto-submit when 6 digits entered
      if (this.value.length === 6) {
        setTimeout(() => {
          verifyCode();
        }, 500);
      }
    });
    
    // Handle paste
    codeInput.addEventListener('paste', function(e) {
      e.preventDefault();
      const paste = (e.clipboardData || window.clipboardData).getData('text');
      const numbers = paste.replace(/[^0-9]/g, '').substring(0, 6);
      this.value = numbers;
      
      if (numbers.length === 6) {
        setTimeout(() => {
          verifyCode();
        }, 500);
      }
    });
  }
  
  // Modal event handlers
  const whatsappModal = document.getElementById('whatsappVerificationModal');
  if (whatsappModal) {
    // Reset modal state when hidden
    whatsappModal.addEventListener('hidden.bs.modal', function() {
      resetVerificationModal();
    });
    
    // Also support jQuery modal events for backward compatibility
    $('#whatsappVerificationModal').on('hidden.bs.modal', function() {
      resetVerificationModal();
    });
    
    // Handle ESC key
    whatsappModal.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeVerificationModal();
      }
    });
  }
});

// Reset verification modal to initial state
function resetVerificationModal() {
  // Reset to step 1
  document.getElementById('verificationStep1').style.display = 'block';
  document.getElementById('verificationStep2').style.display = 'none';
  
  // Reset buttons
  document.getElementById('btnSendCode').style.display = 'inline-block';
  document.getElementById('btnVerifyCode').style.display = 'none';
  
  // Clear verification code
  const codeInput = document.getElementById('verificationCode');
  if (codeInput) {
    codeInput.value = '';
  }
  
  // Hide countdown and show resend link
  const countdown = document.getElementById('countdown');
  const resendLink = document.getElementById('resendCode');
  if (countdown) countdown.style.display = 'none';
  if (resendLink) resendLink.style.display = 'inline';
  
  // Reset button states
  const btnSend = document.getElementById('btnSendCode');
  const btnVerify = document.getElementById('btnVerifyCode');
  
  if (btnSend) {
    btnSend.disabled = false;
    btnSend.innerHTML = '<i class="fas fa-paper-plane"></i> Kirim Kode';
  }
  
  if (btnVerify) {
    btnVerify.disabled = false;
    btnVerify.innerHTML = '<i class="fas fa-check"></i> Verifikasi';
  }
}

// Close verification modal
function closeVerificationModal() {
  if (typeof $ !== 'undefined' && $('#whatsappVerificationModal').length) {
    $('#whatsappVerificationModal').modal('hide');
  } else {
    const modal = document.getElementById('whatsappVerificationModal');
    if (modal && typeof bootstrap !== 'undefined') {
      const bsModal = new bootstrap.Modal(modal);
      bsModal.hide();
    }
  }
}

// Show notification
function showNotification(type, message, autoReload = false) {
  // Use SweetAlert consistent with the system
  if (typeof swal !== 'undefined') {
    const config = {
      title: type === 'success' ? 'Berhasil!' : 'Error!',
      text: message,
      type: type === 'success' ? 'success' : 'error',
      buttonsStyling: false,
      confirmButtonClass: type === 'success' ? 'btn btn-success' : 'btn btn-danger',
      timer: autoReload ? null : 3000,
      showConfirmButton: true,
      allowOutsideClick: !autoReload,
      allowEscapeKey: !autoReload
    };
    
    swal(config).then((result) => {
      if (autoReload) {
        // Auto reload setelah user interaksi apapun dengan alert
        setTimeout(() => {
          location.reload();
        }, 100);
      }
    });
  } else if (typeof Swal !== 'undefined') {
    Swal.fire({
      icon: type === 'success' ? 'success' : 'error',
      title: type === 'success' ? 'Berhasil!' : 'Error!',
      text: message,
      timer: autoReload ? null : 3000,
      showConfirmButton: autoReload,
      allowOutsideClick: !autoReload,
      allowEscapeKey: !autoReload
    }).then((result) => {
      if (autoReload) {
        // Auto reload setelah user interaksi apapun dengan alert
        setTimeout(() => {
          location.reload();
        }, 100);
      }
    });
  } else {
    alert(message);
    if (autoReload) {
      setTimeout(() => {
        location.reload();
      }, 100);
    }
  }
}

// Initialize password field event listeners for real-time validation
document.addEventListener("DOMContentLoaded", function () {
  const confirmField = document.getElementById("confirm_password");
  const newPasswordField = document.getElementById("new_password");
  
  if (confirmField && newPasswordField) {
    confirmField.addEventListener("input", function () {
      if (this.value.length > 0 && newPasswordField.value !== this.value) {
        this.classList.add("is-invalid");
      } else {
        this.classList.remove("is-invalid");
      }
    });
    
    newPasswordField.addEventListener("input", function () {
      const confirmPassword = confirmField.value;
      if (confirmPassword.length > 0 && this.value !== confirmPassword) {
        confirmField.classList.add("is-invalid");
      } else {
        confirmField.classList.remove("is-invalid");
      }
    });
  }
});