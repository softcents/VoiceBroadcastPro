<?php

namespace App\Filament\User\Resources\ApiTokenResource\Pages;

use App\Filament\User\Resources\ApiTokenResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class ManageApiTokens extends ManageRecords
{
    protected static string $resource = ApiTokenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('create')
                ->label('Create API Token')
                ->form([
                    TextInput::make('name')
                        ->required()
                        ->label('Token Name')
                        ->placeholder('My API Token'),
                ])
                ->action(function (array $data) {
                    $user = auth()->user();
                    $token = $user->createToken($data['name']);
                    
                    $this->replaceMountedAction('showToken', [
                        'token' => $token->plainTextToken,
                    ]);
                }),
            Actions\Action::make('showToken')
                ->label('Token Created')
                ->modalHeading('API Token Created')
                ->modalDescription('Please copy your API token now. You will not be able to see it again.')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->form([
                    TextInput::make('token')
                        ->label('API Token')
                        ->default(fn ($arguments) => $arguments['token'])
                        ->readonly()
                        ->extraInputAttributes(['onclick' => 'this.select()']), 
                ])
                ->mountUsing(fn () => null), // Hack to make it mountable via replaceMountedAction
        ];
    }
}
