<?php

session_start();

$pathConfigFile = dirname(__DIR__) . '/path-config.php';
$lockFile = dirname(__DIR__) . '/installed.lock';
// Nao usar DOCUMENT_ROOT (muitos hosts reportam caminho errado).
$publicRoot = dirname(__DIR__);

function detect_livreos_system_path(string $publicDir): string
{
    $publicDir = rtrim(str_replace('\\', '/', $publicDir), '/');
    $parent = dirname($publicDir);
    $candidates = [
        $parent . '/sistema_erp',
        $parent . '/sistema_erp_sisbr',
        $publicDir,
    ];
    $grand = dirname($parent);
    if ($grand !== '' && $grand !== '.' && $grand !== $parent) {
        $candidates[] = $grand . '/sistema_erp';
        $candidates[] = $grand . '/sistema_erp_sisbr';
    }
    $fallback = $parent . '/sistema_erp';
    foreach ($candidates as $c) {
        $rp = @realpath(str_replace('/', DIRECTORY_SEPARATOR, $c));
        if ($rp !== false && is_file($rp . DIRECTORY_SEPARATOR . 'artisan')) {
            return str_replace('\\', '/', $rp);
        }
    }
    return $fallback;
}

$defaultSystemPath = detect_livreos_system_path($publicRoot);
$defaultConfig = [
    'public_path' => $publicRoot,
    'system_path' => $defaultSystemPath,
    'install_mode' => 'subdominio',
    'public_base_path' => '/',
];
$cfg = file_exists($pathConfigFile) ? (require $pathConfigFile) : [];
$cfg = is_array($cfg) ? $cfg : [];
$cfg = array_merge($defaultConfig, $cfg);

if (!isset($_SESSION['installer'])) {
    $_SESSION['installer'] = [];
}

function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function norm_path(string $p): string { return rtrim(str_replace('\\', '/', trim($p)), '/'); }
function bool_icon(bool $ok): string { return $ok ? 'OK' : 'ERRO'; }

$pubCheck = norm_path((string)($cfg['public_path'] ?? $publicRoot));
$sysCheck = norm_path((string)($cfg['system_path'] ?? ''));
if ($sysCheck === '' || !is_file($sysCheck . '/artisan')) {
    $cfg['system_path'] = detect_livreos_system_path($pubCheck !== '' ? $pubCheck : $publicRoot);
}

function update_env_value(string $envContent, string $key, string $value): string
{
    $line = $key . '=' . $value;
    if (preg_match('/^' . preg_quote($key, '/') . '=.*$/m', $envContent)) {
        return (string)preg_replace('/^' . preg_quote($key, '/') . '=.*$/m', $line, $envContent);
    }
    return rtrim($envContent) . PHP_EOL . $line . PHP_EOL;
}

function write_path_config(string $file, array $data): void
{
    $export = var_export($data, true);
    $php = "<?php\n\nreturn " . $export . ";\n";
    file_put_contents($file, $php);
}

function boot_artisan(string $systemPath): array
{
    $autoload = $systemPath . '/vendor/autoload.php';
    $bootstrap = $systemPath . '/bootstrap/app.php';

    if (!file_exists($autoload)) {
        throw new RuntimeException('vendor/autoload.php nao encontrado. O pacote precisa ser gerado com vendor.');
    }
    if (!file_exists($bootstrap)) {
        throw new RuntimeException('bootstrap/app.php nao encontrado no caminho do sistema.');
    }

    require_once $autoload;
    $app = require $bootstrap;
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    return [$app, $kernel];
}

$step = $_GET['step'] ?? '1';
$errors = [];
$logs = [];

if (file_exists($lockFile) && $step !== '5') {
    $step = '5';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postStep = $_POST['step'] ?? '';

    if ($postStep === '2') {
        $installMode = (string)($_POST['install_mode'] ?? 'subdominio');
        $publicPath = norm_path((string)($_POST['public_path'] ?? ''));
        $systemPath = norm_path((string)($_POST['system_path'] ?? ''));
        $publicBasePath = trim((string)($_POST['public_base_path'] ?? '/'));
        if ($publicBasePath === '') { $publicBasePath = '/'; }
        if ($publicBasePath[0] !== '/') { $publicBasePath = '/' . $publicBasePath; }

        if (!in_array($installMode, ['raiz', 'subpasta', 'subdominio'], true)) {
            $errors[] = 'Modo de instalacao invalido.';
        }
        if ($publicPath === '' || !is_dir($publicPath)) {
            $errors[] = 'Caminho da pasta publica invalido.';
        }
        if ($systemPath === '' || !is_dir($systemPath)) {
            $errors[] = 'Caminho da pasta do sistema invalido.';
        }
        if ($systemPath !== '' && is_dir($systemPath) && !is_file($systemPath . '/artisan')) {
            $errors[] = 'Na pasta do sistema deve existir o arquivo artisan. Informe o caminho absoluto completo (ex.: /home2/usuario/sistema_erp). Se a aplicacao estiver toda na pasta publica, use o mesmo caminho em pasta publica e pasta do sistema.';
        }

        if (!$errors) {
            $checks = [
                $systemPath . '/storage',
                $systemPath . '/bootstrap/cache',
                $systemPath . '/database',
            ];
            foreach ($checks as $dir) {
                if (!is_dir($dir)) {
                    $errors[] = 'Pasta obrigatoria ausente: ' . $dir;
                } elseif (!is_writable($dir)) {
                    $errors[] = 'Sem permissao de escrita: ' . $dir;
                }
            }
        }

        if (!$errors) {
            $cfgData = [
                'public_path' => $publicPath,
                'system_path' => $systemPath,
                'install_mode' => $installMode,
                'public_base_path' => $publicBasePath,
            ];
            write_path_config($pathConfigFile, $cfgData);
            $_SESSION['installer']['paths'] = $cfgData;
            $step = '3';
        } else {
            $step = '2';
        }
    }

    if ($postStep === '3') {
        $paths = $_SESSION['installer']['paths'] ?? $cfg;
        $systemPath = norm_path((string)($paths['system_path'] ?? ''));
        $envExample = $systemPath . '/.env.example';
        $envFile = $systemPath . '/.env';

        $appName = trim((string)($_POST['app_name'] ?? 'LivreOS'));
        $appUrl = trim((string)($_POST['app_url'] ?? 'http://localhost'));
        $dbConnection = (string)($_POST['db_connection'] ?? 'sqlite');
        $dbHost = trim((string)($_POST['db_host'] ?? '127.0.0.1'));
        $dbPort = trim((string)($_POST['db_port'] ?? '3306'));
        $dbDatabase = trim((string)($_POST['db_database'] ?? ''));
        $dbUsername = trim((string)($_POST['db_username'] ?? ''));
        $dbPassword = (string)($_POST['db_password'] ?? '');

        if (!in_array($dbConnection, ['sqlite', 'mysql'], true)) {
            $errors[] = 'Tipo de banco invalido.';
        }
        if (!file_exists($envExample)) {
            $errors[] = '.env.example nao encontrado em ' . $envExample;
        }
        if ($dbConnection === 'mysql') {
            if ($dbDatabase === '' || $dbUsername === '') {
                $errors[] = 'Informe DB_DATABASE e DB_USERNAME para MySQL.';
            }
        }

        if (!$errors) {
            $env = file_get_contents($envExample);
            if ($env === false) {
                $errors[] = 'Falha ao ler .env.example';
            } else {
                $safeAppName = '"' . str_replace('"', '\"', $appName) . '"';
                $env = update_env_value($env, 'APP_NAME', $safeAppName);
                $env = update_env_value($env, 'APP_URL', $appUrl);
                $env = update_env_value($env, 'APP_ENV', 'production');
                $env = update_env_value($env, 'APP_DEBUG', 'false');
                $env = update_env_value($env, 'DB_CONNECTION', $dbConnection);

                if ($dbConnection === 'sqlite') {
                    $env = update_env_value($env, 'DB_DATABASE', 'database/database.sqlite');
                    $env = update_env_value($env, 'DB_HOST', '127.0.0.1');
                    $env = update_env_value($env, 'DB_PORT', '3306');
                    $env = update_env_value($env, 'DB_USERNAME', 'root');
                    $env = update_env_value($env, 'DB_PASSWORD', '');
                } else {
                    $env = update_env_value($env, 'DB_HOST', $dbHost);
                    $env = update_env_value($env, 'DB_PORT', $dbPort === '' ? '3306' : $dbPort);
                    $env = update_env_value($env, 'DB_DATABASE', $dbDatabase);
                    $env = update_env_value($env, 'DB_USERNAME', $dbUsername);
                    $env = update_env_value($env, 'DB_PASSWORD', $dbPassword);
                }

                if (file_put_contents($envFile, $env) === false) {
                    $errors[] = 'Falha ao gravar .env';
                } else {
                    $_SESSION['installer']['env'] = [
                        'app_name' => $appName,
                        'app_url' => $appUrl,
                        'db_connection' => $dbConnection,
                        'db_host' => $dbHost,
                        'db_port' => $dbPort,
                        'db_database' => $dbDatabase,
                        'db_username' => $dbUsername,
                    ];
                    $step = '4';
                }
            }
        } else {
            $step = '3';
        }
    }

    if ($postStep === '4') {
        $paths = $_SESSION['installer']['paths'] ?? $cfg;
        $env = $_SESSION['installer']['env'] ?? [];
        $systemPath = norm_path((string)($paths['system_path'] ?? ''));
        $dbConnection = (string)($env['db_connection'] ?? 'sqlite');

        try {
            if ($dbConnection === 'sqlite') {
                $sqliteFile = $systemPath . '/database/database.sqlite';
                $dbDir = dirname($sqliteFile);
                if (!is_dir($dbDir)) {
                    throw new RuntimeException('Pasta database nao encontrada: ' . $dbDir);
                }
                if (!file_exists($sqliteFile)) {
                    if (file_put_contents($sqliteFile, '') === false) {
                        throw new RuntimeException('Falha ao criar arquivo SQLite.');
                    }
                    $logs[] = 'Arquivo SQLite criado: ' . $sqliteFile;
                } else {
                    $logs[] = 'Arquivo SQLite ja existe: ' . $sqliteFile;
                }
            }

            [$app, $kernel] = boot_artisan($systemPath);

            \Illuminate\Support\Facades\Artisan::call('key:generate', ['--force' => true]);
            $logs[] = "key:generate --force\n" . \Illuminate\Support\Facades\Artisan::output();

            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            $logs[] = "migrate --force\n" . \Illuminate\Support\Facades\Artisan::output();

            \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
            $logs[] = "db:seed --force\n" . \Illuminate\Support\Facades\Artisan::output();

            try {
                \Illuminate\Support\Facades\Artisan::call('plugins:resync-after-install');
                $logs[] = "plugins:resync-after-install\n" . \Illuminate\Support\Facades\Artisan::output();
            } catch (Throwable $pluginResyncEx) {
                $logs[] = 'plugins:resync-after-install (aviso): ' . $pluginResyncEx->getMessage();
            }

            file_put_contents($lockFile, 'installed_at=' . date('c'));
            $_SESSION['installer']['logs'] = $logs;
            $step = '5';
        } catch (Throwable $e) {
            $errors[] = 'Falha na instalacao: ' . $e->getMessage();
            $logs = array_merge($logs, $_SESSION['installer']['logs'] ?? []);
            $step = '4';
        }
    }
}

$phpOk = version_compare(PHP_VERSION, '8.2.0', '>=');
$pathsForCheck = $_SESSION['installer']['paths'] ?? $cfg;
$checkSystemPath = norm_path((string)($pathsForCheck['system_path'] ?? ''));
$artisanOk = ($checkSystemPath !== '' && is_file($checkSystemPath . '/artisan'));
$dirChecks = $artisanOk ? [
    $checkSystemPath . '/storage',
    $checkSystemPath . '/bootstrap/cache',
    $checkSystemPath . '/database',
] : [];

?><!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Instalador LivreOS</title>
    <style>
        body{font-family:Arial,sans-serif;background:#f3f4f6;margin:0;padding:20px;color:#111827}
        .card{max-width:920px;margin:0 auto;background:#fff;border-radius:10px;padding:24px;box-shadow:0 4px 20px rgba(0,0,0,.08)}
        h1{margin:0 0 8px;font-size:24px}
        h2{font-size:18px;margin-top:0}
        .muted{color:#4b5563}
        .row{display:flex;gap:16px;flex-wrap:wrap}
        .col{flex:1 1 280px}
        label{display:block;font-weight:bold;margin:12px 0 6px}
        input,select{width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px}
        .btn{display:inline-block;background:#2563eb;color:#fff;border:none;border-radius:8px;padding:10px 16px;cursor:pointer}
        .btn:disabled{opacity:.6;cursor:not-allowed}
        .alert{padding:12px;border-radius:8px;margin:10px 0}
        .err{background:#fee2e2;color:#991b1b}
        .ok{background:#dcfce7;color:#166534}
        .warn{background:#fef3c7;color:#92400e}
        code,pre{background:#111827;color:#e5e7eb;padding:10px;border-radius:8px;display:block;overflow:auto}
        ul{padding-left:18px}
        .steps{display:flex;gap:8px;flex-wrap:wrap;margin:12px 0 20px}
        .step{padding:6px 10px;border-radius:999px;background:#e5e7eb;font-size:12px}
        .active{background:#2563eb;color:#fff}
    </style>
</head>
<body>
<div class="card">
    <h1>Instalador LivreOS</h1>
    <p class="muted">Instalacao guiada para hospedagem compartilhada (sem terminal).</p>

    <div class="steps">
        <span class="step <?php echo $step === '1' ? 'active' : ''; ?>">1. Requisitos</span>
        <span class="step <?php echo $step === '2' ? 'active' : ''; ?>">2. Caminhos</span>
        <span class="step <?php echo $step === '3' ? 'active' : ''; ?>">3. .env</span>
        <span class="step <?php echo $step === '4' ? 'active' : ''; ?>">4. Banco e migracoes</span>
        <span class="step <?php echo $step === '5' ? 'active' : ''; ?>">5. Finalizacao</span>
    </div>

    <?php foreach ($errors as $e): ?>
        <div class="alert err"><?php echo h($e); ?></div>
    <?php endforeach; ?>

    <?php if ($step === '1'): ?>
        <h2>1) Verificacao de requisitos</h2>
        <div class="alert <?php echo $phpOk ? 'ok' : 'err'; ?>">
            PHP >= 8.2: <?php echo bool_icon($phpOk); ?> (atual: <?php echo h(PHP_VERSION); ?>)
        </div>
        <?php if (!$artisanOk): ?>
        <div class="alert warn">
            Ainda nao foi encontrado <code>artisan</code> no caminho do sistema (<code><?php echo h($checkSystemPath !== '' ? $checkSystemPath : '(vazio)'); ?></code>).
            Isso costuma acontecer quando a hospedagem usa caminhos como <code>/home2/public_html</code> (o instalador antigo sugeria <code>/home2/sistema_erp</code>, que esta errado).
            Avance para a <strong>etapa 2</strong> e informe o caminho absoluto correto da pasta onde esta o <code>artisan</code>
            (no seu caso, se tudo esta numa unica pasta, use esse caminho completo, ex.: <code>/home2/usuario/sistema_erp</code> para pasta publica e do sistema).
        </div>
        <?php else: ?>
        <p class="muted">Pasta do sistema detectada: <code><?php echo h($checkSystemPath); ?></code></p>
        <ul>
            <?php foreach ($dirChecks as $d): ?>
                <?php $ok = is_dir($d) && is_writable($d); ?>
                <li><?php echo h($d); ?> - <?php echo $ok ? 'OK' : 'ERRO'; ?></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
        <p class="muted">Se algum caminho estiver incorreto, ajuste na proxima tela.</p>
        <a class="btn" href="?step=2">Continuar</a>
    <?php endif; ?>

    <?php if ($step === '2'): ?>
        <h2>2) Configuracao de caminhos e tipo de publicacao</h2>
        <form method="post">
            <input type="hidden" name="step" value="2">
            <label>Como sera a instalacao?</label>
            <select name="install_mode">
                <option value="raiz" <?php echo ($cfg['install_mode'] === 'raiz') ? 'selected' : ''; ?>>Na raiz do site</option>
                <option value="subpasta" <?php echo ($cfg['install_mode'] === 'subpasta') ? 'selected' : ''; ?>>Em subpasta do site</option>
                <option value="subdominio" <?php echo ($cfg['install_mode'] === 'subdominio') ? 'selected' : ''; ?>>Em subdominio</option>
            </select>

            <label>Caminho absoluto da pasta publica (onde esta index.php e a pasta install)</label>
            <input type="text" name="public_path" value="<?php echo h((string)$cfg['public_path']); ?>" required>

            <label>Caminho absoluto da pasta do sistema Laravel (onde esta o arquivo artisan)</label>
            <input type="text" name="system_path" value="<?php echo h((string)$cfg['system_path']); ?>" required>
            <p class="muted">Se enviou app, bootstrap, vendor e public na mesma pasta (ex.: sistema_erp_sisbr), pode repetir o mesmo caminho nos dois campos.</p>

            <label>Base publica (use "/" na raiz, "/sis2" em subpasta)</label>
            <input type="text" name="public_base_path" value="<?php echo h((string)$cfg['public_base_path']); ?>" required>

            <p style="margin-top:14px"><button class="btn" type="submit">Salvar e continuar</button></p>
        </form>
    <?php endif; ?>

    <?php if ($step === '3'): ?>
        <?php $env = $_SESSION['installer']['env'] ?? []; ?>
        <h2>3) Configuracao do .env</h2>
        <form method="post">
            <input type="hidden" name="step" value="3">
            <div class="row">
                <div class="col">
                    <label>APP_NAME</label>
                    <input type="text" name="app_name" value="<?php echo h((string)($env['app_name'] ?? 'LivreOS')); ?>" required>
                </div>
                <div class="col">
                    <label>APP_URL</label>
                    <input type="text" name="app_url" value="<?php echo h((string)($env['app_url'] ?? 'https://seu-dominio.com')); ?>" required>
                </div>
            </div>

            <label>Banco de dados</label>
            <select name="db_connection" id="db_connection" onchange="toggleDbFields()">
                <option value="sqlite" <?php echo (($env['db_connection'] ?? 'sqlite') === 'sqlite') ? 'selected' : ''; ?>>SQLite (arquivo)</option>
                <option value="mysql" <?php echo (($env['db_connection'] ?? '') === 'mysql') ? 'selected' : ''; ?>>MySQL</option>
            </select>

            <div id="mysql_fields" style="display:none;">
                <div class="row">
                    <div class="col"><label>DB_HOST</label><input type="text" name="db_host" value="<?php echo h((string)($env['db_host'] ?? '127.0.0.1')); ?>"></div>
                    <div class="col"><label>DB_PORT</label><input type="text" name="db_port" value="<?php echo h((string)($env['db_port'] ?? '3306')); ?>"></div>
                </div>
                <div class="row">
                    <div class="col"><label>DB_DATABASE</label><input type="text" name="db_database" value="<?php echo h((string)($env['db_database'] ?? '')); ?>"></div>
                    <div class="col"><label>DB_USERNAME</label><input type="text" name="db_username" value="<?php echo h((string)($env['db_username'] ?? '')); ?>"></div>
                </div>
                <label>DB_PASSWORD</label>
                <input type="password" name="db_password" value="">
            </div>

            <p style="margin-top:14px"><button class="btn" type="submit">Salvar .env e continuar</button></p>
        </form>
        <script>
            function toggleDbFields() {
                var select = document.getElementById('db_connection');
                var mysql = document.getElementById('mysql_fields');
                mysql.style.display = select.value === 'mysql' ? 'block' : 'none';
            }
            toggleDbFields();
        </script>
    <?php endif; ?>

    <?php if ($step === '4'): ?>
        <h2>4) Criacao de banco, key e migracoes</h2>
        <p>Esta etapa vai:</p>
        <ul>
            <li>Criar database/database.sqlite (quando SQLite)</li>
            <li>Rodar key:generate</li>
            <li>Rodar migrate --force</li>
            <li>Rodar db:seed --force</li>
            <li>Desativar e reativar plugins ativos (plugins:resync-after-install)</li>
        </ul>
        <form method="post">
            <input type="hidden" name="step" value="4">
            <button class="btn" type="submit">Executar instalacao</button>
        </form>

        <?php if ($logs): ?>
            <h3>Logs</h3>
            <?php foreach ($logs as $lg): ?>
                <pre><?php echo h($lg); ?></pre>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($step === '5'): ?>
        <?php $savedLogs = $_SESSION['installer']['logs'] ?? []; ?>
        <div class="alert ok">Instalacao concluida com sucesso.</div>
        <h2>5) Finalizacao</h2>
        <p>Credenciais padrao:</p>
        <ul>
            <li><strong>E-mail:</strong> admin@admin.com</li>
            <li><strong>Senha:</strong> password</li>
        </ul>
        <div class="alert warn">
            Por seguranca, apague a pasta <strong>install</strong> ou bloqueie acesso a ela apos validar o login.
        </div>
        <p><a class="btn" href="/">Abrir sistema</a></p>

        <?php if ($savedLogs): ?>
            <h3>Logs da instalacao</h3>
            <?php foreach ($savedLogs as $lg): ?>
                <pre><?php echo h($lg); ?></pre>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>