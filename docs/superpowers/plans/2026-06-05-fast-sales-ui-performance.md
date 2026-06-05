# Fast Sales UI Performance Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Hacer que las pantallas de comprobantes y creacion de ventas se sientan mas rapidas reduciendo consultas, payload Livewire y busquedas lentas.

**Architecture:** La primera fase optimiza el camino mas visible: `vouchers`, selects backend de cliente/producto/documento y el formulario de venta. Se mantienen las mismas pantallas y contratos Livewire, pero se separan datos pesados de la tabla, se agregan indices PostgreSQL seguros y se reduce hidratacion innecesaria.

**Tech Stack:** PHP 8.3, Laravel 13, Livewire 4, Flux UI 2, PostgreSQL, Pest 4, Laravel Pint.

---

## Scope

Este plan implementa solo mejoras de rendimiento de bajo riesgo y alto impacto:

- Listado de comprobantes mas liviano.
- Busquedas backend preparadas para volumen.
- Menos requests Livewire en inputs no criticos.
- Menos trabajo de hidratacion en modales pesados.
- Pruebas enfocadas para bloquear regresiones.

Queda fuera de este plan: colas SUNAT, Redis, cache avanzado de PDF, Octane, cambios de infraestructura y refactors grandes de modelos.

## Files

- Modify: `app/Livewire/Forms/SaleForm.php`
  - Reducir columnas del listado.
  - Extraer resumen a una sola consulta agregada.
  - Centralizar busqueda por comprobante.
- Modify: `resources/views/pages/sale/⚡vouchers.blade.php`
  - Dejar la tabla con payload minimo.
  - Cargar detalle CDR bajo demanda cuando el usuario lo pida.
- Modify: `app/Livewire/Pages/Sale/CreateSaleDocumentPage.php`
  - Controlar montaje/carga de modales.
- Modify: `resources/views/livewire/pages/sale/create-sale-document-page.blade.php`
  - Usar `wire:model.defer` o `wire:model.blur` en inputs no criticos.
  - Renderizar modales pesados solo cuando se necesiten.
- Modify: `app/Livewire/Forms/ClientForm.php`
  - Seleccionar solo columnas necesarias.
  - Normalizar busqueda y preparar uso de indices.
- Modify: `app/Livewire/Forms/ProductForm.php`
  - Seleccionar precio y columnas necesarias para evitar consultas posteriores.
- Create: `database/migrations/2026_06_05_000001_add_trigram_search_indexes.php`
  - Agregar extension `pg_trgm` e indices concurrentes de busqueda.
- Create: `tests/Feature/Sale/SaleVoucherPerformanceTest.php`
  - Proteger presupuesto de consultas y payload de listado.
- Create: `tests/Feature/Sale/SaleSearchPerformanceTest.php`
  - Proteger resultados de busqueda sin depender de UI.
- Create: `tests/Feature/Livewire/CreateSaleDocumentPayloadTest.php`
  - Proteger que inputs no criticos no disparen requests innecesarios.

## Database Safety

- No editar migraciones existentes.
- No usar `migrate:fresh`, `migrate:refresh`, `db:wipe`, `TRUNCATE` ni resets.
- Crear indices PostgreSQL con `CREATE INDEX CONCURRENTLY` y `public $withinTransaction = false`.
- Ejecutar `php artisan migrate --pretend` antes de migrar.
- Si el entorno no es PostgreSQL, la migracion debe salir sin romper tests SQLite.

### Task 1: Add Voucher Query Budget Test

**Files:**
- Create: `tests/Feature/Sale/SaleVoucherPerformanceTest.php`
- Read: `app/Livewire/Forms/SaleForm.php`

- [ ] **Step 1: Write the failing query-budget test**

Create `tests/Feature/Sale/SaleVoucherPerformanceTest.php`:

```php
<?php

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\Sunat\DocIdentityType;
use App\Enums\Sunat\DocSunatType;
use App\Enums\Sunat\OperationType;
use App\Enums\Sunat\PaymentForm;
use App\Livewire\Forms\SaleForm;
use App\Models\Client;
use App\Models\Company;
use App\Models\SaleDocument;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

function makeSalePerformanceForm(): SaleForm
{
    return new SaleForm(
        new class extends Component
        {
            public function render(): string
            {
                return '';
            }
        },
        'sale',
    );
}

function createPerformanceCompany(): Company
{
    return Company::query()->create([
        'company_name' => 'Eunoia Demo',
        'ruc' => '20123456789',
        'sol_user' => 'MODDATOS',
        'sol_pass' => 'MODDATOS',
        'department' => 'LIMA',
        'province' => 'LIMA',
        'district' => 'LIMA',
    ]);
}

function createPerformanceClient(string $number): Client
{
    return Client::query()->create([
        'name' => 'Cliente '.$number,
        'trade_name' => null,
        'doc_identity_type' => DocIdentityType::DNI->value,
        'document_number' => $number,
        'is_active' => true,
    ]);
}

function createPerformanceSale(Company $company, Client $client, int $index): SaleDocument
{
    return SaleDocument::query()->create([
        'document_type' => DocumentType::SALE->value,
        'ubl_version' => '2.1',
        'doc_sunat_type' => $index % 2 === 0 ? DocSunatType::BOLETA->value : DocSunatType::FACTURA->value,
        'operation_type' => OperationType::INTERNAL_SALE->value,
        'payment_form' => PaymentForm::CASH->value,
        'currency' => 'PEN',
        'serie' => $index % 2 === 0 ? 'B001' : 'F001',
        'correlative' => str_pad((string) $index, 8, '0', STR_PAD_LEFT),
        'total_taxed' => 100,
        'total_exempted' => 0,
        'total_unaffected' => 0,
        'total_export' => 0,
        'total_free' => 0,
        'total_igv' => 18,
        'total_igv_free' => 0,
        'icbper' => 0,
        'total_taxes' => 18,
        'sale_value' => 100,
        'sub_total' => 118,
        'total_sale' => 118,
        'rounding' => 0,
        'total' => 118,
        'sunat_state' => true,
        'cdr' => ['success' => true, 'cdrResponse' => ['code' => '0', 'description' => 'Aceptado']],
        'date_issue' => now('America/Lima')->subMinutes($index),
        'date_expiration' => now('America/Lima'),
        'status' => DocumentStatus::DRAFT->value,
        'company_id' => $company->id,
        'client_id' => $client->id,
    ]);
}

test('sale voucher list and summary stay within query budget', function () {
    $company = createPerformanceCompany();

    foreach (range(1, 20) as $index) {
        createPerformanceSale($company, createPerformanceClient('700000'.str_pad((string) $index, 2, '0', STR_PAD_LEFT)), $index);
    }

    $queries = [];
    DB::listen(function ($query) use (&$queries): void {
        $queries[] = $query->sql;
    });

    $form = makeSalePerformanceForm();
    $documents = $form->list(companyId: (string) $company->id);
    $summary = $form->summary(companyId: (string) $company->id);

    expect($documents['data'])->toHaveCount(15);
    expect($summary['total'])->toBeGreaterThan(0);
    expect($queries)->toHaveCount()->toBeLessThanOrEqual(4);
    expect(collect($documents['data'])->pluck('cdr')->filter())->toBeEmpty();
});
```

- [ ] **Step 2: Run the focused test and record the failure**

Run:

```bash
php artisan test --compact tests/Feature/Sale/SaleVoucherPerformanceTest.php
```

Expected before implementation:

```text
FAIL  Tests\Feature\Sale\SaleVoucherPerformanceTest
```

The failure should show either too many queries or `cdr` present in the list payload.

### Task 2: Make Voucher List Payload Lean

**Files:**
- Modify: `app/Livewire/Forms/SaleForm.php`
- Modify: `resources/views/pages/sale/⚡vouchers.blade.php`
- Test: `tests/Feature/Sale/SaleVoucherPerformanceTest.php`

- [ ] **Step 1: Remove `cdr` from the base list select**

In `app/Livewire/Forms/SaleForm.php`, change `list()` so the select block does not include `cdr`:

```php
->select([
    'id',
    'date_issue',
    'serie',
    'correlative',
    'affected_sale_document_id',
    'affected_serie',
    'affected_correlative',
    'client_id',
    'doc_sunat_type',
    'total',
    'status',
    'sunat_state',
])
->with('client:id,name,trade_name,document_number')
->latest('date_issue')
->paginate(15)
->toArray();
```

- [ ] **Step 2: Add a focused CDR lookup method**

In `app/Livewire/Forms/SaleForm.php`, add this method after `list()`:

```php
public function cdrDetail(string $saleId): array
{
    $sale = SaleDocument::query()
        ->select(['id', 'cdr'])
        ->findOrFail($saleId);

    return is_array($sale->cdr) ? $sale->cdr : [];
}
```

- [ ] **Step 3: Store selected CDR in the vouchers component**

In `resources/views/pages/sale/⚡vouchers.blade.php`, add public state near `$sendSaleId`:

```php
public ?string $cdrSaleId = null;

public array $cdrDetail = [];
```

Add this method near `previewPdf()`:

```php
public function loadCdrDetail(string $saleId): void
{
    $this->cdrSaleId = $saleId;
    $this->cdrDetail = $this->sale->cdrDetail($saleId);
}
```

- [ ] **Step 4: Change the status tooltip trigger**

In the status cell, replace the eager `$cdr = data_get($row, 'cdr')` use with lazy state:

```blade
@php($isCurrentCdr = $cdrSaleId === ($row['id'] ?? null))
@php($cdr = $isCurrentCdr ? $cdrDetail : [])
```

Add `wire:mouseenter` to the status button:

```blade
<button
    type="button"
    class="inline-flex cursor-pointer"
    wire:mouseenter="loadCdrDetail('{{ $row['id'] }}')"
>
```

- [ ] **Step 5: Keep delete visibility behavior without full CDR**

Replace delete visibility condition with status-only logic:

```blade
@if(
    (
        $row['sunatState'] === null ||
        $row['sunatState'] === true
    )
    && in_array($row['status'] ?? null, [DocumentStatus::DRAFT->value, DocumentStatus::REJECTED->value], true)
)
```

- [ ] **Step 6: Run the test**

Run:

```bash
php artisan test --compact tests/Feature/Sale/SaleVoucherPerformanceTest.php
```

Expected:

```text
PASS  Tests\Feature\Sale\SaleVoucherPerformanceTest
```

### Task 3: Consolidate Summary Query

**Files:**
- Modify: `app/Livewire/Forms/SaleForm.php`
- Test: `tests/Feature/Sale/SaleVoucherPerformanceTest.php`

- [ ] **Step 1: Ensure `summary()` uses one aggregate query**

Keep `summary()` in `app/Livewire/Forms/SaleForm.php` as a single conditional aggregate:

```php
$summary = $query
    ->selectRaw(
        <<<'SQL'
            coalesce(sum(case when doc_sunat_type = ? then total else 0 end), 0) as boletas,
            coalesce(sum(case when doc_sunat_type = ? then total else 0 end), 0) as facturas,
            coalesce(sum(case when doc_sunat_type = ? then -total_igv else total_igv end), 0) as signed_total_igv,
            coalesce(sum(case when doc_sunat_type = ? then -sale_value else sale_value end), 0) as signed_sale_value,
            coalesce(sum(case when doc_sunat_type = ? then -total else total end), 0) as signed_total
        SQL,
        [
            DocSunatType::BOLETA->value,
            DocSunatType::FACTURA->value,
            DocSunatType::NOTA_CREDITO->value,
            DocSunatType::NOTA_CREDITO->value,
            DocSunatType::NOTA_CREDITO->value,
        ],
    )
    ->first();
```

- [ ] **Step 2: Keep datetime range index-friendly**

Verify `documentsQuery()` uses datetime bounds:

```php
->when($from, fn ($query) => $query->where('date_issue', '>=', Carbon::parse($from)->startOfDay()))
->when($to, fn ($query) => $query->where('date_issue', '<', Carbon::parse($to)->addDay()->startOfDay()))
```

- [ ] **Step 3: Run query-budget test**

Run:

```bash
php artisan test --compact tests/Feature/Sale/SaleVoucherPerformanceTest.php
```

Expected:

```text
PASS  Tests\Feature\Sale\SaleVoucherPerformanceTest
```

### Task 4: Add Search Behavior Tests

**Files:**
- Create: `tests/Feature/Sale/SaleSearchPerformanceTest.php`
- Modify: `app/Livewire/Forms/ClientForm.php`
- Modify: `app/Livewire/Forms/ProductForm.php`

- [ ] **Step 1: Write client and product search tests**

Create `tests/Feature/Sale/SaleSearchPerformanceTest.php`:

```php
<?php

use App\Enums\Sunat\DocIdentityType;
use App\Livewire\Forms\ClientForm;
use App\Livewire\Forms\ProductForm;
use App\Models\Client;
use App\Models\Product;
use Livewire\Component;

function makeClientPerformanceForm(): ClientForm
{
    return new ClientForm(
        new class extends Component
        {
            public function render(): string
            {
                return '';
            }
        },
        'client',
    );
}

function makeProductPerformanceForm(): ProductForm
{
    return new ProductForm(
        new class extends Component
        {
            public function render(): string
            {
                return '';
            }
        },
        'product',
    );
}

test('client search returns only option payload fields', function () {
    Client::query()->create([
        'name' => 'Juan Perez',
        'trade_name' => null,
        'doc_identity_type' => DocIdentityType::DNI->value,
        'document_number' => '71234567',
        'is_active' => true,
    ]);

    $form = makeClientPerformanceForm();
    $results = $form->search('7123');

    expect($results)->toHaveCount(1);
    expect(array_keys($results[0]))->toBe(['value', 'label']);
    expect($results[0]['label'])->toContain('71234567');
});

test('product search returns id label and price-ready source columns', function () {
    Product::query()->create([
        'name' => 'Servicio mensual',
        'unit' => 'NIU',
        'sku' => 'SERV-001',
        'price' => 120,
        'is_active' => true,
    ]);

    $form = makeProductPerformanceForm();
    $results = $form->search('SERV');

    expect($results)->toHaveCount(1);
    expect(array_keys($results[0]))->toBe(['value', 'label']);
    expect($results[0]['label'])->toContain('Servicio mensual');
});
```

- [ ] **Step 2: Run tests before code changes**

Run:

```bash
php artisan test --compact tests/Feature/Sale/SaleSearchPerformanceTest.php
```

Expected:

```text
PASS or FAIL depending current payload
```

If it already passes, keep it as regression coverage.

- [ ] **Step 3: Select only required client columns**

In `app/Livewire/Forms/ClientForm.php`, update both `search()` and `searchWithoutDni()` queries:

```php
return Client::query()
    ->select(['id', 'name', 'trade_name', 'document_number'])
    ->when(
        filled($q),
        fn ($query) => $query->where(fn ($subQuery) => $subQuery
            ->where('name', 'ilike', "%{$q}%")
            ->orWhere('trade_name', 'ilike', "%{$q}%")
            ->orWhere('document_number', 'ilike', "%{$q}%")
        )
    )
    ->limit(20)
    ->get()
    ->map(fn ($client) => [
        'value' => (string) $client->id,
        'label' => ($client->name ?: $client->trade_name).' - '.$client->document_number,
    ])
    ->toArray();
```

For `searchWithoutDni()`, keep this line before `when()`:

```php
->where('doc_identity_type', DocIdentityType::RUC->value)
```

- [ ] **Step 4: Select product columns explicitly**

In `app/Livewire/Forms/ProductForm.php`, use:

```php
return Product::query()
    ->select(['id', 'name', 'unit', 'sku'])
    ->when($q, fn ($query) => $query->where(fn ($subQuery) => $subQuery
        ->where('name', 'ilike', "%{$q}%")
        ->orWhere('unit', 'ilike', "%{$q}%")
        ->orWhere('sku', 'ilike', "%{$q}%")
    ))
    ->limit(20)
    ->get()
    ->map(fn ($p) => [
        'value' => (string) $p->id,
        'label' => $p->name.' '.$p->unit.' '.$p->sku,
    ])
    ->toArray();
```

- [ ] **Step 5: Run search tests**

Run:

```bash
php artisan test --compact tests/Feature/Sale/SaleSearchPerformanceTest.php
```

Expected:

```text
PASS  Tests\Feature\Sale\SaleSearchPerformanceTest
```

### Task 5: Add PostgreSQL Trigram Indexes

**Files:**
- Create: `database/migrations/2026_06_05_000001_add_trigram_search_indexes.php`
- Test manually: `php artisan migrate --pretend`

- [ ] **Step 1: Create the migration**

Run:

```bash
php artisan make:migration add_trigram_search_indexes --no-interaction
```

Rename the generated file to:

```text
database/migrations/2026_06_05_000001_add_trigram_search_indexes.php
```

- [ ] **Step 2: Replace migration content**

Use this migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        $this->createIndex('clients_name_trgm_idx', 'clients USING gin (name gin_trgm_ops)');
        $this->createIndex('clients_trade_name_trgm_idx', 'clients USING gin (trade_name gin_trgm_ops)');
        $this->createIndex('clients_document_number_trgm_idx', 'clients USING gin (document_number gin_trgm_ops)');
        $this->createIndex('products_name_trgm_idx', 'products USING gin (name gin_trgm_ops)');
        $this->createIndex('products_sku_trgm_idx', 'products USING gin (sku gin_trgm_ops)');
        $this->createIndex('companies_company_name_trgm_idx', 'companies USING gin (company_name gin_trgm_ops)');
        $this->createIndex('companies_ruc_trgm_idx', 'companies USING gin (ruc gin_trgm_ops)');
        $this->createIndex('sale_documents_number_trgm_idx', "sale_documents USING gin ((serie || '-' || correlative) gin_trgm_ops)");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ([
            'sale_documents_number_trgm_idx',
            'companies_ruc_trgm_idx',
            'companies_company_name_trgm_idx',
            'products_sku_trgm_idx',
            'products_name_trgm_idx',
            'clients_document_number_trgm_idx',
            'clients_trade_name_trgm_idx',
            'clients_name_trgm_idx',
        ] as $index) {
            DB::statement("DROP INDEX CONCURRENTLY IF EXISTS {$index}");
        }
    }

    private function createIndex(string $name, string $definition): void
    {
        DB::statement("CREATE INDEX CONCURRENTLY IF NOT EXISTS {$name} ON {$definition}");
    }
};
```

- [ ] **Step 3: Review SQL without applying**

Run:

```bash
php artisan migrate --pretend
```

Expected:

```text
CREATE EXTENSION IF NOT EXISTS pg_trgm
CREATE INDEX CONCURRENTLY IF NOT EXISTS ...
```

There must be no `DROP TABLE`, `TRUNCATE`, destructive alter, or data rewrite.

- [ ] **Step 4: Run focused tests**

Run:

```bash
php artisan test --compact tests/Feature/Sale/SaleSearchPerformanceTest.php
```

Expected:

```text
PASS  Tests\Feature\Sale\SaleSearchPerformanceTest
```

### Task 6: Reduce Create Sale Livewire Requests

**Files:**
- Modify: `resources/views/livewire/pages/sale/create-sale-document-page.blade.php`
- Modify: `app/Livewire/Pages/Sale/CreateSaleDocumentPage.php`
- Create: `tests/Feature/Livewire/CreateSaleDocumentPayloadTest.php`

- [ ] **Step 1: Add modal state to the component**

In `app/Livewire/Pages/Sale/CreateSaleDocumentPage.php`, add public flags:

```php
public bool $clientModalLoaded = false;

public bool $saleItemModalLoaded = false;
```

Add methods:

```php
public function openClientModal(): void
{
    $this->clientModalLoaded = true;
    Flux::modal('client-create')->show();
}

public function openSaleItemModal(): void
{
    $this->saleItemModalLoaded = true;
    Flux::modal('sale-item')->show();
}
```

- [ ] **Step 2: Replace modal triggers**

In `resources/views/livewire/pages/sale/create-sale-document-page.blade.php`, replace each client modal trigger:

```blade
<flux:modal.trigger name="client-create">
    <x-form.button
        variant="success"
        size="icon"
        type="button"
        leftIcon="plus"
    />
</flux:modal.trigger>
```

with:

```blade
<x-form.button
    variant="success"
    size="icon"
    type="button"
    leftIcon="plus"
    wire:click="openClientModal"
/>
```

Replace the sale item trigger:

```blade
<flux:modal.trigger name="sale-item">
```

with:

```blade
<x-form.button
    variant="success"
    type="button"
    leftIcon="plus"
    size="sm"
    wire:click="openSaleItemModal"
>
    Agregar
</x-form.button>
```

- [ ] **Step 3: Render heavy Livewire children only after first use**

Wrap modal children:

```blade
<flux:modal
    name="client-create"
    class="max-w-lg bg-gray-100"
    scroll="body"
    :dismissible="false"
    :closable="false"
>
    @if ($clientModalLoaded)
        <livewire:client.create />
    @endif
</flux:modal>
```

```blade
<flux:modal
    name="sale-item"
    class="max-w-lg bg-gray-100"
    scroll="body"
    :dismissible="false"
    :closable="false"
>
    @if ($saleItemModalLoaded)
        <livewire:sale.modal-item />
    @endif
</flux:modal>
```

- [ ] **Step 4: Change non-critical bindings**

In `resources/views/livewire/pages/sale/create-sale-document-page.blade.php`, change:

```blade
wire:model="sale.additionalInfo"
```

to:

```blade
wire:model.defer="sale.additionalInfo"
```

Change non-credit-note date issue binding:

```blade
wire:model="sale.dateIssue"
```

to:

```blade
wire:model.defer="sale.dateIssue"
```

Do not change fields that currently depend on immediate UI behavior, such as `sale.affectedDocSunatType`.

- [ ] **Step 5: Add render regression test**

Create `tests/Feature/Livewire/CreateSaleDocumentPayloadTest.php`:

```php
<?php

use App\Livewire\Pages\Sale\CreateSaleDocumentPage;
use Livewire\Livewire;

test('create sale page does not mount heavy modals before first use', function () {
    Livewire::test(CreateSaleDocumentPage::class)
        ->assertSet('clientModalLoaded', false)
        ->assertSet('saleItemModalLoaded', false)
        ->assertDontSeeLivewire('client.create')
        ->assertDontSeeLivewire('sale.modal-item');
});

test('create sale page mounts sale item modal after opening it', function () {
    Livewire::test(CreateSaleDocumentPage::class)
        ->call('openSaleItemModal')
        ->assertSet('saleItemModalLoaded', true)
        ->assertSeeLivewire('sale.modal-item');
});
```

- [ ] **Step 6: Run focused Livewire test**

Run:

```bash
php artisan test --compact tests/Feature/Livewire/CreateSaleDocumentPayloadTest.php
```

Expected:

```text
PASS  Tests\Feature\Livewire\CreateSaleDocumentPayloadTest
```

### Task 7: Formatting And Verification

**Files:**
- Modified files from Tasks 1-6.

- [ ] **Step 1: Run Pint on dirty PHP files**

Run:

```bash
vendor/bin/pint --dirty --format agent
```

Expected:

```text
PASS
```

- [ ] **Step 2: Run focused tests**

Run:

```bash
php artisan test --compact tests/Feature/Sale/SaleVoucherPerformanceTest.php
php artisan test --compact tests/Feature/Sale/SaleSearchPerformanceTest.php
php artisan test --compact tests/Feature/Livewire/CreateSaleDocumentPayloadTest.php
```

Expected:

```text
PASS
```

- [ ] **Step 3: Run migration preview**

Run:

```bash
php artisan migrate --pretend
```

Expected:

```text
Only additive CREATE EXTENSION / CREATE INDEX statements for the new performance migration.
```

- [ ] **Step 4: Run route and view cache smoke checks**

Run:

```bash
php artisan route:cache
php artisan view:cache
php artisan optimize:clear
```

Expected:

```text
Routes cached successfully.
Blade templates cached successfully.
Caches cleared successfully.
```

- [ ] **Step 5: Check git diff**

Run:

```bash
git diff --stat
git diff -- tests/Feature/Sale/SaleVoucherPerformanceTest.php tests/Feature/Sale/SaleSearchPerformanceTest.php tests/Feature/Livewire/CreateSaleDocumentPayloadTest.php
```

Expected:

```text
Only the planned performance files changed.
```

## Self-Review

- Spec coverage: The plan covers voucher list payload, summary query, backend searches, PostgreSQL search indexes, Livewire modal hydration, non-critical bindings and verification.
- Placeholder scan: No `TBD`, generic "handle edge cases", or undocumented implementation steps remain.
- Type consistency: Methods added are `cdrDetail()`, `loadCdrDetail()`, `openClientModal()` and `openSaleItemModal()` and the same names are used in Blade and tests.

## Execution Notes

- The active working tree already had a modified `resources/views/livewire/pages/sale/create-sale-document-page.blade.php` before this plan. Read it before applying Task 6 and preserve user changes.
- The existing broad plan `docs/superpowers/plans/2026-06-04-system-performance-and-consistency-audit.md` remains useful for later SUNAT/PDF/runtime work, but this plan is the recommended first slice.
