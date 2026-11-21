# Prácticas de integración del sistema de restaurante

Este repositorio contiene una API Laravel para registrar direcciones de entrega, normalizar entradas de distintos clientes, componer un resumen mediante servicios HTTP de cobertura y estimación, procesar ese resumen también mediante una cola asíncrona y proteger el acceso con Laravel Sanctum. Cada guía indica los comandos, solicitudes y resultados esperados para reproducir y verificar una práctica.

| Guía | Función que permite verificar |
| --- | --- |
| [Práctica 2.2](README_Tema_2_2.md) | Contrato JSON, registro y errores de validación de direcciones. |
| [Práctica 2.3](README_Tema_2_3.md) | Normalización de la entrada plana y la entrada anidada del checkout. |
| [Práctica 2.4](README_Tema_2_4.md) | Autenticación, autorización por propietario y protección de credenciales. |
| [Práctica 3.1](README_Tema_3_1.md) | Cola persistente, productor, consumidor y seguimiento del resumen de entrega. |
| [Práctica 3.3](README_Tema_3_3.md) | Orquestación síncrona de cobertura y estimación, correlación y fallos controlados. |

Para ejecutar las solicitudes manuales de cualquiera de las guías, prepara primero la base local y los tokens de prueba con [la guía de seguridad](README_Tema_2_4.md). El estado actual del proyecto exige un token Bearer para registrar direcciones, consultar el resumen síncrono y publicar o consultar solicitudes asíncronas. El worker de la práctica 3.1 se ejecuta por separado; su guía explica cómo iniciarlo. Las pruebas automatizadas usan SQLite en memoria y se ejecutan desde la raíz con `php artisan test --compact`.

Los datos de ejemplo son ficticios. `.env` y los tokens deben permanecer fuera del repositorio.
