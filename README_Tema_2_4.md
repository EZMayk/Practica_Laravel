# Práctica individual 2.4 Seguridad en la integración del restaurante

**Fecha de la práctica:** viernes 20 de noviembre de 2026, 23:00 (America/Guayaquil). **Estudiante:** Maykel Josue Menéndez Vélez. **Proyecto:** API Laravel para registro de direcciones y resumen de entrega.

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

El primer comando debe mostrar `.env` y el segundo no debe mostrar nada. No incluyas el contenido de `.env`, contraseñas ni tokens en capturas. En producción configura `APP_DEBUG=false`, usa HTTPS para Postman/cliente → Laravel y TLS para Laravel → servicios de cobertura y estimación. Las URL locales `http://127.0.0.1` de este laboratorio **no cifran** el tráfico; solo deben usarse en localhost. Configura las URL HTTPS en variables de entorno al desplegar y verifica los certificados del servidor. No desactives la verificación TLS del cliente HTTP.

## Preparación local para Postman

1. En la raíz del proyecto, ejecuta `php artisan migrate --no-interaction`. Las direcciones registradas antes de esta práctica tienen `user_id` vacío; **registra una dirección nueva con el token de Cliente A** para estas pruebas.
2. Abre tres terminales en la raíz del proyecto y mantenlas abiertas durante P1:

   ```powershell
   # Terminal 1: cobertura
   $env:SERVICIO_TIPO='cobertura'; php -S 127.0.0.1:9001 servicios/router.php
   ```

   ```powershell
   # Terminal 2: estimación
   $env:SERVICIO_TIPO='estimacion'; php -S 127.0.0.1:9002 servicios/router.php
   ```

   ```powershell
   # Terminal 3: API Laravel
   php artisan serve --host=127.0.0.1 --port=8000 --no-interaction
   ```

   Las terminales 1 y 2 son necesarias para que el GET autorizado devuelva 200. P2 y P3 devuelven 401 y 403 antes de llamar a esos servicios.
3. Crea dos usuarios ficticios y sus tokens temporales en una consola local con `php artisan tinker`. Pega cada línea por separado:

   ```php
   use App\Models\User;
   use Illuminate\Support\Str;
   $clienteA = User::firstOrCreate(['email' => 'cliente.a@example.test'], ['name' => 'Cliente A de prueba', 'password' => Str::random(40)]);
   $clienteB = User::firstOrCreate(['email' => 'cliente.b@example.test'], ['name' => 'Cliente B de prueba', 'password' => Str::random(40)]);
   $clienteA->createToken('practica-2-4-a', ['*'], now()->addHour())->plainTextToken;
   $clienteB->createToken('practica-2-4-b', ['*'], now()->addHour())->plainTextToken;
   ```

   Copia cada token **solo a una variable local de Postman** (`tokenA` y `tokenB`). No guardes esos valores en el repositorio ni exportes el entorno de Postman con ellos. Cierra la consola de Tinker cuando termines. Los correos `example.test` y los nombres son ficticios; las contraseñas se generan aleatoriamente y no se muestran.
4. En Postman, usa `Accept: application/json`. En la pestaña **Authorization** selecciona **Bearer Token** y escribe `{{tokenA}}` o `{{tokenB}}` según el caso. **Antes de fotografiar Postman, verifica que el valor del token no esté visible** en Authorization, Headers, Console ni en la barra lateral.

## Pruebas y evidencias P1 a P4

Ejecuta los casos en este orden. Conserva en cada captura el método, la URL, el código HTTP y el JSON de respuesta. No fotografíes la consola que imprimió los tokens.

| Caso | Acción en Postman | Resultado que se debe capturar |
| --- | --- | --- |
| **P1, autorizado** | Con `{{tokenA}}`, envía `POST http://127.0.0.1:8000/api/v1/clientes/direcciones` con el JSON de abajo. Copia `data.id`. Luego consulta el GET del resumen para ese mismo ID con `{{tokenA}}` y ambos servicios activos. | POST **201** y GET **200** con `data.direccion.id`, `data.cobertura` y `data.estimacion`. Una captura de cada respuesta si el espacio lo permite. |
| **P2, sin autenticación** | En el mismo GET, selecciona **No Auth** y elimina cualquier header `Authorization` agregado manualmente. Pulsa Send. | **401 Unauthorized** y mensaje JSON; ninguna información de la dirección. |
| **P3, sin permiso** | En ese mismo GET y con el mismo ID de la dirección de A, cambia a Bearer `{{tokenB}}`. Pulsa Send. | **403 Forbidden**; ninguna información de la dirección. |
| **P4, secretos** | En VS Code o una terminal, muestra `.gitignore` y la salida de los dos comandos de comprobación anteriores. | `.env` está excluido y `git ls-files .env` no devuelve ninguna ruta. No abras el contenido de `.env`. |

JSON ficticio para el POST de P1:

```json
{"etiqueta":"Casa de prueba","direccion":"Calle Ficticia 123","lat":-0.9580285,"lng":-80.6384797}
```

**P3 se prueba con la dirección creada por A, no con una dirección de B.** El cliente B sí puede registrar sus propias direcciones, pero no leer el resumen de A. Si el GET de P1 devuelve 502, comprueba que cobertura y estimación están activos; ese resultado corresponde a la práctica 3.3, no a una falla de autenticación.

## Resultados verificados en el código

Las pruebas automatizadas usan SQLite en memoria y respuestas HTTP simuladas, sin guardar tokens o contraseñas reales en el repositorio. `tests/Feature/SeguridadDireccionEntregaTest.php` comprueba 401 sin token, 201 con usuario autenticado, 403 para otra cuenta, bloqueo de registros antiguos sin propietario y 200 con un Bearer real de prueba. El test de propiedad comprueba la política por separado. Los tests existentes de registro, normalización y orquestación se actualizaron para autenticarse.

```powershell
php artisan test --compact
```

Resultado local: **35 pruebas y 182 aserciones correctas**. Las capturas P1–P3 de Postman quedan pendientes de ejecutar y pegar por el estudiante; esta cifra de pruebas automatizadas no sustituye esas evidencias manuales.

## Reflexión individual

**Problema resuelto:** un consumidor anónimo o un cliente diferente podía acceder al flujo de direcciones y resúmenes de entrega. **Decisión técnica:** reutilizar Sanctum instalado y una política de propiedad vinculada a `user_id`, sin introducir dependencias. **Verificación:** solicitudes 2xx, 401 y 403 en pruebas automatizadas y el procedimiento P1–P4 en Postman; la revisión de `.gitignore` confirma que `.env` no se publica.
