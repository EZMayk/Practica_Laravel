# Práctica individual de contrato JSON y dirección de entrega


## Proceso y alcance

**Proceso:** registrar una dirección de entrega para pedidos a domicilio. **Actores:** cliente web o móvil y API Laravel. **Endpoint:** `POST /api/v1/clientes/direcciones`.

Es una adaptación educativa independiente del sistema de restaurante. En el sistema original la dirección pertenece a un cliente autenticado; aquí no hay cuentas, autenticación ni vínculo con un cliente. La ruta representa el mismo tipo de operación, pero **no afirma ser una API idéntica** a la del sistema original. La única operación implementada para esta práctica es el registro.

## Archivos de entrega

La implementación de esta práctica está en `routes/api.php` (ruta POST de direcciones), `app/Http/Requests/StoreDireccionEntregaRequest.php` (validación), `app/Http/Controllers/Api/DireccionEntregaController.php` (registro), `app/Models/DireccionEntrega.php` (modelo), `app/Http/Resources/DireccionEntregaResource.php` (respuesta JSON) y `database/migrations/2026_11_15_234102_create_direccion_entregas_table.php` (tabla SQLite). Los errores de las rutas API se configuran como JSON en `bootstrap/app.php`.

Las pruebas están en `tests/Feature/RegistrarDireccionEntregaTest.php`; `phpunit.xml` las aísla en SQLite en memoria. Los tres cuerpos JSON para Postman aparecen en la sección de pruebas manuales de este documento. `database/factories/DireccionEntregaFactory.php` y `database/seeders/DatabaseSeeder.php` sirven únicamente para crear un registro ficticio opcional. El contrato y los comandos de ejecución se encuentran en este README.

### Referencia del flujo del proyecto de aula

En el sistema de restaurante, un cliente identificado desde la web o la aplicación móvil registra una dirección para utilizarla en pedidos a domicilio. Los dos clientes necesitan enviar e interpretar los mismos campos. Esta práctica toma **ese proceso y su vocabulario** como referencia; no consulta ni modifica la base de datos del sistema original. La correspondencia siguiente es conceptual y no atribuye nombres de clases ni una arquitectura concreta al proyecto original.

| En el proceso del restaurante | En esta práctica Laravel |
| --- | --- |
| Cliente web o móvil envía una dirección de entrega | Postman simula al cliente y envía JSON a `POST /api/v1/clientes/direcciones` |
| Los campos de la dirección tienen límites acordados para el proyecto de aula | `StoreDireccionEntregaRequest` aplica máximos de 40 caracteres para `etiqueta`, 200 para `direccion`, 20 para `telefono` y 200 para `observaciones` y `referencia`; también valida obligatoriedad, tipos y rangos de coordenadas |
| La dirección pertenece a un cliente autenticado | Se omiten cuentas, autenticación y `cliente_id`; se guarda solo la dirección para estudiar el contrato JSON |
| Registrar la dirección permite usarla después en un pedido a domicilio | `DireccionEntregaController` guarda los datos validados mediante el modelo `DireccionEntrega` en SQLite; no se implementan pedidos |
| El cliente necesita conocer la dirección registrada | `DireccionEntregaResource` define la respuesta pública bajo `data`, con `id`, campos de la dirección, `activa` y `creadoAt` |

En términos de integración, el punto común entre ambos clientes es el **contrato JSON**: nombres y tipos de entrada, restricciones, respuesta y errores. Laravel recibe y deserializa JSON; el Form Request valida; el controlador registra; el Resource selecciona la salida que Laravel serializa como JSON.

## Contrato de solicitud

El cliente envía `Content-Type: application/json` y solicita JSON con `Accept: application/json`. Los campos opcionales pueden omitirse o enviarse como `null`. Los límites de texto usados en esta práctica son los acordados como referencia para el proceso del proyecto de aula.

| Campo | Tipo JSON | Obligatorio | Restricciones | Significado |
| --- | --- | --- | --- | --- |
| `etiqueta` | string | Sí | No vacío; máximo 40 caracteres | Nombre corto de la dirección |
| `direccion` | string | Sí | No vacío; máximo 200 caracteres | Dirección escrita |
| `telefono` | string o null | No | Máximo 20 caracteres; no se exige formato telefónico | Contacto de la entrega |
| `observaciones` | string o null | No | Máximo 200 caracteres | Instrucciones adicionales |
| `referencia` | string o null | No | Máximo 200 caracteres | Punto de referencia |
| `lat` | number o null | No | Número JSON entre -90 y 90, inclusive; cadenas numéricas rechazadas | Latitud |
| `lng` | number o null | No | Número JSON entre -180 y 180, inclusive; cadenas numéricas rechazadas | Longitud |

Solicitud válida mínima, usada en P1:

```json
{"etiqueta":"Casa de prueba","direccion":"Calle Ficticia 123"}
```

Otra solicitud válida, que ilustra todos los campos opcionales:

```json
{"etiqueta":"Oficina de prueba","direccion":"Avenida Imaginaria 45","telefono":"0990000000","observaciones":"Portería ficticia","referencia":"Edificio de prueba","lat":-0.2,"lng":-78.5}
```

## Contrato de respuesta

Al crear, la API devuelve **HTTP 201 Created** con el objeto `data`. Los campos opcionales omitidos aparecen como `null`.

| Campo en `data` | Tipo JSON | Obligatorio en respuesta | Significado |
| --- | --- | --- | --- |
| `id` | integer | Sí | Identificador generado por SQLite |
| `etiqueta` | string | Sí | Nombre corto guardado |
| `direccion` | string | Sí | Dirección guardada |
| `telefono` | string o null | Sí | Contacto o `null` |
| `observaciones` | string o null | Sí | Instrucciones o `null` |
| `referencia` | string o null | Sí | Punto de referencia o `null` |
| `lat` | number o null | Sí | Latitud o `null` |
| `lng` | number o null | Sí | Longitud o `null` |
| `activa` | boolean | Sí | `true` al registrar |
| `creadoAt` | string | Sí | Fecha y hora ISO 8601 con desplazamiento de zona horaria |

Ejemplo de respuesta de P1; en cada ejecución cambiarán `id` y `creadoAt`:

```json
{"data":{"id":3,"etiqueta":"Casa de prueba","direccion":"Calle Ficticia 123","telefono":null,"observaciones":null,"referencia":null,"lat":null,"lng":null,"activa":true,"creadoAt":"2026-11-16T03:11:01+00:00"}}
```

Si la validación falla, la API devuelve **HTTP 422 Unprocessable Content**, un mensaje general `message` y un objeto `errors` con listas de mensajes por campo. Ejemplo real de P2:

```json
{"message":"The etiqueta field is required.","errors":{"etiqueta":["The etiqueta field is required."]}}
```

En P3, `lat` vale `"norte"` y la respuesta contiene `errors.lat`. También se rechaza `"lat":"1.5"`: es texto JSON, aunque parezca numérico. El idioma del texto de los mensajes puede cambiar; las claves `message`, `errors` y el nombre del campo son la estructura estable.

## Cómo ocurre el intercambio

```text
Cliente web o móvil → API Laravel → Form Request
    Datos válidos → controlador y modelo (registro) → SQLite
                 → API Resource → JSON 201 → cliente web o móvil
    Datos inválidos → errors por campo → JSON 422 → cliente web o móvil
```

Laravel **deserializa** el cuerpo JSON y deja sus valores disponibles en `StoreDireccionEntregaRequest`. Este Form Request valida antes de que se ejecute el controlador. El controlador guarda únicamente `$request->validated()` mediante el modelo `DireccionEntrega`. `DireccionEntregaResource` selecciona los campos públicos y Laravel los **serializa** en el objeto JSON `data`. El contrato evita que clientes diferentes interpreten `lat` como texto, `activa` como número o la fecha con otro formato.

## SQLite

La práctica usa el archivo local `database/database.sqlite`. No necesita un servidor de base de datos. Las pruebas automatizadas usan otra base SQLite en memoria (`:memory:`), por lo que no cambian los registros del archivo local.

## Preparación y arranque en PowerShell

Se requiere PHP 8.3 o superior con `pdo_sqlite`, Composer y Postman. Abre PowerShell en la raíz de este repositorio y comprueba los requisitos:

```powershell
php -v
php -m | Select-String pdo_sqlite
composer -V
composer install
if (-not (Test-Path .env)) { Copy-Item .env.example .env; php artisan key:generate --no-interaction }
if (-not (Test-Path database/database.sqlite)) { New-Item database/database.sqlite -ItemType File | Out-Null }
```

En `.env` (archivo ignorado por el repositorio), deja `DB_CONNECTION=sqlite` y comenta o quita `DB_DATABASE` para usar `database/database.sqlite`. Si `.env` ya existía pero `APP_KEY` está vacío, ejecuta `php artisan key:generate --no-interaction`. No hace falta configurar host, puerto, usuario ni contraseña. `phpunit.xml` fija `DB_CONNECTION=sqlite` y `DB_DATABASE=:memory:` para las pruebas automatizadas.

```powershell
php artisan config:clear --no-interaction
php artisan migrate --no-interaction
php artisan serve --host=127.0.0.1 --port=8000 --no-interaction
```

Deja abierta esa terminal mientras pruebas en Postman. El servidor debe indicar que escucha en `http://127.0.0.1:8000`. Si el puerto 8000 está ocupado, usa otro puerto en `php artisan serve` y cambia la URL de Postman para que coincida.

## Pruebas manuales en Postman

1. Crea una solicitud HTTP nueva. Selecciona **POST** y escribe `http://127.0.0.1:8000/api/v1/clientes/direcciones`.
2. En **Authorization**, selecciona **No Auth**. En **Headers**, agrega `Content-Type: application/json` y `Accept: application/json`.
3. En **Body**, selecciona **raw** y luego **JSON**. Pega uno de los cuerpos siguientes y pulsa **Send**. Reutiliza la misma solicitud cambiando solo el cuerpo para P2 y P3.

**P1: solicitud válida.** Pega:

```json
{"etiqueta":"Casa de prueba","direccion":"Calle Ficticia 123"}
```

Debe aparecer **201 Created**. La respuesta debe tener `data.id` entero, `data.etiqueta` y `data.direccion` iguales a lo enviado, campos opcionales en `null`, `data.activa` en `true` y `data.creadoAt` como fecha ISO 8601. El `id` y la fecha no tienen un valor fijo. P1 crea una fila en SQLite cada vez que pulses **Send**.

**P2: falta un campo obligatorio.** Reemplaza el cuerpo por:

```json
{"direccion":"Calle Ficticia 123"}
```

Debe aparecer **422 Unprocessable Content** con `errors.etiqueta` y no debe crearse otra fila.

**P3: tipo incorrecto.** Reemplaza el cuerpo por:

```json
{"etiqueta":"Casa de prueba","direccion":"Calle Ficticia 123","lat":"norte"}
```

Debe aparecer **422 Unprocessable Content** con `errors.lat` y tampoco debe crearse otra fila. Compara los tres estados y cuerpos con los contratos anteriores. El texto exacto de `message` puede variar según el idioma configurado; comprueba sobre todo las claves de `errors`.

## Pruebas automatizadas

Abre una segunda terminal PowerShell en la raíz del repositorio y ejecuta:

```powershell
php artisan test --compact tests/Feature/RegistrarDireccionEntregaTest.php
php artisan test --compact
```

Estos tests comprueban P1, P2 y P3, incluidos los 422 y que las solicitudes inválidas no inserten filas. Usan SQLite en memoria y no cambian `database/database.sqlite`. La comprobación previa de la prueba detiene las migraciones de test si la conexión no es `sqlite` o la base de test no es `:memory:`.

## Resultados de las pruebas

| Caso | Entrada | Resultado observado |
| --- | --- | --- |
| P1 | Campos obligatorios válidos | 201, JSON `data`, dirección registrada |
| P2 | Falta `etiqueta` | 422, `errors.etiqueta`, sin fila nueva |
| P3 | `lat` es `"norte"` | 422, `errors.lat`, sin fila nueva |

La suite específica pasó **8 pruebas y 74 aserciones**; la suite completa pasó **10 pruebas y 76 aserciones**. Las pruebas comprueban el estado y el cuerpo JSON, así como la ausencia de registros nuevos tras P2 y P3.

## Reflexión individual

El problema de integración era que un cliente web y uno móvil podían enviar o interpretar la misma dirección de maneras distintas. El contrato fija nombres, tipos, límites, campos opcionales y forma de respuesta. Elegí un Form Request para rechazar datos inválidos antes de guardar y un API Resource para mantener estable la salida. Verifiqué la decisión con P1, P2 y P3 contra SQLite y con pruebas automáticas que comprueban la respuesta y la ausencia de registros tras errores. La simplificación frente al sistema original es que aquí la dirección no pertenece a un cliente autenticado.
