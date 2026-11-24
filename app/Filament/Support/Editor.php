<?php

namespace App\Filament\Support;

use Filament\Forms\Components\RichEditor;

/**
 * Éditeur de texte riche partagé, réglé pour un copier-coller depuis Word :
 * seules les mises en forme utiles sont proposées, le reste est nettoyé
 * à l'enregistrement par App\Support\HtmlCleaner.
 */
class Editor
{
    public static function make(string $name, bool $full = false): RichEditor
    {
        $buttons = $full
            ? [
                ['bold', 'italic', 'underline', 'link'],
                ['h2', 'h3'],
                ['bulletList', 'orderedList', 'blockquote'],
                ['table', 'horizontalRule'],
                ['clearFormatting'],
                ['undo', 'redo'],
            ]
            : [
                ['bold', 'italic', 'underline', 'link'],
                ['bulletList'],
                ['clearFormatting'],
                ['undo', 'redo'],
            ];

        return RichEditor::make($name)
            ->toolbarButtons($buttons)
            ->extraInputAttributes(['style' => 'min-height: '.($full ? '18rem' : '7rem')]);
    }
}
