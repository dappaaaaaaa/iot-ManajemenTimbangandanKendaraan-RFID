let currentTag = null;

function startPolling() {
    console.log("📡 Polling RFID dimulai...");

    setInterval(async () => {
        try {
            const res = await fetch("/api/rfid/latest-scan");
            const data = await res.json();

            if (data.success && data.tag_id && currentTag !== data.tag_id) {
                currentTag = data.tag_id;
                console.log("✅ Tag diupdate oleh Livewire:", data.tag_id);

                if (window.Livewire) {
                    console.log(
                        "📤 Call Livewire.setTagId dengan:",
                        data.tag_id
                    );

                    Livewire.all().forEach((component) => {
                        if (typeof component.call === "function") {
                            component.call("setTagId", data.tag_id);
                        }
                    });
                }
            }
        } catch (e) {
            console.error("❌ Gagal polling RFID:", e);
        }
    }, 2000);
}

document.addEventListener("livewire:initialized", () => {
    console.log("✅ Livewire siap, mulai polling...");
    startPolling();
});
