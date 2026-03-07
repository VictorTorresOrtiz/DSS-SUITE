# DSS Suite (v3.0)

DSS Suite es una plataforma modular de **WordPress** disenada para centralizar herramientas de SEO, branding, soporte mediante IA, gestion remota y personalizacion del escritorio bajo una arquitectura unica, profesional y de alto rendimiento.

---

## Arquitectura del Sistema

El plugin opera bajo un modelo de **Nucleo + Modulos**. El nucleo se encarga de la orquestacion global, mientras que cada funcionalidad reside en un modulo independiente que puede ser activado o desactivado desde el Panel de Control.

### Estructura de Directorios

```
dss-suite.php                     # Entry point del plugin
includes/
  class-dss-suite-core.php        # Core: registro de modulos, menu, auth
  class-dss-notifications.php     # Sistema de notificaciones toast
modules/
  dashboard/                      # Rediseno visual del escritorio WP
  seo-manager/                    # Motor de procesamiento de etiquetas HTML
  white-label/                    # Marca blanca, widgets y branding
  chatbox/                        # Asistente IA para administradores (Gemini)
  public-chat/                    # Chatbot flotante para visitantes
    addons/room-designer/         # Addon: diseno de interiores con IA
  cpt-sorter/                     # Ordenamiento Drag & Drop para CPTs
  dss-connector/                  # API remota para DSS Gestion
assets/                           # Recursos globales (CSS, JS)
```

---

## Modulos

### 1. Panel de Control (Core)

Gestiona la activacion de modulos de forma granular. Los modulos inactivos no consumen recursos del servidor. Protegido con Master Key definida en `wp-config.php`.

### 2. IA y Licencia

Configuracion centralizada para la **Gemini API Key** y el sistema de licencias. Los modulos de chat utilizan estos ajustes por defecto.

### 3. DSS Dashboard

Rediseno completo del escritorio de WordPress con modulos internos de navegacion, colores, login, footer y demos.

### 4. SEO Manager (Server-Side)

Utiliza un motor basado en `DOMDocument` y `XPath` para modificar la estructura de encabezados (H1-H6) y clases HTML en tiempo real antes de que la pagina sea enviada al cliente.

### 5. Widget & Theme Controller (White Label)

Permite una marca blanca total del sitio:

- Reemplazo del logo de WordPress en la barra superior.
- Personalizacion de creditos en el pie de pagina.
- Widgets personalizados en el dashboard (Bienvenida, Ventas, Estado del Sistema, Consultas Pesadas).
- Ocultacion selectiva de avisos de actualizacion.

### 6. Content Sorter

Ordenamiento manual (Drag & Drop) para cualquier CPT y taxonomia publica. Aplica el orden tanto en el frontend como en el listado de administracion.

### 7. Chatbox de Soporte (Premium)

Chatbox moderno en el area de administracion para consultas de clientes, impulsado por Gemini AI.

### 8. Chat Publico (Premium)

Chatbot flotante para la parte publica del sitio con soporte multimodal (texto e imagenes).

### 9. Room Designer (Addon)

Addon del Chat Publico. El cliente sube una foto de su habitacion y la IA sugiere y coloca muebles del catalogo WooCommerce. Utiliza Gemini 2.0 Flash con generacion de imagenes.

**Requiere**: Chat Publico activo.

### 10. DSS Connector

API remota via `admin-ajax.php` para que **DSS Gestion** pueda administrar el sitio WordPress sin necesidad de SSH. Autenticacion por API Key (header `X-DSS-Key`).

**Acciones disponibles:**

| Accion | Descripcion |
|--------|-------------|
| `site_info` | Informacion general del sitio |
| `plugin_list` / `plugin_update` / `plugin_update_all` | Gestion de plugins |
| `theme_list` / `theme_update` / `theme_update_all` | Gestion de temas |
| `user_list` / `user_create` / `user_delete` | Gestion de usuarios |
| `core_version` / `core_check_update` / `core_update` | Actualizaciones de WordPress |
| `db_export` | Exportar base de datos |
| `maintenance_toggle` | Activar/desactivar modo mantenimiento |
| `cache_flush` | Limpiar cache y transients |

---

## Guia de Desarrollo de Modulos

Para anadir un nuevo modulo a la suite:

1. Crear una carpeta en `/modules/`.
2. Definir un punto de entrada (ej: `nuevo-modulo.php`).
3. Registrar el modulo en el array `$modules` de `class-dss-suite-core.php`.
4. Implementar la clase de administracion usando el hook `admin_menu` bajo el slug padre `dss-suite`.
5. Para addons, agregar la propiedad `requires` con el slug del modulo padre.

---

## Seguridad

- Acceso restringido a usuarios con capacidad `manage_options`.
- Panel protegido con **Master Key** (definida en `wp-config.php` como `DSS_MASTER_KEY`).
- Proteccion mediante **Nonces** en todas las peticiones AJAX.
- Sanitizacion estricta de entradas de usuario.
- API Connector autenticado via API Key con `hash_equals()`.

---

Desarrollado por **Victor Torres Ortiz** - [DSS NETWORK](https://dssnetwork.es)
