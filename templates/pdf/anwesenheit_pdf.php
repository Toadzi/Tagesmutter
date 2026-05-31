<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #333; }
        .header { border-bottom: 2px solid #557d58; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { color: #557d58; font-size: 16pt; margin: 0 0 5px 0; }
        .header .meta { color: #666; font-size: 9pt; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #f4f7f4; color: #365038; font-size: 9pt; text-transform: uppercase; letter-spacing: 0.5px; }
        th, td { border: 1px solid #ddd; padding: 5px 8px; text-align: left; }
        tr:nth-child(even) { background: #fafafa; }
        .weekend { background: #f0f0f0; }
        .abwesend { color: #dc3545; }
        .summary { background: #f4f7f4; padding: 12px; border-radius: 6px; }
        .summary td { border: none; padding: 3px 15px 3px 0; }
        .footer { margin-top: 30px; font-size: 9pt; color: #999; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Anwesenheitsnachweis</h1>
        <div class="meta">
            <?= htmlspecialchars($kind['vorname'] . ' ' . $kind['nachname']) ?>
            &nbsp;|&nbsp; <?= htmlspecialchars($monatsname) ?> <?= $jahr ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Datum</th>
                <th>Tag</th>
                <th>Ankunft</th>
                <th>Abholung</th>
                <th>Stunden</th>
                <th>Bemerkung</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $wochentage = ['So','Mo','Di','Mi','Do','Fr','Sa'];
            $gesamtStunden = 0;
            $anwesendTage = 0;
            $tageImMonat = cal_days_in_month(CAL_GREGORIAN, $monat, $jahr);

            for ($tag = 1; $tag <= $tageImMonat; $tag++):
                $datum = sprintf('%04d-%02d-%02d', $jahr, $monat, $tag);
                $wt = (int)date('w', strtotime($datum));
                $isWeekend = $wt == 0 || $wt == 6;
                $eintrag = null;
                foreach ($eintraege as $e) {
                    if ($e['datum'] === $datum) { $eintrag = $e; break; }
                }
                $stunden = '';
                if ($eintrag && !$eintrag['abwesend'] && $eintrag['ankunft'] && $eintrag['abholung']) {
                    $diff = (strtotime($eintrag['abholung']) - strtotime($eintrag['ankunft'])) / 3600;
                    if ($diff > 0) {
                        $stunden = number_format($diff, 1);
                        $gesamtStunden += $diff;
                        $anwesendTage++;
                    }
                }
            ?>
            <tr class="<?= $isWeekend ? 'weekend' : '' ?>">
                <td><?= date('d.m.', strtotime($datum)) ?></td>
                <td><?= $wochentage[$wt] ?></td>
                <td><?= $eintrag && !$eintrag['abwesend'] ? htmlspecialchars($eintrag['ankunft'] ?? '') : '' ?></td>
                <td><?= $eintrag && !$eintrag['abwesend'] ? htmlspecialchars($eintrag['abholung'] ?? '') : '' ?></td>
                <td><?= $stunden ?></td>
                <td class="<?= $eintrag && $eintrag['abwesend'] ? 'abwesend' : '' ?>">
                    <?php if ($eintrag && $eintrag['abwesend']): ?>
                        Abwesend<?= $eintrag['abwesenheitsgrund'] ? ' (' . htmlspecialchars($eintrag['abwesenheitsgrund']) . ')' : '' ?>
                    <?php elseif ($eintrag && $eintrag['notiz']): ?>
                        <?= htmlspecialchars($eintrag['notiz']) ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endfor; ?>
        </tbody>
    </table>

    <table class="summary">
        <tr>
            <td><strong>Anwesend:</strong></td><td><?= $anwesendTage ?> Tage</td>
            <td><strong>Gesamtstunden:</strong></td><td><?= number_format($gesamtStunden, 1) ?> h</td>
        </tr>
    </table>

    <div class="footer">
        Erstellt am <?= date('d.m.Y') ?>
    </div>
</body>
</html>
