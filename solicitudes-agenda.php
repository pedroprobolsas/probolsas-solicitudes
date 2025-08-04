<?php
/**
 * Plugin Name: Solicitudes y Agenda de Acciones
 * Description: Módulo para gestionar solicitudes con vista de calendario y lista
 * Version: 2.0
 * Author: Antony Díaz
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class SolicitudesAgenda {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'crear_tablas'));
        add_action('wp_enqueue_scripts', array($this, 'cargar_scripts'));
        add_action('wp_ajax_crear_solicitud', array($this, 'ajax_crear_solicitud'));
        add_action('wp_ajax_obtener_solicitudes', array($this, 'ajax_obtener_solicitudes'));
        add_action('wp_ajax_actualizar_estado', array($this, 'ajax_actualizar_estado'));
        add_action('wp_ajax_actualizar_fecha_ejecucion', array($this, 'ajax_actualizar_fecha_ejecucion'));
        add_action('wp_ajax_eliminar_solicitud', array($this, 'ajax_eliminar_solicitud'));
        add_shortcode('solicitudes_agenda', array($this, 'mostrar_interfaz'));
    }
    
    public function init() {
        // Inicialización del plugin
    }
    
    public function crear_tablas() {
        global $wpdb;
        
        $tabla_solicitudes = $wpdb->prefix . 'solicitudes_agenda';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $tabla_solicitudes (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            lider_proceso varchar(100) NOT NULL,
            solicitud text NOT NULL,
            fecha_solicitud date NOT NULL,
            fecha_ejecucion varchar(50) DEFAULT 'Por definirse',
            estado int(1) DEFAULT 1,
            usuario_id int(11),
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function cargar_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/ui-lightness/jquery-ui.css');
        
        // Cargar Font Awesome
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');
        
        wp_enqueue_script('solicitudes-js', plugin_dir_url(__FILE__) . 'solicitudes.js', array('jquery'), '1.0', true);
        wp_enqueue_style('solicitudes-css', plugin_dir_url(__FILE__) . 'solicitudes.css', array(), '1.0');
        
        wp_localize_script('solicitudes-js', 'solicitudes_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('solicitudes_nonce')
        ));
    }
    
    public function mostrar_interfaz() {
        
        ob_start();
        ?>
        <div class="header-container">
            <h1>Gestión de Solicitudes para el SGC</h1>
        </div>

        <div id="solicitudes-container">
            <!-- Navegación entre vistas -->
            <div class="solicitudes-nav">
                <button id="btn-lista" class="nav-btn active">Vista Lista</button>
                <button id="btn-calendario" class="nav-btn">Ver Calendario</button>
                <button id="btn-nueva-solicitud" class="btn-primary">Nueva Solicitud</button>
            </div>
            
            <!-- Vista de Lista -->
                <div id="vista-lista" class="vista-container">
                    <div class="filtros-container">
                        <div class="filtro-grupo">
                            <label for="filtro-proceso">Filtrar por proceso:</label>
                            <select id="filtro-proceso">
                                <option value="">Todos los procesos</option>
                                <option value="Gerencial">Gerencial</option>
                                <option value="Logística">Logística</option>
                                <option value="Producción">Producción</option>
                                <option value="Calidad">Calidad</option>
                                <option value="Atención al Cliente">Atención al Cliente</option>
                                <option value="Marketing">Marketing</option>
                                <option value="Talento Humano">Talento Humano</option>
                                <option value="Mantenimiento e Infraestructura">Mantenimiento e Infraestructura</option>
                                <option value="Contabilidad">Contabilidad</option>
                                <option value="Presupuesto y Compras">Presupuesto y Compras</option>
                            </select>
                        </div>
                        
                        <div class="filtro-grupo">
                            <label for="filtro-estado">Filtrar por estado:</label>
                            <select id="filtro-estado">
                                <option value="">Todos los estados</option>
                                <option value="1">Solicitado</option>
                                <option value="2">En proceso</option>
                                <option value="3">Aprobado</option>
                            </select>
                        </div>
                        
                        <div class="filtro-grupo">
                            <label for="filtro-semana">Filtrar por semana:</label>
                            <input type="week" id="filtro-semana" placeholder="Seleccionar semana">
                        </div>
                        
                        <div class="filtro-grupo">
                            <label for="filtro-mes">Filtrar por mes:</label>
                            <input type="month" id="filtro-mes" placeholder="Seleccionar mes">
                        </div>
                        
                        <div class="filtro-grupo">
                            <button id="btn-filtrar">Aplicar Filtros</button>
                            <button id="btn-limpiar-filtros" class="btn-secondary">Limpiar</button>
                        </div>
                    </div>
                    
                    <!-- Contenedor con scroll horizontal para la tabla -->
                    <div class="tabla-scroll-wrapper">
                        <table id="tabla-solicitudes">
                            <thead>
                                <tr>
                                    <th>Líder de Proceso</th>
                                    <th>Fecha Solicitud</th>
                                    <th>Fecha Ejecución</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Se carga dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                </div>
            
            <!-- Vista de Calendario -->
            <div id="vista-calendario" class="vista-container" style="display:none;">
                <div id="calendario-solicitudes"></div>
                <div class="leyenda-calendario">
                    <div class="leyenda-item">
                        <span class="color-proceso"></span> En proceso
                    </div>
                    <div class="leyenda-item">
                        <span class="color-aprobado"></span> Aprobado
                    </div>
                </div>
            </div>
            
            <!-- Modal Nueva Solicitud -->
            <div id="modal-nueva-solicitud" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2>Nueva Solicitud</h2>
                    <form id="form-nueva-solicitud">
                        <div class="form-group">
                            <label for="lider-proceso">Líder de Proceso que solicita *</label>
                            <select id="lider-proceso" name="lider_proceso" required>
                                <option value="" selected disabled hidden>Seleccionar proceso</option>
                                <option value="Gerencial">Gerencial</option>
                                <option value="Logística">Logística</option>
                                <option value="Producción">Producción</option>
                                <option value="Calidad">Calidad</option>
                                <option value="Atención al Cliente">Atención al Cliente</option>
                                <option value="Marketing">Marketing</option>
                                <option value="Talento Humano">Talento Humano</option>
                                <option value="Mantenimiento e Infraestructura">Mantenimiento e Infraestructura</option>
                                <option value="Contabilidad">Contabilidad</option>
                                <option value="Presupuesto y Compras">Presupuesto y Compras</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="descripcion-solicitud">Describe tu solicitud *</label>
                            <textarea id="descripcion-solicitud" name="solicitud" placeholder="Ej: Se requiere la revisión y actualización del Procedimiento de Compras para el Crysol" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="fecha-solicitud">Fecha de la solicitud</label>
                            <input type="date" id="fecha-solicitud" name="fecha_solicitud" readonly>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-primary">Guardar Solicitud</button>
                            <button type="button" class="btn-secondary" id="btn-cancelar">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Modal Editar Estado -->
            <div id="modal-editar-estado" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2>Actualizar estado de la solicitud</h2>
                    <form id="form-editar-estado">
                        <input type="hidden" id="solicitud-id" name="solicitud_id">
                        <div class="form-group">
                            <label for="nuevo-estado">Estado actual</label>
                            <select id="nuevo-estado" name="estado" required>
                                <option value="1">Solicitado</option>
                                <option value="2">En proceso</option>
                                <option value="3">Aprobado</option>
                            </select>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-primary">Guardar</button>
                            <button type="button" class="btn-secondary" id="btn-cancelar-estado">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Modal Editar Fecha Ejecución -->
            <div id="modal-editar-fecha" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2>Editar fecha de ejecución</h2>
                    <form id="form-editar-fecha">
                        <input type="hidden" id="solicitud-id-fecha" name="solicitud_id">
                        <div class="form-group">
                            <label for="nueva-fecha-ejecucion">Fecha de ejecución</label>
                            <input type="date" id="nueva-fecha-ejecucion" name="fecha_ejecucion" required>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-primary">Guardar</button>
                            <button type="button" class="btn-secondary" id="btn-cancelar-fecha">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Modal Confirmación Eliminar -->
            <div id="modal-confirmar-eliminar" class="modal">
                <div class="modal-content modal-small">
                    <span class="close">&times;</span>
                    <h2>Confirmar eliminación</h2>
                    <div class="mensaje-confirmacion">
                        <p>¿Estás seguro de que quieres eliminar esta solicitud?</p>
                        <p><strong>Esta acción es irreversible.</strong></p>
                    </div>
                    <input type="hidden" id="solicitud-eliminar-id">
                    <div class="form-actions">
                        <button id="btn-confirmar-eliminar" class="btn-danger">Sí, eliminar</button>
                        <button id="btn-cancelar-eliminar" class="btn-secondary">Cancelar</button>
                    </div>
                </div>
            </div>
        </div>
        
        <?php
        return ob_get_clean();
    }
    
    public function ajax_crear_solicitud() {
        check_ajax_referer('solicitudes_nonce', 'nonce');
        
        global $wpdb;
        
        $lider_proceso = sanitize_text_field($_POST['lider_proceso']);
        $solicitud = sanitize_textarea_field($_POST['solicitud']);
        $fecha_solicitud = sanitize_text_field($_POST['fecha_solicitud']);
        
        $tabla = $wpdb->prefix . 'solicitudes_agenda';
        
        $resultado = $wpdb->insert(
            $tabla,
            array(
                'lider_proceso' => $lider_proceso,
                'solicitud' => $solicitud,
                'fecha_solicitud' => $fecha_solicitud,
                'fecha_ejecucion' => 'Por definirse',
                'estado' => 1,
                'usuario_id' => get_current_user_id()
            ),
            array('%s', '%s', '%s', '%s', '%d', '%d')
        );
        
        if ($resultado) {
            wp_send_json_success('Solicitud created successfully');
        } else {
            wp_send_json_error('Error creating solicitud');
        }
    }
    
    public function ajax_obtener_solicitudes() {
        check_ajax_referer('solicitudes_nonce', 'nonce');
        
        global $wpdb;
        
        $tabla = $wpdb->prefix . 'solicitudes_agenda';
        
        $where = "WHERE 1=1";
        $params = array();
        
        if (!empty($_POST['proceso'])) {
            $where .= " AND lider_proceso = %s";
            $params[] = $_POST['proceso'];
        }
        
        if (!empty($_POST['estado'])) {
            $where .= " AND estado = %d";
            $params[] = intval($_POST['estado']);
        }
        
        // Filtro por semana
        if (!empty($_POST['semana'])) {
            $semana = $_POST['semana']; // Formato: 2024-W35
            $year = substr($semana, 0, 4);
            $week = substr($semana, 6);
            
            // Calcular primer y último día de la semana
            $dto = new DateTime();
            $dto->setISODate($year, $week);
            $primer_dia = $dto->format('Y-m-d');
            $dto->modify('+6 days');
            $ultimo_dia = $dto->format('Y-m-d');
            
            $where .= " AND ((fecha_ejecucion != 'Por definirse' AND fecha_ejecucion BETWEEN %s AND %s) OR fecha_solicitud BETWEEN %s AND %s)";
            $params[] = $primer_dia;
            $params[] = $ultimo_dia;
            $params[] = $primer_dia;
            $params[] = $ultimo_dia;
        }
        
        // Filtro por mes
        if (!empty($_POST['mes'])) {
            $mes = $_POST['mes']; // Formato: 2024-08
            $where .= " AND ((fecha_ejecucion != 'Por definirse' AND DATE_FORMAT(fecha_ejecucion, '%%Y-%%m') = %s) OR DATE_FORMAT(fecha_solicitud, '%%Y-%%m') = %s)";
            $params[] = $mes;
            $params[] = $mes;
        }
        
        $sql = "SELECT * FROM $tabla $where ORDER BY fecha_solicitud DESC";
        
        if (!empty($params)) {
            $solicitudes = $wpdb->get_results($wpdb->prepare($sql, $params));
        } else {
            $solicitudes = $wpdb->get_results($sql);
        }
        
        wp_send_json_success($solicitudes);
    }
    
    public function ajax_actualizar_estado() {
        check_ajax_referer('solicitudes_nonce', 'nonce');
        
        global $wpdb;
        
        $solicitud_id = intval($_POST['solicitud_id']);
        $estado = intval($_POST['estado']);
        
        $tabla = $wpdb->prefix . 'solicitudes_agenda';
        
        $resultado = $wpdb->update(
            $tabla,
            array('estado' => $estado),
            array('id' => $solicitud_id),
            array('%d'),
            array('%d')
        );
        
        if ($resultado !== false) {
            wp_send_json_success('Estado actualizado');
        } else {
            wp_send_json_error('Error actualizando estado');
        }
    }
    
    public function ajax_actualizar_fecha_ejecucion() {
        check_ajax_referer('solicitudes_nonce', 'nonce');
        
        global $wpdb;
        
        $solicitud_id = intval($_POST['solicitud_id']);
        $fecha_ejecucion = sanitize_text_field($_POST['fecha_ejecucion']);
        
        $tabla = $wpdb->prefix . 'solicitudes_agenda';
        
        // Primero obtenemos el estado actual de la solicitud
        $solicitud_actual = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $solicitud_id
        ));
        
        if (!$solicitud_actual) {
            wp_send_json_error('Solicitud no encontrada');
            return;
        }
        
        // Preparar datos para actualizar
        $datos_actualizar = array('fecha_ejecucion' => $fecha_ejecucion);
        $tipos_datos = array('%s');
        
        // Lógica de cambio automático de estado:
        // Si la fecha cambia de "Por definirse" a una fecha válida Y el estado actual es 1 (Solicitado)
        // entonces cambiar el estado a 2 (En proceso)
        if ($solicitud_actual->fecha_ejecucion === 'Por definirse' && 
            $fecha_ejecucion !== 'Por definirse' && 
            $this->es_fecha_valida($fecha_ejecucion) && 
            intval($solicitud_actual->estado) === 1) {
            
            $datos_actualizar['estado'] = 2;
            $tipos_datos[] = '%d';
        }
        
        $resultado = $wpdb->update(
            $tabla,
            $datos_actualizar,
            array('id' => $solicitud_id),
            $tipos_datos,
            array('%d')
        );
        
        if ($resultado !== false) {
            $mensaje = 'Fecha actualizada';
            if (isset($datos_actualizar['estado'])) {
                $mensaje .= ' y estado cambiado automáticamente a "En proceso"';
            }
            wp_send_json_success($mensaje);
        } else {
            wp_send_json_error('Error actualizando fecha');
        }
    }
    
    /**
     * Función auxiliar para validar si una fecha está en formato válido YYYY-MM-DD
     */
    private function es_fecha_valida($fecha) {
        if (empty($fecha) || $fecha === 'Por definirse') {
            return false;
        }
        
        $d = DateTime::createFromFormat('Y-m-d', $fecha);
        return $d && $d->format('Y-m-d') === $fecha;
    }
    
    public function ajax_eliminar_solicitud() {
        check_ajax_referer('solicitudes_nonce', 'nonce');
        
        global $wpdb;
        
        $solicitud_id = intval($_POST['solicitud_id']);
        
        $tabla = $wpdb->prefix . 'solicitudes_agenda';
        
        $resultado = $wpdb->delete(
            $tabla,
            array('id' => $solicitud_id),
            array('%d')
        );
        
        if ($resultado !== false) {
            wp_send_json_success('Solicitud eliminada exitosamente');
        } else {
            wp_send_json_error('Error eliminando la solicitud');
        }
    }
}

new SolicitudesAgenda();