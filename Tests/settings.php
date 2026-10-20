<?php
require __DIR__.'/../Classes/Domain/ReaderSettings.php';require __DIR__.'/../Classes/Domain/PageMap.php';
use Knthlz\PdfFlipper\Domain\ReaderSettings;
use Knthlz\PdfFlipper\Domain\PageMap;
function check(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
$s=ReaderSettings::fromRecord([]);check($s['format']==='webp'&&$s['quality']===85&&count($s['buttons'])===14,'defaults');
$s=ReaderSettings::fromRecord(['tx_pdfflipper_format'=>'../evil','tx_pdfflipper_quality'=>1000,'tx_pdfflipper_width'=>5000,'tx_pdfflipper_hdwidth'=>0,'tx_pdfflipper_background'=>'red;position:fixed','tx_pdfflipper_buttons'=>'zoom,invalid,download','tx_pdfflipper_reading'=>'read']);
check($s['format']==='webp'&&$s['quality']===100&&$s['width']===1200&&$s['hdWidth']===1200,'bounds');check($s['background']==='#f4f3f1'&&$s['buttons']===['zoom','download']&&$s['readingMode']==='read','allowed settings');
check(ReaderSettings::fromRecord(['tx_pdfflipper_buttons'=>''])['buttons']===[],'hide all');
$p=['width'=>595,'height'=>842];$w=['width'=>1190,'height'=>842];
$m=PageMap::fromSizes([$p,...array_fill(0,15,$w),$p],'auto');check(count($m)===32&&$m[1]['half']==='left'&&$m[31]['pdfPage']===17,'mixed covers');
$m=PageMap::fromSizes(array_fill(0,16,$w),'auto');check(count($m)===32&&$m[0]['half']==='right'&&$m[31]['half']==='left'&&$m[31]['pdfPage']===1,'cover spread');
check(count(PageMap::fromSizes(array_fill(0,24,$p),'auto'))===24,'portrait');
echo "Reader settings and PHP/JS-equivalent page map checks passed\n";

$s=ReaderSettings::fromRecord(['tx_pdfflipper_corner'=>-5,'tx_pdfflipper_delay'=>9000,'tx_pdfflipper_duration'=>0,'tx_pdfflipper_effect'=>'x','tx_pdfflipper_clickmode'=>'x','tx_pdfflipper_reading'=>'scroll']);
check($s['cornerSensitivity']===0&&$s['cornerDelay']===600&&$s['duration']===150&&$s['effect']==='curl'&&$s['clickMode']==='page'&&$s['readingMode']==='scroll','motion bounds and safe defaults');
