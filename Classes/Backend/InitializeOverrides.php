<?php
declare(strict_types=1);
namespace Knthlz\PdfFlipper\Backend;
use Knthlz\PdfFlipper\Domain\ReaderSettings;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
final class InitializeOverrides
{
    public function processDatamap_preProcessFieldArray(array &$incoming, string $table, $id, DataHandler $handler): void
    {
        if ($table !== 'tt_content' || !array_key_exists('tx_pdfflipper_overrides',$incoming)) return;
        $row = is_numeric($id) ? GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table)->fetchAssociative('SELECT * FROM tt_content WHERE uid=?',[(int)$id]) : ['CType'=>$incoming['CType'] ?? '', 'tx_pdfflipper_preset'=>'inherit'];
        if (!$row || ($incoming['CType'] ?? $row['CType']) !== 'pdf_flipper') return;
        $incoming = self::initialize($row,$incoming);
    }
    public static function initialize(array $row, array $incoming): array
    {
        $old = explode(',',(string)($row['tx_pdfflipper_overrides'] ?? ''));
        $selected = $incoming['tx_pdfflipper_overrides'] ?? '';
        $new = is_array($selected) ? array_values(array_intersect(ReaderSettings::FIELDS,$selected)) : explode(',',(string)$selected);
        $incoming['tx_pdfflipper_overrides'] = implode(',',$new);
        $basis = array_replace($row,$incoming);
        // Seed newly enabled fields from what the editor just saw, not stale DB defaults.
        $basis['tx_pdfflipper_overrides'] = implode(',',array_intersect($old,$new));
        $values = ReaderSettings::recordValues(ReaderSettings::fromRecord($basis));
        foreach (array_intersect(ReaderSettings::FIELDS,array_diff($new,$old)) as $field) {
            $column = 'tx_pdfflipper_'.$field;
            if (!array_key_exists($column,$incoming)) $incoming[$column] = $values[$column];
        }
        return $incoming;
    }
}
