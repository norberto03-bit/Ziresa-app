# Ziresa Studio App - Modo Nomadas

Esta es la arquitectura oficial de Ziresa Studio para esta etapa.

## Stack

- HTML
- CSS
- JavaScript vanilla
- PHP
- JSON en `data/*.json`
- PWA basica con `manifest.webmanifest` y `service-worker.js`

No usa Supabase, Firebase, MySQL, WordPress ni Next.js en esta version.

## Raiz funcional

La raiz web es esta carpeta:

`Ziresa app/`

El archivo principal es:

`index.html`

## Pantallas activas

- `index.html`: sitio publico, servicios, promociones, ubicacion, login y agenda.
- `clientes.html`: hub de clienta, tarjeta frecuente, wallet descargable e info util.
- `admin.html`: panel operativo, calendario, clientas, finanzas, lealtad y retencion.
- `manicurista.html`: hub de staff para agenda del dia.
- `cliente.html`: alias/redirect de compatibilidad hacia `clientes.html`.

## Backend

- `api/`: endpoints PHP.
- `core/`: helpers compartidos de auth, citas, JSON y lealtad.
- `data/`: persistencia JSON.

Todos los endpoints deben usar `core/json-db.php` para leer/escribir JSON. No escribir directo con `file_put_contents` fuera de `db_write()`.

## Datos principales

- `data/services.json`: servicios, precios, duracion y puntos.
- `data/appointments.json`: citas.
- `data/clients.json`: clientas, visitas, puntos y wallet.
- `data/manicurists.json`: staff.
- `data/finances.json`: ingresos, egresos y propinas.
- `data/settings.json`: marca, horarios, WhatsApp, URLs y contenido publico.
- `data/admins.json`: accesos admin.

## Flujo de reserva

1. La clienta abre `index.html`.
2. Selecciona uno o mas servicios.
3. La app calcula duracion total.
4. Consulta horarios por `api/calendar/get.php`.
5. `core/appointments.php` valida disponibilidad real.
6. La cita se crea en `api/appointments/create_public.php`.
7. La cita queda `pendiente_confirmacion_wa`.
8. Admin confirma, cancela o gestiona desde `admin.html`.
9. Staff consulta su agenda en `manicurista.html`.
10. Al completar/pagar, se actualizan visitas, puntos y finanzas.

## Reglas criticas

- El calendario es el cerebro del sistema.
- Ninguna cita debe saltarse `is_bookable_slot()`.
- Las finanzas solo deben contar pagos/movimientos registrados.
- La tarjeta frecuente se deriva de visitas y puntos reales en `clients.json`.
- `core/` y `data/` no deben exponerse publicamente.

## Backups JSON

`db_write()` crea backups automaticos en:

`data/_backups/YYYY-MM/<archivo>/`

La variable opcional `ZIRESA_BACKUP_MAX_PER_FILE` controla cuantos backups conservar por archivo. Default: 25.

## Carpeta descartada

La prueba `ziresa-pwa` de Next/Supabase fue retirada para evitar confusion. Esta version oficial vuelve al modo Nomadas.
