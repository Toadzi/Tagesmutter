<?php

namespace App\Config;

/**
 * Zentrale Definition der Bildungsbereiche.
 * Wird von Berichten, Tagebuch, Textbausteinen und PDF-Export verwendet.
 */
class Bildungsbereiche
{
    /** @var string[] Schlüssel der Bildungsbereiche in Anzeige-Reihenfolge */
    public const KEYS = [
        'sprache',
        'motorik',
        'sozial',
        'kognitiv',
        'kreativ',
        'musik',
        'natur',
        'alltag',
    ];

    /** @var array<string,string> Klartext-Bezeichnungen für PDF und Templates */
    public const LABELS = [
        'sprache'  => 'Sprache & Kommunikation',
        'motorik'  => 'Motorik & Bewegung',
        'sozial'   => 'Soziale Kompetenz',
        'kognitiv' => 'Kognitive Entwicklung',
        'kreativ'  => 'Kreativität & Gestalten',
        'musik'    => 'Musik & Rhythmik',
        'natur'    => 'Natur & Umwelt',
        'alltag'   => 'Alltagskompetenz',
    ];

    /** @var array<string,string> Emoji-Symbole für die UI */
    public const EMOJIS = [
        'sprache'  => '💬',
        'motorik'  => '🏃',
        'sozial'   => '🤝',
        'kognitiv' => '🧠',
        'kreativ'  => '🎨',
        'musik'    => '🎵',
        'natur'    => '🌿',
        'alltag'   => '⭐',
    ];
}
