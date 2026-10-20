<?php
declare(strict_types=1);
namespace Knthlz\PdfFlipper\Domain;

final class PageSelection
{
    /** Validate syntax server-side; PDF-dependent bounds are checked by the reader. */
    public static function normalize(string $value): string
    {
        $value = trim(str_replace(['–', '—'], '-', $value));
        if ($value === '') {
            return '';
        }
        if (strlen($value) > 1024) {
            throw new \InvalidArgumentException('Die Seitenauswahl ist zu lang.');
        }
        $result = [];
        foreach (explode(',', $value) as $part) {
            if (preg_match('/^\s*([1-9][0-9]{0,4})(?:\s*-\s*([1-9][0-9]{0,4}))?\s*$/D', $part, $match) !== 1) {
                throw new \InvalidArgumentException('Ungültige Seitenauswahl. Bitte zum Beispiel 3–8, 12 angeben.');
            }
            $start = (int)$match[1];
            $end = isset($match[2]) ? (int)$match[2] : $start;
            if ($end < $start || $end > 10000) {
                throw new \InvalidArgumentException('Seitenbereiche müssen aufsteigen und innerhalb von 1–10000 liegen.');
            }
            $result[] = $start === $end ? (string)$start : $start . '-' . $end;
        }
        return implode(', ', $result);
    }
}
