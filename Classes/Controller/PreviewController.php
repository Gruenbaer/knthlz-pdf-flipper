<?php
declare(strict_types=1);
namespace Knthlz\PdfFlipper\Controller;
use Knthlz\PdfFlipper\Imaging\PreviewRenderer;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;
final class PreviewController
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try{
            if(!in_array($request->getMethod(),['GET','HEAD'],true))return new Response('php://temp',405,['Allow'=>'GET, HEAD']);
            $image=GeneralUtility::makeInstance(PreviewRenderer::class)->render((string)($request->getQueryParams()['token']??''));
            $headers=['Content-Type'=>$image['mime'],'Content-Length'=>(string)$image['bytes'],'Cache-Control'=>'public, max-age=31536000, immutable','ETag'=>'"'.$image['sha256'].'"','X-Content-Type-Options'=>'nosniff'];
            if($request->getHeaderLine('If-None-Match')===$headers['ETag'])return new Response('php://temp',304,$headers);
            $response=new Response('php://temp',200,$headers);if($request->getMethod()!=='HEAD')$response->getBody()->write(file_get_contents($image['path']));return $response;
        }catch(\Throwable $error){$response=new Response('php://temp',$error instanceof \InvalidArgumentException?400:503,['Content-Type'=>'text/plain; charset=utf-8','Cache-Control'=>'no-store']);$response->getBody()->write('PDF-Vorschau nicht verfügbar. Bitte die Original-PDF öffnen.');return $response;}
    }
}
