# 🚀 DSS Suite (v2.0)

DSS Suite es una plataforma modular de **WordPress** diseñada para centralizar herramientas de SEO, branding, soporte mediante IA y personalización del escritorio bajo una arquitectura única, profesional y de alto rendimiento.

---

## 🏗️ Arquitectura del Sistema

El plugin opera bajo un modelo de **Núcleo + Módulos**. El núcleo se encarga de la orquestación global, mientras que cada funcionalidad reside en un módulo independiente que puede ser activado o desactivado.

### Estructura de Directorios

- `includes/`: Lógica central del plugin (Carga de módulos, menú global).
- `modules/`: Directorio de funcionalidades activables.
  - `seo-manager/`: Motor de procesamiento de etiquetas H1-H6 mediante servidor.
  - `white-label/`: Personalización de marca (Barra admin, Footer, Login).
  - `chatbox/`: Asistente de IA para el administrador (Gemini).
  - `public-chat/`: Chatbot frontal para visitantes.
  - `cpt-sorter/`: Gestor de orden para Portfolios (Drag & Drop).
  - `dashboard/`: Rediseño visual del escritorio de WordPress.
- `assets/`: Recursos globales (CSS, JS, Imágenes).

---

## 🛠️ Módulos Principales

### 1. Centro de Control

Gestiona la activación de módulos de forma granular. Los módulos inactivos no consumen recursos del servidor.

### 2. IA y Licencia

Configuración centralizada para la **Gemini API Key** y el sistema de licencias (Facturación). Los módulos de chat utilizan estos ajustes por defecto.

### 3. SEO Manager (Server-Side)

Utiliza un motor basado en `DOMDocument` y `XPath` para modificar la estructura de encabezados (H1-H6) en tiempo real antes de que la página sea enviada al cliente.

### 4. White Label Pro

Permite una marca blanca total del sitio:

- Reemplazo del logo de WordPress en la barra superior.
- Personalización de créditos en el pie de página.
- Ocultación selectiva de avisos de actualización de sistema.

---

## 🚀 Guía de Desarrollo de Módulos

Para añadir un nuevo módulo a la suite:

1.  Crear una carpeta en `/modules/`.
2.  Definir un punto de entrada (ej: `nuevo-modulo.php`).
3.  Registrar el módulo en el array `$modules` de `class-dss-suite-core.php`.
4.  Implementar la clase de administración usando el hook `admin_menu` bajo el slug padre `dss-suite`.

---

## 🔒 Seguridad

- Acceso restringido a usuarios con capacidad `manage_options`.
- Protección mediante **Nonces** en todas las peticiones AJAX.
- Sanitización estricta de entradas de usuario.

---

Desarrollado por **Víctor Torres Ortiz** - [DSS NETWORK](https://dssnetwork.es)
