# Botões de ação — padrão visual

Padrão único para botões em tabelas, formulários, filtros e cabeçalhos. Estilos em `public/assets/css/design-system.css`; helper PHP em `app/Helpers/ActionButton.php`; partial em `app/Views/components/action-buttons.php`.

## Variantes

| Classe | Uso | Exemplos |
|--------|-----|----------|
| `btn-primary` | Ação principal | Salvar, Novo, Filtrar, Nova venda |
| `btn-secondary` | Ação neutra | Cancelar, Voltar, Limpar, atalhos no dashboard |
| `btn-outline` | Edição em listas | Editar (linha da tabela) |
| `btn-destructive` | Remoção / risco | Excluir, Remover vínculo |
| `btn-danger` | Confirmação forte (sólido) | Confirmar no modal global |
| `btn-warning` | Atenção / toggle | Desativar usuário, Desativar empresa |
| `btn-ghost` | Terciária / link | Detalhes, Ver relatório, Senha |
| `btn-export-pdf` / `btn-export-xlsx` | Exportação | Barra de relatórios |

**Compatibilidade:** `btn-outline-secondary`, `btn-danger-soft` e `btn-outline-danger` continuam mapeados às variantes novas (secundária / destrutiva).

## Tamanhos

| Classe | Uso |
|--------|-----|
| `btn-sm` | Ações em tabelas (padrão para linha) |
| *(omitir ou `btn-md`)* | Formulários, filtros, cabeçalhos |
| `btn-lg` | CTAs raros em landing/onboarding |

## Grupos de ações

```html
<div class="table-actions">
  <a class="btn btn-sm btn-outline" href="...">Editar</a>
  <button class="btn btn-sm btn-destructive" type="submit">Excluir</button>
</div>

<div class="form-actions">
  <button type="submit" class="btn btn-primary">Salvar</button>
  <a class="btn btn-secondary" href="...">Cancelar</a>
</div>

<div class="filter-actions">
  <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filtrar</button>
  <a class="btn btn-secondary" href="...">Limpar</a>
</div>
```

## Partial PHP (`filter-panel.php`)

Envolve filtros em `card-soft filter-panel` e inclui automaticamente o modo `filter` de `action-buttons.php`.

```php
<?php
ob_start();
?>
<div class="col-md-4">
    <label class="form-label" for="q">Busca</label>
    <input class="form-control" id="q" name="q" value="...">
</div>
<?php
$filterContent = ob_get_clean();
$filterAction = $url('orders');
$filterClearHref = $url('orders');
require dirname(__DIR__) . '/components/filter-panel.php';
```

## Partial PHP (`action-buttons.php`)

### Linha de tabela (`table-row`)

```php
<?php
$mode = 'table-row';
$editHref = $url('customers/edit?id=' . $c->id);
$deleteAction = $url('customers/delete');
$deleteId = (int) $c->id;
$deleteConfirm = 'Remover este cliente?';
$deleteTitle = 'Excluir cliente';
$canEdit = \App\Helpers\Permission::can('clientes', 'editar');
$canDelete = \App\Helpers\Permission::can('clientes', 'excluir');
require dirname(__DIR__) . '/components/action-buttons.php';
?>
```

### Rodapé de formulário (`form-footer`)

```php
<?php
$mode = 'form-footer';
$cancelHref = $url('customers');
$saveLoadingText = 'Salvando...'; // opcional
require dirname(__DIR__) . '/components/action-buttons.php';
?>
```

### Filtros (`filter`)

```php
<?php
$mode = 'filter';
$clearHref = $url('products'); // omitir se não houver limpar
require dirname(__DIR__) . '/components/action-buttons.php';
?>
```

## Helper `ActionButton`

```php
use App\Helpers\ActionButton;

echo ActionButton::link($url('orders'), 'Voltar', 'secondary', 'md');
echo ActionButton::classes('destructive', 'sm'); // "btn btn-destructive btn-sm"
```

## Tema claro / escuro

Todas as variantes usam variáveis CSS (`--text`, `--border`, `--danger`, etc.) definidas em `app.css` para `[data-theme="dark"]`.

## Telas já padronizadas (referência)

- CRUD listagens: clientes, produtos, serviços, categorias, usuários, empresas (index com `page-header` + `filter-panel` quando há filtros)
- Comercial: vendas (`orders/index`)
- Comercial: vendas (lista), dashboard (atalhos)
- Financeiro: painel, contas a receber, parcelamentos, fluxo
- Relatórios: export PDF/Excel, filtros Limpar
- Formulários principais com `form-footer`
- Modal de confirmação global

## Pendências

- Migrar telas secundárias (onboarding, LGPD, access-logs) se ainda usarem combinações antigas
- Adotar `filter` mode do partial nos painéis de filtro repetidos
- Avaliar `btn-lg` em CTAs de onboarding
