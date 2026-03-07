# DSS Suite (v3.5)

DSS Suite es una plataforma modular de **WordPress** diseñada para centralizar herramientas de SEO, branding, soporte mediante IA y personalización del escritorio bajo una arquitectura única, profesional y de alto rendimiento.

---

## Arquitectura del Sistema

El plugin opera bajo un modelo de **Núcleo + Módulos**. El núcleo se encarga de la orquestación global, mientras que cada funcionalidad reside en un módulo independiente que puede ser activado o desactivado. Los módulos inactivos no consumen recursos del servidor.

### Flujo de Inicialización

1. `dss-suite.php` se ejecuta en el hook `plugins_loaded`.
2. Instancia `DSS_Suite_Core` (en `includes/class-dss-suite-core.php`).
3. El Core carga el singleton `DSS_Notifications`.
4. Registra el menú de administración (posición 65, slug `dss-suite`).
5. Ejecuta `load_modules()` — solo carga los módulos habilitados en `get_option('dss_suite_active_modules')`.

### Estructura de Directorios

```
DSS-SUITE/
  dss-suite.php                          # Punto de entrada
  includes/
    class-dss-suite-core.php             # Orquestación central
    class-dss-notifications.php          # Singleton de notificaciones
  modules/
    dashboard/                           # Rediseño visual del escritorio de WordPress
    seo-manager/                         # Motor de etiquetas H1-H6 + auditoría SEO
    white-label/                         # Marca blanca, widgets y control de tema
    cpt-sorter/                          # Ordenamiento Drag & Drop para CPTs
    chatbox/                             # Asistente IA para el administrador (Gemini)
    public-chat/                         # Chatbot flotante para visitantes (Gemini)
      addons/
        room-designer/                   # Addon: Diseñador de habitaciones con IA
        course-advisor/                  # Addon: Asesor de formaciones con IA
    duplicate-finder/                    # Buscador de productos duplicados en WooCommerce
  assets/                                # Recursos globales (CSS, JS)
```

---

## Módulos

### Centro de Control

Panel principal protegido por **Master Key** (definida en `wp-config.php`). Permite activar y desactivar módulos de forma granular mediante una interfaz visual con tarjetas y switches.

### IA y Licencia

Configuración centralizada para la **Gemini API Key** y el sistema de licencias (número de factura). Los módulos de chat utilizan estos ajustes de forma compartida.

### SEO Manager

Motor basado en `DOMDocument` y `XPath` que modifica la estructura de encabezados HTML (H1-H6) en tiempo real del lado del servidor, antes de que la página sea enviada al cliente. Permite añadir clases personalizadas a las etiquetas.

### White Label Pro (Widget & Theme Controller)

Control total sobre la apariencia del sitio WordPress:

- Reemplazo del logo de WordPress en la barra superior.
- Personalización de créditos en el pie de página.
- Ocultación selectiva de avisos de actualización del sistema.
- Gestión de widgets del dashboard.

### Content Sorter

Ordenamiento manual mediante **Drag & Drop** para cualquier Custom Post Type (CPT) y taxonomía registrada en WordPress.

### Chatbox de Soporte (Premium)

Chatbox moderno integrado en el área de administración de WordPress. Utiliza la **API de Gemini** para ofrecer un asistente de IA al administrador del sitio, ideal para consultas de clientes y soporte técnico.

### Chat Público Beta (Premium)

Chatbot flotante para la parte pública del sitio web. Soporta envío de fotos y prompts personalizados, permitiendo a los visitantes interactuar con un asistente de IA potenciado por **Gemini**.

### Addons del Chat Público

| Addon | Descripción |
|-------|-------------|
| **Room Designer** | El cliente sube una foto de su habitación y la IA coloca los muebles de la tienda. Requiere WooCommerce. |
| **Course Advisor** | Asesor IA para webs de formaciones. Recomienda cursos según los objetivos y nivel del visitante. |

> Los addons requieren que el módulo **Chat Público** esté activo para funcionar.

### Duplicate Finder

Encuentra y gestiona productos duplicados en **WooCommerce**. Incluye detección multilingüe mediante **Polylang** y sistema de rollback por usuario.

---

## Seguridad

- Acceso restringido a usuarios con capacidad `manage_options`.
- Protección mediante **Nonces** en todas las peticiones AJAX y formularios.
- Sanitización estricta de entradas de usuario.
- Páginas críticas protegidas por **Master Key** con transient de 8 horas.

---

## Dependencias Externas

| Dependencia | Uso |
|-------------|-----|
| **WooCommerce** | Requerido por Duplicate Finder y Room Designer. |
| **Polylang** | Opcional. Duplicate Finder detecta el idioma de los productos. |
| **Gemini API** | Utilizada por Chatbox, Chat Público y sus addons. |

---

## Guía de Desarrollo de Módulos

1. Crear una carpeta en `modules/<slug>/`.
2. Definir un punto de entrada (`function.php` o `<slug>.php`).
3. Registrar el módulo en el array `$modules` de `class-dss-suite-core.php`.
4. Usar `add_submenu_page('dss-suite', ...)` para el menú de administración.
5. Seguir los patrones existentes de assets, AJAX y seguridad.

---

## Licencia

MIT

---

Desarrollado por **Víctor Torres Ortiz** — [DSS NETWORK](https://dssnetwork.es)
