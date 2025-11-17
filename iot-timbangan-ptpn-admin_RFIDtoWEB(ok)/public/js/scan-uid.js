// public/js/scan-uid.js
document.addEventListener("DOMContentLoaded", function () {
    const pollingInterval = 1000; // 1 detik

    setInterval(async () => {
        try {
            const response = await fetch("/rfid/latest"); // pastikan route ini ada
            const data = await response.json();

            if (data.uid) {
                const event = new CustomEvent("rfid-scanned", {
                    detail: { uid: data.uid },
                });
                window.dispatchEvent(event);
            }
        } catch (e) {
            console.error("Gagal polling UID:", e);
        }
    }, pollingInterval);
});
