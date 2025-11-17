<?php

namespace App\Filament\Resources\RfidResource\Pages;

use App\Filament\Resources\RfidResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\QueryException;


class CreateRfid extends CreateRecord
{
    protected static string $resource = RfidResource::class;

    public ?string $tag_id = null;

    protected $listeners = ['setTagId'];

    // Menerima event setTagId dari JS
    public function setTagId(string $tagId): void
    {
        $this->tag_id = $tagId;

        // Isi form dengan tag_id
        $this->form->fill([
            'tag_id' => $this->tag_id,
        ]);
    }

    public function mount(): void
    {
        parent::mount();

        $this->form->fill([
            'tag_id' => $this->tag_id,
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        logger()->info('RFID form data:', $data);

        // Pastikan tag_id tidak null
        if (empty($data['tag_id'])) {
            $data['tag_id'] = $this->tag_id;
        }

        // Tambahkan scanned_at jika kosong
        if (empty($data['scanned_at'])) {
            $data['scanned_at'] = now();
        }

        // Status otomatis
        $data['status'] = 'active';

        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        try {
            return parent::handleRecordCreation($data);
        } catch (QueryException $e) {
            if ($e->getCode() == '23505') { // Unique violation in PostgreSQL
                Notification::make()
                    ->title('Tag RFID sudah terdaftar')
                    ->danger()
                    ->send();

                $this->halt(); // Stop the form submission
            }
            throw $e;
        }
    }
}
