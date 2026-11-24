<?php

namespace App\Filament\Resources\Newsletters\Pages;

use App\Filament\Resources\Newsletters\NewsletterResource;
use App\Mail\NewsletterMail;
use App\Models\Subscriber;
use App\Services\NewsletterSender;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditNewsletter extends EditRecord
{
    protected static string $resource = NewsletterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('Aperçu')
                ->icon(Heroicon::OutlinedEye)
                ->color('gray')
                ->modalHeading('Aperçu de l\'e-mail')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Fermer')
                ->modalWidth('4xl')
                ->modalContent(fn () => view('filament.newsletter-preview', [
                    'html' => (new NewsletterMail($this->record))->render(),
                ])),

            Action::make('test')
                ->label('Envoyer un test')
                ->icon(Heroicon::OutlinedBeaker)
                ->color('gray')
                ->schema([
                    TextInput::make('email')->label('Adresse de test')->email()->required()->default(fn () => auth()->user()?->email),
                ])
                ->action(function (array $data, NewsletterSender $sender) {
                    $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
                    $sender->sendTest($this->record, $data['email']);
                    Notification::make()->title('Test envoyé à '.$data['email'])->success()->send();
                }),

            Action::make('send')
                ->label(fn () => $this->record->recipients_count > 0 ? 'Reprendre l\'envoi' : 'Envoyer aux abonnés')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->color('primary')
                ->visible(fn () => ! $this->record->isSent())
                ->requiresConfirmation()
                ->modalHeading('Envoyer la newsletter ?')
                ->modalDescription(fn (NewsletterSender $sender) => 'Elle partira vers '.$sender->remaining($this->record).' abonné(s). Cette action ne peut pas être annulée.')
                ->modalSubmitActionLabel('Oui, envoyer')
                ->action(function (NewsletterSender $sender) {
                    $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
                    $result = $sender->send($this->record->refresh());

                    $notification = Notification::make()
                        ->title("{$result['sent']} e-mail(s) envoyé(s)")
                        ->body(collect([
                            $result['failed'] ? "{$result['failed']} échec(s) : voir le journal." : null,
                            $result['remaining'] ? "Il reste {$result['remaining']} abonné(s) : cliquez à nouveau sur « Reprendre l'envoi » dans une heure (limite d'envoi de l'hébergeur)." : 'Envoi terminé.',
                        ])->filter()->implode(' '));

                    $result['remaining'] || $result['failed'] ? $notification->warning()->persistent()->send() : $notification->success()->send();
                    $this->refreshFormData(['status']);
                }),

            DeleteAction::make()->visible(fn () => ! $this->record->isSent()),
        ];
    }

    protected function getFormActions(): array
    {
        return $this->record->isSent() ? [] : parent::getFormActions();
    }

    public function getSubheading(): ?string
    {
        return $this->record->isSent()
            ? 'Envoyée le '.$this->record->sent_at?->format('d/m/Y à H:i').' à '.$this->record->recipients_count.' abonné(s).'
            : Subscriber::confirmed()->count().' abonné(s) confirmé(s).';
    }
}
