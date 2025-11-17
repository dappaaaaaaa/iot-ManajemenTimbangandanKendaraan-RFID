import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        // Ubah host menjadi '0.0.0.0'
        host: "0.0.0.0", // Ini akan membuat Vite mendengarkan di semua interface
        port: 5173,
        hmr: {
            // HMR host tetap spesifik ke IP Laravel Anda
            host: "192.168.18.13",
            clientPort: 5173, // Kadang perlu eksplisit menentukan clientPort
        },
        // Tambahkan ini untuk memaksa CORS, terutama jika host tidak cocok persis
        cors: true,
    },
});
