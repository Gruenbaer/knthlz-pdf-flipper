<?php
declare(strict_types=1);
namespace Knthlz\PdfFlipper\Backend;
use Knthlz\PdfFlipper\Domain\ReaderSettings;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
final class PresetValues implements FormDataProviderInterface
{
    public function addData(array $result): array
    {
        $row = $result['databaseRow'];
        if ($result['tableName'] !== 'tt_content' || ($row['CType'] ?? '') !== 'pdf_flipper' || ($row['tx_pdfflipper_preset'] ?? 'legacy') === 'legacy') return $result;
        $settings = ReaderSettings::fromRecord($row);
        $map = ['hdwidth'=>'hdWidth','buttonbg'=>'buttonBackground','buttoncolor'=>'buttonColor','reading'=>'readingMode','corner'=>'cornerSensitivity','delay'=>'cornerDelay','clickmode'=>'clickMode','support'=>'showSupport','sizeaxis'=>'sizeAxis','sizevalue'=>'sizeValue'];
        $overrides = explode(',',(string)($row['tx_pdfflipper_overrides'] ?? ''));
        foreach (ReaderSettings::FIELDS as $field) {
            $column = 'tx_pdfflipper_'.$field;
            if (in_array($field,$overrides,true)) continue;
            $value = $settings[$map[$field] ?? $field];
            $result['databaseRow'][$column] = is_array($value) ? implode(',',$value) : (is_bool($value) ? (int)$value : $value);
            $result['processedTca']['columns'][$column]['config']['readOnly'] = true;
        }
        return $result;
    }
}
