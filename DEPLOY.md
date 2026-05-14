# Despliegue de Ziresa Studio en app.ziresa.mx

## Raiz esperada

Sube el contenido de esta carpeta como document root de `app.ziresa.mx`:

`Ziresa app/`

La app requiere PHP en el servidor porque los endpoints viven en `api/*.php`.

## Configuracion importante

- Actualiza `data/settings.json` y reemplaza `business_whatsapp` con el numero real en formato internacional, por ejemplo `5281XXXXXXXX`.
- En cPanel, apunta el subdominio `app.ziresa.mx` a esta carpeta o a la carpeta remota donde se suba este contenido.
- Verifica que Apache permita `.htaccess`; protege `core/` y `data/`.
- Si usas GitHub Actions, configura estos secrets:
  - `FTP_SERVER`
  - `FTP_USERNAME`
  - `FTP_PASSWORD`
  - `FTP_SERVER_DIR`

## Prueba rapida despues de subir

1. Abre `https://app.ziresa.mx`.
2. Entra a reserva y verifica que carguen servicios.
3. Crea una cita de prueba.
4. Entra como admin con el usuario configurado.
5. Revisa dashboard, calendario y clientas.
