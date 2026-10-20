<?php
require __DIR__.'/../vendor/autoload.php';
$classes=[\Knthlz\PdfFlipper\DataProcessing\FlipperProcessor::class,\Knthlz\PdfFlipper\Controller\PreviewController::class,\Knthlz\PdfFlipper\Backend\PresetValues::class,\Knthlz\PdfFlipper\Backend\InitializeOverrides::class,\Knthlz\PdfFlipper\Command\RenderCommand::class];
foreach($classes as$class)if(!class_exists($class))throw new RuntimeException('Autoload failed: '.$class);
if(!method_exists(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::class,'addRecordType')||!method_exists(\TYPO3\CMS\Core\Utility\CommandUtility::class,'imageMagickCommand'))throw new RuntimeException('Required TYPO3 API missing');
echo "Composer autoload and required TYPO3 APIs are available. This is not a full CMS installation acceptance.\n";

$provider=new \Knthlz\PdfFlipper\Backend\PresetValues();
$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['pdf_flipper']=['defaultPreset'=>'compact','default_corner'=>51];
$r=$provider->addData(['tableName'=>'tt_content','databaseRow'=>['CType'=>'pdf_flipper','tx_pdfflipper_preset'=>'inherit','tx_pdfflipper_overrides'=>'corner','tx_pdfflipper_corner'=>0],'processedTca'=>['columns'=>[]]]);
if($r['databaseRow']['tx_pdfflipper_sizevalue']!==480||$r['databaseRow']['tx_pdfflipper_corner']!==0||!$r['processedTca']['columns']['tx_pdfflipper_sizevalue']['config']['readOnly']||isset($r['processedTca']['columns']['tx_pdfflipper_corner']))throw new RuntimeException('Effective editor values/override mismatch');
foreach([\TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEditRow::class,\TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew::class,\TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessCommon::class]as$class)if(!class_exists($class))throw new RuntimeException('Missing FormEngine dependency');
echo "Editor preset provider and dependency classes verified.\n";
