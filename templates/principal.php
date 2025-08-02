<?php
/**
 * Template: Interfaz Principal de Solicitudes
 * 
 * @package ProbolsasSolicitudes
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

$es_admin = current_user_can('manage_options');
$puede_editar = current_user_can('edit_others_posts');
?>

<div id="probolsas-solicitudes-container">
    
    <!-- Navegación entre vistas -->
    <div class="probolsas-nav">
        <div class="nav-buttons">
            <button id="btn-lista" class="nav-btn active">
                <span class="dashicons dashicons-list-view"></span>
                <?php _e('Vista Lista', 'probolsas-solicitudes'); ?>
            </button>
            <button id="btn-calendario" class="nav-btn">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php _e('Ver Calendario', 'probolsas-solicitudes'); ?>
            </button>
        </div>
        <div class="nav-actions">
            <?php if ($es_admin): ?>
            <button id="btn-gestionar-procesos" class="btn-secondary">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php _e('Gestionar Procesos', 'probolsas-solicitudes'); ?>
            </button>
            <?php endif; ?>
            <button id="btn-nueva-solicitud" class="btn-primary">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php _e('Nueva Solicitud', 'probolsas-solicitudes'); ?>
            </button>
        </div>
    </div>
    
    <!-- Vista de Lista -->
    <div id="vista-lista" class="vista-container">
        <div class="filtros-container">
            <div class="filtros-row">
                <div class="filtro-grupo">
                    <label for="filtro-proceso"><?php _e('Filtrar por proceso:', 'probolsas-solicitudes'); ?></label>
                    <select id="filtro-proceso">
                        <option value=""><?php _e('Todos los procesos', 'probolsas-solicitudes'); ?></option>
                        <!-- Se carga dinámicamente -->
                    </select>
                </div>
                
                <div class="filtro-grupo">
                    <label for="filtro-estado"><?php _e('Filtrar por estado:', 'probolsas-solicitudes'); ?></label>
                    <select id="filtro-estado">
                        <option value=""><?php _e('Todos los estados', 'probolsas-solicitudes'); ?></option>
                        <option value="1"><?php _e('Solicitado', 'probolsas-solicitudes'); ?></option>
                        <option value="2"><?php _e('En proceso', 'probolsas-solicitudes'); ?></option>
                        <option value="3"><?php _e('Aprobado', 'probolsas-solicitudes'); ?></option>
                    </select>
                </div>
                
                <div class="filtro-grupo">
                    <label for="filtro-semana"><?php _e('Filtrar por semana:', 'probolsas-solicitudes'); ?></label>
                    <input type="week" id="filtro-semana" placeholder="<?php _e('Seleccionar semana', 'probolsas-solicitudes'); ?>">
                </div>
                
                <div class="filtro-grupo">
                    <label for="filtro-mes"><?php _e('Filtrar por mes:', 'probolsas-solicitudes'); ?></label>
                    <input type="month" id="filtro-mes" placeholder="<?php _e('Seleccionar mes', 'probolsas-solicitudes'); ?>">
                </div>
                
                <div class="filtro-grupo filtro-acciones">
                    <button id="btn-filtrar" class="btn-success">
                        <span class="dashicons dashicons-search"></span>
                        <?php _e('Aplicar Filtros', 'probolsas-solicitudes'); ?>
                    </button>
                    <button id="btn-limpiar-filtros" class="btn-secondary">
                        <span class="dashicons dashicons-dismiss"></span>
                        <?php _e('Limpiar', 'probolsas-solicitudes'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <div class="tabla-container">
            <table id="tabla-solicitudes" class="probolsas-table">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'probolsas-solicitudes'); ?></th>
                        <th><?php _e('Proceso', 'probolsas-solicitudes'); ?></th>
                        <th><?php _e('Solicitud', 'probolsas-solicitudes'); ?></th>
                        <th><?php _e('Fecha Solicitud', 'probolsas-solicitudes'); ?></th>
                        <th><?php _e('Fecha Ejecución', 'probolsas-solicitudes'); ?></th>
                        <th><?php _e('Estado', 'probolsas-solicitudes'); ?></th>
                        <th><?php _e('Acciones', 'probolsas-solicitudes'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7" class="loading-row">
                            <div class="loading-spinner">
                                <span class="dashicons dashicons-update spin"></span>
                                <?php _e('Cargando solicitudes...', 'probolsas-solicitudes'); ?>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Vista de Calendario -->
    <div id="vista-calendario" class="vista-container" style="display:none;">
        <div id="calendario-solicitudes"></div>
        <div class="leyenda-calendario">
            <h4><?php _e('Leyenda de Estados:', 'probolsas-solicitudes'); ?></h4>
            <div class="leyenda-items">
                <div class="leyenda-item">
                    <span class="color-solicitado"></span>
                    <?php _e('Solicitado', 'probolsas-solicitudes'); ?>
                </div>
                <div class="leyenda-item">
                    <span class="color-proceso"></span>
                    <?php _e('En proceso', 'probolsas-solicitudes'); ?>
                </div>
                <div class="leyenda-item">
                    <span class="color-aprobado"></span>
                    <?php _e('Aprobado', 'probolsas-solicitudes'); ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Nueva Solicitud -->
    <div id="modal-nueva-solicitud" class="probolsas-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><?php _e('Nueva Solicitud', 'probolsas-solicitudes'); ?></h2>
                <span class="close">&times;</span>
            </div>
            <form id="form-nueva-solicitud">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="proceso-id"><?php _e('Proceso que solicita', 'probolsas-solicitudes'); ?> *</label>
                        <select id="proceso-id" name="proceso_id" required>
                            <option value=""><?php _e('Seleccionar proceso', 'probolsas-solicitudes'); ?></option>
                            <!-- Se carga dinámicamente -->
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion-solicitud"><?php _e('Describe tu solicitud', 'probolsas-solicitudes'); ?> *</label>
                        <textarea id="descripcion-solicitud" name="solicitud" 
                                placeholder="<?php _e('Ej: Se requiere la revisión y actualización del Procedimiento de Compras para el Crysol', 'probolsas-solicitudes'); ?>" 
                                required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha-solicitud"><?php _e('Fecha de la solicitud', 'probolsas-solicitudes'); ?></label>
                        <input type="date" id="fecha-solicitud" name="fecha_solicitud" readonly>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="submit" class="btn-primary">
                        <span class="dashicons dashicons-saved"></span>
                        <?php _e('Guardar Solicitud', 'probolsas-solicitudes'); ?>
                    </button>
                    <button type="button" class="btn-secondary" id="btn-cancelar">
                        <?php _e('Cancelar', 'probolsas-solicitudes'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <?php if ($puede_editar): ?>
    <!-- Modal Editar Estado -->
    <div id="modal-editar-estado" class="probolsas-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><?php _e('Actualizar estado de la solicitud', 'probolsas-solicitudes'); ?></h2>
                <span class="close">&times;</span>
            </div>
            <form id="form-editar-estado">
                <input type="hidden" id="solicitud-id" name="solicitud_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="nuevo-estado"><?php _e('Estado actual', 'probolsas-solicitudes'); ?></label>
                        <select id="nuevo-estado" name="estado" required>
                            <option value="1"><?php _e('Solicitado', 'probolsas-solicitudes'); ?></option>
                            <option value="2"><?php _e('En proceso', 'probolsas-solicitudes'); ?></option>
                            <option value="3"><?php _e('Aprobado', 'probolsas-solicitudes'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-primary">
                        <?php _e('Guardar', 'probolsas-solicitudes'); ?>
                    </button>
                    <button type="button" class="btn-secondary" id="btn-cancelar-estado">
                        <?php _e('Cancelar', 'probolsas-solicitudes'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Editar Fecha Ejecución -->
    <div id="modal-editar-fecha" class="probolsas-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><?php _e('Editar fecha de ejecución', 'probolsas-solicitudes'); ?></h2>
                <span class="close">&times;</span>
            </div>
            <form id="form-editar-fecha">
                <input type="hidden" id="solicitud-id-fecha" name="solicitud_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="nueva-fecha-ejecucion"><?php _e('Fecha de ejecución', 'probolsas-solicitudes'); ?></label>
                        <input type="date" id="nueva-fecha-ejecucion" name="fecha_ejecucion" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-primary">
                        <?php _e('Guardar', 'probolsas-solicitudes'); ?>
                    </button>
                    <button type="button" class="btn-secondary" id="btn-cancelar-fecha">
                        <?php _e('Cancelar', 'probolsas-solicitudes'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Confirmación Eliminar -->
    <div id="modal-confirmar-eliminar" class="probolsas-modal">
        <div class="modal-content modal-small">
            <div class="modal-header">
                <h2><?php _e('Confirmar eliminación', 'probolsas-solicitudes'); ?></h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div class="mensaje-confirmacion">
                    <div class="icon-warning">
                        <span class="dashicons dashicons-warning"></span>
                    </div>
                    <p><?php _e('¿Estás seguro de que quieres eliminar esta solicitud?', 'probolsas-solicitudes'); ?></p>
                    <p class="strong"><?php _e('Esta acción es irreversible.', 'probolsas-solicitudes'); ?></p>
                </div>
                <input type="hidden" id="solicitud-eliminar-id">
            </div>
            <div class="modal-footer">
                <button id="btn-confirmar-eliminar" class="btn-danger">
                    <span class="dashicons dashicons-trash"></span>
                    <?php _e('Sí, eliminar', 'probolsas-solicitudes'); ?>
                </button>
                <button id="btn-cancelar-eliminar" class="btn-secondary">
                    <?php _e('Cancelar', 'probolsas-solicitudes'); ?>
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($es_admin): ?>
    <!-- Modal Gestión de Procesos -->
    <div id="modal-gestionar-procesos" class="probolsas-modal modal-large">
        <div class="modal-content">
            <div class="modal-header">
                <h2><?php _e('Gestión de Procesos', 'probolsas-solicitudes'); ?></h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div class="procesos-actions">
                    <button id="btn-nuevo-proceso" class="btn-primary">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php _e('Nuevo Proceso', 'probolsas-solicitudes'); ?>
                    </button>
                </div>
                <div class="procesos-lista">
                    <table id="tabla-procesos" class="probolsas-table">
                        <thead>
                            <tr>
                                <th><?php _e('ID', 'probolsas-solicitudes'); ?></th>
                                <th><?php _e('Nombre', 'probolsas-solicitudes'); ?></th>
                                <th><?php _e('Descripción', 'probolsas-solicitudes'); ?></th>
                                <th><?php _e('Estado', 'probolsas-solicitudes'); ?></th>
                                <th><?php _e('Orden', 'probolsas-solicitudes'); ?></th>
                                <th><?php _e('Acciones', 'probolsas-solicitudes'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Se carga dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="btn-cerrar-procesos">
                    <?php _e('Cerrar', 'probolsas-solicitudes'); ?>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal Nuevo/Editar Proceso -->
    <div id="modal-proceso" class="probolsas-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="titulo-modal-proceso"><?php _e('Nuevo Proceso', 'probolsas-solicitudes'); ?></h2>
                <span class="close">&times;</span>
            </div>
            <form id="form-proceso">
                <input type="hidden" id="proceso-id-editar" name="proceso_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="proceso-nombre"><?php _e('Nombre del proceso', 'probolsas-solicitudes'); ?> *</label>
                        <input type="text" id="proceso-nombre" name="nombre" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="proceso-descripcion"><?php _e('Descripción', 'probolsas-solicitudes'); ?></label>
                        <textarea id="proceso-descripcion" name="descripcion"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="proceso-orden"><?php _e('Orden', 'probolsas-solicitudes'); ?></label>
                            <input type="number" id="proceso-orden" name="orden" min="0" value="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="proceso-activo"><?php _e('Estado', 'probolsas-solicitudes'); ?></label>
                            <select id="proceso-activo" name="activo">
                                <option value="1"><?php _e('Activo', 'probolsas-solicitudes'); ?></option>
                                <option value="0"><?php _e('Inactivo', 'probolsas-solicitudes'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="submit" class="btn-primary">
                        <span class="dashicons dashicons-saved"></span>
                        <?php _e('Guardar Proceso', 'probolsas-solicitudes'); ?>
                    </button>
                    <button type="button" class="btn-secondary" id="btn-cancelar-proceso">
                        <?php _e('Cancelar', 'probolsas-solicitudes'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Contenedor de notificaciones -->
    <div id="probolsas-notifications"></div>
</div>