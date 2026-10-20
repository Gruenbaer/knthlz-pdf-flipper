<?php
declare(strict_types=1);
namespace Knthlz\PdfFlipper\DataProcessing;

use Knthlz\PdfFlipper\Domain\PageSelection;
use Knthlz\PdfFlipper\Domain\ReaderSettings;
use Knthlz\PdfFlipper\Imaging\PreviewRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

final class FlipperProcessor implements DataProcessorInterface
{
    public function process(ContentObjectRenderer $cObj, array $contentObjectConfiguration, array $processorConfiguration, array $processedData): array
    {
        $data = $processedData['data'] ?? [];
        $file = $processedData['files'][0] ?? null;
        $pdf = $file instanceof FileReference ? $file->getPublicUrl() : null;
        $error = '';
        try {
            $pages = PageSelection::normalize((string)($data['tx_pdfflipper_pages'] ?? ''));
        } catch (\InvalidArgumentException $exception) {
            $pages = '';
            $error = $exception->getMessage();
        }
        if (!$pdf || strtolower((string)$file->getExtension()) !== 'pdf') {
            $error = 'Bitte eine öffentlich erreichbare PDF-Datei auswählen.';
        }
        $layout = (string)($data['tx_pdfflipper_layout'] ?? 'auto');
        $accent = (string)($data['tx_pdfflipper_accent'] ?? '#c3323a');
        $options = [
            'pdf' => $pdf,
            'contentId' => (int)($data['uid'] ?? 0),
            'title' => trim((string)($data['header'] ?? '')) ?: 'PDF-Blätterbuch',
            'pages' => $pages,
            'selection' => ($data['tx_pdfflipper_selection'] ?? '') === 'only' ? 'only' : 'highlight',
            'layout' => in_array($layout, ['auto', 'single', 'spreads', 'mixed', 'cover-spread'], true) ? $layout : 'auto',
            'start' => max(0, min(10000, (int)($data['tx_pdfflipper_start'] ?? 0))),
            'accent' => preg_match('/^#[a-f0-9]{6}$/iD', $accent) === 1 ? $accent : '#c3323a',
            'error' => $error,
        ];
        $settings = ReaderSettings::fromRecord($data);
        $options = array_merge($options, $settings);
        $support = trim((string)($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['pdf_flipper']['supportUrl'] ?? ''));
        if ($settings['showSupport'] && preg_match('~^https://(?:www\.)?buymeacoffee\.com/[a-zA-Z0-9_\-]+/?$~D', $support)) $options['supportUrl'] = $support;
        if (!$error && $settings['format'] !== 'pdf') {
            try {
                $site = $cObj->getRequest()->getAttribute('site');
                $basePath = $site ? (string)$site->getBase()->getPath() : '/';
                $options['preview'] = GeneralUtility::makeInstance(PreviewRenderer::class)->manifest($file->getOriginalFile(), $settings, $options['layout'], rtrim($basePath, '/') . '/');
                $options['format'] = $options['preview']['format'];
                if ($settings['format'] !== $options['format']) $options['notice'] = 'Das gewählte Bildformat ist hier nicht verfügbar. Die Vorschau verwendet ' . strtoupper($options['format']) . '.';
            } catch (\Throwable $exception) {
                $options['previewError'] = 'Die Bildvorschau ist hier nicht verfügbar. Die PDF wird direkt angezeigt.';
            }
        }
        $processedData['flipper'] = [
            'pdf' => $pdf,
            'caption' => $file instanceof FileReference ? (string)$file->getProperty('description') : '',
            'error' => $error,
            'options' => json_encode($options, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP),
        ];
        return $processedData;
    }
}
