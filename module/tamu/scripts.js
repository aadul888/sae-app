/**
 * Buku Tamu Digital - JavaScript Handler
 * Menangani QR Scanner, Form Input, dan Camera Selfie
 */

class GuestBookApp {
  constructor() {
    this.currentStep = 1;
    this.qrScanner = null;
    this.stream = null;
    this.capturedPhoto = null;
    this.guestData = {};

    this.init();
  }

  init() {
    this.setupEventListeners();
    this.updateProgress();
  }

  setupEventListeners() {
    // QR Scanner Events
    document
      .getElementById("startScanBtn")
      .addEventListener("click", () => this.startQRScanner());
    document
      .getElementById("skipScanBtn")
      .addEventListener("click", () => this.goToStep(2));

    // Form Navigation Events
    document
      .getElementById("backToScanBtn")
      .addEventListener("click", () => this.goToStep(1));
    document
      .getElementById("nextToPhotoBtn")
      .addEventListener("click", () => this.validateFormAndNext());

    // Photo Events
    document
      .getElementById("backToFormBtn")
      .addEventListener("click", () => this.goToStep(2));
    document
      .getElementById("startCameraBtn")
      .addEventListener("click", () => this.startCamera());
    document
      .getElementById("captureBtn")
      .addEventListener("click", () => this.capturePhoto());
    document
      .getElementById("retakeBtn")
      .addEventListener("click", () => this.retakePhoto());
    document
      .getElementById("submitBtn")
      .addEventListener("click", () => this.submitForm());

    // Form validation real-time
    const requiredFields = ["nama", "instansi", "keperluan"];
    requiredFields.forEach((field) => {
      document
        .getElementById(field)
        .addEventListener("input", () => this.validateForm());
    });
  }

  showAlert(message, type = "info") {
    const alertContainer = document.getElementById("alertContainer");
    const alertId = "alert-" + Date.now();

    const alertHTML = `
            <div class="alert alert-${type} alert-dismissible fade show" id="${alertId}" role="alert">
                <i class="fas fa-${this.getAlertIcon(type)} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

    alertContainer.innerHTML = alertHTML;

    // Auto hide after 5 seconds
    setTimeout(() => {
      const alert = document.getElementById(alertId);
      if (alert) {
        alert.remove();
      }
    }, 5000);
  }

  getAlertIcon(type) {
    const icons = {
      success: "check-circle",
      danger: "exclamation-triangle",
      warning: "exclamation-circle",
      info: "info-circle",
    };
    return icons[type] || "info-circle";
  }

  goToStep(step) {
    // Hide current step
    document.querySelectorAll(".step-content").forEach((content) => {
      content.classList.remove("active");
    });

    // Show target step
    document.getElementById(`step${step}`).classList.add("active");

    // Update step circles
    for (let i = 1; i <= 3; i++) {
      const circle = document.getElementById(`step${i}Circle`);
      circle.classList.remove("active", "completed", "pending");

      if (i < step) {
        circle.classList.add("completed");
      } else if (i === step) {
        circle.classList.add("active");
      } else {
        circle.classList.add("pending");
      }
    }

    this.currentStep = step;
    this.updateProgress();

    // Clean up previous step resources
    if (step !== 1 && this.qrScanner) {
      this.qrScanner.stop();
    }
    if (step !== 3 && this.stream) {
      this.stopCamera();
    }
  }

  updateProgress() {
    const progressFill = document.getElementById("progressFill");
    const progress = ((this.currentStep - 1) / 2) * 100;
    progressFill.style.width = progress + "%";
  }

  // QR Scanner Methods
  async startQRScanner() {
    try {
      const qrReaderContainer = document.getElementById("qrReaderContainer");
      qrReaderContainer.innerHTML =
        '<div id="qrReader" style="width: 100%; height: 100%;"></div>';

      this.qrScanner = new Html5Qrcode("qrReader");

      const config = {
        fps: 10,
        qrbox: { width: 200, height: 200 },
        aspectRatio: 1.0,
      };

      await this.qrScanner.start(
        { facingMode: "environment" },
        config,
        (decodedText, decodedResult) => {
          this.handleQRSuccess(decodedText);
        },
        (errorMessage) => {
          // Handle scan errors silently
        }
      );

      document.getElementById("startScanBtn").style.display = "none";
      this.showAlert("Scanner aktif! Arahkan ke QR code", "info");
    } catch (err) {
      console.error("Error starting QR scanner:", err);
      this.showAlert(
        "Tidak dapat mengakses kamera. Silakan coba lagi atau lewati langkah ini.",
        "warning"
      );
    }
  }

  handleQRSuccess(decodedText) {
    this.qrScanner.stop();

    try {
      // Try to parse as JSON (jika QR berisi data user)
      const qrData = JSON.parse(decodedText);

      // Populate form with QR data
      if (qrData.nama) document.getElementById("nama").value = qrData.nama;
      if (qrData.instansi)
        document.getElementById("instansi").value = qrData.instansi;
      if (qrData.telepon)
        document.getElementById("telepon").value = qrData.telepon;

      this.showAlert(
        "QR Code berhasil dipindai! Data telah diisi otomatis.",
        "success"
      );
    } catch (e) {
      // Jika bukan JSON, gunakan sebagai nama atau ID
      document.getElementById("nama").value = decodedText;
      this.showAlert("QR Code berhasil dipindai!", "success");
    }

    // Auto move to next step after 1.5 seconds
    setTimeout(() => {
      this.goToStep(2);
    }, 1500);
  }

  // Form Validation Methods
  validateForm() {
    const nama = document.getElementById("nama").value.trim();
    const instansi = document.getElementById("instansi").value.trim();
    const keperluan = document.getElementById("keperluan").value;

    const nextBtn = document.getElementById("nextToPhotoBtn");

    if (nama && instansi && keperluan) {
      nextBtn.disabled = false;
      return true;
    } else {
      nextBtn.disabled = true;
      return false;
    }
  }

  validateFormAndNext() {
    if (this.validateForm()) {
      this.collectFormData();
      this.goToStep(3);
    } else {
      this.showAlert(
        "Mohon lengkapi semua field yang wajib diisi (bertanda *)",
        "warning"
      );
    }
  }

  collectFormData() {
    this.guestData = {
      nama: document.getElementById("nama").value.trim(),
      instansi: document.getElementById("instansi").value.trim(),
      telepon: document.getElementById("telepon").value.trim(),
      keperluan: document.getElementById("keperluan").value,
      keterangan: document.getElementById("keterangan").value.trim(),
      tanggal: new Date().toISOString().split("T")[0],
      waktu: new Date().toLocaleTimeString("id-ID"),
    };
  }

  // Camera Methods
  async startCamera() {
    try {
      this.stream = await navigator.mediaDevices.getUserMedia({
        video: {
          facingMode: "user",
          width: { ideal: 640 },
          height: { ideal: 480 },
        },
      });

      const video = document.getElementById("video");
      const placeholder = document.querySelector(".camera-placeholder");

      video.srcObject = this.stream;
      video.style.display = "block";
      placeholder.style.display = "none";

      document.getElementById("startCameraBtn").style.display = "none";
      document.getElementById("captureBtn").style.display = "inline-block";

      this.showAlert(
        "Kamera aktif! Posisikan wajah Anda dan klik Ambil Foto",
        "info"
      );
    } catch (err) {
      console.error("Error accessing camera:", err);
      this.showAlert(
        "Tidak dapat mengakses kamera. Pastikan izin kamera telah diberikan.",
        "danger"
      );
    }
  }

  capturePhoto() {
    const video = document.getElementById("video");
    const canvas = document.getElementById("canvas");
    const context = canvas.getContext("2d");

    // Set canvas size
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    // Draw video frame to canvas
    context.drawImage(video, 0, 0);

    // Get image data
    this.capturedPhoto = canvas.toDataURL("image/jpeg", 0.8);

    // Show captured photo
    video.style.display = "none";
    canvas.style.display = "block";

    // Update buttons
    document.getElementById("captureBtn").style.display = "none";
    document.getElementById("retakeBtn").style.display = "inline-block";
    document.getElementById("submitBtn").disabled = false;

    this.showAlert(
      "Foto berhasil diambil! Klik Selesai untuk menyimpan data.",
      "success"
    );
  }

  retakePhoto() {
    const video = document.getElementById("video");
    const canvas = document.getElementById("canvas");

    // Show video again
    video.style.display = "block";
    canvas.style.display = "none";

    // Reset buttons
    document.getElementById("captureBtn").style.display = "inline-block";
    document.getElementById("retakeBtn").style.display = "none";
    document.getElementById("submitBtn").disabled = true;

    this.capturedPhoto = null;
  }

  stopCamera() {
    if (this.stream) {
      this.stream.getTracks().forEach((track) => track.stop());
      this.stream = null;

      const video = document.getElementById("video");
      video.style.display = "none";

      const placeholder = document.querySelector(".camera-placeholder");
      placeholder.style.display = "block";

      document.getElementById("startCameraBtn").style.display = "inline-block";
      document.getElementById("captureBtn").style.display = "none";
    }
  }

  // Submit Methods
  async submitForm() {
    if (!this.capturedPhoto) {
      this.showAlert("Mohon ambil foto selfie terlebih dahulu.", "warning");
      return;
    }

    // Show loading
    this.showLoading(true);

    try {
      // Prepare form data
      const formData = new FormData();

      // Add guest data
      Object.keys(this.guestData).forEach((key) => {
        formData.append(key, this.guestData[key]);
      });

      // Add photo
      const photoBlob = this.dataURLtoBlob(this.capturedPhoto);
      formData.append("foto", photoBlob, "selfie_" + Date.now() + ".jpg");

      // Add timestamp
      formData.append("created_at", new Date().toISOString());

      // Submit to server
      const response = await fetch("proses.php", {
        method: "POST",
        body: formData,
      });

      const result = await response.json();

      if (result.success) {
        this.showSuccessMessage(result);
      } else {
        throw new Error(
          result.message || "Terjadi kesalahan saat menyimpan data"
        );
      }
    } catch (error) {
      console.error("Submit error:", error);
      this.showAlert("Terjadi kesalahan: " + error.message, "danger");
    } finally {
      this.showLoading(false);
    }
  }

  showLoading(show) {
    const steps = document.querySelectorAll(".step-content");
    const loading = document.getElementById("loadingSpinner");

    if (show) {
      steps.forEach((step) => (step.style.display = "none"));
      loading.style.display = "block";
    } else {
      loading.style.display = "none";
      document.getElementById("step3").style.display = "block";
    }
  }

  showSuccessMessage(result) {
    const cardBody = document.querySelector(".card-body");

    const qrBlock = result.qr_url
      ? `
                <div class="my-3">
                    <img src="${result.qr_url}" alt="QR Check-out" style="width:190px;height:190px;border:1px solid #e5e7eb;border-radius:12px;padding:6px;background:#fff;">
                    <div class="small text-muted mt-2"><i class="fas fa-qrcode me-1"></i>Simpan / screenshot QR ini. Pindai kembali saat <strong>keluar</strong> untuk check-out &amp; mengisi survey.</div>
                </div>`
      : "";
    const checkoutLink = result.checkout_url
      ? `<a href="${result.checkout_url}" class="btn btn-outline-success w-100 mb-2"><i class="fas fa-sign-out-alt me-2"></i>Halaman Check-out</a>`
      : "";

    cardBody.innerHTML = `
            <div class="text-center py-4">
                <div class="mb-3">
                    <i class="fas fa-check-circle text-success" style="font-size: 70px;"></i>
                </div>
                <h4 class="text-success mb-2">Pendaftaran Berhasil!</h4>
                <p class="text-muted mb-2">
                    Terima kasih <strong>${this.guestData.nama}</strong><br>
                    ID Tamu Anda: <strong>${
                      result.guest_id || "GUEST-" + Date.now()
                    }</strong>
                </p>
                ${qrBlock}
                ${checkoutLink}
                <button type="button" class="btn btn-primary w-100" onclick="location.reload()">
                    <i class="fas fa-plus me-2"></i>Daftar Tamu Baru
                </button>
            </div>
        `;

    // Update progress to 100%
    document.getElementById("progressFill").style.width = "100%";

    // Update all step circles to completed
    for (let i = 1; i <= 3; i++) {
      const circle = document.getElementById(`step${i}Circle`);
      circle.classList.remove("active", "pending");
      circle.classList.add("completed");
    }
  }

  dataURLtoBlob(dataURL) {
    const arr = dataURL.split(",");
    const mime = arr[0].match(/:(.*?);/)[1];
    const bstr = atob(arr[1]);
    let n = bstr.length;
    const u8arr = new Uint8Array(n);

    while (n--) {
      u8arr[n] = bstr.charCodeAt(n);
    }

    return new Blob([u8arr], { type: mime });
  }

  // Cleanup method
  cleanup() {
    if (this.qrScanner) {
      this.qrScanner.stop();
    }
    this.stopCamera();
  }
}

// Initialize app when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  window.guestBookApp = new GuestBookApp();
});

// Cleanup on page unload
window.addEventListener("beforeunload", () => {
  if (window.guestBookApp) {
    window.guestBookApp.cleanup();
  }
});

// Handle visibility change (when tab is hidden/shown)
document.addEventListener("visibilitychange", () => {
  if (document.hidden && window.guestBookApp) {
    // Pause cameras when tab is hidden
    if (window.guestBookApp.stream) {
      window.guestBookApp.stopCamera();
    }
  }
});
