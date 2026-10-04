# Guia de Instalação Descomplicada do LivreOS

Bem-vindo! Este guia vai te ajudar a instalar o **LivreOS** em um servidor Debian de forma simples, utilizando o servidor web Apache, a linguagem PHP 8.4 e um banco de dados leve chamado SQLite.

A maior regra de segurança deste tutorial é a **separação de pastas**:
* **O que o público vê:** Ficará na pasta `/var/www/html/`.
* **O "cérebro" do sistema:** Ficará protegido na pasta `/var/www/sistema_livreos/`, fora do alcance direto da internet.

---

## 1. Baixando o Sistema
Primeiro, vamos baixar o código oficial para o seu servidor:

```bash
cd /var/www
sudo git clone https://github.com/viniciusvams/LivreOS.git sistema_livreos
```
Para confirmar que baixou corretamente, verifique se o arquivo do sistema (chamado `artisan`) está lá:
```bash
ls -la /var/www/sistema_livreos/artisan
```

## 2. Preparando a Área Pública
Agora, vamos preparar a pasta que os usuários realmente vão acessar pelo navegador:

```bash
sudo mkdir -p /var/www/html
```
*Atenção:* Os arquivos públicos (`index.php`, `path-config.php` e a pasta `install/`) devem ficar dentro de `/var/www/html`. O restante do sistema deve continuar escondido em `/var/www/sistema_livreos`.

## 3. Instalando o Servidor Web (Apache)
O Apache é o programa responsável por colocar o seu site no ar. Instale e ligue-o com os comandos abaixo:

```bash
sudo apt update
sudo apt install apache2 -y
sudo systemctl enable apache2
sudo systemctl start apache2
```

## 4. Instalando a Linguagem (PHP 8.4) e suas Ferramentas
O LivreOS é escrito em PHP. Vamos instalar a versão principal e também os "acessórios" (extensões) que ele precisa para lidar com imagens, arquivos e banco de dados:

```bash
sudo apt install php -y
sudo apt install php-dom php-xml php-sqlite3 -y
sudo apt install php-{zip,mysql,mbstring,curl,pdo,gd,intl,bcmath,tokenizer,ctype,json} -y
```
*Nota:* O pacote `php-sqlite3` é obrigatório aqui. Sem ele, o sistema não conseguirá se comunicar com o banco de dados.

## 5. Configurando o Apache
Precisamos "ensinar" ao Apache onde estão os arquivos públicos do LivreOS. Edite as configurações:

```bash
sudo nano /etc/apache2/sites-enabled/000-default.conf
```
Apague o que estiver lá e deixe o arquivo parecido com o modelo abaixo, ajustando o `ServerName` para o seu domínio:

```apache
<VirtualHost *:80>
    ServerName livreos.tecnoroot.com.br
    DocumentRoot /var/www/html

    <Directory /var/www/html>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/livreos-error.log
    CustomLog ${APACHE_LOG_DIR}/livreos-access.log combined
</VirtualHost>
```
*Aviso de Segurança:* Note que deixamos apenas `Options FollowSymLinks`. Isso impede que curiosos consigam listar os arquivos do seu servidor pelo navegador. O segredo para as páginas do sistema abrirem sem erro é a linha `AllowOverride All`.

Salve o arquivo e reinicie o servidor:
```bash
sudo systemctl reload apache2
```

## 6. Ajustando as Permissões
O servidor precisa de autorização para salvar arquivos e gravar dados no sistema. Rode os comandos abaixo para liberar esse acesso ao Apache (usuário `www-data`):

```bash
sudo chown -R www-data:www-data /var/www/sistema_livreos/storage
sudo chown -R www-data:www-data /var/www/sistema_livreos/bootstrap/cache
```

## 7. Mostrando o Caminho
Você precisa dizer à pasta pública onde o resto do sistema está escondido. Abra o arquivo `/var/www/html/path-config.php` e garanta que a linha `system_path` esteja apontando para o local certo:

```php
'system_path' => '/var/www/sistema_livreos',
```

## 8. Banco de Dados e Configurações Essenciais
As configurações de segurança do sistema ficam no arquivo `/var/www/sistema_livreos/.env`. Abra-o ou crie-o, e certifique-se de preencher as seguintes linhas para garantir a segurança e a comunicação com o banco:

```dotenv
APP_ENV=production
APP_DEBUG=false

DB_CONNECTION=sqlite
DB_DATABASE=/var/www/sistema_livreos/database/database.sqlite
```
*Aviso de Segurança:* A linha `APP_DEBUG=false` é obrigatória. Ela impede que falhas exibam senhas ou caminhos de pastas na tela dos usuários.

Em seguida, crie o arquivo vazio para o banco de dados e dê as permissões corretas **tanto para o arquivo quanto para a pasta**. Isso impede travamentos quando o sistema tentar salvar informações:
```bash
sudo touch /var/www/sistema_livreos/database/database.sqlite

sudo chown www-data:www-data /var/www/sistema_livreos/database
sudo chown www-data:www-data /var/www/sistema_livreos/database/database.sqlite

sudo chmod 775 /var/www/sistema_livreos/database
sudo chmod 664 /var/www/sistema_livreos/database/database.sqlite
```

## 9. Baixando Pacotes de Terceiros (Composer)
Caso a pasta `vendor` não exista, você precisará baixar as dependências que fazem o sistema funcionar. Para um ambiente de produção (seguro), utilize:
```bash
cd /var/www/sistema_livreos
composer install --optimize-autoloader --no-dev
```
*(Dica: A tag `--no-dev` impede a instalação de ferramentas de teste que não devem ficar em um servidor online).*

Para ter certeza de que tudo deu certo, rode:
```bash
php artisan about
```
Se mostrar as informações do sistema, o motor principal está funcionando!

## 10. Instalação Final no Navegador
A parte dos comandos no terminal acabou! Abra o seu navegador e acesse a tela de instalação visual:
👉 `http://livreos.tecnoroot.com.br/install/` *(substitua pelo seu endereço de site ou IP)*

O instalador vai conferir os requisitos, preparar as tabelas do banco de dados e finalizar tudo sozinho. Quando terminar, o sistema criará uma trava automática para impedir que outras pessoas acessem o instalador novamente.

### Dados de Acesso Padrão:
* **E-mail:** admin@admin.com
* **Senha:** password

*(Importante: Troque essa senha imediatamente após o primeiro login!)*

---

### Solução Rápida de Problemas (FAQ)
* **Erro `could not find driver`:** Faltou instalar a extensão do banco de dados. Reveja o passo 4 (pacote `php-sqlite3`).
* **Erro `Class "DOMDocument" not found`:** Faltou instalar o `php-dom` e o `php-xml` (passo 4).
* **Página com erro `Not Found` ou links quebrando:** Reveja o passo 5. Provavelmente faltou o bloco com `AllowOverride All` nas configurações do seu Apache.
* **Tela Branca ou `Internal Server Error`:** O jeito mais rápido de descobrir o que houve é olhar no "diário" de erros do servidor. Execute `sudo tail -n 100 /var/log/apache2/livreos-error.log` para ver exatamente o que travou a página.
