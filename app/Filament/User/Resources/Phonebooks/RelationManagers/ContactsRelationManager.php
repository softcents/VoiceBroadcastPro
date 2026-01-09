<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\Phonebooks\RelationManagers;

use EightyNine\ExcelImport\Tables\ExcelImportRelationshipAction;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use LaraZeus\Tabler\Tabler;
use Propaganistas\LaravelPhone\PhoneNumber;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

final class ContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'contacts';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextColumn::make('id')
                            ->label('ID')
                            ->sortable()
                            ->width(0)
                            ->alignCenter(),
                        TextInput::make('first_name')
                            ->label('First Name')
                            ->required(),
                        TextInput::make('last_name')
                            ->label('Last Name'),
                        PhoneInput::make('phone_number')
                            ->label('Phone Number')
                            ->defaultCountry('BD')
                            ->onlyCountries(['BD'])
                            ->required()
                            ->rules(['phone:BD']),
                    ]),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('first_name')
                    ->label('First Name')
                    ->placeholder('-'),
                TextEntry::make('last_name')
                    ->label('Last Name')
                    ->placeholder('-'),
                TextEntry::make('phone_number')
                    ->label('Phone Number')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label('Updated At')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('phone_number')
            ->columns([
                TextColumn::make('first_name')
                    ->label('First Name')
                    ->searchable(),
                TextColumn::make('last_name')
                    ->label('Last Name')
                    ->searchable(),
                TextColumn::make('phone_number')
                    ->label('Phone Number')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                ExcelImportRelationshipAction::make()
                    ->slideOver()
                    ->icon(Tabler::TableImport)
                    ->color('primary')
                    ->sampleExcel(
                        sampleData: [
                            ['first_name' => 'Bishwajit', 'last_name' => 'Adhikary', 'phone_number' => '+8801700000000'],
                            ['first_name' => 'John', 'last_name' => 'Doe', 'phone_number' => '+8801600000000'],
                        ],
                        fileName: 'sample.xlsx',
                        sampleButtonLabel: 'Download Sample',
                        customiseActionUsing: fn (Action $action) => $action->color('secondary')->icon(Tabler::TableExport),
                    )
                    ->mutateBeforeValidationUsing(
                        closure: function (array $data): array {
                            if (isset($data['phone_number'])) {
                                // Convert to string in case it's an integer from Excel
                                $phoneNumber = (string) $data['phone_number'];

                                // Remove any whitespace, dots, or dashes
                                $phoneNumber = preg_replace('/[\s.\-()]/', '', $phoneNumber);

                                // Remove any existing + prefix to avoid duplication
                                $phoneNumber = mb_ltrim($phoneNumber, '+');

                                // Add + prefix
                                $phoneNumber = '+'.$phoneNumber;

                                // Format using Laravel Phone
                                try {
                                    $data['phone_number'] = new PhoneNumber($phoneNumber)->formatE164();
                                } catch (Exception) {
                                    // If formatting fails, keep the original value with + prefix
                                    $data['phone_number'] = $phoneNumber;
                                }
                            }

                            return $data;
                        },
                        shouldRetainBeforeValidationMutation: true,
                    )
                    ->validateUsing([
                        'phone_number' => ['required', 'phone:E164'],
                        'first_name' => ['nullable', 'string', 'max:255'],
                        'last_name' => ['nullable', 'string', 'max:255'],
                    ])
                    ->successNotificationTitle('Contacts Imported Successfully'),
                CreateAction::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
