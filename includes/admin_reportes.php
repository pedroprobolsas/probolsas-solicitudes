<?php
/**
 * Página de reportes y estadísticas
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

// Obtener estadísticas generales
$stats_generales = $wpdb->get_row("
    SELECT 
        COUNT(*) as total_solicitudes,
        SUM(CASE WHEN estado = 1 THEN 1 ELSE 0 END) as solicitadas,
        SUM(CASE WHEN estado = 2 THEN 1 ELSE 0 END) as en_proceso,
        SUM(CASE WHEN estado = 3 THEN 1 ELSE 0 END) as aprobadas,
        SUM(CASE WHEN fecha_ejecucion != 'Por definirse' THEN 1 ELSE 0 END) as con_fecha
    FROM {$tabla_solicitudes}
");

// Estadísticas por proceso
$stats_por_proceso = $wpdb->get_results("
    SELECT 
        p.nombre as proceso_nombre,
        COUNT(s.id) as total_solicitudes,
        SUM(CASE WHEN s.estado = 1 THEN 1 ELSE 0 END) as solicitadas,
        SUM(CASE WHEN s.estado = 2 THEN 1 ELSE 0 END) as en_proceso,
        SUM(CASE WHEN s.estado = 3 THEN 1 ELSE 0 END) as aprobadas
    FROM {$tabla_procesos} p
    LEFT JOIN {$tabla_solicitudes} s ON p.id = s.proceso_id
    WHERE p.activo = 1
    GROUP BY p.id, p.nombre
    ORDER BY total_solicitudes DESC
");

// Estadísticas por mes
$stats_por_mes = $wpdb->get_results("
    SELECT 
        DATE_FORMAT(fecha_solicitud, '%Y-%m') as mes,
        COUNT(*) as total_solicitudes,
        SUM(CASE WHEN estado = 3 THEN 1 ELSE 0 END) as aprobadas
    FROM {$tabla_solicitudes}
    WHERE fecha_solicitud >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(fecha_solicitud, '%Y-%m')
    ORDER BY mes DESC
    LIMIT 12
");

// Solicitudes recientes
$solicitudes_recientes = $wpdb->get_results("
    SELECT 
        s.*,
        p.nombre as proceso_nombre,
        u.display_name as usuario_nombre
    FROM {$tabla_solicitudes} s
    LEFT JOIN {$tabla_procesos} p ON s.proceso_id = p.id
    LEFT JOIN {$wpdb->users} u ON s.usuario_id = u.ID
    ORDER BY s.fecha_creacion DESC
    LIMIT 10
");

// Procesamiento de exportación
if (isset($_GET['export']) && wp_verify_nonce($_GET['nonce'], 'probolsas_export')) {
    $tipo_export = $_GET['export'];
    
    switch ($tipo_export) {
        case 'solicitudes_csv':
            exportar_solicitudes_csv();
            break;
        case 'reportes_pdf':
            // Funcionalidad para PDF (requiere librería adicional)
            break;
    }
}

function exportar_solicitudes_csv() {
    global $wpdb, $tabla_solicitudes, $tabla_procesos;
    
    $solicitudes = $wpdb->get_results("
        SELECT 
            s.*,
            p.nombre as proceso_nombre
        FROM {$tabla_solicitudes} s
        LEFT JOIN {$tabla_procesos} p ON s.proceso_id = p.id
        ORDER BY s.fecha_solicitud DESC
    ");
    
    $filename = 'solicitudes_probolsas_' . date('Y-m-d') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    
    $output = fopen('php://output', 'w');
    
    // Encabezados CSV
    fputcsv($output, array(
        'ID',
        'Proceso',
        'Solicitud',
        'Fecha Solicitud',
        'Fecha Ejecución',
        'Estado',
        'Fecha Creación'
    ));
    
    // Datos
    foreach ($solicitudes as $solicitud) {
        $estado_texto = '';
        switch ($solicitud->estado) {
            case 1: $estado_texto = 'Solicitado'; break;
            case 2: $estado_texto = 'En proceso'; break;
            case 3: $estado_texto = 'Aprobado'; break;
        }
        
        fputcsv($output, array(
            $solicitud->id,
            $solicitud->proceso_nombre,
            $solicitud->solicitud,
            $solicitud->fecha_solicitud,
            $solicitud->fecha_ejecucion,
            $estado_texto,
            $solicitud->fecha_creacion
        ));
    }
    
    fclose($output);
    exit;
}

function obtener_color_estado($estado) {
    switch ($estado) {
        case 1: return '#ffebee'; // Solicitado - Rojo claro
        case 2: return '#fff3e0'; // En proceso - Naranja claro  
        case 3: return '#e8f5e8'; // Aprobado - Verde claro
        default: return '#f5f5f5';
    }
}
?>

<div class="wrap">
    <h1><?php _e('Reportes y Estadísticas - Probolsas', 'probolsas-solicitudes'); ?></h1>
    
    <div class="probolsas-reportes-container">
        
        <!-- Estadísticas Generales -->
        <div class="postbox">
            <h2 class="hndle"><?php _e('Resumen General', 'probolsas-solicitudes'); ?></h2>
            <div class="inside">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats_generales->total_solicitudes; ?></div>
                        <div class="stat-label"><?php _e('Total Solicitudes', 'probolsas-solicitudes'); ?></div>
                    </div>
                    <div class="stat-card stat-solicitado">
                        <div class="stat-number"><?php echo $stats_generales->solicitadas; ?></div>
                        <div class="stat-label"><?php _e('Solicitadas', 'probolsas-solicitudes'); ?></div>
                    </div>
                    <div class="stat-card stat-proceso">
                        <div class="stat-number"><?php echo $stats_generales->en_proceso; ?></div>
                        <div class="stat-label"><?php _e('En Proceso', 'probolsas-solicitudes'); ?></div>
                    </div>
                    <div class="stat-card stat-aprobado">
                        <div class="stat-number"><?php echo $stats_generales->aprobadas; ?></div>
                        <div class="stat-label"><?php _e('Aprobadas', 'probolsas-solicitudes'); ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats_generales->con_fecha; ?></div>
                        <div class="stat-label"><?php _e('Con Fecha Definida', 'probolsas-solicitudes'); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Gráfico de Estados -->
        <div class="postbox">
            <h2 class="hndle"><?php _e('Distribución por Estados', 'probolsas-solicitudes'); ?></h2>
            <div class="inside">
                <canvas id="grafico-estados" width="400" height="200"></canvas>
            </div>
        </div>
        
        <!-- Estadísticas por Proceso -->
        <div class="postbox">
            <h2 class="hndle"><?php _e('Estadísticas por Proceso', 'probolsas-solicitudes'); ?></h2>
            <div class="inside">
                <?php if (empty($stats_por_proceso)): ?>
                    <p><?php _e('No hay datos disponibles.', 'probolsas-solicitudes'); ?></p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Proceso', 'probolsas-solicitudes'); ?></th>
                                <th><?php _e('Total', 'probolsas-solicitudes'); ?></th>
                                <th><?php _e('Solicitadas', 'probolsas-solicitudes'); ?></th>
                                <th><?php _e('En Proceso', 'probolsas-solicitudes'); ?></th>
                                <th><?php _e('Aprobadas', 'probolsas-solicitudes'); ?></th>
                                <th><?php _e('% Aprobación', 'probolsas-solicitudes'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats_por_proceso as $stat): ?>
                                <?php 
                                $porcentaje_aprobacion = $stat->total_solicitudes > 0 
                                    ? round(($stat->aprobadas / $stat->total_solicitudes) * 100, 1) 
                                    : 0;
                                ?>
                                <tr>
                                    <td><strong><?php echo esc_html($stat->proceso_nombre); ?></strong></td>
                                    <td><?php echo $stat->total_solicitudes; ?></td>
                                    <td><?php echo $stat->solicitadas; ?></td>
                                    <td><?php echo $stat->en_proceso; ?></td>
                                    <td><?php echo $stat->aprobadas; ?></td>
                                    <td>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $porcentaje_aprobacion; ?>%"></div>
                                            <span class="progress-text"><?php echo $porcentaje_aprobacion; ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Estadísticas por Mes -->
        <div class="postbox">
            <h2 class="hndle"><?php _e('Tendencia por Mes (Últimos 12 meses)', 'probolsas-solicitudes'); ?></h2>
            <div class="inside">
                <canvas id="grafico-meses" width="400" height="200"></canvas>
            </div>
        </div>
        
        <!-- Solicitudes Recientes -->
        <div class="postbox">
            <h2 class="hndle"><?php _e('Solicitudes Recientes', 'probolsas-solicitudes'); ?></h2>
            <div class="inside">
                <?php if (empty($solicitudes_recientes)): ?>
                    <p><?php _e('No hay solicitudes recientes.', 'probolsas-solicitudes'); ?></p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('ID', 'probolsas-solicitudes'); ?></th>
                                <th><?php _e('Proceso', 'probolsas-solicitudes'); ?></th>
                                <th><?php _e('Solicitud', 'probolsas-solicitudes'); ?></th>
                                <th><?php _e('Usuario', 'probolsas-solicitudes'); ?></th>
                                <th><?php _e('Estado', 'probolsas-solicitudes'); ?></th>
                                <th><?php _e('Fecha', 'probolsas-solicitudes'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($solicitudes_recientes as $solicitud): ?>
                                <?php
                                $estado_texto = '';
                                $estado_class = '';
                                switch ($solicitud->estado) {
                                    case 1: 
                                        $estado_texto = 'Solicitado'; 
                                        $estado_class = 'stat-solicitado';
                                        break;
                                    case 2: 
                                        $estado_texto = 'En proceso'; 
                                        $estado_class = 'stat-proceso';
                                        break;
                                    case 3: 
                                        $estado_texto = 'Aprobado'; 
                                        $estado_class = 'stat-aprobado';
                                        break;
                                }
                                ?>
                                <tr>
                                    <td><?php echo $solicitud->id; ?></td>
                                    <td><?php echo esc_html($solicitud->proceso_nombre); ?></td>
                                    <td>
                                        <?php 
                                        $descripcion = strlen($solicitud->solicitud) > 60 
                                            ? substr($solicitud->solicitud, 0, 60) . '...' 
                                            : $solicitud->solicitud;
                                        echo esc_html($descripcion);
                                        ?>
                                    </td>
                                    <td><?php echo esc_html($solicitud->usuario_nombre ?: 'N/A'); ?></td>
                                    <td><span class="estado-badge <?php echo $estado_class; ?>"><?php echo $estado_texto; ?></span></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($solicitud->fecha_creacion)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Exportación -->
        <div class="postbox">
            <h2 class="hndle"><?php _e('Exportar Datos', 'probolsas-solicitudes'); ?></h2>
            <div class="inside">
                <p><?php _e('Descarga los datos en diferentes formatos para análisis externo.', 'probolsas-solicitudes'); ?></p>
                <p>
                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=probolsas-reportes&export=solicitudes_csv'), 'probolsas_export', 'nonce'); ?>" 
                       class="button button-secondary">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Descargar Solicitudes CSV', 'probolsas-solicitudes'); ?>
                    </a>
                </p>
            </div>
        </div>
        
    </div>
</div>

<!-- JavaScript para gráficos -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Gráfico de Estados
const ctxEstados = document.getElementById('grafico-estados').getContext('2d');
new Chart(ctxEstados, {
    type: 'doughnut',
    data: {
        labels: ['Solicitadas', 'En Proceso', 'Aprobadas'],
        datasets: [{
            data: [<?php echo $stats_generales->solicitadas; ?>, <?php echo $stats_generales->en_proceso; ?>, <?php echo $stats_generales->aprobadas; ?>],
            backgroundColor: ['#ffcdd2', '#ffe0b2', '#c8e6c9'],
            borderColor: ['#c62828', '#ef6c00', '#2e7d32'],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Gráfico de Meses
const ctxMeses = document.getElementById('grafico-meses').getContext('2d');
new Chart(ctxMeses, {
    type: 'line',
    data: {
        labels: [<?php echo '"' . implode('","', array_reverse(array_column($stats_por_mes, 'mes'))) . '"'; ?>],
        datasets: [{
            label: 'Total Solicitudes',
            data: [<?php echo implode(',', array_reverse(array_column($stats_por_mes, 'total_solicitudes'))); ?>],
            borderColor: '#007cba',
            backgroundColor: 'rgba(0, 124, 186, 0.1)',
            fill: true
        }, {
            label: 'Aprobadas',
            data: [<?php echo implode(',', array_reverse(array_column($stats_por_mes, 'aprobadas'))); ?>],
            borderColor: '#2e7d32',
            backgroundColor: 'rgba(46, 125, 50, 0.1)',
            fill: true
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>

<style>
.probolsas-reportes-container .postbox {
    margin-bottom: 20px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.stat-card {
    text-align: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #dee2e6;
}

.stat-card.stat-solicitado {
    background: #ffebee;
    border-color: #ffcdd2;
}

.stat-card.stat-proceso {
    background: #fff3e0;
    border-color: #ffe0b2;
}

.stat-card.stat-aprobado {
    background: #e8f5e8;
    border-color: #c8e6c9;
}

.stat-number {
    font-size: 2.5em;
    font-weight: bold;
    color: #007cba;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 14px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.progress-bar {
    position: relative;
    height: 20px;
    background: #f0f0f0;
    border-radius: 10px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #2e7d32, #4caf50);
    transition: width 0.3s ease;
}

.progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 12px;
    font-weight: bold;
    color: #333;
}

.estado-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
}

.estado-badge.stat-solicitado {
    background: #ffebee;
    color: #c62828;
}

.estado-badge.stat-proceso {
    background: #fff3e0;
    color: #ef6c00;
}

.estado-badge.stat-aprobado {
    background: #e8f5e8;
    color: #2e7d32;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
    }
    
    .stat-number {
        font-size: 2em;
    }
}
</style>