# Instalação do LivreOS — Debian + Apache + PHP 8.4

Este guia descreve uma instalação do **LivreOS** em um servidor Debian, utilizando Apache, PHP 8.4 e SQLite.

A estrutura utilizada é:

```text
/var/www/
├── html/                  # pasta pública do Apache
│   ├── index.php
│   ├── install/
│   ├── build/
│   ├── images/
│   └── ...
│
└── sistema_livreos/      # aplicação Laravel, fora da área pública
    ├── app/
    ├── artisan
    ├── bootstrap/
    ├── config/
    ├── database/
    ├── plugins/
    ├── resources/
    ├── routes/
    ├── storage/
    ├── vendor/
    └── ...
```

> **Importante:** o `DocumentRoot` do Apache deve apontar para `/var/www/html`, enquanto a aplicação Laravel fica em `/var/www/sistema_livreos`.

---

## 1. Clonar o repositório

Clone o projeto:

```bash
cd /var/www
sudo git clone https://github.com/viniciusvams/LivreOS.git sistema_livreos
```

Entre na pasta:

```bash
cd /var/www/sistema_livreos
```

Verifique se o arquivo `artisan` existe:

```bash
ls -la /var/www/sistema_livreos/artisan
```

---

## 2. Preparar a pasta pública

O LivreOS separa a aplicação dos arquivos públicos. A aplicação fica fora do `DocumentRoot` e somente os arquivos públicos ficam em `/var/www/html`.

Prepare a pasta pública:

```bash
sudo mkdir -p /var/www/html
```

Os arquivos públicos do projeto devem estar em:

```text
/var/www/html/index.php
/var/www/html/install/index.php
/var/www/html/path-config.php
```

A aplicação Laravel deve permanecer em:

```text
/var/www/sistema_livreos
```

---

## 3. Instalar o Apache

```bash
sudo apt update
sudo apt install apache2 -y
```

Habilite e inicie o Apache:

```bash
sudo systemctl enable apache2
sudo systemctl start apache2
```

Verifique o serviço:

```bash
sudo systemctl status apache2
```

---

## 4. Instalar PHP

Neste ambiente foi utilizado o PHP 8.4.

```bash
sudo apt install php -y
```

Verifique a versão:

```bash
php -v
```

---

## 5. Instalar extensões PHP

Instale as extensões utilizadas pelo LivreOS:

```bash
sudo apt install php-dom -y
sudo apt install php-xml -y
sudo apt install php-{zip,mysql,mbstring,curl,pdo,gd,intl,bcmath,tokenizer,ctype,json} -y
sudo apt install php-sqlite3 -y
```

Verifique as extensões:

```bash
php -m
```

Entre as extensões esperadas:

```text
bcmath
ctype
curl
dom
fileinfo
gd
intl
mbstring
PDO
pdo_sqlite
SimpleXML
sqlite3
tokenizer
xml
xmlreader
xmlwriter
zip
```

### SQLite

Quando o banco utilizado for SQLite, o driver SQLite precisa estar instalado:

```bash
sudo apt install php-sqlite3 -y
```

Sem ele, o Laravel pode apresentar:

```text
could not find driver
```

---

## 6. PHP-FPM

O PHP-FPM pode ser instalado caso seja desejado utilizar Apache com PHP-FPM:

```bash
sudo apt install php-fpm -y

sudo systemctl enable php8.4-fpm
sudo systemctl start php8.4-fpm
```

Porém, na configuração utilizada neste laboratório, o Apache está utilizando **mod_php**.

Verifique:

```bash
sudo apache2ctl -M | grep -E 'php|mpm'
```

Resultado esperado:

```text
mpm_prefork_module (shared)
php_module (shared)
```

Nesse cenário, o PHP-FPM não é necessário para o funcionamento da aplicação.

---

## 7. Configurar o Apache

O `DocumentRoot` deve ser:

```text
/var/www/html
```

Edite:

```bash
sudo nano /etc/apache2/sites-enabled/000-default.conf
```

Utilize uma configuração semelhante a:

```apache
<VirtualHost *:80>
    ServerName livreos.tecnoroot.com.br

    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html

    <Directory /var/www/html>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/livreos-error.log
    CustomLog ${APACHE_LOG_DIR}/livreos-access.log combined
</VirtualHost>
```

O ponto mais importante é:

```apache
AllowOverride All
```

Isso permite que o Apache processe as regras presentes no `.htaccess`.

Teste a configuração:

```bash
sudo apache2ctl configtest
```

O resultado deve ser:

```text
Syntax OK
```

Recarregue o Apache:

```bash
sudo systemctl reload apache2
```

---

## 8. Verificar o `.htaccess`

Verifique o arquivo público:

```bash
ls -la /var/www/html/.htaccess
```

Caso exista um `.htaccess` dentro da aplicação, verifique também:

```bash
ls -la /var/www/sistema_livreos/.htaccess
```

Se o Apache apresentar erro semelhante a:

```text
.htaccess: </IfModule> without matching <IfModule>
```

existe um problema de sintaxe no arquivo `.htaccess`.

---

## 9. Permissões

O Apache normalmente executa como o usuário `www-data`.

Garanta acesso às pastas que precisam ser graváveis:

```bash
sudo chown -R www-data:www-data /var/www/sistema_livreos/storage
sudo chown -R www-data:www-data /var/www/sistema_livreos/bootstrap/cache
```

Para SQLite, o arquivo do banco também precisa ser gravável pelo Apache:

```bash
sudo chown www-data:www-data /var/www/sistema_livreos/database/database.sqlite
sudo chmod 664 /var/www/sistema_livreos/database/database.sqlite
```

---

## 10. Configurar o caminho da aplicação

O arquivo:

```text
/var/www/html/path-config.php
```

deve apontar para a aplicação:

```php
<?php

return array (
    'public_path' => '/var/www/html',
    'system_path' => '/var/www/sistema_livreos',
    'install_mode' => 'subdominio',
    'public_base_path' => '/',
);
```

O ponto fundamental é:

```php
'system_path' => '/var/www/sistema_livreos'
```

E o arquivo `artisan` precisa existir em:

```text
/var/www/sistema_livreos/artisan
```

---

## 11. Configurar o `.env`

O arquivo `.env` fica dentro da aplicação:

```text
/var/www/sistema_livreos/.env
```

Para um laboratório utilizando SQLite:

```dotenv
APP_NAME="LivreOS"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://livreos.tecnoroot.com.br

APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=pt_BR
APP_TIMEZONE=America/Sao_Paulo

DB_CONNECTION=sqlite
DB_DATABASE=/var/www/sistema_livreos/database/database.sqlite

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=debug

FILESYSTEM_DISK=local
```

Durante o desenvolvimento e laboratório, `APP_DEBUG=true` é útil para diagnosticar erros. Não utilize essa configuração em um ambiente público de produção.

---

## 12. Criar o banco SQLite

Caso o arquivo ainda não exista:

```bash
sudo touch /var/www/sistema_livreos/database/database.sqlite
```

Ajuste as permissões:

```bash
sudo chown www-data:www-data /var/www/sistema_livreos/database/database.sqlite
sudo chmod 664 /var/www/sistema_livreos/database/database.sqlite
```

---

## 13. Dependências do Composer

Se o projeto já possuir a pasta `vendor`, verifique:

```bash
ls /var/www/sistema_livreos/vendor/autoload.php
```

Caso seja necessário instalar as dependências:

```bash
cd /var/www/sistema_livreos
composer install
```

---

## 14. Verificar o Laravel

Teste o Artisan:

```bash
cd /var/www/sistema_livreos
php artisan --version
```

Teste também:

```bash
php artisan about
```

Se esses comandos funcionarem, a aplicação Laravel está sendo encontrada corretamente.

---

## 15. Executar o instalador

Com Apache e PHP configurados, acesse:

```text
http://livreos.tecnoroot.com.br/install/
```

Ou, caso o HTTPS esteja configurado:

```text
https://livreos.tecnoroot.com.br/install/
```

O instalador deverá:

1. Verificar os requisitos;
2. Identificar a pasta do `artisan`;
3. Configurar os caminhos;
4. Configurar o `.env`;
5. Configurar o banco;
6. Executar `key:generate`;
7. Executar as migrations;
8. Executar os seeders;
9. Finalizar a instalação.

---

## 16. Verificar a instalação

Após uma instalação concluída, o arquivo:

```text
/var/www/html/installed.lock
```

deve existir.

O sistema deverá deixar de redirecionar para `/install/` e abrir normalmente a aplicação.

Para verificar as migrations:

```bash
cd /var/www/sistema_livreos
php artisan migrate:status
```

---

## 17. Credenciais iniciais

```text
E-mail: admin@admin.com
Senha: password
```

> **Importante:** altere a senha após o primeiro acesso.

---

## 18. Diagnóstico de erros

### `Class "DOMDocument" not found`

```bash
sudo apt install php-dom php-xml -y
sudo systemctl restart apache2
```

### `could not find driver`

Para SQLite:

```bash
sudo apt install php-sqlite3 -y
sudo systemctl restart apache2
```

Verifique:

```bash
php -m | grep -E 'sqlite|pdo'
```

O resultado deve conter:

```text
PDO
pdo_sqlite
sqlite3
```

### `Not Found` ao acessar uma rota do Laravel

Verifique:

```bash
sudo grep -n "AllowOverride" /etc/apache2/apache2.conf
```

O VirtualHost deve possuir:

```apache
<Directory /var/www/html>
    AllowOverride All
    Require all granted
</Directory>
```

Depois:

```bash
sudo apache2ctl configtest
sudo systemctl reload apache2
```

### `Internal Server Error`

Primeiro verifique o log específico do site:

```bash
sudo tail -n 100 /var/log/apache2/livreos-error.log
```

Depois o log geral:

```bash
sudo tail -n 100 /var/log/apache2/error.log
```

Se o erro ocorrer antes do Laravel iniciar, verifique:

```bash
sudo cat /var/www/html/bootstrap-error.log
```

---

## 19. Verificação final

A instalação está corretamente estruturada quando:

```text
/var/www/html
```

é o `DocumentRoot` do Apache e contém a parte pública do sistema, enquanto:

```text
/var/www/sistema_livreos
```

contém a aplicação Laravel completa.

Entre os arquivos e diretórios esperados:

```text
/var/www/sistema_livreos/
├── artisan
├── app/
├── bootstrap/
├── config/
├── database/
├── plugins/
├── resources/
├── routes/
├── storage/
├── vendor/
└── .env
```

Verifique a configuração do Apache:

```bash
sudo apache2ctl -S
```

Verifique o PHP carregado pelo Apache:

```bash
sudo apache2ctl -M | grep -E 'php|mpm'
```

Verifique o PHP pela CLI:

```bash
php -v
php -m
```

Verifique o Laravel:

```bash
cd /var/www/sistema_livreos
php artisan --version
php artisan migrate:status
```

Com esses componentes funcionando, o LivreOS estará instalado em um ambiente adequado para desenvolvimento e testes.
