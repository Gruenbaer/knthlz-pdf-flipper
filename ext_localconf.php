<?php
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
defined('TYPO3') || die();
// Available as soon as the extension is active; no site-package dependency.
ExtensionManagementUtility::addTypoScript('pdf_flipper', 'setup', "\n@import 'EXT:pdf_flipper/Configuration/TypoScript/setup.typoscript'\n");
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['pdf_flipper_preview'] = \Knthlz\PdfFlipper\Controller\PreviewController::class . '::handle';

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'][\Knthlz\PdfFlipper\Backend\PresetValues::class] = [
    'depends'=>[\TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEditRow::class,\TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew::class],
    'before'=>[\TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessCommon::class],
];
