# System Performance and Consistency Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Eliminar los errores críticos actuales y reducir tiempo de respuesta, consumo de memoria y carga innecesaria de PostgreSQL en los flujos de ventas, clientes, productos y SUNAT.

**Architecture:** Primero se estabilizará el renderizado Blade/Livewire y se añadirán pruebas de regresión. Después se optimizarán consultas e índices usando mediciones PostgreSQL, y finalmente se extraerán integraciones lentas fuera de la petición HTTP mediante jobs idempotentes.

**Tech Stack:** PHP 8.3, Laravel 13, Livewire 4, Flux UI 2, PostgreSQL, Pest 4, Greenter, database queues.

---

## Production Database Safety Policy

Este proyecto ya tiene una base de datos en producción con información que debe preservarse. Todas las tareas de base de datos de este plan deben cumplir estas reglas:

- Nunca ejecutar `migrate:fresh`, `migrate:refresh`, `db:wipe`, `schema:drop`, `DROP TABLE`, `TRUNCATE` ni reseeders destructivos sobre producción.
- Nunca editar una migración que ya pudo ejecutarse en producción. Crear siempre una migración nueva, incremental y con timestamp posterior.
- Antes de cada despliegue con migraciones, obtener un backup verificable y probar su restauración en un entorno separado.
- Ejecutar primero `php artisan migrate:status` y `php artisan migrate --pretend` contra una copia reciente del esquema productivo.
- Probar migraciones con una copia anonimizada o restaurada de producción, no solamente con una base vacía.
- Preferir cambios aditivos compatibles hacia atrás: agregar columnas nullable, índices o tablas antes de cambiar código que dependa de ellos.
- Separar cambios grandes mediante estrategia expand-contract:
  1. Expandir esquema sin romper la versión actual.
  2. Desplegar código compatible con esquema viejo y nuevo.
  3. Migrar o completar datos en lotes pequeños y reanudables.
  4. Validar conteos, nulos, duplicados y reglas de negocio.
  5. Aplicar constraints o retirar columnas antiguas en un despliegue posterior.
- No agregar `UNIQUE`, `NOT NULL`, foreign keys o cambios de tipo sin auditar previamente los datos existentes.
- No usar una transacción extensa para backfills masivos. Procesar por lotes con checkpoint para evitar locks prolongados.
- Para índices grandes de PostgreSQL, preferir `CREATE INDEX CONCURRENTLY` fuera de transacción y verificar locks/espacio disponible.
- Todo cambio debe incluir estrategia de rollback que preserve datos. Si revertir el esquema implicaría pérdida, el rollback será de aplicación, no un `down()` destructivo.
- Durante el despliegue, monitorear locks, duración de consultas, errores, workers y crecimiento de tablas.

### Required Pre-Deployment Evidence

- Backup identificado por fecha/hora y restauración de prueba exitosa.
- Conteos por tabla crítica antes del cambio.
- Resultado de consultas de auditoría de duplicados, nulos y referencias huérfanas.
- Salida revisada de `migrate --pretend`.
- Duración estimada de cada migración usando una copia de volumen similar a producción.
- Plan de rollback de aplicación y responsable de decisión.

## Audit Findings

### Critical

- `resources/views/components/form/input.blade.php` contiene implementación de select y se invoca a sí mismo mediante `<x-form.input>`. Produce recursión de Blade y errores repetidos de memoria agotada con límite de 512 MB.
- `app/Livewire/Pages/Sale/CreateSaleDocumentPage.php` rechaza siempre una nota de crédito de factura porque valida el tipo afectado, pero no comprueba si realmente falta `clientId`.

### High Impact

- El listado de comprobantes carga `items`, aunque la tabla no los muestra. Esto aumenta consultas, memoria, serialización y tamaño de respuesta.
- Cada render del listado ejecuta la consulta paginada y cinco consultas agregadas independientes para el resumen.
- Las búsquedas principales usan `ILIKE '%texto%'` y concatenación de serie/correlativo sin índices PostgreSQL apropiados.
- Las tablas `sale_documents`, `sale_document_items`, `clients`, `products` y `series` carecen de varios índices compuestos alineados con sus consultas frecuentes.
- El envío a SUNAT ocurre síncronamente dentro de una acción Livewire. La petición puede quedar bloqueada por red, generación XML y procesamiento de respuesta.
- La generación PDF con `wkhtmltopdf` ocurre síncronamente en cada solicitud de PDF y vuelve a leer recursos desde disco.
- La lógica de envío a SUNAT está duplicada entre `SaleForm::send()` y `resources/views/components/⚡send-modal.blade.php`, con riesgo de comportamiento divergente.

### Medium Impact

- `SaleForm::summary()` repite el mismo conjunto de filtros cinco veces; puede consolidarse en una consulta agregada.
- `whereDate()` sobre `date_issue` puede impedir el uso eficiente de índices. Conviene usar límites datetime inclusivo/exclusivo.
- `delete()` y `restore()` en vouchers llaman nuevamente a `mount()`, mezclando inicialización con refresco de estado y causando trabajo adicional.
- Los componentes select serializan todas las opciones dentro de Alpine mediante `@js($options)` además de renderizarlas como HTML.
- La API de identidad tiene timeout de 15 segundos, pero no define `connectTimeout`, reintentos controlados ni cache de consultas exitosas.
- `CACHE_STORE=database` y `QUEUE_CONNECTION=database` agregan carga a PostgreSQL. Son válidos para una instalación pequeña, pero deben medirse antes de crecer.
- Hay tipos y firmas inconsistentes, por ejemplo precios como `?int` aunque se almacenan como decimal, métodos sin tipos de retorno y estilos no uniformes.
- La cobertura automática no protege adecuadamente renderizado de componentes, consultas del listado, concurrencia de correlativos, envío SUNAT ni generación PDF.

## Target Metrics

- Renderizar la página de creación sin error y con consumo máximo menor a 128 MB.
- Listado de comprobantes: máximo 4 consultas SQL por render normal y sin cargar `sale_document_items`.
- Resumen de ventas: una sola consulta agregada.
- Búsquedas de cliente/producto/documento: respuesta p95 menor a 300 ms con el volumen esperado.
- Envío SUNAT: acción HTTP menor a 500 ms al encolar; estado procesado por worker con reintentos e idempotencia.
- PDF: servir archivo cacheado cuando el comprobante no cambió.
- Cero fallos en pruebas enfocadas y suite completa.

### Task 1: Stabilize Blade Components

**Files:**
- Modify: `resources/views/components/form/input.blade.php`
- Review: `resources/views/components/form/select.blade.php`
- Create: `tests/Feature/View/FormComponentsTest.php`

- [ ] Crear una prueba Pest que renderice `<x-form.input>` y `<x-form.select>` y verifique que ambos terminan sin recursión.
- [ ] Ejecutar `php artisan test --compact tests/Feature/View/FormComponentsTest.php` y confirmar que falla por memoria o estructura incorrecta.
- [ ] Restaurar `input.blade.php` como input real siguiendo sus props y convenciones existentes, sin ninguna referencia a `<x-form.input>` dentro del propio archivo.
- [ ] Mantener en `select.blade.php` el input de búsqueda, ahora apuntando al componente input corregido.
- [ ] Ejecutar `php artisan view:clear` y `php artisan view:cache`.
- [ ] Ejecutar la prueba enfocada y confirmar consumo de memoria estable.
- [ ] Ejecutar `vendor/bin/pint --dirty --format agent`.

### Task 2: Fix Sale Flow Inconsistencies

**Files:**
- Modify: `app/Livewire/Pages/Sale/CreateSaleDocumentPage.php`
- Create or modify: `tests/Feature/Livewire/CreateSaleDocumentPageTest.php`

- [ ] Añadir una prueba que permita guardar una nota de crédito de factura cuando `clientId` existe.
- [ ] Añadir una prueba que rechace la misma operación cuando `clientId` falta.
- [ ] Cambiar la condición para exigir cliente únicamente cuando el documento afectado es factura y `clientId` está vacío.
- [ ] Añadir pruebas de creación, edición y duplicación para boleta, factura y nota de crédito.
- [ ] Ejecutar `php artisan test --compact tests/Feature/Livewire/CreateSaleDocumentPageTest.php`.

### Task 3: Establish Query Measurements

**Files:**
- Create: `tests/Feature/Performance/SaleVoucherQueryBudgetTest.php`
- Review: `app/Livewire/Forms/SaleForm.php`
- Review: `resources/views/pages/sale/⚡vouchers.blade.php`

- [ ] Crear factories o estados mínimos para empresas, clientes, comprobantes e items.
- [ ] Crear una prueba que use `DB::listen()` para contar consultas durante listado y resumen.
- [ ] Registrar como línea base el número de consultas, tiempo total SQL y memoria pico con 15 comprobantes y varios items.
- [ ] Añadir presupuestos de regresión: el listado no debe cargar items y listado más resumen no debe superar 4 consultas.
- [ ] Ejecutar `php artisan test --compact tests/Feature/Performance/SaleVoucherQueryBudgetTest.php`.

### Task 4: Optimize Voucher Listing And Summary

**Files:**
- Modify: `app/Livewire/Forms/SaleForm.php`
- Modify: `resources/views/pages/sale/⚡vouchers.blade.php`
- Modify: `tests/Feature/Performance/SaleVoucherQueryBudgetTest.php`

- [ ] Remover `items` del eager loading del listado y seleccionar solamente las columnas mostradas.
- [ ] Reemplazar las cinco consultas de `summary()` por una sola consulta con agregaciones condicionales.
- [ ] Reemplazar `whereDate()` por límites datetime: inicio inclusivo y día siguiente exclusivo.
- [ ] Evitar llamar `mount()` desde `delete()`, `restore()` y cierre de modal; refrescar solamente paginación/datos necesarios.
- [ ] Ejecutar la prueba de presupuesto de consultas y confirmar los objetivos.
- [ ] Ejecutar pruebas funcionales del listado, filtros, eliminación y restauración.

### Task 5: Add PostgreSQL Indexes Based On Real Queries

**Files:**
- Create: `database/migrations/<timestamp>_add_performance_indexes.php`
- Create: `tests/Feature/Database/PerformanceIndexesTest.php`

- [ ] Restaurar un backup reciente de producción en un entorno aislado y registrar conteos de las tablas críticas.
- [ ] Capturar `EXPLAIN (ANALYZE, BUFFERS)` de listado, resumen y búsquedas usando datos representativos.
- [ ] Verificar permisos, locks esperados y espacio disponible antes de habilitar `pg_trgm` o crear índices.
- [ ] Habilitar `pg_trgm` de forma idempotente solamente si el entorno productivo lo permite.
- [ ] Añadir índices compuestos para filtros frecuentes de `sale_documents`, empezando por `(company_id, sunat_state, date_issue)` y `(company_id, doc_sunat_type, status, date_issue)`.
- [ ] Auditar duplicados de serie/correlativo por empresa y tipo documental antes de considerar un índice único.
- [ ] Corregir duplicados mediante una decisión explícita de negocio; nunca eliminarlos automáticamente.
- [ ] Añadir índice único de negocio solamente cuando la auditoría confirme que los datos existentes cumplen la regla.
- [ ] Añadir índices para claves foráneas consultadas, incluyendo `sale_document_items.sale_document_id`.
- [ ] Añadir índices trigram para búsqueda por nombres, documentos, SKU y número de comprobante solamente después de validar el plan de ejecución.
- [ ] Añadir índice compuesto de series alineado con `getSerieForUpdate()`.
- [ ] Crear índices grandes con `CREATE INDEX CONCURRENTLY` en migraciones separadas y sin transacción cuando el volumen productivo lo requiera.
- [ ] Ejecutar `php artisan migrate --pretend` y revisar que no exista SQL destructivo.
- [ ] Ejecutar migración sobre la copia de producción y comparar locks, duración y `EXPLAIN ANALYZE` antes/después.
- [ ] Documentar rollback de aplicación y eliminación concurrente de índices solo si fuera necesaria.

### Task 6: Reduce Livewire Payload And Render Work

**Files:**
- Modify: `resources/views/components/form/select.blade.php`
- Modify: `resources/views/livewire/pages/sale/create-sale-document-page.blade.php`
- Modify: `app/Livewire/Pages/Sale/CreateSaleDocumentPage.php`
- Modify: `resources/views/pages/sale/⚡vouchers.blade.php`
- Create: `tests/Feature/Livewire/LivewirePayloadTest.php`

- [ ] Medir tamaño de snapshot/respuesta Livewire en creación y listado.
- [ ] Evitar duplicar opciones grandes tanto en `@js($options)` como en HTML; mantener búsqueda backend para colecciones dinámicas.
- [ ] Aplicar `wire:model.live` solamente donde una petición inmediata sea necesaria.
- [ ] Mantener objetos y relaciones Eloquent fuera de propiedades públicas; almacenar arreglos mínimos.
- [ ] Añadir límites de tamaño de respuesta a pruebas de regresión.

### Task 7: Centralize And Queue SUNAT Sending

**Files:**
- Create: `app/Actions/Sales/SendSaleDocumentToSunat.php`
- Create: `app/Jobs/SendSaleDocumentToSunat.php`
- Modify: `app/Livewire/Forms/SaleForm.php`
- Modify: `resources/views/components/⚡send-modal.blade.php`
- Modify: `config/queue.php`
- Create: `tests/Feature/Jobs/SendSaleDocumentToSunatTest.php`

- [ ] Extraer validación, hidratación, envío y persistencia SUNAT a una única acción.
- [ ] Añadir una prueba que demuestre que ambos puntos de entrada producen el mismo resultado.
- [ ] Crear job idempotente con bloqueo por comprobante, reintentos, backoff y timeout explícito.
- [ ] Configurar ejecución `after_commit` para evitar procesar comprobantes no confirmados.
- [ ] Cambiar Livewire para encolar y mostrar estado pendiente, sin esperar la red SUNAT.
- [ ] Probar éxito, rechazo, timeout, reintento y doble clic.
- [ ] Ejecutar `php artisan queue:work --once --queue=default` únicamente como verificación controlada en desarrollo o staging.
- [ ] Verificar que el job de prueba se procesa y que el worker termina después de ese único job.

### Task 8: Cache Generated PDFs

**Files:**
- Create: `app/Actions/Sales/GenerateSaleDocumentPdf.php`
- Modify: `app/Http/Controllers/InvoiceController.php`
- Modify: `app/Services/SunatService.php`
- Create: `tests/Feature/SaleDocumentPdfTest.php`

- [ ] Añadir prueba que confirme que dos solicitudes sin cambios generan PDF una sola vez.
- [ ] Generar y guardar PDF por comprobante/hash en storage privado.
- [ ] Invalidar PDF al editar el comprobante.
- [ ] Servir el archivo cacheado con headers correctos.
- [ ] Mantener generación bajo demanda como fallback controlado.

### Task 9: Harden External Identity Calls

**Files:**
- Modify: `app/Services/IdentityDiurvanService.php`
- Modify: `app/Livewire/Forms/ClientForm.php`
- Create: `tests/Feature/Services/IdentityDiurvanServiceTest.php`

- [ ] Añadir pruebas con `Http::fake()` para éxito, error, timeout y respuesta inválida.
- [ ] Añadir `connectTimeout`, reintentos limitados solo para fallos transitorios y mensajes uniformes.
- [ ] Cachear consultas exitosas por documento durante un periodo corto.
- [ ] Evitar repetir consultas mientras una acción Livewire ya está ejecutándose.

### Task 10: Normalize Types And Database Constraints

**Files:**
- Modify: `app/Livewire/Forms/ProductForm.php`
- Modify: `app/Livewire/Forms/ClientForm.php`
- Modify: relevant models and migrations
- Create or modify: focused Pest tests

- [ ] Cambiar precio de producto a un tipo compatible con decimal y definir validación de escala.
- [ ] Añadir tipos de retorno a métodos públicos sin tipar.
- [ ] Auditar valores actuales, precisión, nulos y formatos antes de cualquier cambio de tipo.
- [ ] Hacer cambios de tipo mediante columna nueva nullable, backfill por lotes, validación y cambio de lectura/escritura; no alterar directamente una columna poblada si existe riesgo de lock o truncamiento.
- [ ] Validar unicidad de documentos de cliente y SKU según las reglas reales del negocio antes de crear restricciones.
- [ ] Generar reportes de duplicados para revisión humana; no borrar ni fusionar registros automáticamente.
- [ ] Añadir restricciones e índices únicamente después de confirmar que todos los datos existentes cumplen.
- [ ] Aplicar `NOT NULL` o constraints en un despliegue posterior al backfill y a la validación.
- [ ] Ejecutar pruebas enfocadas y `vendor/bin/pint --dirty --format agent`.

### Task 11: Production Runtime Configuration

**Files:**
- Modify only when deploying: environment configuration
- Review: `composer.json`
- Review: worker/process manager configuration outside the repository

- [ ] Mantener `.env` local actual para desarrollo; definir configuración separada para producción.
- [ ] En producción usar `APP_ENV=production`, `APP_DEBUG=false` y HTTPS.
- [ ] Medir PostgreSQL con cache/queue database; migrar cache y colas a Redis si generan contención o crecimiento significativo.
- [ ] No depender de `composer run dev`, `queue:listen`, una terminal abierta ni acciones manuales del cliente para procesar colas.
- [ ] Usar PM2 como supervisor multiplataforma del worker Laravel en Windows y macOS.
- [ ] Crear `ecosystem.config.cjs` para ejecutar `artisan queue:work database --queue=default --sleep=3 --tries=3 --timeout=120 --max-time=3600`.
- [ ] Configurar en PM2 `interpreter: 'none'`, reinicio automático, backoff exponencial, timestamps, rotación de logs y límite de memoria.
- [ ] Hacer configurable la ruta del proyecto mediante `APP_PATH` y el ejecutable PHP mediante `PHP_BINARY`; no incluir rutas específicas de un equipo dentro del repositorio.
- [ ] En Windows con Laravel Herd, usar la ruta absoluta del PHP administrado por Herd y registrar el arranque de PM2 mediante el Programador de tareas o un servicio de Windows que ejecute `pm2 resurrect`.
- [ ] En macOS, ejecutar `pm2 startup` para registrar PM2 mediante `launchd` y ejecutar `pm2 save` para persistir la lista de procesos.
- [ ] Si el worker de macOS debe funcionar antes de iniciar sesión, registrar PM2 o el worker directamente como daemon de sistema de `launchd`.
- [ ] Ejecutar PM2 con una cuenta que tenga acceso mínimo al proyecto, logs, certificados, `wkhtmltopdf` y red hacia PostgreSQL/SUNAT.
- [ ] Guardar la configuración con `pm2 save` y comprobar que `pm2 resurrect` recupera el worker.
- [ ] Añadir al procedimiento de despliegue `php artisan queue:restart` después de publicar código nuevo; el administrador del servicio debe iniciar nuevamente el worker.
- [ ] Verificar después de reiniciar Windows que PM2 inicia y procesa un job sin abrir Herd ni iniciar una terminal.
- [ ] Verificar después de reiniciar macOS que PM2 inicia y procesa un job sin abrir una terminal.
- [ ] Mantener WinSW o NSSM como alternativa en Windows cuando no se quiera instalar Node.js/PM2 en el equipo del cliente.
- [ ] Mantener una tarea programada de monitoreo que alerte sobre `failed_jobs` o jobs antiguos pendientes.
- [ ] Ejecutar en despliegue `composer install --no-dev --optimize-autoloader`.
- [ ] Ejecutar `php artisan config:cache`, `php artisan route:cache` y `php artisan view:cache`.
- [ ] Configurar rotación de logs, monitoreo de jobs fallidos y tiempos p95.

### Task 12: Final Verification

**Files:**
- No application changes expected

- [ ] Ejecutar `composer validate`.
- [ ] Ejecutar `vendor/bin/pint --dirty --format agent`.
- [ ] Ejecutar `php artisan test --compact`.
- [ ] Ejecutar `composer audit`.
- [ ] Ejecutar `php artisan migrate:status`.
- [ ] Restaurar el backup productivo en staging y ejecutar todas las migraciones pendientes sobre esa copia.
- [ ] Comparar conteos, sumas financieras, relaciones huérfanas y duplicados antes/después de migrar.
- [ ] Confirmar que ninguna migración pendiente requiere reset, truncado o pérdida de datos.
- [ ] Ejecutar `php artisan schedule:list` y `php artisan queue:failed`.
- [ ] Repetir mediciones de consultas, memoria, payload Livewire, búsqueda, envío SUNAT y PDF.
- [ ] Comparar resultados contra Target Metrics y bloquear despliegue si no se cumplen los objetivos críticos.

## Recommended Execution Order

1. Tasks 1-2: estabilización y corrección funcional.
2. Tasks 3-5: medición, consultas e índices.
3. Tasks 6-9: reducción de payload y trabajo lento fuera de la petición.
4. Tasks 10-12: consistencia, configuración y verificación final.
