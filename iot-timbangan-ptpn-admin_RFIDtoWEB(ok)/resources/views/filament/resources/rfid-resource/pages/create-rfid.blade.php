<x-filament::page>
    <div x-data x-init="setInterval(async () => {
        try {
            const res = await fetch('/api/rfid/get-pending-tag');
            if (!res.ok) return;
            const data = await res.json();
            const input = document.querySelector('#tag-id-input');
    
            if (data.tag_id && input && input.value !== data.tag_id) {
                input.value = data.tag_id;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                console.log('Tag diupdate:', data.tag_id);
            }
        } catch (e) {
            console.warn('Polling gagal:', e);
        }
    }, 2000)">
    </div>
</x-filament::page>
