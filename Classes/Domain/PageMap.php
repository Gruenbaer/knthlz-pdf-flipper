<?php
declare(strict_types=1);
namespace Knthlz\PdfFlipper\Domain;
final class PageMap
{
    public static function fromSizes(array $sizes, string $layout): array
    {
        if (!$sizes || count($sizes)>500) throw new \InvalidArgumentException('PDF-Seitenzahl außerhalb des erlaubten Bereichs.');
        $wide = static fn(array $s): bool => $s['width']/$s['height']>1.2;
        foreach ($sizes as $s) if (!is_finite((float)$s['width']) || !is_finite((float)$s['height']) || $s['width']<=0 || $s['height']<=0) throw new \InvalidArgumentException('Ungültige PDF-Seitenmaße.');
        $parts=[];$add=static function(int $index, ?string $half=null)use(&$parts,$sizes):void{$parts[]=['pdfPage'=>$index+1,'half'=>$half,'width'=>$sizes[$index]['width']/($half?2:1),'height'=>$sizes[$index]['height'],'number'=>count($parts)+1];};
        $wrap=$layout==='cover-spread'||($layout==='auto'&&count(array_filter($sizes,$wide))===count($sizes));
        if($wrap){if(!$wide($sizes[0]))throw new \InvalidArgumentException('Umschlag ist keine Doppelseite.');$add(0,'right');for($i=1;$i<count($sizes);$i++){$add($i,'left');$add($i,'right');}$add(0,'left');}
        else foreach($sizes as$i=>$s){$split=$layout==='spreads'||(in_array($layout,['mixed','auto'],true)&&$wide($s)&&($layout!=='mixed'||($i>0&&$i<count($sizes)-1)));if($split){$add($i,'left');$add($i,'right');}else $add($i);}
        return $parts;
    }
}
