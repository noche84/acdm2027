<?php

namespace App\Filament\Resources\Users;

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Operation;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string | UnitEnum | null $navigationGroup = 'Administration';

    protected static ?string $modelLabel = 'utilisateur';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label('Nom')->required(),
            TextInput::make('email')->label('E-mail')->email()->required()->unique(ignoreRecord: true),
            Select::make('role')->label('Rôle')->options(UserRole::class)->required()->default(UserRole::Editor->value)
                ->helperText('Éditeur : concerts, pages, partenaires et newsletter. Administrateur : tout, y compris les utilisateurs.'),
            TextInput::make('password')->label('Mot de passe')->password()->revealable()->minLength(12)
                ->required(fn (string $operation) => $operation === Operation::Create->value)
                ->dehydrated(fn (?string $state) => filled($state))
                ->helperText('12 caractères minimum. Laisser vide pour ne pas le changer.'),
            Toggle::make('is_active')->label('Compte actif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nom'),
                TextColumn::make('email')->label('E-mail'),
                TextColumn::make('role')->label('Rôle')->badge(),
                IconColumn::make('is_active')->label('Actif')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->visible(fn (User $r) => $r->id !== auth()->id()),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageUsers::route('/')];
    }
}
