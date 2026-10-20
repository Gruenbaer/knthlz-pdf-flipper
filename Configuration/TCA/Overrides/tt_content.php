<?php
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
defined('TYPO3') || die();
ExtensionManagementUtility::addTCAcolumns('tt_content', [
    'tx_pdfflipper_effect' => ['label'=>'Blättereffekt','config'=>['type'=>'select','renderType'=>'selectSingle','default'=>'curl','items'=>[
        ['label'=>'Papier umblättern','value'=>'curl'],['label'=>'Seitlich schieben','value'=>'slide'],['label'=>'Sofort wechseln (ohne Animation)','value'=>'none'],
    ]]],
    'tx_pdfflipper_corner' => ['label'=>'Seitenecken-Empfindlichkeit (0–100)','description'=>'0 schaltet die Mausvorschau aus. Kleine Werte: nur nahe der Ecke; große Werte: größere Reaktionszone. Ziehen und Buttons bleiben nutzbar.','config'=>['type'=>'number','default'=>35,'range'=>['lower'=>0,'upper'=>100]]],
    'tx_pdfflipper_delay' => ['label'=>'Seitenecken-Verzögerung (Millisekunden)','description'=>'Verhindert, dass kurzes Überfahren der Ecke sofort eine Vorschau auslöst.','config'=>['type'=>'number','default'=>120,'range'=>['lower'=>0,'upper'=>600]]],
    'tx_pdfflipper_duration' => ['label'=>'Blätterdauer (Millisekunden)','config'=>['type'=>'number','default'=>650,'range'=>['lower'=>150,'upper'=>1500]]],
    'tx_pdfflipper_clickmode' => ['label'=>'Mausbedienung','config'=>['type'=>'select','renderType'=>'selectSingle','default'=>'page','items'=>[
        ['label'=>'Klick auf die Seite oder Ziehen','value'=>'page'],['label'=>'Nur Ecken anklicken oder Ziehen','value'=>'corners'],['label'=>'Nur Buttons / Tastatur; keine Blattgesten','value'=>'controls'],
    ]]],
    'tx_pdfflipper_support' => ['label'=>'Dezenten Unterstützungslink anzeigen','description'=>'Optional. Verwendet den Buy-Me-a-Coffee-Link der Extension-Konfiguration. Keine Einblendung über dem Dokument, kein externes Skript.','config'=>['type'=>'check','default'=>0]],


    'tx_pdfflipper_format' => ['label'=>'Bildart / Darstellung','description'=>'Bilder werden automatisch serverseitig erzeugt und zwischengespeichert. PDF-Direktanzeige benötigt keine Bildkonvertierung.','config'=>['type'=>'select','renderType'=>'selectSingle','default'=>'webp','items'=>[
        ['label'=>'WebP','value'=>'webp'],['label'=>'JPEG','value'=>'jpg'],['label'=>'PNG (verlustfrei)','value'=>'png'],['label'=>'PDF direkt im Browser','value'=>'pdf'],
    ]]],
    'tx_pdfflipper_quality' => ['label'=>'Bildqualität (1–100)','description'=>'Für WebP und JPEG. Höhere Werte bedeuten größere Dateien. PNG bleibt verlustfrei.','displayCond'=>'FIELD:tx_pdfflipper_format:IN:webp,jpg','config'=>['type'=>'number','default'=>85,'range'=>['lower'=>1,'upper'=>100]]],
    'tx_pdfflipper_width' => ['label'=>'Vorschau-Breite je Heftseite (Pixel)','config'=>['type'=>'number','default'=>800,'range'=>['lower'=>320,'upper'=>1200]]],
    'tx_pdfflipper_hdwidth' => ['label'=>'HD-Breite für Zoom / Retina (Pixel)','description'=>'Mindestens Vorschau-Breite. Sehr große oder ungewöhnliche Seiten werden auf 4000 Pixel je PDF-Bogen begrenzt.','config'=>['type'=>'number','default'=>1600,'range'=>['lower'=>640,'upper'=>2000]]],
    'tx_pdfflipper_background' => ['label'=>'Hintergrundfarbe','config'=>['type'=>'color','default'=>'#f4f3f1']],
    'tx_pdfflipper_buttonbg' => ['label'=>'Button-Hintergrund','config'=>['type'=>'color','default'=>'#ffffff']],
    'tx_pdfflipper_buttoncolor' => ['label'=>'Button-Schriftfarbe','config'=>['type'=>'color','default'=>'#222222']],
    'tx_pdfflipper_reading' => ['label'=>'Lesemodus beim Öffnen','config'=>['type'=>'select','renderType'=>'selectSingle','default'=>'auto','items'=>[
        ['label'=>'Automatisch: mobil einzeln, sonst Doppelseite','value'=>'auto'],['label'=>'Immer Einzelseiten mit Blättereffekt','value'=>'single'],['label'=>'Immer Doppelseiten mit Blättereffekt','value'=>'double'],['label'=>'Ruhiger Lesemodus: einzeln, ohne Blättereffekt','value'=>'read'],['label'=>'Fortlaufend untereinander scrollen','value'=>'scroll'],
    ]]],
    'tx_pdfflipper_buttons' => ['label'=>'Sichtbare Bedienelemente','description'=>'Nur ausgewählte Buttons werden angezeigt. Der Lesemodus kann über den entsprechenden Button gewechselt werden.','config'=>['type'=>'select','renderType'=>'selectCheckBox','default'=>'previous,page,next,zoom,fullscreen,download,reading,view,overview,search,contents,share,bookmark,back','items'=>[
        ['label'=>'Zurück','value'=>'previous'],['label'=>'Seiteneingabe / Seitenzahl','value'=>'page'],['label'=>'Weiter','value'=>'next'],['label'=>'Zoom','value'=>'zoom'],['label'=>'Vollbild','value'=>'fullscreen'],['label'=>'PDF-Download','value'=>'download'],['label'=>'Lesemodus wechseln','value'=>'reading'],['label'=>'Ansicht / Effekt wählen','value'=>'view'],['label'=>'Seitenübersicht','value'=>'overview'],['label'=>'Textsuche','value'=>'search'],['label'=>'PDF-Inhaltsverzeichnis','value'=>'contents'],['label'=>'Seitenlink teilen','value'=>'share'],['label'=>'Lokales Lesezeichen','value'=>'bookmark'],['label'=>'Lesefaden / zurück zur Fundstelle','value'=>'back'],
    ]]],
    'tx_pdfflipper_pages' => [
        'label' => 'Relevante Heftseiten',
        'description' => 'Zum Beispiel 3–8, 12. Heftseiten nach Aufteilung der Doppelseiten. Leer: alle Seiten. Die PDF bleibt vollständig herunterladbar.',
        'config' => ['type' => 'input', 'size' => 40, 'max' => 1024, 'eval' => 'trim'],
    ],
    'tx_pdfflipper_selection' => [
        'label' => 'Umgang mit relevanten Seiten',
        'config' => ['type' => 'select', 'renderType' => 'selectSingle', 'default' => 'highlight', 'items' => [
            ['label' => 'Markieren; vollständiges Heft behalten', 'value' => 'highlight'],
            ['label' => 'Nur ausgewählte Heftseiten anzeigen', 'value' => 'only'],
        ]],
    ],
    'tx_pdfflipper_layout' => [
        'label' => 'Aufbau der PDF',
        'description' => 'Automatisch: Hochformat einzeln, Querformat als Doppelseite. Bei ausschließlich Querformat wird die erste Seite als Umschlag (Rückseite links, Titel rechts) behandelt. Bei abweichendem Aufbau ausdrücklich wählen.',
        'config' => ['type' => 'select', 'renderType' => 'selectSingle', 'default' => 'auto', 'items' => [
            ['label' => 'Automatisch erkennen', 'value' => 'auto'],
            ['label' => 'Jede PDF-Seite ist eine Heftseite', 'value' => 'single'],
            ['label' => 'Jede PDF-Seite enthält zwei Heftseiten', 'value' => 'spreads'],
            ['label' => 'Einzelne Umschlagseiten; Doppelseiten innen', 'value' => 'mixed'],
            ['label' => 'Erste Doppelseite ist der Umschlag', 'value' => 'cover-spread'],
        ]],
    ],
    'tx_pdfflipper_start' => [
        'label' => 'Beim Öffnen angezeigte Heftseite',
        'description' => '0: erste relevante Seite, sonst Titel. Bezieht sich auf die ursprüngliche Heftseitennummer.',
        'config' => ['type' => 'number', 'default' => 0, 'range' => ['lower' => 0, 'upper' => 10000]],
    ],
    'tx_pdfflipper_accent' => [
        'label' => 'Farbe der Bedienelemente',
        'config' => ['type' => 'color', 'default' => '#c3323a'],
    ],
]);
ExtensionManagementUtility::addRecordType(
    ['label' => 'knthlz PDF Flipper', 'description' => 'PDF als Blätterbuch mit relevanten Heftseiten', 'value' => 'pdf_flipper', 'icon' => 'mimetypes-pdf', 'group' => 'default'],
    '--palette--;;headers,assets,tx_pdfflipper_pages,tx_pdfflipper_selection,tx_pdfflipper_start,tx_pdfflipper_layout,--div--;Bilder und Qualität,tx_pdfflipper_format,tx_pdfflipper_quality,tx_pdfflipper_width,tx_pdfflipper_hdwidth,--div--;Reader und Farben,tx_pdfflipper_reading,tx_pdfflipper_effect,tx_pdfflipper_buttons,tx_pdfflipper_accent,tx_pdfflipper_background,tx_pdfflipper_buttonbg,tx_pdfflipper_buttoncolor,tx_pdfflipper_support,--div--;Maus und Bewegung,tx_pdfflipper_corner,tx_pdfflipper_delay,tx_pdfflipper_duration,tx_pdfflipper_clickmode,--div--;Erscheinungsbild,--palette--;;frames,--palette--;;appearanceLinks',
    ['columnsOverrides' => ['assets' => ['label' => 'PDF-Datei', 'config' => ['allowed' => 'pdf', 'minitems' => 1, 'maxitems' => 1]]]]
);
