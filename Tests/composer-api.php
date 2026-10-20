<?php
require __DIR__.'/../vendor/autoload.php';
$classes=[\Knthlz\PdfFlipper\DataProcessing\FlipperProcessor::class,\Knthlz\PdfFlipper\Controller\PreviewController::class,\Knthlz\PdfFlipper\Command\RenderCommand::class];
foreach($classes as$class)if(!class_exists($class))throw new RuntimeException('Autoload failed: '.$class);
if(!method_exists(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::class,'addRecordType')||!method_exists(\TYPO3\CMS\Core\Utility\CommandUtility::class,'imageMagickCommand'))throw new RuntimeException('Required TYPO3 API missing');
echo "Composer autoload and required TYPO3 APIs are available. This is not a full CMS installation acceptance.\n";
