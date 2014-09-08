<?php

date_default_timezone_set('Europe/Rome');

$json_filename = '../webstatus.json';
$json_array = (array) json_decode(file_get_contents($json_filename), true);

$products = [
    'discoplace'        => '',
    'fireplace'         => '',
    'spartacus'         => '',
    'webpay'            => '',
    'zippy'             => '',
    'zamboni'           => '',
    'marketplace-stats' => '',
    'commbadge'         => '',
    'rocketfuel'        => '',
];

$excluded_locales = ['an', 'br', 'db-LB', 'dsb', 'en', 'en-GB', 'en-US',
                     'en-ZA', 'es-AR', 'es-CL', 'es-ES', 'es-MX', 'fy', 'ga',
                     'gu-IN', 'hsb', 'hy-AM', 'is', 'ja-JP-mac', 'kk', 'lv',
                     'mai', 'mr', 'nb', 'nn-NO', 'pa-IN', 'pt', 'rm', 'son',
                     'sr-LATN', 'sv', 'sw', 'zh-Hant-TW', 'metadata'];

// Extract locales
$locales = [];
foreach ($json_array as $locale => $product) {
    array_push($locales, $locale);
}
sort($locales);
// Exclude some locales
$locales = array_diff($locales, $excluded_locales);

// Extract product names from en-US
foreach ($json_array['en-US'] as $code => $product) {
    if (array_key_exists($code, $products)) {
        $products[$code] = $product['name'];
    }
}
// asort($products);

function getRowStyle($current_product) {
    $perc = $current_product['percentage'];
    $opacity = 1;
    if ($perc < 100) {
        $opacity = floor(round(($perc-20)/100,2)*10)/10;
    }
    if ($perc >= 70) {
        $stylerow = "background-color: rgba(146, 204, 110, {$opacity});";
    } elseif ($perc >= 40) {
        $opacity = 1 - $opacity;
        $stylerow = "background-color: rgba(235, 235, 110, {$opacity});";
    } else {
        $opacity = 1 - $opacity;
        $stylerow = "background-color: rgba(255, 82, 82, {$opacity});";
    }
    $stylerow = "style='{$stylerow}'";
    return $stylerow;
}
?>

<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset=utf-8>
    <title>Marketplace Status</title>
    <link rel="stylesheet" href="../css/mpstats.css" type="text/css" media="all" />
</head>

<body>
<?php
    $content = "<table>\n";
    $content .= "  <thead>\n";
    $content .= "     <tr>\n";
    $content .= "       <th>&nbsp;</th>\n";
    foreach ($products as $code => $name) {
        $content .= "       <th colspan='3'>{$name}</th>\n";
    }
    $content .= "     </tr>\n";
    $content .= "     <tr>\n";
    $content .= "       <th>Locale</th>\n";
    for ($i=0; $i < count($products); $i++) {
        $content .= "       <th class='firstsection'>trans.</th>\n";
        $content .= "       <th>untrans.</th>\n";
        $content .= "       <th class='lastsection'>%</th>\n";
    }
    $content .= "     </tr>\n";
    $content .= "   </thead>\n";
    $content .= "   <tbody>\n";
    foreach ($locales as $locale) {
        $content .= "     <tr>\n";
        $content .= "       <th class='rowheader'><span>{$locale}</span></th>\n";
        foreach ($products as $code => $name) {
            if (array_key_exists($code, $json_array[$locale])) {
                $current_product = $json_array[$locale][$code];
                $content .= "       <td class='firstsection' " . getRowStyle($current_product) . ">{$current_product['translated']}</td>\n";
                $content .= "       <td " . getRowStyle($current_product) . ">{$current_product['untranslated']}</td>\n";
                $content .= "       <td class='lastsection' " . getRowStyle($current_product) . ">{$current_product['percentage']}</td>\n";
            } else {
                // Missing products
                $content .= "       <td colspan='3'>&nbsp;</td>\n";
            }

        }
        $content .= "     </tr>\n";
    }
    $content .= "   </tbody>\n";
    $content .= " </table>\n";
    $content .= "<p class='lastupdate'>Last update: {$json_array['metadata']['creation_date']}</p>";
    echo $content;
?>
</body>
</html>