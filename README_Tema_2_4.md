# Práctica individual 2.4 Seguridad en la integración del restaurante

## Problema y solución implementada

El proyecto permitía registrar direcciones y consultar resúmenes de entrega sin identificar al cliente. La práctica aplica autenticación con tokens Bearer de Laravel Sanctum a esas rutas y autoriza la consulta del resumen únicamente al propietario de la dirección. Cada dirección nueva guarda internamente `user_id` del usuario autenticado; ese campo no se acepta desde el JSON del cliente ni se devuelve en el recurso. Una dirección antigua sin propietario permanece inaccesible hasta que se asigne de forma verificada a su dueño.

`POST /api/productos` y `POST /api/categorias` también requieren autenticación. Son validaciones de ejemplo que aún no persisten productos o categorías. Las consultas públicas del catálogo (`GET /api/productos`, `GET /api/categorias` y sus rutas de detalle) siguen disponibles sin token.

## Matriz de acceso

| Endpoint | Método | Sin token | Cliente A autenticado | Cliente B autenticado |
| --- | --- | --- | --- | --- |
| `/api/v1/clientes/direcciones` | POST | 401 | 201, dirección de A | 201, dirección de B |
| `/api/v1/clientes/direcciones/checkout` | POST | 401 | 201, dirección de A | 201, dirección de B |
| `/api/v1/clientes/direcciones/{id}/resumen-entrega`, dirección de A | GET | 401 | 200 | 403 |
| `/api/productos` y `/api/categorias` | POST | 401 | Acceso autenticado; validación del cuerpo | Acceso autenticado; validación del cuerpo |
| `/api/productos` y `/api/categorias` | GET | Público | Público | Público |

El 401 significa que falta autenticación o el token no es válido. El 403 significa que la cuenta fue autenticada, pero no es dueña de la dirección solicitada. La comprobación se hace antes de consultar los servicios de cobertura y estimación. La política `app/Policies/DireccionEntregaPolicy.php` implementa la regla de propiedad.

## Configuración y credenciales

Sanctum ya estaba instalado (`laravel/sanctum` 4.3.3). `app/Models/User.php` usa `HasApiTokens` y las rutas seleccionadas tienen `auth:sanctum`. Los tokens se almacenan como hashes en `personal_access_tokens`. La configuración `SANCTUM_TOKEN_EXPIRATION` limita su duración a 60 minutos por defecto; un token de prueba puede tener además una caducidad individual de una hora.

Variables relevantes: `APP_KEY`, `APP_DEBUG`, `DB_CONNECTION`, `SANCTUM_TOKEN_EXPIRATION`, `SERVICIO_COBERTURA_URL` y `SERVICIO_ESTIMACION_URL`. Los nombres y valores locales no confidenciales se muestran en `.env.example`; las claves y tokens reales deben permanecer en `.env` o en el almacén de secretos del entorno de despliegue. `.gitignore` excluye `.env`. Para comprobarlo sin mostrar su contenido:

```powershell
git check-ignore .env
git ls-files .env
```

El primer comando debe mostrar `.env` y el segundo no debe mostrar nada. No publiques el contenido de `.env`, contraseñas ni tokens. Configura `APP_DEBUG=false` también al verificar respuestas de error para que Laravel no exponga trazas. En producción usa HTTPS entre cliente y Laravel y TLS entre Laravel y los servicios de cobertura y estimación. Las URL locales `http://127.0.0.1` de este laboratorio solo deben usarse en localhost. Configura las URL HTTPS en variables de entorno al desplegar y verifica los certificados del servidor.

## Reproducir la implementación

1. Desde la raíz del repositorio, prepara PHP 8.3 con `pdo_sqlite`, Composer y una base SQLite local:

   ```powershell
   composer install
   if (-not (Test-Path .env)) { Copy-Item .env.example .env; php artisan key:generate --no-interaction }
   if (-not (Test-Path database/database.sqlite)) { New-Item database/database.sqlite -ItemType File | Out-Null }
   php artisan config:clear --no-interaction
   php artisan migrate --no-interaction
   ```

   En `.env`, utiliza `DB_CONNECTION=sqlite`, deja `DB_DATABASE` sin definir y configura `APP_DEBUG=false` para probar también los errores. Si `.env` ya existía y `APP_KEY` está vacío, ejecuta `php artisan key:generate --no-interaction`. Las direcciones registradas antes de esta práctica tienen `user_id` vacío; se debe crear una dirección nueva para verificar la propiedad.
2. Abre tres terminales en la raíz del proyecto y mantenlas activas durante el caso autorizado:

   ```powershell
   # Terminal 1: cobertura
   Remove-Item Env:SERVICIO_MODO -ErrorAction SilentlyContinue
   $env:SERVICIO_TIPO='cobertura'; php -S 127.0.0.1:9001 servicios/router.php
   ```

   ```powershell
   # Terminal 2: estimación
   Remove-Item Env:SERVICIO_MODO -ErrorAction SilentlyContinue
   $env:SERVICIO_TIPO='estimacion'; php -S 127.0.0.1:9002 servicios/router.php
   ```

   ```powershell
   # Terminal 3: API Laravel
   php artisan serve --host=127.0.0.1 --port=8000 --no-interaction
   ```

   Cobertura y estimación son necesarias para que el GET autorizado devuelva 200. Las respuestas 401 y 403 ocurren antes de llamarlos.
3. Crea dos usuarios ficticios y tokens temporales en una cuarta terminal con `php artisan tinker`. Ejecuta cada línea por separado:

   ```php
   use App\Models\User;
   use Illuminate\Support\Str;
   $clienteA = User::firstOrCreate(['email' => 'cliente.a@example.test'], ['name' => 'Cliente A de prueba', 'password' => Str::random(40)]);
   $clienteB = User::firstOrCreate(['email' => 'cliente.b@example.test'], ['name' => 'Cliente B de prueba', 'password' => Str::random(40)]);
   $clienteA->tokens()->where('name', 'practica-2-4-a')->delete();
   $clienteB->tokens()->where('name', 'practica-2-4-b')->delete();
   $clienteA->createToken('practica-2-4-a', ['*'], now()->addHour())->plainTextToken;
   $clienteB->createToken('practica-2-4-b', ['*'], now()->addHour())->plainTextToken;
   ```

   Conserva los valores devueltos solo en el entorno local de Postman. Crea las variables `tokenA` y `tokenB` con esa escritura exacta: Postman distingue mayúsculas de minúsculas. Pega cada token completo, incluido el prefijo numérico y `|`, sin comillas ni la palabra `Bearer`. No agregues headers personalizados llamados `tokenA` o `tokenB`, no exportes esos valores y no los incluyas en el repositorio. Cierra Tinker al terminar. Los correos `example.test` y los nombres son ficticios; las contraseñas se generan aleatoriamente.
4. En Postman selecciona el entorno local, agrega `Accept: application/json` y usa **Authorization → Bearer Token** con `{{tokenA}}` o `{{tokenB}}` según el caso. Postman construye la cabecera `Authorization`; elimina cualquier cabecera `Authorization` agregada manualmente. Los tokens caducan en una hora: si una solicitud posterior devuelve 401, genera otros con el paso 3.

## Verificación manual de acceso

Ejecuta los casos en este orden y compara el código HTTP y las claves JSON. Usa siempre el mismo ID de dirección creado por el Cliente A.

| Caso | Solicitud y comprobación | Resultado esperado |
| --- | --- | --- |
| **P1, autorizado** | Con `{{tokenA}}`, envía `POST http://127.0.0.1:8000/api/v1/clientes/direcciones` con el JSON de abajo. Lee el número de `data.id` y envía `GET http://127.0.0.1:8000/api/v1/clientes/direcciones/{id}/resumen-entrega`, sustituyendo `{id}` por ese número, con `{{tokenA}}` y ambos servicios activos. | POST **201** con `data.id`; GET **200** con el mismo `data.direccion.id`, `data.cobertura` y `data.estimacion`. |
| **P2, sin autenticación** | Repite el mismo GET con **No Auth** y sin cabecera `Authorization` manual. | **401 Unauthorized**, `message: "Unauthenticated."`, sin datos de la dirección. |
| **P3, sin permiso** | Repite el mismo GET y el mismo ID con Bearer `{{tokenB}}`. | **403 Forbidden** y `message: "This action is unauthorized."`, sin datos de la dirección. |
| **P4, secretos** | Ejecuta `git check-ignore .env` y `git ls-files .env`; revisa la regla `.env` en `.gitignore` sin abrir el archivo `.env`. | El primer comando muestra `.env`; el segundo no devuelve rutas. |

JSON ficticio para el POST de P1:

```json
{"etiqueta":"Casa de prueba","direccion":"Calle Ficticia 123","lat":-0.9580285,"lng":-80.6384797}
```

**P3 usa la dirección creada por A**, aunque B también pueda registrar direcciones propias. Si el GET de P1 devuelve 502, comprueba que cobertura y estimación siguen activos en modo normal; ese resultado significa que falló una dependencia del resumen, no la autenticación. Si P3 devuelve una traza, comprueba `APP_DEBUG=false` y reinicia Laravel.

## Resultados verificados en el código

Las pruebas automatizadas usan SQLite en memoria y respuestas HTTP simuladas, sin guardar tokens o contraseñas reales en el repositorio. `tests/Feature/SeguridadDireccionEntregaTest.php` comprueba 401 sin token, 201 con usuario autenticado, 403 para otra cuenta, bloqueo de registros antiguos sin propietario y 200 con un Bearer real de prueba. El test de propiedad comprueba la política por separado. Los tests existentes de registro, normalización y orquestación se actualizaron para autenticarse.

```powershell
php artisan test --compact
```

Resultado de referencia: **35 pruebas y 182 aserciones correctas**. La suite automatizada verifica el comportamiento de la aplicación; los casos manuales anteriores permiten repetirlo contra los servicios locales.

## Reflexión individual

**Problema resuelto:** un consumidor anónimo o un cliente diferente podía acceder al flujo de direcciones y resúmenes de entrega. **Decisión técnica:** reutilizar Sanctum instalado y una política de propiedad vinculada a `user_id`, sin introducir dependencias. **Verificación:** solicitudes 2xx, 401 y 403 en pruebas automatizadas y manuales; la revisión de `.gitignore` confirma que `.env` no se publica.
