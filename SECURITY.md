# Seguridad operativa Ziresa App

Stack actual: HTML, CSS, JavaScript vanilla, PHP y JSON local. No requiere MySQL, Supabase, Firebase, Next.js ni frameworks.

## Datos JSON

Los archivos `data/*.json` son productivos/locales y no deben subirse al repositorio.
Usa `data/*.example.json` como plantilla para nuevos despliegues.

Si los JSON productivos ya estaban trackeados por Git, limpiar el indice una sola vez:

```bash
git rm --cached "Ziresa app/data/*.json"
git add .gitignore "Ziresa app/data/*.example.json" "Ziresa app/data/.htaccess"
git commit -m "Harden local JSON data handling"
```

No borres los JSON del servidor si son la base activa de operacion; solo quitalos del control de versiones.

## CSRF

`api/auth/session.php` emite `csrf_token`.
Las paginas usan `assets/security.js` y `apiFetch()` para enviar `X-CSRF-Token` en acciones `POST`.

Endpoints protegidos:

- `api/auth/login.php`
- `api/auth/logout.php`
- `api/appointments/create_public.php`
- `api/appointments/create.php`
- `api/appointments/update_status.php`
- `api/calendar/save_day.php`
- `api/clients/update_loyalty.php`
- `api/finances/add.php`
- `api/loyalty/process.php`

## Reservas atomicas

La creacion de citas valida disponibilidad y escribe la cita bajo lock de JSON. Esto evita doble reserva cuando dos clientas intentan tomar el mismo horario al mismo tiempo.
