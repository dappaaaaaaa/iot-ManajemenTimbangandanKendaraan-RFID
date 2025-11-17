<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Form;

class EditProfile extends BaseEditProfile
{
    public function getUser(): \App\Models\User
    {
        return Auth::user();
    }

    public function mount(): void
    {
        parent::mount();

        $this->form->fill([
            'name' => $this->getUser()->name,
            'email' => $this->getUser()->email,
            'role' => $this->getRole(),
        ]);
    }

    public function getRole(): string
    {
        return $this->getUser()?->getRoleNames()->first() ?? 'Tanpa Role';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Name')
                    ->required(),

                Forms\Components\TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),

                Forms\Components\TextInput::make('role')
                    ->label('Role')
                    ->default(fn() => $this->getRole()) // penting
                    ->disabled()
                    ->dehydrated(false), // agar tidak ikut disimpan ke database

                Forms\Components\TextInput::make('password')
                    ->label('New password')
                    ->password()
                    ->dehydrateStateUsing(fn($state) => filled($state) ? bcrypt($state) : null)
                    ->required(false),
            ]);
    }
}
