<?php
declare(strict_types=1);
namespace Knthlz\PdfFlipper\Domain;
final class ReaderSettings
{
    public const BUTTONS = ['previous','page','next','progress','zoom','fullscreen','download','reading','view','overview','search','contents','share','bookmark','back'];
    public const FIELDS = ['format','quality','width','hdwidth','background','buttonbg','buttoncolor','reading','effect','corner','delay','duration','clickmode','support','buttons','accent','frame','sizeaxis','sizevalue'];
    public static function presets(?array $config = null): array
    {
        $config ??= $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['pdf_flipper'] ?? [];
        $presets = [
            'standard'=>['label'=>'Standard','settings'=>[]],
            'frameless'=>['label'=>'Rahmenlos','settings'=>['frame'=>'frameless']],
            'quiet'=>['label'=>'Ruhig lesen','settings'=>['frame'=>'frameless','reading'=>'read','effect'=>'none']],
            'compact'=>['label'=>'Kompakt','settings'=>['sizeaxis'=>'height','sizevalue'=>480,'effect'=>'slide']],
        ];
        // Configuration is data only; unknown fields and malformed definitions never reach output.
        $custom = json_decode((string)($config['presets'] ?? ''), true);
        foreach (is_array($custom) ? $custom : [] as $id=>$preset) {
            if (!is_string($id) || !preg_match('/^[a-z][a-z0-9_-]{0,39}$/D', $id) || in_array($id, ['inherit','legacy'], true) || !is_array($preset) || !is_array($preset['settings'] ?? null)) continue;
            $presets[$id] = ['label'=>(preg_match('/^.{0,80}/us', strip_tags((string)($preset['label'] ?? $id)), $label) ? $label[0] : $id), 'settings'=>array_intersect_key($preset['settings'],array_flip(self::FIELDS))];
        }
        return $presets;
    }
    public static function recordValues(array $settings): array
    {
        $map = ['hdwidth'=>'hdWidth','buttonbg'=>'buttonBackground','buttoncolor'=>'buttonColor','reading'=>'readingMode','corner'=>'cornerSensitivity','delay'=>'cornerDelay','clickmode'=>'clickMode','support'=>'showSupport','sizeaxis'=>'sizeAxis','sizevalue'=>'sizeValue'];
        $values = [];
        foreach (self::FIELDS as $field) {
            $value = $settings[$map[$field] ?? $field];
            $values['tx_pdfflipper_'.$field] = is_array($value) ? implode(',',$value) : (is_bool($value) ? (int)$value : $value);
        }
        return $values;
    }
    public static function presetItems(array &$parameters): void
    {
        foreach (self::presets() as $id=>$preset) $parameters['items'][] = ['label'=>$preset['label'],'value'=>$id];
    }
    public static function fromRecord(array $data, ?array $config = null): array
    {
        $config ??= $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['pdf_flipper'] ?? [];
        $preset = (string)($data['tx_pdfflipper_preset'] ?? 'legacy');
        if ($preset !== 'legacy') {
            $resolved = [];
            foreach (self::FIELDS as $field) if (array_key_exists('default_'.$field,$config)) $resolved['tx_pdfflipper_'.$field] = $config['default_'.$field];
            $presets = self::presets($config);
            $id = $preset === 'inherit' ? (string)($config['defaultPreset'] ?? 'standard') : $preset;
            foreach ($presets[$id]['settings'] ?? [] as $field=>$value) $resolved['tx_pdfflipper_'.$field] = $value;
            foreach (explode(',',(string)($data['tx_pdfflipper_overrides'] ?? '')) as $field) {
                if (in_array($field,self::FIELDS,true) && array_key_exists('tx_pdfflipper_'.$field,$data)) $resolved['tx_pdfflipper_'.$field] = $data['tx_pdfflipper_'.$field];
            }
            $data = $resolved;
        }
        $enum = static fn(string $field, array $values, string $default): string => in_array($data['tx_pdfflipper_'.$field] ?? '', $values, true) ? $data['tx_pdfflipper_'.$field] : $default;
        $number = static fn(string $field, int $default, int $min, int $max): int => max($min, min($max, (int)($data['tx_pdfflipper_'.$field] ?? $default)));
        $color = static fn(string $field, string $default): string => preg_match('/^#[a-f0-9]{6}$/iD', (string)($data['tx_pdfflipper_'.$field] ?? '')) === 1 ? $data['tx_pdfflipper_'.$field] : $default;
        $width = $number('width', 800, 320, 1200);
        return [
            'accent' => $color('accent', '#c3323a'),
            'frame' => $enum('frame', ['framed','frameless'], 'framed'),
            'sizeAxis' => $enum('sizeaxis', ['auto','width','height'], 'auto'),
            'sizeValue' => $number('sizevalue', 800, 200, 3000),
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
            'showSupport' => filter_var($data['tx_pdfflipper_support'] ?? false, FILTER_VALIDATE_BOOL),
            'buttons' => array_values(array_intersect(self::BUTTONS, array_map('trim', explode(',', (string)($data['tx_pdfflipper_buttons'] ?? implode(',',self::BUTTONS)))))),
        ];
    }
}
