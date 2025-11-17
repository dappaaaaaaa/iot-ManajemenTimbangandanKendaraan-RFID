<div wire:poll.2000ms="pollForTagId" x-data="{ tagId: @entangle('tagId') }">
    <input type="hidden" name="tag_id" x-model="tagId">
    <span x-text="tagId || 'Tap RFID anda'"></span>
</div>
