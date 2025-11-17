console.log("✅ rfid-poller-debug.js loaded");

let lastTagId = null; // Menyimpan tag_id terakhir
let lastUpdateTime = 0;

function sendTagToLivewire(tag) {
    const now = Date.now();
    if (tag === lastTagId && now - lastUpdateTime < 5000) {
        console.log("⏳ RFID sama, diabaikan dalam 5 detik terakhir.");
        return;
    }
    lastTagId = tag;
    lastUpdateTime = now;

    if (!window.Livewire) {
        console.warn("⚠️ Livewire belum siap.");
        return;
    }
    console.log(`📡 Mengirim tag_id ke Livewire: ${tag}`);
    Livewire.dispatch("setTagId", { tagId: tag });
}

async function checkRfidApi() {
    try {
        const res = await fetch("/api/rfid/latest-scan");
        const data = await res.json();

        if (data.success && data.tag_id) {
            if (data.tag_id !== lastTagId) {
                console.log(`🔄 Data API diterima: ${data.tag_id}`);
                lastTagId = data.tag_id;
                sendTagToLivewire(data.tag_id);
            }
        } else {
            console.warn("⚠️ Tidak ada tag_id di API.");
        }
    } catch (err) {
        console.error("❌ Gagal fetch API RFID:", err);
    }

    setTimeout(checkRfidApi, 3000);
}

document.addEventListener("livewire:initialized", () => {
    // Cek apakah URL mengandung /create
    if (!window.location.pathname.includes("/create")) {
        console.log(
            "⏹ Halaman ini bukan halaman create, polling RFID dinonaktifkan."
        );
        return;
    }

    console.log("✅ Livewire siap, mulai polling RFID di halaman /create...");
    checkRfidApi();
});
