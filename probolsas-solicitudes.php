<?php
/**
 * Plugin Name: Probolsas - Gestión de Solicitudes
 * Plugin URI: https://probolsas.com
 * Description: Sistema de gestión de solicitudes y agenda de acciones para procesos organizacionales de Probolsas
 * Version: 1.0.0
 * Author: Probolsas
 * Author URI: https://probolsas.com
 * Text Domain: probolsas-solicitudes
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('PROBOLSAS_SOLICITUDES_VERSION', '1.0.0');
define('PROBOLSAS_SOLICITUDES_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PROBOLSAS_SOLICITUDES_PLUGIN_PATH', plugin_dir_path(__FILE__));

class ProbolsasSolicitudes {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'probolsas_solicitudes';
        
        // Hooks de activación/desactivación
        register_activation_hook(__FILE__, array($this, 'activar_plugin'));
        register_deactivation_hook(__FILE__, array($this, 'desactivar_plugin'));
        
        // Inicializar plugin
        add_action('plugins_loaded', array($this, 'init'));
        add_action('init', array($this, 'load_textdomain'));
        
        // Scripts y estilos
        add_action('wp_enqueue_scripts', array($this, 'cargar_assets'));
        add_action('admin_enqueue_scripts', array($this, 'cargar_assets_admin'));
        
        // AJAX handlers
        add_action('wp_ajax_probolsas_crear_solicitud', array($this, 'ajax_crear_solicitud'));
        add_action('wp_ajax_probolsas_obtener_solicitudes', array($this, 'ajax_obtener_solicitudes'));
        add_action('wp_ajax_probolsas_actualizar_estado', array($this, 'ajax_actualizar_estado'));
        add_action('wp_ajax_probolsas_actualizar_fecha', array($this, 'ajax_actualizar_fecha'));
        add_action('wp_ajax_probolsas_eliminar_solicitud', array($this, 'ajax_eliminar_solicitud'));
        add_action('wp_ajax_probolsas_obtener_procesos', array($this, 'ajax_obtener_procesos'));
        add_action('wp_ajax_probolsas_crear_proceso', array($this, 'ajax_crear_proceso'));
        add_action('wp_ajax_probolsas_actualizar_proceso', array($this, 'ajax_actualizar_proceso'));
        add_action('wp_ajax_probolsas_eliminar_proceso', array($this, 'ajax_eliminar_proceso'));
        
        // Shortcode
        add_shortcode('probolsas_solicitudes', array($this, 'mostrar_interfaz'));
        
        // Menu de administración
        add_action('admin_menu', array($this, 'agregar_menu_admin'));
        
        // Verificar permisos
        add_action('wp_ajax_nopriv_probolsas_crear_solicitud', array($this, 'verificar_permisos'));
        add_action('wp_ajax_nopriv_probolsas_obtener_solicitudes', array($this, 'verificar_permisos'));
    }
    
    public function init() {
        // Cargar funcionalidades del plugin
        $this->verificar_version_bd();
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('probolsas-solicitudes', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function activar_plugin() {
        $this->crear_tablas();
        $this->insertar_procesos_default();
        $this->crear_pagina_solicitudes();
        
        // Limpiar rewrite rules
        flush_rewrite_rules();
        
        // Registro de activación
        add_option('probolsas_solicitudes_version', PROBOLSAS_SOLICITUDES_VERSION);
        add_option('probolsas_solicitudes_activado', current_time('mysql'));
    }
    
    public function desactivar_plugin() {
        // Limpiar rewrite rules
        flush_rewrite_rules();
        
        // Registro de desactivación
        update_option('probolsas_solicitudes_desactivado', current_time('mysql'));
    }
    
    private function crear_tablas() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tabla de solicitudes
        $sql_solicitudes = "CREATE TABLE {$this->table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            proceso_id mediumint(9) NOT NULL,
            solicitud text NOT NULL,
            fecha_solicitud date NOT NULL,
            fecha_ejecucion varchar(50) DEFAULT 'Por definirse',
            estado int(1) DEFAULT 1,
            usuario_id int(11),
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY proceso_id (proceso_id),
            KEY estado (estado),
            KEY fecha_solicitud (fecha_solicitud),
            KEY usuario_id (usuario_id)
        ) $charset_collate;";
        
        // Tabla de procesos
        $tabla_procesos = $wpdb->prefix . 'probolsas_procesos';
        $sql_procesos = "CREATE TABLE {$tabla_procesos} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            nombre varchar(100) NOT NULL,
            descripcion text,
            activo tinyint(1) DEFAULT 1,
            orden int(11) DEFAULT 0,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY nombre (nombre),
            KEY activo (activo)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_solicitudes);
        dbDelta($sql_procesos);
    }
    
    private function insertar_procesos_default() {
        global $wpdb;
        
        $tabla_procesos = $wpdb->prefix . 'probolsas_procesos';
        
        $procesos_default = array(
            'Gerencial',
            'Abastecimiento',
            'Producción',
            'Calidad',
            'Atención al Cliente',
            'Marketing',
            'Talento Humano',
            'Mantenimiento e Infraestructura',
            'Presupuesto y Compras'
        );
        
        foreach ($procesos_default as $index => $proceso) {
            $wpdb->insert(
                $tabla_procesos,
                array(
                    'nombre' => $proceso,
                    'descripcion' => "Proceso de {$proceso}",
                    'orden' => $index + 1,
                    'activo' => 1
                ),
                array('%s', '%s', '%d', '%d')
            );
        }
    }
    
    private function crear_pagina_solicitudes() {
        // Crear página para mostrar las solicitudes
        $pagina_existe = get_page_by_path('solicitudes-probolsas');
        
        if (!$pagina_existe) {
            wp_insert_post(array(
                'post_title' => 'Gestión de Solicitudes',
                'post_content' => '[probolsas_solicitudes]',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_name' => 'solicitudes-probolsas',
                'comment_status' => 'closed',
                'ping_status' => 'closed'
            ));
        }
    }
    
    public function cargar_assets() {
        if (!$this->es_pagina_solicitudes()) {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/ui-lightness/jquery-ui.css');
        
        wp_enqueue_script(
            'probolsas-solicitudes-js',
            PROBOLSAS_SOLICITUDES_PLUGIN_URL . 'assets/js/solicitudes.js',
            array('jquery'),
            PROBOLSAS_SOLICITUDES_VERSION,
            true
        );
        
        wp_enqueue_style(
            'probolsas-solicitudes-css',
            PROBOLSAS_SOLICITUDES_PLUGIN_URL . 'assets/css/solicitudes.css',
            array(),
            PROBOLSAS_SOLICITUDES_VERSION
        );
        
        wp_localize_script('probolsas-solicitudes-js', 'probolsas_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('probolsas_solicitudes_nonce'),
            'es_admin' => current_user_can('manage_options'),
            'textos' => array(
                'confirmacion_eliminar' => __('¿Estás seguro de que quieres eliminar esta solicitud?', 'probolsas-solicitudes'),
                'accion_irreversible' => __('Esta acción es irreversible.', 'probolsas-solicitudes'),
                'solicitud_creada' => __('Solicitud creada exitosamente', 'probolsas-solicitudes'),
                'solicitud_actualizada' => __('Solicitud actualizada exitosamente', 'probolsas-solicitudes'),
                'solicitud_eliminada' => __('Solicitud eliminada exitosamente', 'probolsas-solicitudes'),
                'error_general' => __('Ha ocurrido un error. Inténtalo de nuevo.', 'probolsas-solicitudes')
            )
        ));
    }
    
    public function cargar_assets_admin() {
        $screen = get_current_screen();
        if (strpos($screen->id, 'probolsas') === false) {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_script(
            'probolsas-admin-js',
            PROBOLSAS_SOLICITUDES_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            PROBOLSAS_SOLICITUDES_VERSION,
            true
        );
        
        wp_enqueue_style(
            'probolsas-admin-css',
            PROBOLSAS_SOLICITUDES_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            PROBOLSAS_SOLICITUDES_VERSION
        );
        
        wp_localize_script('probolsas-admin-js', 'probolsas_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('probolsas_solicitudes_nonce')
        ));
    }
    
    private function es_pagina_solicitudes() {
        global $post;
        return (is_page() && isset($post->post_content) && has_shortcode($post->post_content, 'probolsas_solicitudes')) || 
               (is_page('solicitudes-probolsas'));
    }
    
    public function agregar_menu_admin() {
        add_menu_page(
            __('Probolsas Solicitudes', 'probolsas-solicitudes'),
            __('Solicitudes', 'probolsas-solicitudes'),
            'manage_options',
            'probolsas-solicitudes',
            array($this, 'pagina_admin_principal'),
            'dashicons-clipboard',
            30
        );
        
        add_submenu_page(
            'probolsas-solicitudes',
            __('Gestión de Procesos', 'probolsas-solicitudes'),
            __('Procesos', 'probolsas-solicitudes'),
            'manage_options',
            'probolsas-procesos',
            array($this, 'pagina_admin_procesos')
        );
        
        add_submenu_page(
            'probolsas-solicitudes',
            __('Reportes', 'probolsas-solicitudes'),
            __('Reportes', 'probolsas-solicitudes'),
            'manage_options',
            'probolsas-reportes',
            array($this, 'pagina_admin_reportes')
        );
    }
    
    public function pagina_admin_principal() {
        echo '<div class="wrap">';
        echo '<h1>' . __('Gestión de Solicitudes - Probolsas', 'probolsas-solicitudes') . '</h1>';
        echo '<p>' . __('Desde aquí puedes administrar todas las solicitudes del sistema.', 'probolsas-solicitudes') . '</p>';
        echo do_shortcode('[probolsas_solicitudes]');
        echo '</div>';
    }
    
    public function pagina_admin_procesos() {
        include_once PROBOLSAS_SOLICITUDES_PLUGIN_PATH . 'includes/admin-procesos.php';
    }
    
    public function pagina_admin_reportes() {
        include_once PROBOLSAS_SOLICITUDES_PLUGIN_PATH . 'includes/admin-reportes.php';
    }
    
    public function mostrar_interfaz($atts) {
        // Verificar permisos
        if (!is_user_logged_in()) {
            return '<p>' . __('Debes iniciar sesión para acceder a esta funcionalidad.', 'probolsas-solicitudes') . '</p>';
        }
        
        ob_start();
        include PROBOLSAS_SOLICITUDES_PLUGIN_PATH . 'templates/interfaz-principal.php';
        return ob_get_clean();
    }
    
    // AJAX Handlers
    public function ajax_crear_solicitud() {
        $this->verificar_nonce();
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción.', 'probolsas-solicitudes'));
        }
        
        global $wpdb;
        
        $proceso_id = intval($_POST['proceso_id']);
        $solicitud = sanitize_textarea_field($_POST['solicitud']);
        $fecha_solicitud = sanitize_text_field($_POST['fecha_solicitud']);
        
        $resultado = $wpdb->insert(
            $this->table_name,
            array(
                'proceso_id' => $proceso_id,
                'solicitud' => $solicitud,
                'fecha_solicitud' => $fecha_solicitud,
                'fecha_ejecucion' => 'Por definirse',
                'estado' => 1,
                'usuario_id' => get_current_user_id()
            ),
            array('%d', '%s', '%s', '%s', '%d', '%d')
        );
        
        if ($resultado) {
            wp_send_json_success(array(
                'message' => __('Solicitud creada exitosamente', 'probolsas-solicitudes'),
                'id' => $wpdb->insert_id
            ));
        } else {
            wp_send_json_error(__('Error al crear la solicitud', 'probolsas-solicitudes'));
        }
    }
    
    public function ajax_obtener_solicitudes() {
        $this->verificar_nonce();
        
        global $wpdb;
        $tabla_procesos = $wpdb->prefix . 'probolsas_procesos';
        
        $where = "WHERE s.id IS NOT NULL";
        $params = array();
        
        if (!empty($_POST['proceso_id'])) {
            $where .= " AND s.proceso_id = %d";
            $params[] = intval($_POST['proceso_id']);
        }
        
        if (!empty($_POST['estado'])) {
            $where .= " AND s.estado = %d";
            $params[] = intval($_POST['estado']);
        }
        
        // Filtros de fecha
        if (!empty($_POST['semana'])) {
            $semana = $_POST['semana'];
            $year = substr($semana, 0, 4);
            $week = substr($semana, 6);
            
            $dto = new DateTime();
            $dto->setISODate($year, $week);
            $primer_dia = $dto->format('Y-m-d');
            $dto->modify('+6 days');
            $ultimo_dia = $dto->format('Y-m-d');
            
            $where .= " AND ((s.fecha_ejecucion != 'Por definirse' AND s.fecha_ejecucion BETWEEN %s AND %s) OR s.fecha_solicitud BETWEEN %s AND %s)";
            $params[] = $primer_dia;
            $params[] = $ultimo_dia;
            $params[] = $primer_dia;
            $params[] = $ultimo_dia;
        }
        
        if (!empty($_POST['mes'])) {
            $mes = $_POST['mes'];
            $where .= " AND ((s.fecha_ejecucion != 'Por definirse' AND DATE_FORMAT(s.fecha_ejecucion, '%%Y-%%m') = %s) OR DATE_FORMAT(s.fecha_solicitud, '%%Y-%%m') = %s)";
            $params[] = $mes;
            $params[] = $mes;
        }
        
        $sql = "SELECT s.*, p.nombre as proceso_nombre 
                FROM {$this->table_name} s 
                LEFT JOIN {$tabla_procesos} p ON s.proceso_id = p.id 
                {$where} 
                ORDER BY s.fecha_solicitud DESC";
        
        if (!empty($params)) {
            $solicitudes = $wpdb->get_results($wpdb->prepare($sql, $params));
        } else {
            $solicitudes = $wpdb->get_results($sql);
        }
        
        wp_send_json_success($solicitudes);
    }
    
    public function ajax_actualizar_estado() {
        $this->verificar_nonce();
        
        if (!current_user_can('edit_others_posts')) {
            wp_send_json_error(__('No tienes permisos para actualizar estados.', 'probolsas-solicitudes'));
        }
        
        global $wpdb;
        
        $solicitud_id = intval($_POST['solicitud_id']);
        $estado = intval($_POST['estado']);
        
        $resultado = $wpdb->update(
            $this->table_name,
            array('estado' => $estado),
            array('id' => $solicitud_id),
            array('%d'),
            array('%d')
        );
        
        if ($resultado !== false) {
            wp_send_json_success(__('Estado actualizado exitosamente', 'probolsas-solicitudes'));
        } else {
            wp_send_json_error(__('Error al actualizar el estado', 'probolsas-solicitudes'));
        }
    }
    
    public function ajax_actualizar_fecha() {
        $this->verificar_nonce();
        
        if (!current_user_can('edit_others_posts')) {
            wp_send_json_error(__('No tienes permisos para actualizar fechas.', 'probolsas-solicitudes'));
        }
        
        global $wpdb;
        
        $solicitud_id = intval($_POST['solicitud_id']);
        $fecha_ejecucion = sanitize_text_field($_POST['fecha_ejecucion']);
        
        $resultado = $wpdb->update(
            $this->table_name,
            array('fecha_ejecucion' => $fecha_ejecucion),
            array('id' => $solicitud_id),
            array('%s'),
            array('%d')
        );
        
        if ($resultado !== false) {
            wp_send_json_success(__('Fecha actualizada exitosamente', 'probolsas-solicitudes'));
        } else {
            wp_send_json_error(__('Error al actualizar la fecha', 'probolsas-solicitudes'));
        }
    }
    
    public function ajax_eliminar_solicitud() {
        $this->verificar_nonce();
        
        if (!current_user_can('delete_others_posts')) {
            wp_send_json_error(__('No tienes permisos para eliminar solicitudes.', 'probolsas-solicitudes'));
        }
        
        global $wpdb;
        
        $solicitud_id = intval($_POST['solicitud_id']);
        
        $resultado = $wpdb->delete(
            $this->table_name,
            array('id' => $solicitud_id),
            array('%d')
        );
        
        if ($resultado !== false) {
            wp_send_json_success(__('Solicitud eliminada exitosamente', 'probolsas-solicitudes'));
        } else {
            wp_send_json_error(__('Error al eliminar la solicitud', 'probolsas-solicitudes'));
        }
    }
    
    public function ajax_obtener_procesos() {
        $this->verificar_nonce();
        
        global $wpdb;
        $tabla_procesos = $wpdb->prefix . 'probolsas_procesos';
        
        $procesos = $wpdb->get_results(
            "SELECT * FROM {$tabla_procesos} WHERE activo = 1 ORDER BY orden ASC, nombre ASC"
        );
        
        wp_send_json_success($procesos);
    }
    
    public function ajax_crear_proceso() {
        $this->verificar_nonce();
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('No tienes permisos para crear procesos.', 'probolsas-solicitudes'));
        }
        
        global $wpdb;
        $tabla_procesos = $wpdb->prefix . 'probolsas_procesos';
        
        $nombre = sanitize_text_field($_POST['nombre']);
        $descripcion = sanitize_textarea_field($_POST['descripcion']);
        $orden = intval($_POST['orden']);
        
        $resultado = $wpdb->insert(
            $tabla_procesos,
            array(
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'orden' => $orden,
                'activo' => 1
            ),
            array('%s', '%s', '%d', '%d')
        );
        
        if ($resultado) {
            wp_send_json_success(__('Proceso creado exitosamente', 'probolsas-solicitudes'));
        } else {
            wp_send_json_error(__('Error al crear el proceso', 'probolsas-solicitudes'));
        }
    }
    
    public function ajax_actualizar_proceso() {
        $this->verificar_nonce();
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('No tienes permisos para actualizar procesos.', 'probolsas-solicitudes'));
        }
        
        global $wpdb;
        $tabla_procesos = $wpdb->prefix . 'probolsas_procesos';
        
        $proceso_id = intval($_POST['proceso_id']);
        $nombre = sanitize_text_field($_POST['nombre']);
        $descripcion = sanitize_textarea_field($_POST['descripcion']);
        $orden = intval($_POST['orden']);
        $activo = intval($_POST['activo']);
        
        $resultado = $wpdb->update(
            $tabla_procesos,
            array(
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'orden' => $orden,
                'activo' => $activo
            ),
            array('id' => $proceso_id),
            array('%s', '%s', '%d', '%d'),
            array('%d')
        );
        
        if ($resultado !== false) {
            wp_send_json_success(__('Proceso actualizado exitosamente', 'probolsas-solicitudes'));
        } else {
            wp_send_json_error(__('Error al actualizar el proceso', 'probolsas-solicitudes'));
        }
    }
    
    public function ajax_eliminar_proceso() {
        $this->verificar_nonce();
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('No tienes permisos para eliminar procesos.', 'probolsas-solicitudes'));
        }
        
        global $wpdb;
        $tabla_procesos = $wpdb->prefix . 'probolsas_procesos';
        
        $proceso_id = intval($_POST['proceso_id']);
        
        // Verificar si hay solicitudes asociadas
        $solicitudes_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE proceso_id = %d",
            $proceso_id
        ));
        
        if ($solicitudes_count > 0) {
            wp_send_json_error(__('No se puede eliminar el proceso porque tiene solicitudes asociadas.', 'probolsas-solicitudes'));
        }
        
        $resultado = $wpdb->delete(
            $tabla_procesos,
            array('id' => $proceso_id),
            array('%d')
        );
        
        if ($resultado !== false) {
            wp_send_json_success(__('Proceso eliminado exitosamente', 'probolsas-solicitudes'));
        } else {
            wp_send_json_error(__('Error al eliminar el proceso', 'probolsas-solicitudes'));
        }
    }
    
    private function verificar_nonce() {
        if (!wp_verify_nonce($_POST['nonce'], 'probolsas_solicitudes_nonce')) {
            wp_send_json_error(__('Token de seguridad inválido.', 'probolsas-solicitudes'));
            wp_die();
        }
    }
    
    public function verificar_permisos() {
        wp_send_json_error(__('Debes iniciar sesión para realizar esta acción.', 'probolsas-solicitudes'));
    }
    
    private function verificar_version_bd() {
        $version_instalada = get_option('probolsas_solicitudes_version');
        
        if (version_compare($version_instalada, PROBOLSAS_SOLICITUDES_VERSION, '<')) {
            $this->actualizar_base_datos();
            update_option('probolsas_solicitudes_version', PROBOLSAS_SOLICITUDES_VERSION);
        }
    }
    
    private function actualizar_base_datos() {
        // Aquí se pueden agregar actualizaciones futuras de la base de datos
        $this->crear_tablas(); // Re-ejecutar para asegurar estructura actualizada
    }
}

// Inicializar el plugin
new ProbolsasSolicitudes();