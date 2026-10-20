<?php
require __DIR__ . '/../Classes/Domain/PageSelection.php';
use Knthlz\PdfFlipper\Domain\PageSelection;
if (PageSelection::normalize(' 3–8, 12 ') !== '3-8, 12') throw new RuntimeException('Normalization failed');
foreach (['0','8-3','10001','1,','<script>','3;5'] as $input) {
    try { PageSelection::normalize($input); } catch (InvalidArgumentException) { continue; }
    throw new RuntimeException('Accepted invalid range: ' . $input);
}
echo "PHP page-selection cases passed\n";
