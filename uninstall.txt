# readme.txt

```
=== Probolsas - Gestión de Solicitudes ===
Contributors: probolsas
Tags: solicitudes, gestión, procesos, calendario, admin
Requires at least: 5.0
Tested up to: 6.3
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sistema completo de gestión de solicitudes y agenda de acciones para procesos organizacionales de Probolsas.

== Description ==

**Probolsas - Gestión de Solicitudes** es un plugin completo desarrollado específicamente para Probolsas que permite:

* **Gestión completa de procesos**: Crear, editar y administrar los procesos organizacionales
* **Sistema de solicitudes**: Interface intuitiva para crear y gestionar solicitudes
* **Vista de calendario**: Planificación visual de fechas de ejecución
* **Estados personalizables**: Seguimiento del progreso (Solicitado, En proceso, Aprobado)
* **Filtros avanzados**: Búsqueda por proceso, estado, semana y mes
* **Reportes y estadísticas**: Panel completo con gráficos y exportación
* **Permisos granulares**: Control de acceso por roles de usuario
* **Responsive design**: Funciona perfectamente en todos los dispositivos

== Installation ==

1. Sube la carpeta `probolsas-solicitudes` al directorio `/wp-content/plugins/`
2. Activa el plugin a través del menú 'Plugins' en WordPress
3. El plugin creará automáticamente las tablas necesarias y una página de ejemplo
4. Ve a 'Solicitudes' en el menú de administración para configurar procesos
5. Usa el shortcode `[probolsas_solicitudes]` en cualquier página o entrada

== Frequently Asked Questions ==

= ¿Puedo personalizar los procesos? =

Sí, los administradores pueden crear, editar y gestionar todos los procesos desde el panel de administración.

= ¿Qué permisos necesito para usar el plugin? =

- **Usuarios básicos**: Pueden crear solicitudes si están logueados
- **Editores**: Pueden crear solicitudes y ver todas las solicitudes
- **Administradores**: Acceso completo incluyendo gestión de procesos y reportes

= ¿Se pueden exportar los datos? =

Sí, el plugin incluye funcionalidad de exportación a CSV y reportes detallados.

= ¿Es compatible con temas personalizados? =

Sí, el plugin está diseñado para funcionar con cualquier tema de WordPress.

== Screenshots ==

1. Vista principal con lista de solicitudes y filtros
2. Vista de calendario para planificación visual
3. Formulario de nueva solicitud
4. Panel de administración de procesos
5. Reportes y estadísticas detalladas

== Changelog ==

= 1.0.0 =
* Lanzamiento inicial
* Sistema completo de gestión de solicitudes
* Vista de calendario interactiva
* Panel de administración de procesos
* Reportes y estadísticas
* Exportación de datos
* Sistema de notificaciones
* Responsive design

== Technical Requirements ==

* WordPress 5.0 o superior
* PHP 7.4 o superior
* MySQL 5.6 o superior
* Navegadores modernos (Chrome, Firefox, Safari, Edge)

== Support ==

Para soporte técnico, contacta al equipo de desarrollo de Probolsas.

== Privacy Policy ==

Este plugin almacena datos de solicitudes y procesos en la base de datos de WordPress. No envía datos a servicios externos.
```

# uninstall.php

```php
<?php
/**
 * Archivo de desinstalación del plugin
 * 
 * @package ProbolsasSolicitudes
 */

// Si la desinstalación no fue llamada desde WordPress, salir
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Verificar permisos
if (!current_user_can('activate_plugins')) {
    return;
}

// Obtener todas las opciones del plugin
$opciones_plugin = array(
    'probolsas_solicitudes_version',
    'probolsas_solicitudes_activado',
    'probolsas_solicitudes_desactivado'
);

// Eliminar opciones
foreach ($opciones_plugin as $opcion) {
    delete_option($opcion);
}

// Eliminar tablas de la base de datos
global $wpdb;

$tabla_solicitudes = $wpdb->prefix . 'probolsas_solicitudes';
$tabla_procesos = $wpdb->prefix . 'probolsas_procesos';

// Confirmar eliminación solo si se define la constante
if (defined('PROBOLSAS_REMOVE_DATA') && PROBOLSAS_REMOVE_DATA === true) {
    
    // Eliminar tablas
    $wpdb->query("DROP TABLE IF EXISTS {$tabla_solicitudes}");
    $wpdb->query("DROP TABLE IF EXISTS {$tabla_procesos}");
    
    // Eliminar página creada automáticamente
    $pagina = get_page_by_path('solicitudes-probolsas');
    if ($pagina) {
        wp_delete_post($pagina->ID, true);
    }
    
    // Eliminar metadatos de usuarios relacionados (si los hay)
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'probolsas_%'");
    
    // Limpiar cache
    wp_cache_flush();
    
    // Log de desinstalación
    error_log('Plugin Probolsas Solicitudes desinstalado completamente con eliminación de datos');
    
} else {
    
    // Solo desactivar sin eliminar datos
    error_log('Plugin Probolsas Solicitudes desinstalado conservando datos');
    
}

// Limpiar rewrite rules
flush_rewrite_rules();
```

# Instrucciones completas de instalación

## Estructura final de archivos:

```
wp-content/plugins/probolsas-solicitudes/
├── probolsas-solicitudes.php          # Archivo principal
├── readme.txt                         # Información del plugin  
├── uninstall.php                      # Script de desinstalación
├── assets/
│   ├── css/
│   │   ├── solicitudes.css            # Estilos principales
│   │   └── admin.css                  # Estilos del admin
│   └── js/
│       ├── solicitudes.js             # JavaScript principal
│       └── admin.js                   # JavaScript del admin
├── templates/
│   └── interfaz-principal.php         # Template principal
├── includes/
│   ├── admin-procesos.php             # Gestión de procesos
│   └── admin-reportes.php             # Reportes y estadísticas
└── languages/
    └── (archivos de traducción)
```

## Pasos de instalación:

### 1. Crear la estructura:
- Crea la carpeta `probolsas-solicitudes` en `/wp-content/plugins/`
- Crea las subcarpetas: `assets/css/`, `assets/js/`, `templates/`, `includes/`, `languages/`

### 2. Subir archivos:
- Copia cada archivo en su ubicación correspondiente
- Asegúrate de que los permisos sean correctos (644 para archivos, 755 para carpetas)

### 3. Activar el plugin:
- Ve a **Plugins** → **Plugins instalados**
- Busca "Probolsas - Gestión de Solicitudes"
- Haz clic en **Activar**

### 4. Configuración inicial:
- Ve a **Solicitudes** → **Procesos** para revisar los procesos predeterminados
- Visita la página creada automáticamente: `/solicitudes-probolsas/`
- O usa el shortcode `[probolsas_solicitudes]` en cualquier página

## Características principales:

✅ **Sistema completo de solicitudes**
✅ **Gestión de procesos (solo admin)**  
✅ **Vista de calendario interactiva**
✅ **Filtros avanzados**
✅ **Reportes con gráficos**
✅ **Exportación a CSV**
✅ **Sistema de notificaciones**
✅ **Responsive design**
✅ **Multiusuario con permisos**
✅ **Base de datos optimizada**

## Soporte y personalización:

El plugin está completamente funcional y listo para producción. Incluye:
- Manejo de errores
- Validación de datos
- Seguridad (nonces, sanitización)
- Compatibilidad con temas
- Optimización de performance
- Internacionalización (listo para traducir)