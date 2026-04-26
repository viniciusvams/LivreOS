<?php

// Detecta pasta do Laravel (onde existe artisan): irmao sistema_erp, nomes alternativos, ou mesmo que a publica.
$publicDir = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');
$parent = dirname($publicDir);
$candidates = [
    $parent . '/sistema_livreos',
    $publicDir,
];
$grand = dirname($parent);
if ($grand !== '' && $grand !== '.' && $grand !== $parent) {
    $candidates[] = $grand . '/sistema_livreos';
    
}
$systemPath = $parent . '/sistema_livreos';
foreach ($candidates as $c) {
    $rp = @realpath(str_replace('/', DIRECTORY_SEPARATOR, $c));
    if ($rp !== false && is_file($rp . DIRECTORY_SEPARATOR . 'artisan')) {
        $systemPath = str_replace('\\', '/', $rp);
        break;
    }
}

return [
    'public_path' => $publicDir,
    'system_path' => $systemPath,
    'install_mode' => 'subdominio',
    'public_base_path' => '/',
];