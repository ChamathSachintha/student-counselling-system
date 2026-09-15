<?php
// Present existing form results inside the shared StudentCare page layout.
function renderResultPage($content) {
    return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
        . '<title>Your request · StudentCare</title><link rel="stylesheet" href="../css/style.css?v=2"></head>'
        . '<body class="result-page"><header class="public-header"><a class="brand" href="../index.html">'
        . '<span class="brand-mark"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">'
        . '<path d="M19 4c-8-1-14 2-14 8a6 6 0 0 0 6 6c6 0 9-6 8-14ZM5 20l9-9"/></svg></span>'
        . '<span>Student<span class="brand-light">Care</span><small>ROOM TO GROW</small></span></a></header>'
        . '<main class="result-main"><section class="card result-card"><p class="eyebrow">YOUR STUDENTCARE REQUEST</p>'
        . '<div class="result-content">' . $content . '</div><div class="result-links">'
        . '<a class="text-link" href="../index.html">Back to home</a>'
        . '<a class="text-link" href="../login.html">Go to your account →</a></div></section></main></body></html>';
}

// Wrap HTML form submissions while leaving GET JSON responses unchanged.
if (PHP_SAPI !== 'cli' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    ob_start('renderResultPage');
}
