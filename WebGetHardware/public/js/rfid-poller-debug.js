console.log("✅ rfid-poller-improved.js loaded");

class RfidPoller {
    constructor() {
        this.lastTagId = null;
        this.lastTimestamp = null;
        this.pollingActive = false;
        this.pollingInterval = null;
        this.retryCount = 0;
        this.maxRetries = 5;
        this.isPageActive = true;

        // Bind methods
        this.checkRfidApi = this.checkRfidApi.bind(this);
        this.sendTagToLivewire = this.sendTagToLivewire.bind(this);

        // Listen for page visibility changes
        document.addEventListener("visibilitychange", () => {
            this.isPageActive = !document.hidden;
            if (this.isPageActive && this.shouldPoll()) {
                console.log("🔄 Page active, resuming RFID polling");
                this.startPolling();
            } else if (!this.isPageActive) {
                console.log("⏸️ Page hidden, pausing RFID polling");
                this.stopPolling();
            }
        });
    }

    shouldPoll() {
        const currentUrl = window.location.href;
        const targetUrls = [
            "http://127.0.0.1:8000/admin/rfids/create",
            "http://localhost:8000/admin/rfids/create", // untuk development
            "/admin/rfids/create", // relative URL
        ];

        return targetUrls.some((url) => {
            if (url.startsWith("http")) {
                return currentUrl === url;
            } else {
                return currentUrl.includes(url);
            }
        });
    }

    sendTagToLivewire(data) {
        if (!window.Livewire) {
            console.warn("⚠️ Livewire belum siap.");
            return false;
        }

        console.log(`📡 Mengirim data ke Livewire:`, data);

        try {
            // Kirim tag_id utama
            Livewire.dispatch("setTagId", {
                tagId: data.tag_id,
                mode: data.mode,
                ownerName: data.owner_name,
                vehicleNumber: data.vehicle_number,
            });

            // Show notification jika ada
            if (data.mode === "register") {
                this.showNotification(
                    `🆕 Kartu baru terdeteksi: ${data.tag_id}`,
                    "info"
                );
            } else {
                this.showNotification(
                    `✅ Kartu terdaftar: ${data.tag_id}`,
                    "success"
                );
            }

            return true;
        } catch (error) {
            console.error("❌ Error mengirim ke Livewire:", error);
            return false;
        }
    }

    async checkRfidApi() {
        if (this.pollingActive || !this.isPageActive) return;

        this.pollingActive = true;

        try {
            const response = await fetch("/api/rfid/latest-scan", {
                cache: "no-store",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                },
            });

            if (!response.ok) {
                throw new Error(
                    `HTTP ${response.status}: ${response.statusText}`
                );
            }

            const data = await response.json();

            if (data.success && data.tag_id) {
                // Cek apakah ini data baru (berdasarkan timestamp)
                const isNewData =
                    !this.lastTimestamp ||
                    data.timestamp > this.lastTimestamp ||
                    data.tag_id !== this.lastTagId;

                if (isNewData) {
                    console.log(`🔄 Data RFID baru diterima:`, data);

                    this.lastTagId = data.tag_id;
                    this.lastTimestamp = data.timestamp;
                    this.retryCount = 0; // Reset retry counter

                    const success = this.sendTagToLivewire(data);
                    if (success) {
                        console.log(`✅ Data berhasil dikirim ke Livewire`);
                    }
                } else {
                    console.log(`🔄 Data sama, skip: ${data.tag_id}`);
                }
            } else {
                // No data available
                if (this.retryCount === 0) {
                    console.log("⏳ Menunggu scan RFID...");
                }
            }
        } catch (error) {
            this.retryCount++;
            console.error(
                `❌ Error fetching RFID API (attempt ${this.retryCount}):`,
                error
            );

            if (this.retryCount >= this.maxRetries) {
                console.error("🚫 Max retries reached, stopping polling");
                this.stopPolling();
                this.showNotification("❌ Koneksi RFID terputus", "error");
                return;
            }
        }

        this.pollingActive = false;
    }

    startPolling() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
        }

        console.log("🚀 Memulai RFID polling (500ms interval)...");
        this.pollingInterval = setInterval(this.checkRfidApi, 500);

        // Langsung check sekali
        setTimeout(this.checkRfidApi, 100);
    }

    stopPolling() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
            console.log("⏹️ RFID polling dihentikan");
        }
    }

    showNotification(message, type = "info") {
        // Buat simple notification jika tidak ada sistem notifikasi
        console.log(`🔔 ${type.toUpperCase()}: ${message}`);

        // Jika menggunakan toast library seperti Toastify atau SweetAlert
        if (window.Toastify) {
            Toastify({
                text: message,
                duration: 3000,
                gravity: "top",
                position: "right",
                backgroundColor:
                    type === "error"
                        ? "#f56565"
                        : type === "success"
                        ? "#48bb78"
                        : "#4299e1",
            }).showToast();
        }
    }

    // Method untuk testing
    async testScan(tagId = null) {
        try {
            const testTagId =
                tagId ||
                "TEST" + Math.random().toString(36).substr(2, 6).toUpperCase();
            const response = await fetch(
                `/api/rfid/test-scan?tag_id=${testTagId}`,
                {
                    method: "GET",
                    headers: { Accept: "application/json" },
                }
            );

            const result = await response.json();
            console.log("🧪 Test scan result:", result);

            return result;
        } catch (error) {
            console.error("❌ Test scan error:", error);
        }
    }
}

// Initialize poller
const rfidPoller = new RfidPoller();

// Start polling when Livewire is ready
document.addEventListener("livewire:initialized", () => {
    if (rfidPoller.shouldPoll()) {
        console.log("✅ Livewire siap di halaman RFID, memulai polling...");
        rfidPoller.startPolling();
    } else {
        console.log("⏹️ Halaman bukan form RFID, polling tidak dijalankan.");
    }
});

// Cleanup on page unload
window.addEventListener("beforeunload", () => {
    rfidPoller.stopPolling();
});

// Expose to global scope untuk debugging
window.rfidPoller = rfidPoller;
