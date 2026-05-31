<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11pt; color: #333; line-height: 1.5; }
        .header { border-bottom: 2px solid #557d58; padding-bottom: 15px; margin-bottom: 25px; }
        .header h1 { color: #557d58; font-size: 18pt; margin: 0 0 5px 0; }
        .header .meta { color: #666; font-size: 9pt; }
        .kind-info { background: #f4f7f4; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 10pt; }
        .kind-info td { padding: 3px 15px 3px 0; }
        .kind-info .label { color: #666; }
        .bereich { margin-bottom: 20px; page-break-inside: avoid; }
        .bereich h2 { color: #557d58; font-size: 13pt; border-bottom: 1px solid #c7d7c8; padding-bottom: 5px; margin-bottom: 10px; }
        .bereich p { margin: 0; white-space: pre-line; }
        .footer { margin-top: 40px; border-top: 1px solid #ddd; padding-top: 15px; font-size: 9pt; color: #999; }
        .unterschrift { margin-top: 50px; }
        .unterschrift-line { border-top: 1px solid #333; width: 250px; margin-top: 50px; padding-top: 5px; font-size: 9pt; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1><?= htmlspecialchars($bericht['titel'] ?: 'Entwicklungsbericht') ?></h1>
        <div class="meta">
            Zeitraum: <?= date('d.m.Y', strtotime($bericht['zeitraum_von'])) ?> – <?= date('d.m.Y', strtotime($bericht['zeitraum_bis'])) ?>
            &nbsp;|&nbsp; Erstellt am: <?= date('d.m.Y', strtotime($bericht['erstellt_am'])) ?>
        </div>
    </div>

    <div class="kind-info">
        <table>
            <tr><td class="label">Name:</td><td><strong><?= htmlspecialchars($kind['vorname'] . ' ' . $kind['nachname']) ?></strong></td></tr>
            <tr><td class="label">Geburtsdatum:</td><td><?= date('d.m.Y', strtotime($kind['geburtsdatum'])) ?></td></tr>
            <?php if ($kind['betreuung_start']): ?>
            <tr><td class="label">Betreuung seit:</td><td><?= date('d.m.Y', strtotime($kind['betreuung_start'])) ?></td></tr>
            <?php endif; ?>
        </table>
    </div>

    <?php foreach ($bildungsbereiche as $key => $label): ?>
        <?php if (!empty($bericht['inhalt_parsed'][$key])): ?>
        <div class="bereich">
            <h2><?= htmlspecialchars($label) ?></h2>
            <p><?= nl2br(htmlspecialchars($bericht['inhalt_parsed'][$key])) ?></p>
        </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <div class="unterschrift">
        <div class="unterschrift-line">Ort, Datum — Unterschrift Tagesmutter</div>
    </div>

    <div class="footer">
        Dieser Bericht wurde vertraulich erstellt und dient der Dokumentation der kindlichen Entwicklung.
    </div>
</body>
</html>
