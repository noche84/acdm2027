<?php

namespace App\Filament\Resources\Subscribers;

use App\Enums\SubscriberStatus;
use App\Filament\Resources\Subscribers\Pages\ManageSubscribers;
use App\Models\Subscriber;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class SubscriberResource extends Resource
{
    protected static ?string $model = Subscriber::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string | UnitEnum | null $navigationGroup = 'Newsletter';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'abonné';

    public static function getNavigationBadge(): ?string
    {
        return (string) Subscriber::confirmed()->count();
    }

    public static function canDelete($record): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function canDeleteAny(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('email')->label('Adresse e-mail')->email()->required()->unique(ignoreRecord: true),
            Checkbox::make('consent')
                ->label('Cette personne m\'a demandé de l\'inscrire (consentement exprès)')
                ->accepted()
                ->dehydrated(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('email')->label('E-mail')->searchable()->copyable(),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('source')->label('Origine')->formatStateUsing(fn ($state) => match ($state) {
                    'site' => 'Formulaire du site', 'admin' => 'Ajout manuel', 'legacy' => 'Ancien site', 'mailchimp' => 'MailChimp', default => $state,
                }),
                TextColumn::make('confirmed_at')->label('Confirmé le')->date('d/m/Y')->sortable(),
                TextColumn::make('unsubscribed_at')->label('Désinscrit le')->date('d/m/Y')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(SubscriberStatus::class)->default(SubscriberStatus::Confirmed->value),
            ])
            ->recordActions([
                Action::make('unsubscribe')
                    ->label('Désinscrire')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Subscriber $r) => $r->status !== SubscriberStatus::Unsubscribed)
                    ->action(fn (Subscriber $r) => $r->unsubscribe()),
                DeleteAction::make()->modalDescription('Supprime définitivement l\'adresse (droit à l\'effacement). Pour simplement arrêter les envois, préférez « Désinscrire ».'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageSubscribers::route('/')];
    }
}
