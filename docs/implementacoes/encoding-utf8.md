# Encoding UTF-8 (sem BOM)

## Padrão do projeto

Todo arquivo de texto do repositório deve estar em **UTF-8 sem BOM** (sem assinatura `EF BB BF` no início).

Isso evita erros clássicos em PHP (`headers already sent`), caracteres estranhos no HTML e inconsistência entre editores.

## Configuração recomendada

| Ferramenta | Configuração |
|------------|----------------|
| EditorConfig | `.editorconfig` → `charset = utf-8` |
| VS Code / Cursor | `.vscode/settings.json` → `"files.encoding": "utf8"` |
| Cursor Agent | `.cursor/rules/utf8-encoding.mdc` |

No VS Code/Cursor, **não** use "UTF-8 with BOM" ao salvar. Prefira **UTF-8**.

## Verificação automática

```bash
composer encoding
# ou
php bin/check-encoding.php
```

O comando `composer check` também executa essa verificação antes do PHPStan e dos testes.

## Respostas HTTP

- HTML: `Content-Type: text/html; charset=UTF-8` (via `View::render`) + `<meta charset="UTF-8">` nos layouts.
- JSON: `charset=utf-8` + `JSON_UNESCAPED_UNICODE`.
- PHP: `default_charset` e `mb_internal_encoding('UTF-8')` em `app/bootstrap.php`.

## Corrigir arquivo com BOM

1. Abrir o arquivo no editor.
2. Salvar como **UTF-8** (sem BOM), ou
3. Rodar na raiz do projeto (PowerShell):

```powershell
$utf8 = New-Object System.Text.UTF8Encoding $false
Get-ChildItem -Recurse -File caminho/do/arquivo.php | ForEach-Object {
  $b = [IO.File]::ReadAllBytes($_.FullName)
  if ($b.Length -ge 3 -and $b[0]-eq 0xEF -and $b[1]-eq 0xBB -and $b[2]-eq 0xBF) {
    [IO.File]::WriteAllText($_.FullName, $utf8.GetString($b,3,$b.Length-3), $utf8)
  }
}
```
