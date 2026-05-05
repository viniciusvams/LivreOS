<?php
/**
 * Vercel Serverless Entry Point - LivreOS
 * O filesystem da Vercel e read-only, exceto /tmp.
 */
define('LARAVEL_START', microtime(true));

$tmpViews = '/tmp/framework/views';
$tmpCache = '/tmp/framework/cache';

if (!is_dir($tmpViews)) mkdir($tmpViews, 0775, true);
if (!is_dir($tmpCache)) mkdir($tmpCache, 0775, true);

putenv('LOG_CHANNEL=stderr');
putenv('SESSION_DRIVER=cookie');
putenv('CACHE_DRIVER=array');
putenv("VIEW_COMPILED_PATH=$tmpViews");

$_ENV['LOG_CHANNEL']        = 'stderr';
$_ENV['SESSION_DRIVER']     = 'cookie';
$_ENV['CACHE_DRIVER']       = 'array';
$_ENV['VIEW_COMPILED_PATH'] = $tmpViews;

$_SERVER['LOG_CHANNEL']        = 'stderr';
$_SERVER['SESSION_DRIVER']     = 'cookie';
$_SERVER['CACHE_DRIVER']       = 'array';
$_SERVER['VIEW_COMPILED_PATH'] = $tmpViews;

chdir(__DIR__ . '/../public');
require __DIR__ . '/../public/index.php';