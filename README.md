# Ziresa Studio

App web/PWA de Ziresa Studio en modo Nomadas: HTML, CSS, JavaScript, PHP y JSON.

## Abrir local

Si solo revisas pantalla, abre:

`index.html`

Para probar APIs PHP, usa un servidor PHP:

```powershell
cd "C:\Users\Cielito Regio\Desktop\app ziresa\Ziresa app"
php -S 127.0.0.1:8765 -t .
```

Luego abre:

`http://127.0.0.1:8765`

## Despliegue

Sube el contenido de esta carpeta al document root de `app.ziresa.mx`.

Consulta `DEPLOY.md` para los pasos de cPanel/FTP.

## Arquitectura

Consulta `ARCHITECTURE.md`.

## Seguridad

Consulta `SECURITY.md`.

- `data/*.json` son datos productivos/locales y no deben versionarse.
- Usa `data/*.example.json` como plantilla de despliegue.
- Las acciones `POST` usan CSRF por `assets/security.js`.
- La creacion de citas valida disponibilidad y escritura bajo lock.

## Flujo operativo actual

- Publico agenda desde `index.html`.
- Admin opera citas desde `admin.html`.
- Staff opera citas desde `manicurista.html`.
- Completar una cita actualiza lealtad, wallet e ingresos.
