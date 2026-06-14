<?php
/**
 * Seeder für Demodaten.
 * Erzeugt 5 Kinder (1-3 Jahre alt) inkl. Kontakte, Tagebuch, Meilensteine,
 * Anwesenheit und Berichte. Bestehende Daten bleiben unberührt.
 *
 * Aufruf:  php scripts/seed_demo.php
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Database;

$db = Database::get();
$db->beginTransaction();

try {
    // --- Stichtag: heute ---
    $today = new DateTimeImmutable('2026-04-24');

    /** @var array<int,array<string,mixed>> $kinderDaten */
    $kinderDaten = [
        [
            'vorname' => 'Emma', 'nachname' => 'Schneider', 'geschlecht' => 'w',
            'geburtsdatum' => '2023-06-14', // ~ 2 J 10 Mo
            'adresse' => 'Lindenweg 12', 'plz' => '6300', 'ort' => 'Zug',
            'muttersprache' => 'Deutsch',
            'betreuung_start' => '2024-08-01',
            'betreuungszeiten' => 'Mo-Do 08:00-15:00',
            'allergien' => 'Keine bekannt',
            'medikamente' => '',
            'besonderheiten' => 'Schläft gerne mit Kuscheltier "Hase".',
            'arzt_name' => 'Dr. med. Weber',
            'arzt_telefon' => '041 711 22 33',
            'kontakte' => [
                ['rolle' => 'Mutter', 'name' => 'Sarah Schneider', 'mobil' => '079 123 45 67', 'email' => 'sarah.schneider@example.ch', 'notfall' => 1, 'abhol' => 1],
                ['rolle' => 'Vater',  'name' => 'Thomas Schneider','mobil' => '079 234 56 78', 'email' => 'thomas.schneider@example.ch', 'notfall' => 1, 'abhol' => 1],
                ['rolle' => 'Grossmutter', 'name' => 'Heidi Schneider', 'mobil' => '079 345 67 89', 'notfall' => 0, 'abhol' => 1],
            ],
        ],
        [
            'vorname' => 'Noah', 'nachname' => 'Fischer', 'geschlecht' => 'm',
            'geburtsdatum' => '2024-01-22', // ~ 2 J 3 Mo
            'adresse' => 'Sonnenbergstrasse 7', 'plz' => '6330', 'ort' => 'Cham',
            'muttersprache' => 'Deutsch',
            'betreuung_start' => '2025-03-01',
            'betreuungszeiten' => 'Di, Do, Fr 07:30-16:30',
            'allergien' => 'Nüsse (leicht)',
            'medikamente' => '',
            'besonderheiten' => 'Liebt Bagger und Traktoren.',
            'arzt_name' => 'Kinderarztpraxis Cham',
            'arzt_telefon' => '041 780 11 22',
            'kontakte' => [
                ['rolle' => 'Mutter', 'name' => 'Lisa Fischer', 'mobil' => '078 111 22 33', 'email' => 'lisa.fischer@example.ch', 'notfall' => 1, 'abhol' => 1],
                ['rolle' => 'Vater',  'name' => 'Marco Fischer','mobil' => '078 222 33 44', 'email' => 'marco.fischer@example.ch', 'notfall' => 1, 'abhol' => 1],
            ],
        ],
        [
            'vorname' => 'Mia', 'nachname' => 'Keller', 'geschlecht' => 'w',
            'geburtsdatum' => '2024-11-03', // ~ 1 J 5 Mo
            'adresse' => 'Dorfstrasse 45', 'plz' => '6340', 'ort' => 'Baar',
            'muttersprache' => 'Deutsch/Italienisch',
            'betreuung_start' => '2025-09-01',
            'betreuungszeiten' => 'Mo, Mi, Fr 08:00-14:00',
            'allergien' => '',
            'medikamente' => '',
            'besonderheiten' => 'Mag keine lauten Geräusche.',
            'arzt_name' => 'Dr. med. Rossi',
            'arzt_telefon' => '041 761 33 44',
            'kontakte' => [
                ['rolle' => 'Mutter', 'name' => 'Giulia Keller', 'mobil' => '076 333 44 55', 'email' => 'giulia.keller@example.ch', 'notfall' => 1, 'abhol' => 1],
                ['rolle' => 'Vater',  'name' => 'Daniel Keller','mobil' => '076 444 55 66', 'email' => 'daniel.keller@example.ch', 'notfall' => 1, 'abhol' => 1],
                ['rolle' => 'Tante',  'name' => 'Anna Keller',  'mobil' => '076 555 66 77', 'notfall' => 0, 'abhol' => 1],
            ],
        ],
        [
            'vorname' => 'Liam', 'nachname' => 'Brunner', 'geschlecht' => 'm',
            'geburtsdatum' => '2023-09-30', // ~ 2 J 7 Mo
            'adresse' => 'Bahnhofstrasse 3', 'plz' => '6301', 'ort' => 'Zug',
            'muttersprache' => 'Deutsch/Englisch',
            'betreuung_start' => '2024-11-01',
            'betreuungszeiten' => 'Mo-Fr 08:30-15:30',
            'allergien' => 'Laktose (Intoleranz)',
            'medikamente' => 'Laktase-Tabletten vor Milchprodukten',
            'besonderheiten' => 'Zweisprachig, spricht mit Mutter Englisch.',
            'arzt_name' => 'Dr. med. Huber',
            'arzt_telefon' => '041 720 55 66',
            'kontakte' => [
                ['rolle' => 'Mutter', 'name' => 'Emily Brunner', 'mobil' => '079 555 66 77', 'email' => 'emily.brunner@example.ch', 'notfall' => 1, 'abhol' => 1],
                ['rolle' => 'Vater',  'name' => 'Stefan Brunner','mobil' => '079 666 77 88', 'email' => 'stefan.brunner@example.ch', 'notfall' => 1, 'abhol' => 1],
            ],
        ],
        [
            'vorname' => 'Sophia', 'nachname' => 'Weber', 'geschlecht' => 'w',
            'geburtsdatum' => '2025-03-12', // ~ 1 J 1 Mo
            'adresse' => 'Rigistrasse 18', 'plz' => '6312', 'ort' => 'Steinhausen',
            'muttersprache' => 'Deutsch',
            'betreuung_start' => '2026-02-01',
            'betreuungszeiten' => 'Di, Do 09:00-15:00',
            'allergien' => '',
            'medikamente' => '',
            'besonderheiten' => 'Braucht Mittagsschlaf ca. 12:00-14:00.',
            'arzt_name' => 'Kinderpraxis Steinhausen',
            'arzt_telefon' => '041 741 88 99',
            'kontakte' => [
                ['rolle' => 'Mutter', 'name' => 'Nadine Weber', 'mobil' => '078 777 88 99', 'email' => 'nadine.weber@example.ch', 'notfall' => 1, 'abhol' => 1],
                ['rolle' => 'Vater',  'name' => 'Patrick Weber','mobil' => '078 888 99 00', 'email' => 'patrick.weber@example.ch', 'notfall' => 1, 'abhol' => 1],
                ['rolle' => 'Grossvater', 'name' => 'Hans Weber', 'mobil' => '078 999 00 11', 'notfall' => 0, 'abhol' => 1],
            ],
        ],
    ];

    // Tagebucheintrag-Vorlagen: [titel, text, stimmung, kategorien]
    $tagebuchVorlagen = [
        ['Erster Sandkasten-Tag', 'Heute haben wir lange im Sandkasten gespielt. Grosse Freude beim Schaufeln und Eimerchen füllen.', 5, ['motorik','sozial']],
        ['Bilderbuch', 'Wir haben gemeinsam das Tierbuch angeschaut. Viele Tiernamen wurden nachgesprochen.', 4, ['sprache','kognitiv']],
        ['Malen mit Fingerfarben', 'Heute wurde ausgiebig gemalt. Rot und Blau wurden fleissig verwendet und auch vermischt.', 5, ['kreativ']],
        ['Waldspaziergang', 'Kleiner Ausflug in den nahen Wald. Blätter gesammelt, Steine inspiziert, Vögel beobachtet.', 4, ['natur','motorik']],
        ['Erste Wörter', 'Heute neue Worte gesagt: "Hund" und "mehr". Wir haben uns sehr gefreut.', 5, ['sprache']],
        ['Turnen im Bewegungsraum', 'Grosse Matte, Bälle und Reifen. Klettern über den Schaumstoffberg hat viel Spass gemacht.', 4, ['motorik']],
        ['Singkreis', 'Wir haben "Alle meine Entchen" und "Hänschen klein" gesungen. Mitgeklatscht wurde begeistert.', 5, ['musik','sozial']],
        ['Mittagessen', 'Hat heute selbständig mit dem Löffel gegessen. Brokkoli war nicht so beliebt, Kartoffeln dafür umso mehr.', 3, ['alltag']],
        ['Spiel mit Bausteinen', 'Grosse Türme gebaut und mit Begeisterung wieder umgeworfen. Farben sortiert.', 4, ['kognitiv','motorik']],
        ['Gemeinsames Zvieri', 'Apfel und Brot selbständig gegessen. Hat anderen Kindern vom Apfel abgegeben.', 4, ['sozial','alltag']],
        ['Regen im Garten', 'Mit Gummistiefeln in Pfützen gesprungen. Sehr nass, sehr glücklich.', 5, ['natur','motorik']],
        ['Puzzle geschafft', 'Das 6-teilige Holzpuzzle selbständig fertiggestellt. Grosser Stolz war zu sehen.', 5, ['kognitiv']],
    ];

    $meilensteinVorlagen = [
        ['motorik',  'Läuft sicher und klettert erste Stufen.'],
        ['sprache',  'Spricht erste Zwei-Wort-Sätze.'],
        ['sozial',   'Zeigt Empathie, tröstet andere Kinder.'],
        ['alltag',   'Isst selbständig mit dem Löffel.'],
        ['kognitiv', 'Erkennt und benennt Grundfarben.'],
        ['motorik',  'Fährt Laufrad sicher.'],
        ['sprache',  'Benennt gängige Tiere.'],
        ['kreativ',  'Malt erste Kreise und geschlossene Formen.'],
    ];

    // --- Prepared Statements ---
    $insKind = $db->prepare("
        INSERT INTO kinder (vorname, nachname, geburtsdatum, geschlecht, adresse, plz, ort,
            muttersprache, betreuung_start, betreuungszeiten, allergien, medikamente,
            besonderheiten, arzt_name, arzt_telefon, foto_freigabe, aktiv)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1)
    ");
    $insKontakt = $db->prepare("
        INSERT INTO kontakte (kind_id, rolle, name, mobil, email, notfall, abholberechtigt)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $insTagebuch = $db->prepare("
        INSERT INTO tagebuch (kind_id, datum, titel, text, stimmung, kategorien, erstellt_am)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $insMeilenstein = $db->prepare("
        INSERT INTO meilensteine (kind_id, datum, bildungsbereich, beschreibung)
        VALUES (?, ?, ?, ?)
    ");
    $insAnwesenheit = $db->prepare("
        INSERT INTO anwesenheit (kind_id, datum, ankunft, abholung, abwesend, abwesenheitsgrund, notiz)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $insBericht = $db->prepare("
        INSERT INTO berichte (kind_id, titel, zeitraum_von, zeitraum_bis, inhalt, status, erstellt_am)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $kinderAngelegt = 0;
    $tagebuchAngelegt = 0;
    $meilensteineAngelegt = 0;
    $anwesenheitAngelegt = 0;

    foreach ($kinderDaten as $k) {
        $insKind->execute([
            $k['vorname'], $k['nachname'], $k['geburtsdatum'], $k['geschlecht'],
            $k['adresse'], $k['plz'], $k['ort'], $k['muttersprache'],
            $k['betreuung_start'], $k['betreuungszeiten'],
            $k['allergien'], $k['medikamente'], $k['besonderheiten'],
            $k['arzt_name'], $k['arzt_telefon'],
        ]);
        $kindId = (int)$db->lastInsertId();
        $kinderAngelegt++;

        // --- Kontakte ---
        foreach ($k['kontakte'] as $c) {
            $insKontakt->execute([
                $kindId,
                $c['rolle'],
                $c['name'],
                $c['mobil'] ?? null,
                $c['email'] ?? null,
                $c['notfall'] ?? 0,
                $c['abhol'] ?? 0,
            ]);
        }

        // --- Tagebuch: 8 Einträge in den letzten 60 Tagen ---
        $vorlagenShuffled = $tagebuchVorlagen;
        shuffle($vorlagenShuffled);
        $anzahlEintraege = 8;
        for ($i = 0; $i < $anzahlEintraege; $i++) {
            $daysAgo = 3 + ($i * 6) + random_int(0, 3);
            $datum = $today->modify("-{$daysAgo} days")->format('Y-m-d');
            $erstellt = $datum . ' ' . sprintf('%02d:%02d:00', random_int(14, 18), random_int(0, 59));
            $vorlage = $vorlagenShuffled[$i % count($vorlagenShuffled)];
            $insTagebuch->execute([
                $kindId,
                $datum,
                $vorlage[0],
                $vorlage[1],
                $vorlage[2],
                json_encode($vorlage[3], JSON_UNESCAPED_UNICODE),
                $erstellt,
            ]);
            $tagebuchAngelegt++;
        }

        // --- Meilensteine: 4 Stück über Betreuungszeit verteilt ---
        $meilVorlagen = $meilensteinVorlagen;
        shuffle($meilVorlagen);
        for ($i = 0; $i < 4; $i++) {
            $daysAgo = 20 + $i * 35 + random_int(0, 10);
            $datum = $today->modify("-{$daysAgo} days")->format('Y-m-d');
            $v = $meilVorlagen[$i % count($meilVorlagen)];
            $insMeilenstein->execute([$kindId, $datum, $v[0], $v[1]]);
            $meilensteineAngelegt++;
        }

        // --- Anwesenheit: letzte 21 Tage, Werktage, ca. 80 % Anwesenheit ---
        for ($i = 20; $i >= 0; $i--) {
            $d = $today->modify("-{$i} days");
            $wochentag = (int)$d->format('N'); // 1 = Mo, 7 = So
            if ($wochentag >= 6) continue; // Wochenende

            $datum = $d->format('Y-m-d');
            if (random_int(1, 10) <= 2) {
                // Abwesend
                $gruende = ['Krank', 'Ferien', 'Arzttermin', 'Familientermin'];
                $insAnwesenheit->execute([
                    $kindId, $datum, null, null, 1,
                    $gruende[array_rand($gruende)], '',
                ]);
            } else {
                $ankunftH = random_int(7, 8);
                $ankunftM = random_int(0, 59);
                $abholH = random_int(15, 16);
                $abholM = random_int(0, 59);
                $insAnwesenheit->execute([
                    $kindId, $datum,
                    sprintf('%02d:%02d', $ankunftH, $ankunftM),
                    sprintf('%02d:%02d', $abholH, $abholM),
                    0, null, '',
                ]);
            }
            $anwesenheitAngelegt++;
        }

        // --- Bericht: letztes Quartal ---
        $quartalsStart = $today->modify('-3 months')->modify('first day of this month');
        $quartalsEnde  = $today->modify('last day of last month');
        $berichtInhalt = "Entwicklungsbericht für {$k['vorname']} {$k['nachname']}\n\n"
            . "Im Berichtszeitraum zeigte {$k['vorname']} eine sehr erfreuliche Entwicklung in allen Bildungsbereichen. "
            . "Besonders auffällig ist die Freude am gemeinsamen Spiel und das wachsende Interesse an Bilderbüchern. "
            . "Die sprachliche Entwicklung schreitet altersgerecht voran, der Wortschatz wird stetig grösser. "
            . "Im motorischen Bereich sind grosse Fortschritte beim Laufen, Klettern und Balancieren zu beobachten.\n\n"
            . "Für das kommende Quartal werden wir den Fokus auf freies Gestalten und Rollenspiele legen.";
        $insBericht->execute([
            $kindId,
            'Quartalsbericht ' . $quartalsEnde->format('Q.Y'),
            $quartalsStart->format('Y-m-d'),
            $quartalsEnde->format('Y-m-d'),
            $berichtInhalt,
            'finalisiert',
            $quartalsEnde->format('Y-m-d') . ' 16:00:00',
        ]);
    }

    $db->commit();

    echo "Demodaten erfolgreich angelegt:\n";
    echo "  Kinder:       {$kinderAngelegt}\n";
    echo "  Tagebuch:     {$tagebuchAngelegt}\n";
    echo "  Meilensteine: {$meilensteineAngelegt}\n";
    echo "  Anwesenheit:  {$anwesenheitAngelegt}\n";
    echo "  Berichte:     {$kinderAngelegt}\n";

} catch (\Throwable $e) {
    $db->rollBack();
    fwrite(STDERR, "Fehler: " . $e->getMessage() . "\n");
    exit(1);
}
