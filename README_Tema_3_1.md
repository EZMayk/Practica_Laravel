# Práctica individual 3.1 Comunicación asíncrona del resumen de entrega

## Proceso del proyecto

El sistema de restaurante permite consultar cobertura y estimación para una dirección de entrega. La consulta síncrona existente espera las respuestas de ambos servicios antes de devolver el resumen. Esta práctica agrega una opción asíncrona para el mismo cálculo: Laravel recibe la solicitud, publica un trabajo duradero y responde **202 Accepted**; otro proceso calcula y guarda el resultado. El cliente consulta el estado después. La dirección y el resultado pertenecen al usuario autenticado.

| Componente | Implementación |
| --- | --- |
| Productor | `POST /api/v1/clientes/resumenes-entrega`, en `SolicitudResumenEntregaController` |
| Cola persistente | Conexión Laravel `database`, tabla `jobs`, cola `resumenes-entrega` |
| Consumidor | Worker `queue:work database --queue=resumenes-entrega`, que ejecuta `ProcesarResumenEntrega` |
| Resultado | Tabla `solicitud_resumen_entregas`, consultable por el propietario con `GET /api/v1/clientes/resumenes-entrega/{id}` |
| Versión síncrona | `GET /api/v1/clientes/direcciones/{id}/resumen-entrega` |

La cola de base de datos cumple el papel de intermediario persistente en este laboratorio. No se instaló RabbitMQ ni se afirma que exista un broker externo. La API, los dos servicios HTTP y el worker son procesos independientes; la base SQLite guarda los mensajes pendientes y el estado. Las migraciones existentes crean `jobs` y `failed_jobs`; la migración `2026_11_20_233039_create_solicitud_resumen_entregas_table.php` crea el registro de seguimiento.

## Diagrama del flujo

```mermaid
sequenceDiagram
    participant C as Cliente Postman
    participant P as API Laravel productor
    participant Q as Cola database resumenes-entrega
    participant W as Worker consumidor
    participant S as Cobertura y estimación
    participant R as Solicitud en SQLite
    C->>P: POST direccion_id y X-Correlation-ID
    P->>R: Crea solicitud pendiente y message_id
    P->>Q: Publica mensaje con correlation_id
    P-->>C: 202 pendiente, solicitud_id y message_id
    W->>Q: Reserva mensaje
    W->>S: Consulta con el mismo correlation_id
    S-->>W: Cobertura y estimación
    W->>R: Guarda resultado y estado completada
    W->>Q: Confirma al terminar; el trabajo sale de jobs
    C->>P: GET solicitud_id
    P-->>C: 200 estado y resultado
```

El mensaje de dominio se guarda en `solicitud_resumen_entregas.mensaje` y viaja como propiedad del job. Laravel lo envuelve en su propio formato JSON de cola. Contiene `message_id` (UUID único del trabajo), `correlation_id` (identificador compartido por API, consultas HTTP, logs y resultado), `tipo`, `ocurrido_en` y `payload.direccion_id`. No contiene tokens, nombre, dirección postal ni coordenadas. El middleware existente conserva un `X-Correlation-ID` válido o genera uno; la respuesta lo devuelve en la cabecera.

```json
{
  "message_id": "UUID generado por la API",
  "correlation_id": "async-entrega-001",
  "tipo": "resumen_entrega_solicitado",
  "ocurrido_en": "fecha ISO 8601 generada por la API",
  "payload": {"direccion_id": 12}
}
```

## Preparación local para reproducir la práctica

1. Instala las dependencias con `composer install`. Copia `.env.example` a `.env` si aún no existe y genera `APP_KEY` con `php artisan key:generate --no-interaction`. En `.env` usa `DB_CONNECTION=sqlite`, `QUEUE_CONNECTION=database`, `APP_DEBUG=false` y deja `DB_DATABASE` sin definir para utilizar `database/database.sqlite`. No publiques `.env` ni tokens. Crea `database/database.sqlite` si no existe y ejecuta `php artisan migrate --no-interaction`. La conexión `database` usa la misma SQLite para la cola y las solicitudes; no requiere credenciales de broker. Ejecuta `php artisan queue:monitor database:resumenes-entrega --json --no-interaction` para comprobar que existe la cola lógica y ver su tamaño. Con el worker detenido, su tamaño crece al publicar mensajes.
2. Prepara un usuario y un token local `tokenA` con los pasos de `README_Tema_2_4.md`. En Postman usa **Authorization → Bearer Token** con `{{tokenA}}` y `Accept: application/json`. Crea una dirección ficticia con el POST de esa guía y conserva el `data.id` de la respuesta. Usa siempre ese ID y el token de su propietario. No exportes el entorno de Postman con el token.
3. Abre tres terminales en la raíz del proyecto para los procesos HTTP. Mantén **apagado el worker** al iniciar la prueba:

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
   # Terminal 3: API
   php artisan serve --host=127.0.0.1 --port=8000 --no-interaction
   ```

## Verificación manual en orden

1. **Comparación síncrona.** En Postman envía `GET http://127.0.0.1:8000/api/v1/clientes/direcciones/{id}/resumen-entrega` con `{{tokenA}}` y `X-Correlation-ID: sync-entrega-001`. Sustituye `{id}` por el ID de la dirección. Debe responder **200** con `data.cobertura` y `data.estimacion`; anota el tiempo que muestra Postman. La respuesta llega después de las dos consultas HTTP.
2. **Publicación con consumidor detenido.** Sin iniciar un worker, envía `POST http://127.0.0.1:8000/api/v1/clientes/resumenes-entrega` con `{{tokenA}}`, `Content-Type: application/json`, `X-Correlation-ID: async-entrega-001` y cuerpo `{"direccion_id":12}` sustituyendo 12 por tu ID. Debe responder **202** con `data.solicitud_id`, `message_id`, `correlation_id` y `estado: pendiente`. Anota el tiempo de Postman. Repite el POST dos veces, cambiando el header a `async-entrega-002` y `async-entrega-003`; conserva los tres `solicitud_id`.
3. **Pendiente en la cola.** Con el worker todavía detenido, envía un `GET http://127.0.0.1:8000/api/v1/clientes/resumenes-entrega/{solicitud_id}` para cada solicitud, siempre con `{{tokenA}}`. Debe responder **200**, `estado: pendiente` y `resultado: null`. En otra terminal ejecuta `php artisan queue:monitor database:resumenes-entrega --json --no-interaction`: la propiedad `size` debe incluir los tres mensajes, salvo que hubiera otros trabajos previos. Esto comprueba que recibir **202** no equivale a terminar el cálculo.
4. **Consumo.** En una cuarta terminal ejecuta `php artisan queue:work database --queue=resumenes-entrega --tries=3 --timeout=15 --stop-when-empty --no-interaction`. El proceso consume los trabajos pendientes y sale al vaciar la cola. Repite los tres GET de estado: deben mostrar `completada`, `resultado.cobertura`, `resultado.estimacion` y `procesado_en`. Repite `queue:monitor`: el tamaño de esa cola debe bajar. En `storage/logs/laravel.log`, busca `async-entrega-001`: aparecen publicación, consumo y finalización con el mismo `correlation_id` y su `message_id`. El worker solo elimina el trabajo de `jobs` tras completar `handle`; si falla, Laravel reintenta y al agotar los tres intentos pasa a `failed_jobs` y marca la solicitud `fallida`.
5. **Mensaje inválido.** Envía el mismo POST con cuerpo `{}` y `{{tokenA}}`. Debe responder **422** con `errors.direccion_id`; no se publica otro trabajo. Un cliente diferente recibe **403** al intentar solicitar el resumen de la dirección de A. Sin token recibe **401**.

Para comparar las dos rutas, usa la misma dirección, los mismos servicios activos y las mediciones reales de Postman en una misma sesión. El tiempo depende del equipo; no hay una cifra fija prometida.

| Aspecto | GET síncrono | POST asíncrono y GET de estado |
| --- | --- | --- |
| Respuesta inicial | 200 con resumen completo, después de consultar servicios | 202 con identificadores y estado pendiente, antes del cálculo |
| Tiempo inicial | Registrar milisegundos observados en Postman | Registrar milisegundos observados en Postman |
| Worker detenido | La ruta síncrona sigue calculando si los servicios responden | La API acepta el mensaje y permanece pendiente en la cola |
| Servicio externo caído | 502 controlado en la misma petición | El job reintenta; si agota intentos, estado fallida y registro en `failed_jobs` |
| Trazabilidad | `correlation_id` en respuesta, cabecera, servicios y log | `message_id` distingue cada mensaje; `correlation_id` enlaza productor, consumidor, servicios y estado |

## Comprobación automatizada y evidencias

`php artisan test --compact tests/Feature/SolicitudResumenEntregaTest.php` comprueba publicación de tres mensajes, persistencia con worker detenido, procesamiento posterior, correlación, validación, autorización y fallo tras reintentos. `php artisan test --compact` verifica también que el GET síncrono anterior siga funcionando. Las pruebas usan SQLite en memoria y respuestas HTTP simuladas; para la demostración real sigue el procedimiento de Postman y los cuatro procesos locales.

La entrega individual debe mostrar capturas reales de: POST **202** con un `message_id`; estado **pendiente** y tamaño de cola mientras el worker está detenido; estado **completada** y tamaño de cola tras reanudarlo; POST inválido **422**; y el mismo `correlation_id` en los registros. El diagrama anterior y el código del productor, worker, migración y pruebas completan la evidencia técnica. No incluyas tokens ni datos reales en las capturas.
