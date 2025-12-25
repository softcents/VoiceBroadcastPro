<?php

namespace App\Filament\User\Pages;

use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use LaraZeus\Tabler\Tabler;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class Register extends BaseRegister
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPhoneFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }

    protected function getNameFormComponent(): Component
    {
        return TextInput::make('name')
            ->prefixIcon(Tabler::User)
            ->label(__('filament-panels::auth/pages/register.form.name.label'))
            ->placeholder('Enter your full name')
            ->required()
            ->maxLength(255)
            ->autofocus();
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->prefixIcon(Tabler::At)
            ->label(__('filament-panels::auth/pages/register.form.email.label'))
            ->placeholder('Enter your email address')
            ->email()
            ->required()
            ->maxLength(255)
            ->unique($this->getUserModel());
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->prefixIcon(Tabler::Lock)
            ->label(__('filament-panels::auth/pages/register.form.password.label'))
            ->placeholder('Create a secure password')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->rule(Password::default())
            ->showAllValidationMessages()
            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
            ->same('passwordConfirmation')
            ->validationAttribute(__('filament-panels::auth/pages/register.form.password.validation_attribute'));
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return TextInput::make('passwordConfirmation')
            ->prefixIcon(Tabler::LockCheck)
            ->label(__('filament-panels::auth/pages/register.form.password_confirmation.label'))
            ->placeholder('Re-enter your password')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->dehydrated(false);
    }

    protected function getPhoneFormComponent(): Component
    {
        return PhoneInput::make('email')
            ->label("Phone Number")
            ->required()
            ->defaultCountry('BD')
            ->unique($this->getUserModel());
    }
}
