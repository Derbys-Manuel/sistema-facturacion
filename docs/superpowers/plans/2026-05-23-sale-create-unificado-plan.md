# Unificar creación de Boleta/Factura/Nota de crédito Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Reemplazar las 3 páginas de creación (boleta/factura/nota crédito) por un solo componente Livewire reutilizable, manteniendo rutas y comportamiento.

**Architecture:** Un único componente de clase `CreateSaleDocumentPage` determina el modo por `docSunatType` (param en `mount()` o derivado del nombre de ruta). Las tres rutas existentes apuntan a la misma clase; la vista Blade renderiza condicionalmente la sección lateral según el tipo.

**Tech Stack:** Laravel 13, Livewire 4, Pest 4, Flux UI, Blade.

---

## Files (responsabilidad)

- Create: `app/Livewire/Pages/Sale/CreateSaleDocumentPage.php` (lógica unificada, branching por `docSunatType`)
- Create: `resources/views/livewire/pages/sale/create-sale-document-page.blade.php` (UI unificada con `@if` por tipo)
- Modify: `routes/web.php` (apuntar rutas existentes a la clase)
- Modify: `tests/Feature/SaleCreateTest.php` (boleta -> componente unificado)
- Modify: `tests/Feature/SaleFacturaCreateTest.php` (factura -> componente unificado)
- Modify: `tests/Feature/SaleCreditNoteCreateTest.php` (nota crédito -> componente unificado)
- Delete: `resources/views/pages/sale/⚡create-boleta.blade.php` (evitar duplicación)
- Delete: `resources/views/pages/sale/⚡create-factura.blade.php`
- Delete: `resources/views/pages/sale/⚡create-nota-credito.blade.php`

---

### Task 1: Crear componente unificado

**Files:**
- Create: `app/Livewire/Pages/Sale/CreateSaleDocumentPage.php`
- Create: `resources/views/livewire/pages/sale/create-sale-document-page.blade.php`

- [ ] **Step 1: Crear la clase del componente**

Run: `C:\laragon\bin\php\php-8.4.4-Win32-vs17-x64\php.exe artisan make:livewire Pages/Sale/CreateSaleDocumentPage --type=class --no-interaction`

Expected: genera clase y vista en `resources/views/livewire/...`.

- [ ] **Step 2: Mover y unificar la lógica**

Implementar en `app/Livewire/Pages/Sale/CreateSaleDocumentPage.php`:

- `mount(SaleService $saleService, ?string $docSunatType = null): void`
- Branching por tipo:
  - Boleta (`03`) usa `bolClient` y `ClientForm::search`
  - Factura (`01`) usa `ClientForm::searchWithoutDni`
  - Nota crédito (`07`) habilita `affected*` + `noteReason*` + búsqueda/selección de documento afectado
- Mantener eventos:
  - `#[On('sale-item-created')]`
  - `#[On('sale-item-updated')]`
  - `#[On('created-client')]`
  - `#[On('pdf-modal-closed')]`

- [ ] **Step 3: Unificar la vista con condicionales**

En `resources/views/livewire/pages/sale/create-sale-document-page.blade.php`:

- Agregar variables locales (`$isBoleta/$isFactura/$isCreditNote`) basadas en `sale.docSunatType`.
- Renderizar el aside según el tipo.
- Ajustar `x-sale.pdf-preview-modal` para usar `:new-label` dinámico y `new-action="startNewDocument"`.

---

### Task 2: Reapuntar rutas existentes

**Files:**
- Modify: `routes/web.php`

- [ ] **Step 1: Apuntar boleta/factura/nota crédito al mismo componente**

Cambiar:
- `Route::livewire('/boleta', 'pages::sale.create-boleta')`
- `Route::livewire('/factura', 'pages::sale.create-factura')`
- `Route::livewire('/nota-credito', 'pages::sale.create-nota-credito')`

por:
- `Route::livewire('/boleta', CreateSaleDocumentPage::class)`
- `Route::livewire('/factura', CreateSaleDocumentPage::class)`
- `Route::livewire('/nota-credito', CreateSaleDocumentPage::class)`

---

### Task 3: Actualizar tests Pest

**Files:**
- Modify: `tests/Feature/SaleCreateTest.php`
- Modify: `tests/Feature/SaleFacturaCreateTest.php`
- Modify: `tests/Feature/SaleCreditNoteCreateTest.php`

- [ ] **Step 1: Boleta**

Reemplazar `Livewire::test('pages::sale.create-boleta')` por:

```php
Livewire::test(CreateSaleDocumentPage::class, ['docSunatType' => DocSunatType::BOLETA->value])
```

- [ ] **Step 2: Factura**

```php
Livewire::test(CreateSaleDocumentPage::class, ['docSunatType' => DocSunatType::FACTURA->value])
```

- [ ] **Step 3: Nota de crédito**

```php
Livewire::test(CreateSaleDocumentPage::class, ['docSunatType' => DocSunatType::NOTA_CREDITO->value])
```

También actualizar `Livewire::withQueryParams(...)->test(...)` de la misma forma.

- [ ] **Step 4: Ejecutar tests afectados**

Run:
`C:\laragon\bin\php\php-8.4.4-Win32-vs17-x64\php.exe artisan test --compact tests/Feature/SaleCreateTest.php tests/Feature/SaleFacturaCreateTest.php tests/Feature/SaleCreditNoteCreateTest.php tests/Feature/SalePageTest.php`

Expected: PASS.

---

### Task 4: Eliminar páginas duplicadas

**Files:**
- Delete: `resources/views/pages/sale/⚡create-boleta.blade.php`
- Delete: `resources/views/pages/sale/⚡create-factura.blade.php`
- Delete: `resources/views/pages/sale/⚡create-nota-credito.blade.php`

- [ ] **Step 1: Borrar archivos para evitar divergencia futura**

---

### Task 5: Formato (Pint)

**Files:**
- Format: `app/Livewire/Pages/Sale/CreateSaleDocumentPage.php`
- Format: tests/rutas modificados

- [ ] **Step 1: Ejecutar Pint en cambios**

Run: `C:\laragon\bin\php\php-8.4.4-Win32-vs17-x64\php.exe vendor/bin/pint --dirty --format agent`

Expected: `passed` o `fixed`.

