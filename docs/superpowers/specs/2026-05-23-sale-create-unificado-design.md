# Unificar creación de Boleta/Factura/Nota de crédito en un solo componente

Fecha: 2026-05-23

## Contexto

Actualmente existen 3 páginas Livewire (single-file) con lógica duplicada:

- `resources/views/pages/sale/⚡create-boleta.blade.php`
- `resources/views/pages/sale/⚡create-factura.blade.php`
- `resources/views/pages/sale/⚡create-nota-credito.blade.php`

Cada archivo define `new class extends Component` y repite gran parte de la lógica (items, totales, cliente, preview PDF, guardar, reset, etc.), con diferencias puntuales por tipo de comprobante.

Hay tests Pest que ejercitan estas páginas:

- `tests/Feature/SaleCreateTest.php` (boleta)
- `tests/Feature/SaleFacturaCreateTest.php` (factura)
- `tests/Feature/SaleCreditNoteCreateTest.php` (nota crédito)
- `tests/Feature/SalePageTest.php` (render/flows)

## Objetivo

Tener **un solo componente Livewire** reutilizado por:

- Boleta (`docSunatType=03`)
- Factura (`docSunatType=01`)
- Nota de crédito (`docSunatType=07`)

Manteniendo:

- Las mismas rutas y nombres: `create-boleta`, `create-factura`, `create-nota-credito`.
- El mismo comportamiento (UI y flujos) que hoy.
- Los mismos eventos Livewire usados por modales (items/cliente/pdf).

## No objetivos

- Rediseñar UI/UX o cambiar textos.
- Cambiar el modelo de datos o servicios (p.ej. `SaleForm`, `SaleService`, `SerieService`).
- Unificar “vouchers” u otras páginas.

## Enfoque recomendado

### 1) Crear un componente de página único (clase + vista)

Crear un componente Livewire clásico en `app/Livewire/Pages/Sale/` (nombre a definir en implementación), por ejemplo:

- Clase: `App\Livewire\Pages\Sale\CreateSaleDocumentPage`
- Vista: `resources/views/livewire/pages/sale/create-sale-document-page.blade.php`

Este componente tendrá un “prop”/param de entrada:

- `docSunatType` (`'03'|'01'|'07'`)

Se inicializa vía `mount(string $docSunatType)`.

### 2) Apuntar las 3 rutas al mismo componente

Modificar `routes/web.php` para que:

- `/boleta` -> mismo componente con `docSunatType=03`
- `/factura` -> mismo componente con `docSunatType=01`
- `/nota-credito` -> mismo componente con `docSunatType=07`

Nota técnica: Livewire Page Components no inyecta automáticamente `->defaults()` en parámetros de `mount()`. Para que `docSunatType` llegue a `mount()`, las rutas se definirán con un parámetro opcional:

- `/boleta/{docSunatType?}` (default `03`)
- `/factura/{docSunatType?}` (default `01`)
- `/nota-credito/{docSunatType?}` (default `07`)

Esto mantiene exactamente las mismas URLs cuando no se provee el parámetro.

## Diseño del componente único

### Estado compartido (siempre)

- `public SaleForm $sale`
- `public SaleItemForm $saleItem`
- `public DiscountForm $discount`
- `public ClientForm $client`
- `public array $items = []`
- `public array $clients = []`
- `public ?string $selectedClientLabel = null`
- `public bool $pdfPreviewOpen = false`
- `public ?string $pdfPreviewUrl = null`
- `public ?string $savedSaleId = null`
- `public ?string $editingSaleId = null`

### Estado solo para Nota de crédito (docSunatType=07)

- `public array $affectedDocTypeOptions = []`
- `public array $reasonOptions = []`
- `public array $affectedDocuments = []`
- `public ?string $selectedAffectedLabel = null`

### Estado solo para Boleta (docSunatType=03)

Actualmente boleta maneja un toggle `bolClient` (`hide/show`). En el componente unificado se mantendrá, pero **solo se renderiza y aplica** cuando `docSunatType=03`.

### Métodos compartidos

Unificar (sin cambiar comportamiento):

- `mount(SaleService $saleService, string $docSunatType)`
- `loadSaleIntoForm(...)` (con validación/redirección según tipo)
- `editItem(...)`, `deletedItem(...)`
- Manejo de descuentos globales (si aplica en las 3)
- `searchClient(...)` (variará según tipo)
- `selectClient(...)`, `clearClient()`
- `openPdfPreview(...)`, `closePdfPreview(...)`
- `save(SerieService $serieService)` (variará validación previa por tipo)
- `resetForm()`
- `goToVouchers()`
- Handlers `#[On(...)]`:
  - `sale-item-created`
  - `sale-item-updated`
  - `created-client`
  - `pdf-modal-closed`

### Métodos solo Nota de crédito

Mantener:

- `updatedSaleAffectedDocSunatType()`
- `searchAffectedDocument()`
- `selectAffectedDocument()`
- `clearAffectedDocument()`

## Reglas por tipo (docSunatType)

### Boleta (03)

- `sale.docSunatType = 03` en `mount()`/`resetForm()`.
- Soporta “duplicate/edit” por querystring como hoy.
- Si el sale cargado no es boleta, redirige a la ruta correcta.
- `bolClient` se mantiene.
- `searchClient` usa `ClientForm::search`.
- `save()` solo exige `companyId` (como hoy).

### Factura (01)

- `sale.docSunatType = 01` en `mount()`/`resetForm()`.
- `searchClient` usa `ClientForm::searchWithoutDni`.
- `save()` exige `companyId` y `clientId` (como hoy).

### Nota de crédito (07)

- `sale.docSunatType = 07` en `mount()`/`resetForm()`.
- Inicializa:
  - `affectedDocTypeOptions` (boleta/factura)
  - `reasonOptions` (`CreditNoteReasonType::options()`)
- Soporta `?affected=...` (prefill desde voucher aprobado) como hoy.
- Mantiene búsqueda y selección de documento afectado y validaciones actuales.
- `searchClient` depende de `affectedDocSunatType` (factura -> `searchWithoutDni`, boleta -> `search`).
- En testing se re-lanza excepción en `save()` (comportamiento actual de nota crédito).

## Plan de migración

1. Crear componente único (clase + vista) y mover/adaptar lógica desde los 3 archivos actuales.
2. Actualizar `routes/web.php` para que las 3 rutas apunten a ese componente con `{docSunatType?}` + defaults.
3. Dejar los 3 archivos `⚡create-*` sin uso (o eliminarlos si no quedan referencias internas).
4. Actualizar tests para apuntar al nuevo componente y/o rutas; garantizar que:
   - Crear boleta/factura/nota crédito sigue pasando.
   - Render de páginas sigue pasando.
5. Ejecutar `vendor/bin/pint --dirty --format agent` y correr los tests afectados.

## Estrategia de pruebas

Actualizar los tests existentes para que:

- Boleta: `Livewire::test(<nuevo-componente>, ['docSunatType' => '03'])` o vía GET route.
- Factura: idem con `'01'`.
- Nota crédito: idem con `'07'`.

Se mantiene la misma verificación de base de datos y flujos existentes.

## Rollback

Si aparece un bug, se puede revertir el cambio de rutas y volver a apuntar a:

- `pages::sale.create-boleta`
- `pages::sale.create-factura`
- `pages::sale.create-nota-credito`

y restaurar los tests a su estado anterior.

