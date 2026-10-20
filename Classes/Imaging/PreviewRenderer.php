<?php
declare(strict_types=1);
namespace Knthlz\PdfFlipper\Imaging;
use Knthlz\PdfFlipper\Domain\PageMap;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ExecutableFinder;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Imaging\ImageMagickFile;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class PreviewRenderer
{
    private function directory(): string
    {
        $dir=Environment::getVarPath().'/transient/pdf_flipper';
        if(!is_dir($dir)&&!mkdir($dir,0700,true)&&!is_dir($dir))throw new \RuntimeException('Vorschau-Cache nicht verfügbar.');
        return $dir;
    }
    private function backend(): string { return ($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['pdf_flipper']['backend'] ?? 'typo3') === 'poppler' ? 'poppler' : 'typo3'; }
    private function executable(string $name): string
    {
        $configured=trim((string)($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['pdf_flipper'][$name]??''));
        $path=$configured!==''?$configured:(new ExecutableFinder())->find($name);
        if(!$path||!is_executable($path))throw new \RuntimeException('Optionaler Poppler-Prozessor benötigt '.$name.'.');
        return $path;
    }
    private function nativeProcess(string $operation, string $arguments): string
    {
        if(empty($GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_enabled']))throw new \RuntimeException('TYPO3-Bildverarbeitung ist deaktiviert.');
        $process=Process::fromShellCommandline(CommandUtility::imageMagickCommand($operation,$arguments),null,['LC_ALL'=>'C']);$process->setTimeout(45);$process->mustRun();return $process->getOutput();
    }
    public function capabilities(): array
    {
        $gd=extension_loaded('gd')?gd_info():[];
        return ['backend'=>$this->backend(),'processor'=>$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor']??null,'enabled'=>(bool)($GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_enabled']??false),'formats'=>array_values(array_filter(['png','jpg','webp'],static fn(string $f):bool=>($gd[match($f){'png'=>'PNG Support','jpg'=>'JPEG Support','webp'=>'WebP Support'}]??false)))];
    }
    private function run(array $command): string
    {
        $process=new Process($command,null,['LC_ALL'=>'C']);$process->setTimeout(45);$process->mustRun();return $process->getOutput();
    }
    private function source(FileInterface $file): array
    {
        if(!$file->getStorage()->isPublic()||strtolower($file->getExtension())!=='pdf'||!$file->getPublicUrl())throw new \RuntimeException('Nur öffentliche PDF-Dateien sind als Bildquelle erlaubt.');
        $path=$file->getForLocalProcessing(false);
        if(!is_file($path)||filesize($path)>100*1024*1024||file_get_contents($path,false,null,0,5)!=='%PDF-')throw new \RuntimeException('Ungültige oder zu große PDF-Datei.');
        return [$path,hash_file('sha256',$path)];
    }
    public function sizes(FileInterface $file): array
    {
        [$path,$sha]=$this->source($file);$cache=$this->directory().'/'.$sha.'-'.$this->backend().'-sizes-v2.json';
        if(is_file($cache))return json_decode(file_get_contents($cache),true,512,JSON_THROW_ON_ERROR);
        $sizes=[];
        if($this->backend()==='typo3') {
            $info=$this->nativeProcess('identify','-define pdf:use-cropbox=true -format '.CommandUtility::escapeShellArgument("%p %w %h\n").' '.CommandUtility::escapeShellArgument($path));
            foreach(preg_split('/\R/',trim($info)) as $line) if(preg_match('/^\d+\s+(\d+)\s+(\d+)$/',trim($line),$match))$sizes[]=['width'=>(float)$match[1],'height'=>(float)$match[2]];
            $count=count($sizes);if($count<1||$count>500)throw new \RuntimeException('TYPO3-Grafikprozessor liefert keine gültige PDF-Seitenliste.');
        } else {
        $header=$this->run([$this->executable('pdfinfo'),$path]);
        if(!preg_match('/^Pages:\s+(\d+)/m',$header,$m)||(int)$m[1]<1||(int)$m[1]>500)throw new \RuntimeException('Maximal 500 PDF-Seiten erlaubt.');
        $count=(int)$m[1];$info=$this->run([$this->executable('pdfinfo'),'-f','1','-l',(string)$count,'-box',$path]);$sizes=[];
        for($i=1;$i<=$count;$i++){
            if(preg_match('/Page\s+'.$i.'\s+CropBox:\s*([\d.\-]+)\s+([\d.\-]+)\s+([\d.\-]+)\s+([\d.\-]+)/',$info,$box)){$w=(float)$box[3]-(float)$box[1];$h=(float)$box[4]-(float)$box[2];}
            elseif(preg_match('/Page\s+'.$i.'\s+size:\s*([\d.]+)\s+x\s+([\d.]+)/',$info,$box)){$w=(float)$box[1];$h=(float)$box[2];}
            else throw new \RuntimeException('PDF-Seitenmaße fehlen.');
            preg_match('/Page\s+'.$i.'\s+rot:\s*(-?\d+)/',$info,$rotation);if(abs((int)($rotation[1]??0))%180!==0)[$w,$h]=[$h,$w];
            if($w<=0||$h<=0||$w>20000||$h>20000)throw new \RuntimeException('PDF-Seitenmaße außerhalb des erlaubten Bereichs.');$sizes[]=['width'=>$w,'height'=>$h];
        }
        }
        $result=['sha256'=>$sha,'sizes'=>$sizes,'pdfPages'=>$count];$tmp=tempnam($this->directory(),'sizes-');file_put_contents($tmp,json_encode($result,JSON_THROW_ON_ERROR));rename($tmp,$cache);return $result;
    }
    private function sign(string $value): string
    {
        $key=(string)($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']??'');if(strlen($key)<16)throw new \RuntimeException('TYPO3-Signaturschlüssel fehlt.');return hash_hmac('sha256','pdf-flipper-preview-v1:'.$value,$key);
    }
    private function token(array $payload): string
    {
        $value=rtrim(strtr(base64_encode(json_encode($payload,JSON_THROW_ON_ERROR)),'+/','-_'),'=');return $value.'.'.$this->sign($value);
    }
    public function manifest(FileInterface $file, array $settings, string $layout, string $basePath='/'): array
    {
         $capabilities=$this->capabilities();
        if(!in_array($settings['format'],$capabilities['formats'],true)){ $settings['format']=in_array('jpg',$capabilities['formats'],true)?'jpg':(in_array('png',$capabilities['formats'],true)?'png':'pdf'); }
        if($settings['format']==='pdf')throw new \RuntimeException('Bildformate nicht verfügbar.');
        $meta=$this->sizes($file);$pages=PageMap::fromSizes($meta['sizes'],$layout);
        foreach($pages as &$page){foreach(['preview'=>$settings['width'],'hd'=>$settings['hdWidth']]as$label=>$width){$token=$this->token(['file'=>(int)$file->getUid(),'sha256'=>$meta['sha256'],'page'=>$page['pdfPage'],'half'=>$page['half'],'width'=>$width,'format'=>$settings['format'],'quality'=>$settings['quality'],'backend'=>$this->backend()]);$page[$label]=$basePath.'?eID=pdf_flipper_preview&token='.rawurlencode($token);}}
        return ['format'=>$settings['format'],'backend'=>$this->backend(),'capabilities'=>$capabilities,'pages'=>$pages,'pdfPages'=>$meta['pdfPages'],'sha256'=>$meta['sha256']];
    }
    public function render(string $token): array
    {
        if(strlen($token)>1500||substr_count($token,'.')!==1)throw new \InvalidArgumentException('Ungültige Vorschau-Anfrage.');
        [$value,$signature]=explode('.',$token);if(!hash_equals($this->sign($value),$signature))throw new \InvalidArgumentException('Vorschau-Signatur stimmt nicht.');
        $p=json_decode(base64_decode(strtr($value,'-_','+/'),true),true,16,JSON_THROW_ON_ERROR);
        if(!is_int($p['file']??null)||!is_int($p['page']??null)||$p['page']<1||!is_int($p['width']??null)||$p['width']<320||$p['width']>2000||!in_array($p['format']??'', ['webp','jpg','png'],true)||!in_array($p['half']??null,[null,'left','right'],true)||($p['quality']??0)<1||$p['quality']>100)throw new \InvalidArgumentException('Ungültige Vorschau-Parameter.');
        $file=GeneralUtility::makeInstance(ResourceFactory::class)->getFileObject($p['file']);[$path,$sha]=$this->source($file);if(!hash_equals($sha,(string)$p['sha256']))throw new \RuntimeException('PDF wurde geändert. Bitte die Seite neu laden.');
        $meta=$this->sizes($file);if($p['page']>count($meta['sizes']))throw new \InvalidArgumentException('Unbekannte PDF-Seite.');
        $key=hash('sha256',$value);$target=$this->directory().'/'.$key.'.'.$p['format'];
        if(!is_file($target)){
            // Serialize converters per source PDF; bounded memory and no duplicate page jobs.
            $lock=fopen($this->directory().'/'.$sha.'.lock','c');if(!$lock||!flock($lock,LOCK_EX))throw new \RuntimeException('Vorschau gesperrt.');
            try{if(!is_file($target)){
                if(!extension_loaded('gd'))throw new \RuntimeException('GD-Bildverarbeitung fehlt.');
                $size=$meta['sizes'][$p['page']-1];$scale=min($p['width']*($p['half']?2:1)/$size['width'],4000/max($size['width'],$size['height']));$w=max(1,(int)round($size['width']*$scale));$h=max(1,(int)round($size['height']*$scale));
                $temporary=tempnam($this->directory(),'raster-');
                try{
                    if(($p['backend']??'typo3')!==$this->backend())throw new \RuntimeException('Grafikprozessor wurde geändert. Bitte die Seite neu laden.');
                    if($this->backend()==='poppler')$this->run([$this->executable('pdftoppm'),'-f',(string)$p['page'],'-l',(string)$p['page'],'-singlefile','-cropbox','-scale-to-x',(string)$w,'-scale-to-y',(string)$h,'-png',$path,$temporary]);
                    else $this->nativeProcess('convert','-density '.(string)min(400,max(72,(int)ceil($scale*72))).' -define pdf:use-cropbox=true '.ImageMagickFile::fromFilePath($path,$p['page']-1).' -resize '.CommandUtility::escapeShellArgument($w.'x'.$h.'!').' '.CommandUtility::escapeShellArgument($temporary.'.png'));
                    $image=imagecreatefrompng($temporary.'.png');if(!$image)throw new \RuntimeException('PDF-Raster ungültig.');
                    if($p['half']){$partWidth=(int)floor(imagesx($image)/2);$crop=imagecrop($image,['x'=>$p['half']==='right'?$partWidth:0,'y'=>0,'width'=>$partWidth,'height'=>imagesy($image)]);imagedestroy($image);$image=$crop;}
                    if(!$image)throw new \RuntimeException('PDF-Zuschnitt ungültig.');
                    $stage=$temporary.'.'.$p['format'];$ok=match($p['format']){'webp'=>imagewebp($image,$stage,(int)$p['quality']),'jpg'=>imagejpeg($image,$stage,(int)$p['quality']),'png'=>imagepng($image,$stage,6)};imagedestroy($image);
                    $actual=getimagesize($stage);$expected=['webp'=>'image/webp','jpg'=>'image/jpeg','png'=>'image/png'][$p['format']];if(!$ok||!$actual||$actual['mime']!==$expected)throw new \RuntimeException('Bildformat-Prüfung fehlgeschlagen.');
                    chmod($stage,0600);rename($stage,$target);
                }finally{foreach([$temporary,$temporary.'.png',$temporary.'.'.$p['format']]as$tmp)if(is_file($tmp))unlink($tmp);}
            }}finally{flock($lock,LOCK_UN);fclose($lock);}
        }
        $image=getimagesize($target);return ['path'=>$target,'mime'=>$image['mime'],'width'=>$image[0],'height'=>$image[1],'sha256'=>hash_file('sha256',$target),'bytes'=>filesize($target)];
    }
}
