jQuery(document).ready(function($) {
    
    // Variables globales
    let solicitudesData = [];
    let calendarioMesActual = new Date();
    
    // Inicializar fecha actual
    $('#fecha-solicitud').val(new Date().toISOString().split('T')[0]);
    
    // Función para desactivar botón con loader
    function desactivarBoton(boton, textoOriginal = null) {
        const $btn = $(boton);
        if (!textoOriginal) {
            textoOriginal = $btn.text() || $btn.attr('aria-label') || 'Botón';
        }
        
        $btn.prop('disabled', true)
            .data('texto-original', textoOriginal)
            .addClass('btn-procesando');
            
        // Si es un botón de texto, cambiar el texto
        if ($btn.text().trim()) {
            $btn.html('<i class="fas fa-spinner fa-spin"></i> Procesando...');
        }
        // Si es un botón de icono, agregar clase de spin al icono
        else if ($btn.find('i').length) {
            $btn.find('i').addClass('fa-spin');
        }
        
        return textoOriginal;
    }
    
    // Función para reactivar botón
    function reactivarBoton(boton, textoOriginal = null) {
        const $btn = $(boton);
        const textoGuardado = $btn.data('texto-original') || textoOriginal;
        
        $btn.prop('disabled', false)
            .removeClass('btn-procesando')
            .removeData('texto-original');
            
        // Restaurar texto original si existe
        if (textoGuardado && $btn.text().trim()) {
            $btn.text(textoGuardado);
        }
        // Remover spin de iconos
        else if ($btn.find('i').length) {
            $btn.find('i').removeClass('fa-spin');
        }
    }
    
    // Navegación entre vistas
    $('#btn-lista').click(function() {
        $('#vista-lista').show();
        $('#vista-calendario').hide();
        $('.nav-btn').removeClass('active');
        $(this).addClass('active');
    });
    
    $('#btn-calendario').click(function() {
        
        $('#vista-lista').hide();
        $('#vista-calendario').show();
        $('.nav-btn').removeClass('active');
        $(this).addClass('active');
        cargarCalendario();
    });
    
    // Modal Nueva Solicitud
    $('#btn-nueva-solicitud').click(function() {
        $('#modal-nueva-solicitud').show();
        $('#fecha-solicitud').val(new Date().toISOString().split('T')[0]);
    });
    
    $('.close, #btn-cancelar, #btn-cancelar-estado, #btn-cancelar-fecha, #btn-cancelar-eliminar').click(function() {
        $('.modal').hide();
        // Reactivar botones al cerrar modales
        reactivarBoton('#form-nueva-solicitud button[type="submit"]', 'Guardar Solicitud');
        reactivarBoton('#form-editar-estado button[type="submit"]', 'Guardar');
        reactivarBoton('#form-editar-fecha button[type="submit"]', 'Guardar');
        reactivarBoton('#btn-confirmar-eliminar', 'Sí, eliminar');
    });
    
    // Cerrar modal al hacer clic fuera
    $('.modal').click(function(e) {
        if (e.target === this) {
            $(this).hide();
            // Reactivar botones al cerrar modales
            reactivarBoton('#form-nueva-solicitud button[type="submit"]', 'Guardar Solicitud');
            reactivarBoton('#form-editar-estado button[type="submit"]', 'Guardar');
            reactivarBoton('#form-editar-fecha button[type="submit"]', 'Guardar');
            reactivarBoton('#btn-confirmar-eliminar', 'Sí, eliminar');
        }
    });
    
    // Crear nueva solicitud
    $('#form-nueva-solicitud').submit(function(e) {
        e.preventDefault();
        
        const $submitBtn = $(this).find('button[type="submit"]');
        const textoOriginal = desactivarBoton($submitBtn, 'Guardar Solicitud');
        
        const formData = {
            action: 'crear_solicitud',
            nonce: solicitudes_ajax.nonce,
            lider_proceso: $('#lider-proceso').val(),
            solicitud: $('#descripcion-solicitud').val(),
            fecha_solicitud: $('#fecha-solicitud').val()
        };
        
        
        $.post(solicitudes_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                mostrarMensaje('Solicitud creada exitosamente', 'success');
                $('#modal-nueva-solicitud').hide();
                $('#form-nueva-solicitud')[0].reset();
                $('#fecha-solicitud').val(new Date().toISOString().split('T')[0]);
                cargarSolicitudes();
            } else {
                mostrarMensaje('Error al crear la solicitud', 'error');
            }
        }).fail(function() {
            mostrarMensaje('Error de conexión al crear la solicitud', 'error');
        }).always(function() {
            reactivarBoton($submitBtn, textoOriginal);
        });
    });
    
    // Filtrar solicitudes
    $('#btn-filtrar').click(function() {
        const textoOriginal = desactivarBoton(this, 'Aplicar Filtros');
        
        // Simular un pequeño delay para mostrar el loader
        setTimeout(() => {
            cargarSolicitudes();
            reactivarBoton(this, textoOriginal);
        }, 300);
    });
    
    // Limpiar filtros
    $('#btn-limpiar-filtros').click(function() {
        const textoOriginal = desactivarBoton(this, 'Limpiar');
        
        // Limpiar todos los filtros
        $('#filtro-proceso').val('').trigger('change');
        $('#filtro-estado').val('').trigger('change');
        $('#filtro-semana').val('');
        $('#filtro-mes').val('');
        
        // Simular un pequeño delay
        setTimeout(() => {
            // Recargar solicitudes sin filtros
            cargarSolicitudes();
            
            // Mostrar mensaje de confirmación
            mostrarMensaje('Filtros limpiados correctamente', 'success');
            
            reactivarBoton(this, textoOriginal);
        }, 300);
    });
    
    // Función para mostrar mensajes
    function mostrarMensaje(mensaje, tipo) {
        const alertClass = tipo === 'success' ? 'alert-success' : 'alert-error';
        const alertHTML = `<div class="alert ${alertClass}">${mensaje}</div>`;
        
        $('#solicitudes-container').prepend(alertHTML);
        
        setTimeout(function() {
            $('.alert').fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // Cargar solicitudes
    function cargarSolicitudes() {
        const filtros = {
            action: 'obtener_solicitudes',
            nonce: solicitudes_ajax.nonce,
            proceso: $('#filtro-proceso').val(),
            estado: $('#filtro-estado').val(),
            semana: $('#filtro-semana').val(),
            mes: $('#filtro-mes').val()
        };
        
        $.post(solicitudes_ajax.ajax_url, filtros, function(response) {
            if (response.success) {
                solicitudesData = response.data;
                mostrarSolicitudesEnTabla(response.data);
            }
        });
    }
    
    // Mostrar solicitudes en tabla con iconos de Font Awesome
    function mostrarSolicitudesEnTabla(solicitudes) {
        const tbody = $('#tabla-solicitudes tbody');
        tbody.empty();
        
        solicitudes.forEach(function(solicitud) {
            const estadoTexto = getEstadoTexto(solicitud.estado);
            const estadoClass = getEstadoClass(solicitud.estado);
            
            const row = `
                <tr>
                    <td>${solicitud.lider_proceso}</td>
                    <td>${formatearFecha(solicitud.fecha_solicitud)}</td>
                    <td>${solicitud.fecha_ejecucion && solicitud.fecha_ejecucion !== 'Por definirse' ? formatearFecha(solicitud.fecha_ejecucion) : 'Por definirse'}</td>
                    <td><span class="estado ${estadoClass}">${estadoTexto}</span></td>
                    <td class="acciones-cell">
                        <button class="btn-accion btn-editar-estado" data-id="${solicitud.id}" data-estado="${solicitud.estado}" data-tooltip="Editar Estado" aria-label="Editar Estado">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-accion btn-editar-fecha" data-id="${solicitud.id}" data-fecha="${solicitud.fecha_ejecucion || ''}" data-tooltip="Editar Fecha de Ejecución" aria-label="Editar Fecha de Ejecución">
                            <i class="fas fa-calendar-alt"></i>
                        </button>
                        <button class="btn-accion btn-ver-detalle" data-id="${solicitud.id}" data-tooltip="Ver Detalle Completo" aria-label="Ver Detalle Completo">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn-accion btn-eliminar" data-id="${solicitud.id}" data-tooltip="Eliminar Solicitud" aria-label="Eliminar Solicitud">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }
    
    // Editar estado
    $(document).on('click', '.btn-editar-estado', function() {
        // Prevenir múltiples clics mientras se procesa
        if ($(this).prop('disabled')) return;
        
        const id = $(this).data('id');
        const estadoActual = $(this).data('estado');
        
        $('#solicitud-id').val(id);
        $('#nuevo-estado').val(estadoActual);
        $('#modal-editar-estado').show();
    });
    
    $('#form-editar-estado').submit(function(e) {
        e.preventDefault();
        
        const $submitBtn = $(this).find('button[type="submit"]');
        const textoOriginal = desactivarBoton($submitBtn, 'Guardar');
        
        const formData = {
            action: 'actualizar_estado',
            nonce: solicitudes_ajax.nonce,
            solicitud_id: $('#solicitud-id').val(),
            estado: $('#nuevo-estado').val()
        };
        
        $.post(solicitudes_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                mostrarMensaje('Estado actualizado exitosamente', 'success');
                $('#modal-editar-estado').hide();
                cargarSolicitudes();
            } else {
                mostrarMensaje('Error al actualizar el estado', 'error');
            }
        }).fail(function() {
            mostrarMensaje('Error de conexión al actualizar el estado', 'error');
        }).always(function() {
            reactivarBoton($submitBtn, textoOriginal);
        });
    });
    
    // Editar fecha de ejecución
    $(document).on('click', '.btn-editar-fecha', function() {
        // Prevenir múltiples clics mientras se procesa
        if ($(this).prop('disabled')) return;
        
        const id = $(this).data('id');
        const fechaActual = $(this).data('fecha');
        
        $('#solicitud-id-fecha').val(id);
        $('#nueva-fecha-ejecucion').val(fechaActual);
        $('#modal-editar-fecha').show();
    });
    
    $('#form-editar-fecha').submit(function(e) {
        e.preventDefault();

        const $submitBtn = $(this).find('button[type="submit"]');
        const textoOriginal = desactivarBoton($submitBtn, 'Guardar');

        const solicitudId = $('#solicitud-id-fecha').val();
        const nuevaFecha = $('#nueva-fecha-ejecucion').val();

        const formData = {
            action: 'actualizar_fecha_ejecucion',
            nonce: solicitudes_ajax.nonce,
            solicitud_id: solicitudId,
            fecha_ejecucion: nuevaFecha
        };

        $.post(solicitudes_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                // Verificar si también se debe actualizar el estado
                const solicitud = solicitudesData.find(s => s.id == solicitudId);
                if (solicitud && (solicitud.estado == 1) && nuevaFecha && nuevaFecha !== 'Por definirse') {
                    // Cambiar estado de 1 (Solicitado) a 2 (En proceso)
                    const estadoForm = {
                        action: 'actualizar_estado',
                        nonce: solicitudes_ajax.nonce,
                        solicitud_id: solicitudId,
                        estado: 2
                    };
        
                    $.post(solicitudes_ajax.ajax_url, estadoForm, function(estadoResponse) {
                        if (estadoResponse.success) {
                            mostrarMensaje('Fecha y estado actualizados', 'success');
                        } else {
                            mostrarMensaje('Fecha actualizada, pero no se pudo cambiar el estado', 'error');
                        }
        
                        $('#modal-editar-fecha').hide();
                        cargarSolicitudes();
        
                        if ($('#vista-calendario').is(':visible')) {
                            cargarCalendario();
                        }
                    }).fail(function() {
                        mostrarMensaje('Fecha actualizada, pero error al cambiar el estado', 'error');
                        $('#modal-editar-fecha').hide();
                        cargarSolicitudes();
                        
                        if ($('#vista-calendario').is(':visible')) {
                            cargarCalendario();
                        }
                    }).always(function() {
                        reactivarBoton($submitBtn, textoOriginal);
                    });
                } else {
                    mostrarMensaje(response.data || 'Fecha actualizada exitosamente', 'success');
                    $('#modal-editar-fecha').hide();
                    cargarSolicitudes();
        
                    if ($('#vista-calendario').is(':visible')) {
                        cargarCalendario();
                    }
                    reactivarBoton($submitBtn, textoOriginal);
                }
            } else {
                mostrarMensaje('Error al actualizar la fecha', 'error');
                reactivarBoton($submitBtn, textoOriginal);
            }
        }).fail(function() {
            mostrarMensaje('Error de conexión al actualizar la fecha', 'error');
            reactivarBoton($submitBtn, textoOriginal);
        });
    });

    
    // Eliminar solicitud
    $(document).on('click', '.btn-eliminar', function() {
        // Prevenir múltiples clics mientras se procesa
        if ($(this).prop('disabled')) return;
        
        const id = $(this).data('id');
        $('#solicitud-eliminar-id').val(id);
        $('#modal-confirmar-eliminar').show();
    });
    
    $('#btn-confirmar-eliminar').click(function() {
        const $btn = $(this);
        const textoOriginal = desactivarBoton($btn, 'Sí, eliminar');
        const solicitudId = $('#solicitud-eliminar-id').val();
        
        const formData = {
            action: 'eliminar_solicitud',
            nonce: solicitudes_ajax.nonce,
            solicitud_id: solicitudId
        };
        
        $.post(solicitudes_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                mostrarMensaje('Solicitud eliminada exitosamente', 'success');
                $('#modal-confirmar-eliminar').hide();
                cargarSolicitudes();
            } else {
                mostrarMensaje('Error al eliminar la solicitud', 'error');
            }
        }).fail(function() {
            mostrarMensaje('Error de conexión al eliminar la solicitud', 'error');
        }).always(function() {
            reactivarBoton($btn, textoOriginal);
        });
    });
    
    // Ver detalle (modal simple)
    $(document).on('click', '.btn-ver-detalle', function() {
        // Prevenir múltiples clics
        if ($(this).prop('disabled')) return;
        
        const id = $(this).data('id');
        const solicitud = solicitudesData.find(s => s.id == id);
        
        if (solicitud) {
            const estadoTexto = getEstadoTexto(solicitud.estado);
            const detalle = `
                <div class="modal" id="modal-detalle" style="display: block;">
                    <div class="modal-content">
                        <span class="close">&times;</span>
                        <h2>Detalle de Solicitud #${solicitud.id}</h2>
                        <div class="detalle-info">
                            <p><strong>Líder de Proceso:</strong> ${solicitud.lider_proceso}</p>
                            <p><strong>Solicitud:</strong> ${solicitud.solicitud}</p>
                            <p><strong>Fecha de Solicitud:</strong> ${formatearFecha(solicitud.fecha_solicitud)}</p>
                            <p><strong>Fecha de Ejecución:</strong> ${solicitud.fecha_ejecucion && solicitud.fecha_ejecucion !== 'Por definirse' ? formatearFecha(solicitud.fecha_ejecucion) : 'Por definirse'}</p>
                            <p><strong>Estado:</strong> ${estadoTexto}</p>
                            <p><strong>Fecha de Creación:</strong> ${formatearFechaHora(solicitud.fecha_creacion)}</p>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(detalle);
            
            $('#modal-detalle .close').click(function() {
                $('#modal-detalle').remove();
            });
        }
    });
    
    // Cargar calendario
    function cargarCalendario() {
        const calendario = $('#calendario-solicitudes');
        calendario.empty();
        
        // Crear navegación del calendario
        const navegacion = `
            <div class="calendario-navegacion">
                <button id="mes-anterior" class="btn-nav-calendario">&lt;</button>
                <h3 id="titulo-mes">${calendarioMesActual.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' })}</h3>
                <button id="mes-siguiente" class="btn-nav-calendario">&gt;</button>
            </div>
        `;
        
        calendario.append(navegacion);
        
        // Crear calendario
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
            
            let claseDia = 'dia-calendario';
            let contenidoDia = `<div class="numero-dia">${dia}</div>`;
            let dataDescripcion = '';
            
            // Si hay solicitudes, construimos el contenido mejorado
            if (solicitudesDia.length > 0) {
                claseDia += ' tiene-solicitudes';
                
                // Crear descripción combinada para el tooltip
                const descripciones = solicitudesDia.map(s => `${s.lider_proceso}: ${s.solicitud}`).join(' | ');
                dataDescripcion = `data-descripcion="${descripciones.replace(/"/g, '&quot;')}"`;
                
                contenidoDia += '<div class="solicitudes-dia">';
                
                // Si hay múltiples solicitudes, mostrar solo la primera visualmente pero incluir todas en el tooltip
                if (solicitudesDia.length === 1) {
                    const solicitud = solicitudesDia[0];
                    const estadoClass = getEstadoClass(solicitud.estado);
                    contenidoDia += `<div class="solicitud-calendario ${estadoClass}">${solicitud.lider_proceso}</div>`;
                } else {
                    // Para múltiples solicitudes, crear una visualización combinada
                    const estadoMasPrioridad = obtenerEstadoPrioridad(solicitudesDia);
                    const estadoClass = getEstadoClass(estadoMasPrioridad);
                    contenidoDia += `<div class="solicitud-calendario ${estadoClass}">${solicitudesDia.length} solicitudes</div>`;
                }

                contenidoDia += '</div>';
            
                // Aplicar clase del estado con mayor prioridad: Aprobado > Proceso > Solicitado
                const estados = solicitudesDia.map(s => parseInt(s.estado));
                if (estados.includes(3)) {
                    claseDia += ' dia-aprobado';
                } else if (estados.includes(2)) {
                    claseDia += ' dia-proceso';
                } else if (estados.includes(1)) {
                    claseDia += ' dia-solicitado';
                }
            }

            calendarioHTML += `<div class="${claseDia}" ${dataDescripcion}>${contenidoDia}</div>`;
        }
        
        calendarioHTML += '</div>';
        calendario.append(calendarioHTML);
        
        // Eventos de navegación - con protección contra doble clic
        $('#mes-anterior').click(function() {
            if ($(this).prop('disabled')) return;
            
            const textoOriginal = desactivarBoton(this);
            calendarioMesActual.setMonth(calendarioMesActual.getMonth() - 1);
            
            setTimeout(() => {
                cargarCalendario();
                reactivarBoton(this, textoOriginal);
            }, 200);
        });
        
        $('#mes-siguiente').click(function() {
            if ($(this).prop('disabled')) return;
            
            const textoOriginal = desactivarBoton(this);
            calendarioMesActual.setMonth(calendarioMesActual.getMonth() + 1);
            
            setTimeout(() => {
                cargarCalendario();
                reactivarBoton(this, textoOriginal);
            }, 200);
        });
        
        // Event listener para clics en días con solicitudes
        $(document).on('click', '.dia-calendario.tiene-solicitudes', function() {
            const fecha = obtenerFechaDelDia($(this));
            if (fecha) {
                mostrarDetallesDiaSolicitudes(fecha);
            }
        });
    }
    
    // Función para obtener el estado con mayor prioridad
    function obtenerEstadoPrioridad(solicitudes) {
        const estados = solicitudes.map(s => parseInt(s.estado));
        if (estados.includes(3)) return 3; // Aprobado
        if (estados.includes(2)) return 2; // En proceso
        return 1; // Solicitado
    }
    
    // Función para obtener la fecha de un día del calendario
    function obtenerFechaDelDia(elemento) {
        const numeroDia = elemento.find('.numero-dia').text();
        if (numeroDia) {
            const fecha = new Date(calendarioMesActual.getFullYear(), calendarioMesActual.getMonth(), parseInt(numeroDia));
            return fecha.toISOString().split('T')[0];
        }
        return null;
    }
    
    // Función para mostrar detalles de todas las solicitudes de un día
    function mostrarDetallesDiaSolicitudes(fecha) {
        
        $('#modal-detalle-dia').remove();
        
        const solicitudesDia = solicitudesData.filter(s => 
            s.fecha_ejecucion === fecha && s.fecha_ejecucion !== 'Por definirse'
        );
        
        if (solicitudesDia.length === 0) return;
        
        let contenidoModal = `
            <div class="modal" id="modal-detalle-dia" style="display: block;">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2>Solicitudes para ${formatearFecha(fecha)}</h2>
                    <div class="detalle-info">
        `;
        
        solicitudesDia.forEach((solicitud, index) => {
            const estadoTexto = getEstadoTexto(solicitud.estado);
            contenidoModal += `
                <div style="margin-bottom: 25px; padding: 15px; border: 1px solid #e0e0e0; border-radius: 8px;">
                    <h4>Solicitud #${solicitud.id}</h4>
                    <p><strong>Líder de Proceso:</strong> ${solicitud.lider_proceso}</p>
                    <p><strong>Solicitud:</strong> ${solicitud.solicitud}</p>
                    <p><strong>Estado:</strong> ${estadoTexto}</p>
                    <p><strong>Fecha de Solicitud:</strong> ${formatearFecha(solicitud.fecha_solicitud)}</p>
                </div>
            `;
        });
        
        contenidoModal += `
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(contenidoModal);
        
        $('#modal-detalle-dia .close').click(function() {
            $('#modal-detalle-dia').remove();
        });
        
        // Cerrar modal al hacer clic fuera
        $('#modal-detalle-dia').click(function(e) {
            if (e.target === this) {
                $(this).remove();
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
        if (!fecha || typeof fecha !== 'string') return 'Por definirse';
    
        const partes = fecha.split('-');
        if (partes.length !== 3) return 'Por definirse';
    
        const year = parseInt(partes[0], 10);
        const month = parseInt(partes[1], 10);
        const day = parseInt(partes[2], 10);
    
        // Verificación robusta de valores válidos
        if (
            isNaN(year) || isNaN(month) || isNaN(day) ||
            year < 1900 || month < 1 || month > 12 || day < 1 || day > 31
        ) {
            return 'Por definirse';
        }
    
        return `${day.toString().padStart(2, '0')}/${month.toString().padStart(2, '0')}/${year}`;
    }

    
    function formatearFechaHora(fechaHora) {
        if (!fechaHora) return '';
        const d = new Date(fechaHora);
        return d.toLocaleString('es-ES');
    }
    
    // Cargar solicitudes al iniciar
    cargarSolicitudes();
});