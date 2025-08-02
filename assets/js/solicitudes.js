/**
 * JavaScript para Plugin Probolsas - Gestión de Solicitudes
 * 
 * @package ProbolsasSolicitudes
 */

jQuery(document).ready(function($) {
    
    // Variables globales
    let solicitudesData = [];
    let procesosData = [];
    let calendarioMesActual = new Date();
    
    // Inicialización
    init();
    
    function init() {
        // Configurar fecha actual
        $('#fecha-solicitud').val(new Date().toISOString().split('T')[0]);
        
        // Cargar datos iniciales
        cargarProcesos();
        cargarSolicitudes();
        
        // Configurar eventos
        configurarEventos();
    }
    
    function configurarEventos() {
        // Navegación entre vistas
        $('#btn-lista').click(function() {
            mostrarVista('lista');
        });
        
        $('#btn-calendario').click(function() {
            mostrarVista('calendario');
            cargarCalendario();
        });
        
        // Gestión de procesos (solo admin)
        $('#btn-gestionar-procesos').click(function() {
            $('#modal-gestionar-procesos').show();
            cargarTablaProcesos();
        });
        
        // Modal Nueva Solicitud
        $('#btn-nueva-solicitud').click(function() {
            $('#modal-nueva-solicitud').show();
            $('#fecha-solicitud').val(new Date().toISOString().split('T')[0]);
        });
        
        // Cerrar modales
        $('.close, #btn-cancelar, #btn-cancelar-estado, #btn-cancelar-fecha, #btn-cancelar-eliminar, #btn-cancelar-proceso, #btn-cerrar-procesos').click(function() {
            $('.probolsas-modal').hide();
        });
        
        // Cerrar modal al hacer clic fuera
        $('.probolsas-modal').click(function(e) {
            if (e.target === this) {
                $(this).hide();
            }
        });
        
        // Formularios
        $('#form-nueva-solicitud').submit(crearSolicitud);
        $('#form-editar-estado').submit(actualizarEstado);  
        $('#form-editar-fecha').submit(actualizarFecha);
        $('#form-proceso').submit(guardarProceso);
        
        // Filtros
        $('#btn-filtrar').click(cargarSolicitudes);
        $('#btn-limpiar-filtros').click(limpiarFiltros);
        
        // Confirmación eliminar solicitud
        $('#btn-confirmar-eliminar').click(eliminarSolicitud);
        
        // Gestión de procesos
        $('#btn-nuevo-proceso').click(function() {
            $('#titulo-modal-proceso').text(probolsas_ajax.textos.nuevo_proceso || 'Nuevo Proceso');
            $('#form-proceso')[0].reset();
            $('#proceso-id-editar').val('');
            $('#modal-proceso').show();
        });
        
        // Eventos delegados para elementos dinámicos
        $(document).on('click', '.btn-editar-estado', editarEstado);
        $(document).on('click', '.btn-editar-fecha', editarFecha);
        $(document).on('click', '.btn-ver-detalle', verDetalle);
        $(document).on('click', '.btn-eliminar', function() {
            const id = $(this).data('id');
            $('#solicitud-eliminar-id').val(id);
            $('#modal-confirmar-eliminar').show();
        });
        
        // Eventos para gestión de procesos
        $(document).on('click', '.btn-editar-proceso', editarProceso);
        $(document).on('click', '.btn-eliminar-proceso', eliminarProceso);
    }
    
    function mostrarVista(vista) {
        $('.vista-container').hide();
        $('.nav-btn').removeClass('active');
        
        if (vista === 'lista') {
            $('#vista-lista').show();
            $('#btn-lista').addClass('active');
        } else if (vista === 'calendario') {
            $('#vista-calendario').show();
            $('#btn-calendario').addClass('active');
        }
    }
    
    // Gestión de Procesos
    function cargarProcesos() {
        $.post(probolsas_ajax.ajax_url, {
            action: 'probolsas_obtener_procesos',
            nonce: probolsas_ajax.nonce
        }, function(response) {
            if (response.success) {
                procesosData = response.data;
                actualizarSelectoresProcesos();
            }
        });
    }
    
    function actualizarSelectoresProcesos() {
        const selectors = ['#proceso-id', '#filtro-proceso'];
        
        selectors.forEach(selector => {
            const $select = $(selector);
            const valorActual = $select.val();
            
            $select.find('option:not(:first)').remove();
            
            procesosData.forEach(proceso => {
                if (proceso.activo == 1) {
                    $select.append(`<option value="${proceso.id}">${proceso.nombre}</option>`);
                }
            });
            
            if (valorActual) {
                $select.val(valorActual);
            }
        });
    }
    
    function cargarTablaProcesos() {
        $.post(probolsas_ajax.ajax_url, {
            action: 'probolsas_obtener_procesos',
            nonce: probolsas_ajax.nonce,
            incluir_inactivos: true
        }, function(response) {
            if (response.success) {
                mostrarProcesosEnTabla(response.data);
            }
        });
    }
    
    function mostrarProcesosEnTabla(procesos) {
        const tbody = $('#tabla-procesos tbody');
        tbody.empty();
        
        procesos.forEach(proceso => {
            const estadoTexto = proceso.activo == 1 ? 'Activo' : 'Inactivo';
            const estadoClass = proceso.activo == 1 ? 'estado-aprobado' : 'estado-solicitado';
            
            const row = `
                <tr>
                    <td>${proceso.id}</td>
                    <td>${proceso.nombre}</td>
                    <td>${proceso.descripcion || ''}</td>
                    <td><span class="estado ${estadoClass}">${estadoTexto}</span></td>
                    <td>${proceso.orden}</td>
                    <td>
                        <button class="btn-accion btn-editar-proceso" data-id="${proceso.id}">Editar</button>
                        <button class="btn-accion btn-eliminar-proceso btn-danger" data-id="${proceso.id}">Eliminar</button>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }
    
    function editarProceso() {
        const id = $(this).data('id');
        const proceso = procesosData.find(p => p.id == id);
        
        if (proceso) {
            $('#titulo-modal-proceso').text('Editar Proceso');
            $('#proceso-id-editar').val(proceso.id);
            $('#proceso-nombre').val(proceso.nombre);
            $('#proceso-descripcion').val(proceso.descripcion);
            $('#proceso-orden').val(proceso.orden);
            $('#proceso-activo').val(proceso.activo);
            $('#modal-gestionar-procesos').hide();
            $('#modal-proceso').show();
        }
    }
    
    function eliminarProceso() {
        const id = $(this).data('id');
        
        if (confirm('¿Estás seguro de que quieres eliminar este proceso?')) {
            $.post(probolsas_ajax.ajax_url, {
                action: 'probolsas_eliminar_proceso',
                nonce: probolsas_ajax.nonce,
                proceso_id: id
            }, function(response) {
                if (response.success) {
                    mostrarNotificacion(response.data, 'success');
                    cargarTablaProcesos();
                    cargarProcesos(); // Actualizar selectores
                } else {
                    mostrarNotificacion(response.data, 'error');
                }
            });
        }
    }
    
    function guardarProceso(e) {
        e.preventDefault();
        
        const esEdicion = $('#proceso-id-editar').val();
        const action = esEdicion ? 'probolsas_actualizar_proceso' : 'probolsas_crear_proceso';
        
        const formData = {
            action: action,
            nonce: probolsas_ajax.nonce,
            nombre: $('#proceso-nombre').val(),
            descripcion: $('#proceso-descripcion').val(),
            orden: $('#proceso-orden').val(),
            activo: $('#proceso-activo').val()
        };
        
        if (esEdicion) {
            formData.proceso_id = esEdicion;
        }
        
        $.post(probolsas_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                mostrarNotificacion(response.data, 'success');
                $('#modal-proceso').hide();
                $('#modal-gestionar-procesos').show();
                cargarTablaProcesos();
                cargarProcesos(); // Actualizar selectores
            } else {
                mostrarNotificacion(response.data, 'error');
            }
        });
    }
    
    // Gestión de Solicitudes
    function crearSolicitud(e) {
        e.preventDefault();
        
        const formData = {
            action: 'probolsas_crear_solicitud',
            nonce: probolsas_ajax.nonce,
            proceso_id: $('#proceso-id').val(),
            solicitud: $('#descripcion-solicitud').val(),
            fecha_solicitud: $('#fecha-solicitud').val()
        };
        
        $.post(probolsas_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                mostrarNotificacion(probolsas_ajax.textos.solicitud_creada, 'success');
                $('#modal-nueva-solicitud').hide();
                $('#form-nueva-solicitud')[0].reset();
                $('#fecha-solicitud').val(new Date().toISOString().split('T')[0]);
                cargarSolicitudes();
            } else {
                mostrarNotificacion(response.data || probolsas_ajax.textos.error_general, 'error');
            }
        });
    }
    
    function cargarSolicitudes() {
        const filtros = {
            action: 'probolsas_obtener_solicitudes',
            nonce: probolsas_ajax.nonce,
            proceso_id: $('#filtro-proceso').val(),
            estado: $('#filtro-estado').val(),
            semana: $('#filtro-semana').val(),
            mes: $('#filtro-mes').val()
        };
        
        // Mostrar loading
        $('#tabla-solicitudes tbody').html(`
            <tr>
                <td colspan="7" class="loading-row">
                    <div class="loading-spinner">
                        <span class="dashicons dashicons-update spin"></span>
                        Cargando solicitudes...
                    </div>
                </td>
            </tr>
        `);
        
        $.post(probolsas_ajax.ajax_url, filtros, function(response) {
            if (response.success) {
                solicitudesData = response.data;
                mostrarSolicitudesEnTabla(response.data);
            } else {
                $('#tabla-solicitudes tbody').html(`
                    <tr>
                        <td colspan="7" class="loading-row">
                            <div class="loading-spinner">
                                <span class="dashicons dashicons-warning"></span>
                                Error al cargar las solicitudes
                            </div>
                        </td>
                    </tr>
                `);
            }
        }).fail(function() {
            $('#tabla-solicitudes tbody').html(`
                <tr>
                    <td colspan="7" class="loading-row">
                        <div class="loading-spinner">
                            <span class="dashicons dashicons-warning"></span>
                            Error de conexión
                        </div>
                    </td>
                </tr>
            `);
        });
    }
    
    function mostrarSolicitudesEnTabla(solicitudes) {
        const tbody = $('#tabla-solicitudes tbody');
        tbody.empty();
        
        if (solicitudes.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="7" class="loading-row">
                        <div class="loading-spinner">
                            <span class="dashicons dashicons-info"></span>
                            No se encontraron solicitudes
                        </div>
                    </td>
                </tr>
            `);
            return;
        }
        
        solicitudes.forEach(function(solicitud) {
            const estadoTexto = getEstadoTexto(solicitud.estado);
            const estadoClass = getEstadoClass(solicitud.estado);
            const procesoNombre = solicitud.proceso_nombre || 'N/A';
            
            // Truncar descripción
            const descripcionCorta = solicitud.solicitud.length > 50 
                ? solicitud.solicitud.substring(0, 50) + '...' 
                : solicitud.solicitud;
                
            const fechaEjecucion = solicitud.fecha_ejecucion && solicitud.fecha_ejecucion !== 'Por definirse' 
                ? formatearFecha(solicitud.fecha_ejecucion) 
                : 'Por definirse';
            
            let botones = `
                <button class="btn-accion btn-ver-detalle" data-id="${solicitud.id}">Ver Detalle</button>
            `;
            
            // Solo mostrar botones de edición si el usuario puede editar
            if (probolsas_ajax.es_admin || window.probolsas_puede_editar) {
                botones += `
                    <button class="btn-accion btn-editar-estado" data-id="${solicitud.id}" data-estado="${solicitud.estado}">Editar Estado</button>
                    <button class="btn-accion btn-editar-fecha" data-id="${solicitud.id}" data-fecha="${solicitud.fecha_ejecucion || ''}">Editar Fecha</button>
                    <button class="btn-accion btn-eliminar" data-id="${solicitud.id}">Eliminar</button>
                `;
            }
            
            const row = `
                <tr>
                    <td>${solicitud.id}</td>
                    <td>${procesoNombre}</td>
                    <td title="${solicitud.solicitud}">${descripcionCorta}</td>
                    <td>${formatearFecha(solicitud.fecha_solicitud)}</td>
                    <td>${fechaEjecucion}</td>
                    <td><span class="estado ${estadoClass}">${estadoTexto}</span></td>
                    <td>${botones}</td>
                </tr>
            `;
            tbody.append(row);
        });
    }
    
    function editarEstado() {
        const id = $(this).data('id');
        const estadoActual = $(this).data('estado');
        
        $('#solicitud-id').val(id);
        $('#nuevo-estado').val(estadoActual);
        $('#modal-editar-estado').show();
    }
    
    function actualizarEstado(e) {
        e.preventDefault();
        
        const formData = {
            action: 'probolsas_actualizar_estado',
            nonce: probolsas_ajax.nonce,
            solicitud_id: $('#solicitud-id').val(),
            estado: $('#nuevo-estado').val()
        };
        
        $.post(probolsas_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                mostrarNotificacion(response.data, 'success');
                $('#modal-editar-estado').hide();
                cargarSolicitudes();
            } else {
                mostrarNotificacion(response.data || probolsas_ajax.textos.error_general, 'error');
            }
        });
    }
    
    function editarFecha() {
        const id = $(this).data('id');
        const fechaActual = $(this).data('fecha');
        
        $('#solicitud-id-fecha').val(id);
        $('#nueva-fecha-ejecucion').val(fechaActual === 'Por definirse' ? '' : fechaActual);
        $('#modal-editar-fecha').show();
    }
    
    function actualizarFecha(e) {
        e.preventDefault();
        
        const formData = {
            action: 'probolsas_actualizar_fecha',
            nonce: probolsas_ajax.nonce,
            solicitud_id: $('#solicitud-id-fecha').val(),
            fecha_ejecucion: $('#nueva-fecha-ejecucion').val()
        };
        
        $.post(probolsas_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                mostrarNotificacion(response.data, 'success');
                $('#modal-editar-fecha').hide();
                cargarSolicitudes();
            } else {
                mostrarNotificacion(response.data || probolsas_ajax.textos.error_general, 'error');
            }
        });
    }
    
    function eliminarSolicitud() {
        const solicitudId = $('#solicitud-eliminar-id').val();
        
        const formData = {
            action: 'probolsas_eliminar_solicitud',
            nonce: probolsas_ajax.nonce,
            solicitud_id: solicitudId
        };
        
        $.post(probolsas_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                mostrarNotificacion(probolsas_ajax.textos.solicitud_eliminada, 'success');
                $('#modal-confirmar-eliminar').hide();
                cargarSolicitudes();
            } else {
                mostrarNotificacion(response.data || probolsas_ajax.textos.error_general, 'error');
            }
        });
    }
    
    function verDetalle() {
        const id = $(this).data('id');
        const solicitud = solicitudesData.find(s => s.id == id);
        
        if (solicitud) {
            const estadoTexto = getEstadoTexto(solicitud.estado);
            const procesoNombre = solicitud.proceso_nombre || 'N/A';
            const fechaEjecucion = solicitud.fecha_ejecucion && solicitud.fecha_ejecucion !== 'Por definirse' 
                ? formatearFecha(solicitud.fecha_ejecucion) 
                : 'Por definirse';
            
            const detalle = `
                <div class="probolsas-modal" id="modal-detalle" style="display: block;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2>Detalle de Solicitud #${solicitud.id}</h2>
                            <span class="close">&times;</span>
                        </div>
                        <div class="modal-body">
                            <div class="detalle-info">
                                <p><strong>Proceso:</strong> ${procesoNombre}</p>
                                <p><strong>Solicitud:</strong> ${solicitud.solicitud}</p>
                                <p><strong>Fecha de Solicitud:</strong> ${formatearFecha(solicitud.fecha_solicitud)}</p>
                                <p><strong>Fecha de Ejecución:</strong> ${fechaEjecucion}</p>
                                <p><strong>Estado:</strong> <span class="estado ${getEstadoClass(solicitud.estado)}">${estadoTexto}</span></p>
                                <p><strong>Fecha de Creación:</strong> ${formatearFechaHora(solicitud.fecha_creacion)}</p>
                                ${solicitud.fecha_actualizacion && solicitud.fecha_actualizacion !== solicitud.fecha_creacion ? 
                                    `<p><strong>Última Actualización:</strong> ${formatearFechaHora(solicitud.fecha_actualizacion)}</p>` : ''}
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn-secondary close-detail">Cerrar</button>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(detalle);
            
            $('#modal-detalle .close, #modal-detalle .close-detail').click(function() {
                $('#modal-detalle').remove();
            });
            
            $('#modal-detalle').click(function(e) {
                if (e.target === this) {
                    $('#modal-detalle').remove();
                }
            });
        }
    }
    
    function limpiarFiltros() {
        $('#filtro-proceso').val('');
        $('#filtro-estado').val('');
        $('#filtro-semana').val('');
        $('#filtro-mes').val('');
        
        cargarSolicitudes();
        mostrarNotificacion('Filtros limpiados correctamente', 'success');
    }
    
    // Funciones del Calendario
    function cargarCalendario() {
        const calendario = $('#calendario-solicitudes');
        calendario.empty();
        
        // Crear navegación del calendario
        const mesNombre = calendarioMesActual.toLocaleDateString('es-ES', { 
            month: 'long', 
            year: 'numeric' 
        });
        
        const navegacion = `
            <div class="calendario-navegacion">
                <button id="mes-anterior" class="btn-nav-calendario">&lt;</button>
                <h3 id="titulo-mes">${mesNombre}</h3>
                <button id="mes-siguiente" class="btn-nav-calendario">&gt;</button>
            </div>
        `;
        
        calendario.append(navegacion);
        
        // Crear grid del calendario
        const primerDia = new Date(calendarioMesActual.getFullYear(), calendarioMesActual.getMonth(), 1);
        const ultimoDia = new Date(calendarioMesActual.getFullYear(), calendarioMesActual.getMonth() + 1, 0);
        
        let calendarioHTML = '<div class="calendario-grid">';
        
        // Días de la semana
        const diasSemana = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
        diasSemana.forEach(dia => {
            calendarioHTML += `<div class="dia-semana">${dia}</div>`;
        });
        
        // Espacios vacíos al inicio
        for (let i = 0; i < primerDia.getDay(); i++) {
            calendarioHTML += '<div class="dia-vacio"></div>';
        }
        
        // Días del mes
        for (let dia = 1; dia <= ultimoDia.getDate(); dia++) {
            const fechaActual = new Date(calendarioMesActual.getFullYear(), calendarioMesActual.getMonth(), dia);
            const fechaStr = fechaActual.toISOString().split('T')[0];
            
            // Buscar solicitudes para esta fecha
            const solicitudesDia = solicitudesData.filter(s => 
                s.fecha_ejecucion === fechaStr && s.fecha_ejecucion !== 'Por definirse'
            );
            
            let contenidoDia = `<div class="numero-dia">${dia}</div>`;
            
            if (solicitudesDia.length > 0) {
                contenidoDia += '<div class="solicitudes-dia">';
                solicitudesDia.forEach(solicitud => {
                    const estadoClass = getEstadoClass(solicitud.estado);
                    const procesoNombre = solicitud.proceso_nombre || 'N/A';
                    contenidoDia += `<div class="solicitud-calendario ${estadoClass}" 
                                        title="${solicitud.solicitud}" 
                                        data-id="${solicitud.id}">${procesoNombre}</div>`;
                });
                contenidoDia += '</div>';
            }
            
            calendarioHTML += `<div class="dia-calendario">${contenidoDia}</div>`;
        }
        
        calendarioHTML += '</div>';
        calendario.append(calendarioHTML);
        
        // Eventos de navegación
        $('#mes-anterior').click(function() {
            calendarioMesActual.setMonth(calendarioMesActual.getMonth() - 1);
            cargarCalendario();
        });
        
        $('#mes-siguiente').click(function() {
            calendarioMesActual.setMonth(calendarioMesActual.getMonth() + 1);
            cargarCalendario();
        });
        
        // Evento click en solicitudes del calendario
        $(document).on('click', '.solicitud-calendario', function() {
            const id = $(this).data('id');
            const solicitud = solicitudesData.find(s => s.id == id);
            if (solicitud) {
                // Simular click en ver detalle
                verDetalleSolicitud(solicitud);
            }
        });
    }
    
    function verDetalleSolicitud(solicitud) {
        const estadoTexto = getEstadoTexto(solicitud.estado);
        const procesoNombre = solicitud.proceso_nombre || 'N/A';
        const fechaEjecucion = solicitud.fecha_ejecucion && solicitud.fecha_ejecucion !== 'Por definirse' 
            ? formatearFecha(solicitud.fecha_ejecucion) 
            : 'Por definirse';
        
        const detalle = `
            <div class="probolsas-modal" id="modal-detalle-calendario" style="display: block;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Detalle de Solicitud #${solicitud.id}</h2>
                        <span class="close">&times;</span>
                    </div>
                    <div class="modal-body">
                        <div class="detalle-info">
                            <p><strong>Proceso:</strong> ${procesoNombre}</p>
                            <p><strong>Solicitud:</strong> ${solicitud.solicitud}</p>
                            <p><strong>Fecha de Solicitud:</strong> ${formatearFecha(solicitud.fecha_solicitud)}</p>
                            <p><strong>Fecha de Ejecución:</strong> ${fechaEjecucion}</p>
                            <p><strong>Estado:</strong> <span class="estado ${getEstadoClass(solicitud.estado)}">${estadoTexto}</span></p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-secondary close-detail">Cerrar</button>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(detalle);
        
        $('#modal-detalle-calendario .close, #modal-detalle-calendario .close-detail').click(function() {
            $('#modal-detalle-calendario').remove();
        });
        
        $('#modal-detalle-calendario').click(function(e) {
            if (e.target === this) {
                $('#modal-detalle-calendario').remove();
            }
        });
    }
    
    // Funciones auxiliares
    function getEstadoTexto(estado) {
        switch(parseInt(estado)) {
            case 1: return 'Solicitado';
            case 2: return 'En proceso';
            case 3: return 'Aprobado';
            default: return 'Desconocido';
        }
    }
    
    function getEstadoClass(estado) {
        switch(parseInt(estado)) {
            case 1: return 'estado-solicitado';
            case 2: return 'estado-proceso';
            case 3: return 'estado-aprobado';
            default: return '';
        }
    }
    
    function formatearFecha(fecha) {
        if (!fecha) return '';
        const d = new Date(fecha + 'T00:00:00');
        return d.toLocaleDateString('es-ES');
    }
    
    function formatearFechaHora(fechaHora) {
        if (!fechaHora) return '';
        const d = new Date(fechaHora);
        return d.toLocaleString('es-ES');
    }
    
    // Sistema de notificaciones
    function mostrarNotificacion(mensaje, tipo = 'info', duracion = 5000) {
        const iconos = {
            success: 'dashicons-yes-alt',
            error: 'dashicons-dismiss',
            warning: 'dashicons-warning',
            info: 'dashicons-info'
        };
        
        const icono = iconos[tipo] || iconos.info;
        
        const notificacion = $(`
            <div class="probolsas-notification notification-${tipo}">
                <span class="dashicons ${icono}"></span>
                <span class="notification-message">${mensaje}</span>
            </div>
        `);
        
        $('#probolsas-notifications').append(notificacion);
        
        // Auto-ocultar después del tiempo especificado
        setTimeout(function() {
            notificacion.fadeOut(function() {
                notificacion.remove();
            });
        }, duracion);
        
        // Permitir cerrar manualmente
        notificacion.click(function() {
            $(this).fadeOut(function() {
                $(this).remove();
            });
        });
    }
    
    // Manejo de errores AJAX global
    $(document).ajaxError(function(event, xhr, settings, thrownError) {
        console.error('Error AJAX:', {
            url: settings.url,
            data: settings.data,
            error: thrownError,
            status: xhr.status,
            response: xhr.responseText
        });
        
        if (xhr.status === 403) {
            mostrarNotificacion('No tienes permisos para realizar esta acción.', 'error');
        } else if (xhr.status === 500) {
            mostrarNotificacion('Error interno del servidor. Contacta al administrador.', 'error');
        } else if (xhr.status === 0) {
            mostrarNotificacion('Error de conexión. Verifica tu conexión a internet.', 'error');
        }
    });
    
    // Detectar cambios de conectividad
    window.addEventListener('online', function() {
        mostrarNotificacion('Conexión restaurada', 'success', 3000);
    });
    
    window.addEventListener('offline', function() {
        mostrarNotificacion('Sin conexión a internet', 'warning', 3000);
    });
    
    // Prevenir envío múltiple de formularios
    $('form').submit(function() {
        $(this).find('button[type="submit"]').prop('disabled', true);
        setTimeout(() => {
            $(this).find('button[type="submit"]').prop('disabled', false);
        }, 2000);
    });
    
    // Confirmación antes de salir si hay cambios sin guardar
    let hayDatosSinGuardar = false;
    
    $('input, textarea, select').on('input change', function() {
        hayDatosSinGuardar = true;
    });
    
    $('form').submit(function() {
        hayDatosSinGuardar = false;
    });
    
    $(window).on('beforeunload', function() {
        if (hayDatosSinGuardar) {
            return '¿Estás seguro de que quieres salir? Los cambios no guardados se perderán.';
        }
    });
});