<script>
    document.addEventListener("DOMContentLoaded", function() {
        let currentTag = null;

        setInterval(async () => {
            try {
                const res = await fetch("/api/rfid/latest-scan");
                const data = await res.json();

                if (data.success && data.tag_id && currentTag !== data.tag_id) {
                    currentTag = data.tag_id;
                    console.log("Tag diupdate:", currentTag);

                    // 🔥 Emit ke Livewire
                    Livewire.emit('rfidTagScanned', currentTag);
                }
            } catch (e) {
                console.error("Gagal polling RFID:", e);
            }
        }, 2000);
    });
</script>
