// resources/js/echo.js

import Echo from "laravel-echo";

// --- HAPUS ATAU KOMENTARI BARIS BERIKUT KARENA ANDA MENGGUNAKAN REVERB, BUKAN PUSHER ---
// import Pusher from "pusher-js";
// window.Pusher = Pusher;
// --- AKHIR BAGIAN YANG DIHAPUS/DIKOMENTARI ---

const echo = new Echo({
    broadcaster: "reverb", // <-- KOREKSI PENTING: UBAH DARI "pusher" KE "reverb"
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname, // Tambahkan fallback jika VITE_REVERB_HOST kosong
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080, // Tambahkan fallback
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 8080, // Tambahkan fallback
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? "https") === "https", // Gunakan VITE_REVERB_SCHEME
    disableStats: true,
});

echo.channel("rfid-channel").listen(".RfidTapped", (e) => {
    document.getElementById("waiting").classList.add("hidden");
    document.getElementById("createForm").classList.remove("hidden");
    document.getElementById("tag_id").value = e.uid;
});
