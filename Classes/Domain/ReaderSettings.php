<?php
declare(strict_types=1);
namespace Knthlz\PdfFlipper\Domain;
final class ReaderSettings
{
    public const BUTTONS = ['previous','page','next','zoom','fullscreen','download','reading','view','overview','search','contents','share','bookmark','back'];
    public static function fromRecord(array $data): array
    {
        $enum = static fn(string $field, array $values, string $default): string => in_array($data['tx_pdfflipper_'.$field] ?? '', $values, true) ? $data['tx_pdfflipper_'.$field] : $default;
        $number = static fn(string $field, int $default, int $min, int $max): int => max($min, min($max, (int)($data['tx_pdfflipper_'.$field] ?? $default)));
        $color = static fn(string $field, string $default): string => preg_match('/^#[a-f0-9]{6}$/iD', (string)($data['tx_pdfflipper_'.$field] ?? '')) === 1 ? $data['tx_pdfflipper_'.$field] : $default;
        $width = $number('width', 800, 320, 1200);
        return [
            'format' => $enum('format', ['webp','jpg','png','pdf'], 'webp'),
            'quality' => $number('quality', 85, 1, 100),
            'width' => $width, 'hdWidth' => max($width, $number('hdwidth', 1600, 640, 2000)),
            'background' => $color('background', '#f4f3f1'),
            'buttonBackground' => $color('buttonbg', '#ffffff'),
            'buttonColor' => $color('buttoncolor', '#222222'),
            'readingMode' => $enum('reading', ['auto','single','double','read','scroll'], 'auto'),
            'effect' => $enum('effect', ['curl','slide','none'], 'curl'),
            'cornerSensitivity' => $number('corner', 35, 0, 100),
            'cornerDelay' => $number('delay', 120, 0, 600),
            'duration' => $number('duration', 650, 150, 1500),
            'clickMode' => $enum('clickmode', ['page','corners','controls'], 'page'),
            'showSupport' => (bool)($data['tx_pdfflipper_support'] ?? false),
            'buttons' => array_values(array_intersect(self::BUTTONS, array_map('trim', explode(',', (string)($data['tx_pdfflipper_buttons'] ?? implode(',',self::BUTTONS)))))),
        ];
    }
}
