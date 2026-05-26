# Máscaras de formulário (input)

Módulo: `public/assets/js/input-masks.js` (carregado em `layouts/main.php` e `layouts/auth.php`).

Inicialização automática via `app.js` → `MiniErp.masks.init()` no `DOMContentLoaded`.

## Atributos `data-mask-*`

| Atributo | Formato | Exemplo de uso |
|----------|---------|----------------|
| `data-mask-phone` | `(00) 00000-0000` | `customers/form.php` — telefone |
| `data-mask-document` | CPF ou CNPJ | `companies/form.php`, `onboarding/company.php` — `tax_id` |
| `data-mask-cep` | `00000-000` | Campos de CEP (quando existirem no cadastro) |
| `data-mask-money` | `1.234,56` (entrada em centavos) | Preços, custos, valores financeiros |

## API JavaScript

```javascript
MiniErp.masks.parseMoney("1.234,56");   // → 1234.56
MiniErp.masks.formatMoneyBr(19.9);      // → "19,90"
MiniErp.masks.maskMoney(inputElement);  // reaplica máscara
MiniErp.masks.init();                   // vincula todos os [data-mask-*] da página
```

O backend já normaliza com `App\Helpers\Money::normalizeDecimal()`.

## Telas com moeda

- `products/form.php` — `cost_price`, `price`
- `services/form.php` — `cost_price`, `price`
- `finance/accounts-receivable/receive.php` — `amount`, PIX
