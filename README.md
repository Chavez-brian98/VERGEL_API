![PHP](https://img.shields.io/badge/PHP-8.6-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.2-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Status](https://img.shields.io/badge/status-en%20desarrollo-yellow?style=for-the-badge)

# VERGEL_API

API REST para la gestión integral de punto de venta (POS), que administra usuarios, roles y permisos, inventario, ventas y caja registradora, con un módulo de auditoría que registra la actividad del sistema.

## ¿Qué es el proyecto?

VERGEL_API es el backend del sistema VERGEL, encargado de exponer los servicios necesarios para operar un negocio de venta al detalle: control de inventario, procesamiento de ventas, manejo de caja, devoluciones y administración de usuarios con permisos configurables por rol.

## Problema que soluciona

Muchos negocios pequeños y medianos operan sus ventas e inventario de forma manual o con herramientas dispersas (hojas de cálculo, cuadernos, sistemas no integrados), lo que genera:

- Falta de control sobre el stock disponible y los productos con bajo inventario.
- Dificultad para llevar un cierre de caja confiable y detectar diferencias.
- Ausencia de trazabilidad sobre quién hizo qué cambio dentro del sistema.
- Permisos de usuario rígidos (todo o nada), sin poder adaptar el acceso según el rol real de cada colaborador.

VERGEL_API centraliza estas operaciones en una sola API, con un sistema de roles y permisos granular que permite definir con precisión qué puede ver y hacer cada usuario dentro de cada módulo del sistema.

## Alcance del proyecto

- Exposición de servicios vía API REST consumibles por un frontend (web y/o móvil).
- Autenticación y autorización de usuarios basada en roles y permisos granulares.
- Gestión de catálogo de productos, categorías (con subcategorías) e inventario.
- Registro y control de ventas, incluyendo múltiples métodos de pago.
- Apertura, cierre y arqueo de caja registradora.
- Procesamiento de devoluciones de productos.
- Registro de auditoría (bitácora) de las acciones realizadas en el sistema.

Quedan fuera del alcance de esta primera etapa integraciones con pasarelas de pago externas, facturación electrónica y aplicaciones móviles nativas, aunque el diseño de la API contempla poder incorporarlas más adelante.

## Módulos del proyecto

| Módulo | Descripción |
|---|---|
| **Usuarios** | Registro y administración de usuarios del sistema. |
| **Roles y Permisos** | Creación de roles personalizados y asignación granular de permisos (recurso + acción) por rol, con soporte para múltiples roles por usuario y excepciones de permisos por usuario. |
| **Clientes** | Registro de clientes individuales y empresariales. |
| **Categorías y Productos** | Catálogo de productos organizados en categorías y subcategorías, con control de stock, precios y márgenes. |
| **Caja Registradora** | Apertura, cierre y arqueo de caja por usuario y turno. |
| **Ventas** | Registro de ventas con detalle de productos, métodos de pago y estado de la transacción. |
| **Devoluciones** | Procesamiento de devoluciones de productos vendidos, con su respectivo detalle. |
| **Auditoría** | Bitácora de acciones realizadas por los usuarios sobre las distintas entidades del sistema. |

## Stack tecnológico

- **PHP** 8.6
- **Laravel** 13
- **MySQL** 8.2

## Estado del proyecto

🚧 Proyecto en desarrollo.
