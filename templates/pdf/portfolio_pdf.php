<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11pt; color: #333; }
        .header { border-bottom: 2px solid #557d58; padding-bottom: 15px; margin-bottom: 25px; text-align: center; }
        .header h1 { color: #557d58; font-size: 20pt; margin: 0 0 5px 0; }
        .header .meta { color: #666; font-size: 10pt; }
        .foto-item { margin-bottom: 25px; page-break-inside: avoid; }
        .foto-item img { max-width: 100%; max-height: 400px; border-radius: 6px; }
        .foto-item .beschreibung { margin-top: 8px; font-style: italic; color: #666; }
        .foto-item .datum { font-size: 9pt; color: #999; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Portfolio</h1>
        <div class="meta"><?= htmlspecialchars($kind['vorname'] . ' ' . $kind['nachname']) ?></div>
    </div>

    <?php foreach ($fotos as $foto): ?>
    <div class="foto-item">
        <?php
        $imgPath = $uploads_path . $foto['dateiname'];
        if (file_exists($imgPath)):
            $data = base64_encode(file_get_contents($imgPath));
            $mime = mime_content_type($imgPath);
        ?>
        <img src="data:<?= $mime ?>;base64,<?= $data ?>">
        <?php endif; ?>
        <?php if ($foto['beschreibung']): ?>
        <div class="beschreibung"><?= htmlspecialchars($foto['beschreibung']) ?></div>
        <?php endif; ?>
        <?php if ($foto['aufnahme_datum']): ?>
        <div class="datum"><?= date('d.m.Y', strtotime($foto['aufnahme_datum'])) ?></div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</body>
</html>
