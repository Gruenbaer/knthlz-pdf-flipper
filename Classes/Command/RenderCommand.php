<?php
declare(strict_types=1);
namespace Knthlz\PdfFlipper\Command;
use Knthlz\PdfFlipper\Domain\ReaderSettings;
use Knthlz\PdfFlipper\Imaging\PreviewRenderer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
final class RenderCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('pdf-flipper:render')->setDescription('Standard- und HD-Bilder eines PDF-Flipper-Inhaltselements vorab erzeugen.')->addArgument('content',InputArgument::REQUIRED,'UID des Inhaltselements');
    }
    protected function execute(InputInterface $input,OutputInterface $output): int
    {
        $uid=filter_var($input->getArgument('content'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if(!$uid)throw new \InvalidArgumentException('Inhalts-UID erforderlich.');
        $db=GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tt_content');$row=$db->fetchAssociative("SELECT * FROM tt_content WHERE uid=? AND CType='pdf_flipper' AND deleted=0",[$uid]);if(!$row)throw new \RuntimeException('PDF-Flipper-Inhaltselement fehlt.');
        $settings=ReaderSettings::fromRecord($row);if($settings['format']==='pdf'){$output->writeln('PDF-Direktanzeige: keine Vorschaubilder erforderlich.');return Command::SUCCESS;}
        $files=GeneralUtility::makeInstance(FileRepository::class)->findByRelation('tt_content','assets',$uid);if(count($files)!==1)throw new \RuntimeException('Genau eine PDF erforderlich.');
        $renderer=GeneralUtility::makeInstance(PreviewRenderer::class);$manifest=$renderer->manifest($files[0]->getOriginalFile(),$settings,$row['tx_pdfflipper_layout']);$count=0;$bytes=0;
        foreach($manifest['pages']as$page)foreach(['preview','hd']as$variant){parse_str((string)parse_url($page[$variant],PHP_URL_QUERY),$query);$image=$renderer->render($query['token']);$count++;$bytes+=$image['bytes'];}
        $output->writeln(json_encode(['content'=>$uid,'format'=>$settings['format'],'quality'=>$settings['quality'],'logical_pages'=>count($manifest['pages']),'images'=>$count,'bytes'=>$bytes,'pdf_sha256'=>$manifest['sha256']],JSON_THROW_ON_ERROR));return Command::SUCCESS;
    }
}
