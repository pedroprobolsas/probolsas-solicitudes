<?php
/**
 * Página de administración de procesos
 * 
 * @package ProbolsasSolicitudes
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die(__('No tienes permisos para acceder a esta página.', 'probolsas-solicitudes'));
}

global $wpdb;
$tabla_procesos = $wpdb->prefix . 'probolsas_procesos';
$tabla_solicitudes = $wpdb->prefix . 'probolsas_solicitudes';

// Procesar acciones
if (isset($_POST['action']) && wp_verify_nonce($_POST['probolsas_nonce'], 'probolsas_admin')) {
    
    switch ($_POST['action']) {
        case 'crear_proceso':
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
                echo '<div class="notice notice-success"><p>' . __('Proceso creado exitosamente.', 'probolsas-solicitudes') . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . __('Error al crear el proceso.', 'probolsas-solicitudes') . '</p></div>';
            }
            break;
            
        case 'actualizar_proceso':
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
                echo '<div class="notice notice-success"><p>' . __('Proceso actualizado exitosamente.', 'probolsas-solicitudes') . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . __('Error al actualizar el proceso.', 'probolsas-solicitudes') . '</p></div>';
            }
            break;
            
        case 'eliminar_proceso':
            $proceso_id = intval($_POST['proceso_id']);
            
            // Verificar si hay solicitudes asociadas
            $solicitudes_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_solicitudes} WHERE proceso_id = %d",
                $proceso_id
            ));
            
            if ($solicitudes_count > 0) {
                echo '<div class="notice notice-error"><p>' . __('No se puede eliminar el proceso porque tiene solicitudes asociadas.', 'probolsas-solicitudes') . '</p></div>';
            } else {
                $resultado = $wpdb->delete(
                    $tabla_procesos,
                    array('id' => $proceso_id),
                    array('%d')
                );
                
                if ($resultado !== false) {
                    echo '<div class="notice notice-success"><p>' . __('Proceso eliminado exitosamente.', 'probolsas-solicitudes') . '</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>' . __('Error al eliminar el proceso.', 'probolsas-solicitudes') . '</p></div>';
                }
            }
            break;
    }
}

// Obtener proceso para editar
$proceso_editar = null;
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    $proceso_editar = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$tabla_procesos} WHERE id = %d",
        intval($_GET['editar'])
    ));
}

// Obtener todos los procesos
$procesos = $wpdb->get_results(
    "SELECT p.*, 
     (SELECT COUNT(*) FROM {$tabla_solicitudes} s WHERE s.proceso_id = p.id) as total_solicitudes
     FROM {$tabla_procesos} p 
     ORDER BY p.orden ASC, p.nombre ASC"
);
?>

<div class="wrap">
    <h1><?php _e('Gestión de Procesos - Probolsas', 'probolsas-solicitudes'); ?></h1>
    
    <div class="probolsas-admin-container">
        
        <!-- Formulario para crear/editar proceso -->
        <div class="postbox">
            <h2 class="hndle">
                <?php echo $proceso_editar ? __('Editar Proceso', 'probolsas-solicitudes') : __('Nuevo Proceso', 'probolsas-solicitudes'); ?>
            </h2>
            <div class="inside">
                <form method="post" action="">
                    <?php wp_nonce_field('probolsas_admin', 'probolsas_nonce'); ?>
                    <input type="hidden" name="action" value="<?php echo $proceso_editar ? 'actualizar_proceso' : 'crear_proceso'; ?>">
                    <?php if ($proceso_editar): ?>
                        <input type="hidden" name="proceso_id" value="<?php echo $proceso_editar->id; ?>">
                    <?php endif; ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="nombre"><?php _e('Nombre del Proceso', 'probolsas-solicitudes'); ?> *</label>
                            </th>
                            <td>
                                <input type="text" id="nombre" name="nombre" class="regular-text" 
                                       value="<?php echo $proceso_editar ? esc_attr($proceso_editar->nombre) : ''; ?>" required>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="descripcion"><?php _e('Descripción', 'probolsas-solicitudes'); ?></label>
                            </th>
                            <td>
                                <textarea id="descripcion" name="descripcion" rows="3" class="large-text"><?php echo $proceso_editar ? esc_textarea($proceso_editar->descripcion) : ''; ?></textarea>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="orden"><?php _e('Orden de visualización', 'probolsas-solicitudes'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="orden" name="orden" class="small-text" min="0" 
                                       value="<?php echo $proceso_editar ? $proceso_editar->orden : '0'; ?>">
                                <p class="description"><?php _e('Número que determina el orden de aparición en las listas.', 'probolsas-solicitudes'); ?></p>
                            </td>
                        </tr>
                        
                        <?php if ($proceso_editar): ?>
                        <tr>
                            <th scope="row">
                                <label for="activo"><?php _e('Estado', 'probolsas-solicitudes'); ?></label>
                            </th>
                            <td>
                                <select id="activo" name="activo">
                                    <option value="1" <?php selected($proceso_editar->activo, 1); ?>><?php _e('Activo', 'probolsas-solicitudes'); ?></option>
                                    <option value="0" <?php selected($proceso_editar->activo, 0); ?>><?php _e('Inactivo', 'probolsas-solicitudes'); ?></option>
                                </select>
                                <p class="description"><?php _e('Los procesos inactivos no aparecen en los formularios.', 'probolsas-solicitudes'); ?></p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" class="button-primary" 
                               value="<?php echo $proceso_editar ? __('Actualizar Proceso', 'probolsas-solicitudes') : __('Crear Proceso', 'probolsas-solicitudes'); ?>">
                        <?php if ($proceso_editar): ?>
                            <a href="<?php echo admin_url('admin.php?page=probolsas-procesos'); ?>" class="button"><?php _e('Cancelar', 'probolsas-solicitudes'); ?></a>
                        <?php endif; ?>
                    </p>
                </form>
            </div>
        </div>
        
        <!-- Lista de procesos existentes -->
        <div class="postbox">
            <h2 class="hndle"><?php _e('Procesos Existentes', 'probolsas-solicitudes'); ?></h2>
            <div class="inside">
                <?php if (empty($procesos)): ?>
                    <p><?php _e('No hay procesos creados aún.', 'probolsas-solicitudes'); ?></p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th scope="col" class="manage-column"><?php _e('ID', 'probolsas-solicitudes'); ?></th>
                                <th scope="col" class="manage-column"><?php _e('Nombre', 'probolsas-solicitudes'); ?></th>
                                <th scope="col" class="manage-column"><?php _e('Descripción', 'probolsas-solicitudes'); ?></th>
                                <th scope="col" class="manage-column"><?php _e('Estado', 'probolsas-solicitudes'); ?></th>
                                <th scope="col" class="manage-column"><?php _e('Orden', 'probolsas-solicitudes'); ?></th>
                                <th scope="col" class="manage-column"><?php _e('Solicitudes', 'probolsas-solicitudes'); ?></th>
                                <th scope="col" class="manage-column"><?php _e('Acciones', 'probolsas-solicitudes'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($procesos as $proceso): ?>
                                <tr class="<?php echo $proceso->activo ? '' : 'inactive'; ?>">
                                    <td><?php echo $proceso->id; ?></td>
                                    <td>
                                        <strong><?php echo esc_html($proceso->nombre); ?></strong>
                                        <?php if (!$proceso->activo): ?>
                                            <span class="post-state"><?php _e('(Inactivo)', 'probolsas-solicitudes'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html($proceso->descripcion); ?></td>
                                    <td>
                                        <?php if ($proceso->activo): ?>
                                            <span class="dashicons dashicons-yes-alt" style="color: green;" title="<?php _e('Activo', 'probolsas-solicitudes'); ?>"></span>
                                        <?php else: ?>
                                            <span class="dashicons dashicons-dismiss" style="color: red;" title="<?php _e('Inactivo', 'probolsas-solicitudes'); ?>"></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $proceso->orden; ?></td>
                                    <td>
                                        <?php if ($proceso->total_solicitudes > 0): ?>
                                            <strong><?php echo $proceso->total_solicitudes; ?></strong>
                                        <?php else: ?>
                                            <span class="description">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=probolsas-procesos&editar=' . $proceso->id); ?>" 
                                           class="button button-small"><?php _e('Editar', 'probolsas-solicitudes'); ?></a>
                                        
                                        <?php if ($proceso->total_solicitudes == 0): ?>
                                            <form method="post" style="display: inline-block;" 
                                                  onsubmit="return confirm('<?php _e('¿Estás seguro de eliminar este proceso?', 'probolsas-solicitudes'); ?>');">
                                                <?php wp_nonce_field('probolsas_admin', 'probolsas_nonce'); ?>
                                                <input type="hidden" name="action" value="eliminar_proceso">
                                                <input type="hidden" name="proceso_id" value="<?php echo $proceso->id; ?>">
                                                <input type="submit" class="button button-small button-link-delete" 
                                                       value="<?php _e('Eliminar', 'probolsas-solicitudes'); ?>">
                                            </form>
                                        <?php else: ?>
                                            <span class="description"><?php _e('No se puede eliminar (tiene solicitudes)', 'probolsas-solicitudes'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Información adicional -->
        <div class="postbox">
            <h2 class="hndle"><?php _e('Información', 'probolsas-solicitudes'); ?></h2>
            <div class="inside">
                <h4><?php _e('Gestión de Procesos', 'probolsas-solicitudes'); ?></h4>
                <ul>
                    <li><?php _e('Los procesos definen las áreas o departamentos que pueden hacer solicitudes.', 'probolsas-solicitudes'); ?></li>
                    <li><?php _e('Solo los procesos activos aparecen en los formularios de solicitud.', 'probolsas-solicitudes'); ?></li>
                    <li><?php _e('No se pueden eliminar procesos que tienen solicitudes asociadas.', 'probolsas-solicitudes'); ?></li>
                    <li><?php _e('El orden determina cómo aparecen los procesos en las listas desplegables.', 'probolsas-solicitudes'); ?></li>
                </ul>
                
                <h4><?php _e('Estadísticas Rápidas', 'probolsas-solicitudes'); ?></h4>
                <?php
                $stats = $wpdb->get_row("
                    SELECT 
                        COUNT(*) as total_procesos,
                        SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) as procesos_activos
                    FROM {$tabla_procesos}
                ");
                
                $total_solicitudes = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_solicitudes}");
                ?>
                <ul>
                    <li><strong><?php _e('Total de procesos:', 'probolsas-solicitudes'); ?></strong> <?php echo $stats->total_procesos; ?></li>
                    <li><strong><?php _e('Procesos activos:', 'probolsas-solicitudes'); ?></strong> <?php echo $stats->procesos_activos; ?></li>
                    <li><strong><?php _e('Total de solicitudes:', 'probolsas-solicitudes'); ?></strong> <?php echo $total_solicitudes; ?></li>
                </ul>
            </div>
        </div>
        
    </div>
</div>

<style>
.probolsas-admin-container .postbox {
    margin-bottom: 20px;
}

.probolsas-admin-container .inactive td {
    opacity: 0.6;
}

.probolsas-admin-container .form-table th {
    width: 200px;
}

.probolsas-admin-container .wp-list-table th,
.probolsas-admin-container .wp-list-table td {
    padding: 12px;
}

.probolsas-admin-container .button-small {
    font-size: 11px;
    height: auto;
    line-height: 1.5;
    padding: 2px 8px;
}

.probolsas-admin-container .description {
    font-style: italic;
    color: #666;
}
</style>