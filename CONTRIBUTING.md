# Contribuindo para o LivreOS (Contributing Guidelines)

## Obrigado por querer contribuir!
A comunidade livre se mantém viva graças à colaboração de desenvolvedores, designers e usuários como você. Siga estas diretrizes para que sua contribuição seja bem‑recebida.

### 1. Configurando o ambiente de desenvolvimento
```bash
# Clone o repositório
git clone https://github.com/viniciusvams/LivreOS.git
cd LivreOS

# Instale as dependências PHP (Composer) e front‑end (npm)
composer install
npm install

# Copie o arquivo de exemplo .env e gere a chave da aplicação
cp .env.example .env
php artisan key:generate
```

### 2. Fluxo de trabalho básico
1. **Crie uma branch** a partir de `main` com um nome descritivo:
   ```bash
   git checkout -b feature/minha-nova-funcionalidade
   ```
2. **Faça suas alterações**, respeitando o padrão de codificação do projeto (PSR‑12).
3. **Execute testes** (se houver) e verifique o lint:
   ```bash
   php artisan test
   vendor/bin/phpcs --standard=PSR12 app
   ```
4. **Commit** suas mudanças com mensagens claras:
   ```bash
   git add .
   git commit -m "feat: descrição curta da nova funcionalidade"
   ```
5. **Push** para o seu fork e abra um Pull Request (PR) contra `main`.

### 3. Pull Request
- **Título**: use o padrão `type: short description` (ex.: `feat: adicionar filtro de data nas OS`).
- **Descrição**: descreva o que foi alterado, por quê, e inclua screenshots ou exemplos de uso se necessário.
- **Teste**: inclua instruções de teste ou screenshots que demonstrem a mudança.
- **Link**: referencie issues relacionadas usando `Closes #XYZ`.

### 4. Revisão de código
- Mantenedores irão revisar dentro de 48h.
- Responda a comentários e ajuste o PR conforme solicitado.
- Quando aprovado, o PR será mesclado.

### 5. Boas práticas
- **Código limpo**: siga PSR‑12, use nomes de variáveis claros e evite código duplicado.
- **Documentação**: atualize o README ou docs quando adicionar funcionalidades.
- **Internacionalização**: adicione traduções nas pastas `resources/lang/pt_BR` e `resources/lang/en` quando houver strings novas.

### 6. Reportando bugs ou sugerindo melhorias
- Use os templates de *issue* disponíveis em `.github/ISSUE_TEMPLATE/`.
- Forneça o máximo de detalhes: versão do PHP, Laravel, logs, passos para reproduzir, screenshots.

### 7. Código de Conduta
Ao participar, você concorda em seguir o [Código de Conduta](CODE_OF_CONDUCT.md).

---

## Thank you for contributing! (English)

### 1. Set up your development environment
```bash
# Clone the repository
git clone https://github.com/viniciusvams/LivreOS.git
cd LivreOS

# Install PHP and front‑end dependencies
composer install
npm install

# Copy the example env file and generate the app key
cp .env.example .env
php artisan key:generate
```

### 2. Basic workflow
1. **Create a branch** from `main`:
   ```bash
   git checkout -b feature/my-new-feature
   ```
2. Make your changes following the project's coding standards (PSR‑12).
3. Run tests and lint:
   ```bash
   php artisan test
   vendor/bin/phpcs --standard=PSR12 app
   ```
4. **Commit** with a clear message:
   ```bash
   git add .
   git commit -m "feat: short description of the new feature"
   ```
5. **Push** and open a Pull Request against `main`.

### 3. Pull Request guidelines
- **Title**: `type: short description` (e.g. `feat: add date filter to OS`).
- **Description**: Explain *what* and *why*, include screenshots or usage examples.
- **Testing**: Provide steps or screenshots that prove the change works.
- **Link**: Reference issues with `Closes #XYZ`.

### 4. Code Review
- Maintainers will review within 48 hours.
- Answer comments and adjust the PR as needed.
- Once approved, the PR will be merged.

### 5. Best practices
- **Clean code**: Follow PSR‑12, use clear naming, avoid duplication.
- **Documentation**: Update README or docs for new features.
- **Internationalization**: Add translations in `resources/lang/pt_BR` and `resources/lang/en` for new strings.

### 6. Reporting bugs / feature requests
- Use the issue templates in `.github/ISSUE_TEMPLATE/`.
- Provide as much detail as possible: PHP/Laravel version, logs, reproduction steps, screenshots.

### 7. Code of Conduct
By participating you agree to abide by the [Code of Conduct](CODE_OF_CONDUCT.md).

---

*We appreciate your effort and look forward to your contributions!*
