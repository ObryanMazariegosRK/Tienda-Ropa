@extends('admin.admin')

@section('title', 'Correo de envío')

@section('content')

    <h1 class="mt-4">Correo de envío</h1>
    <ol class="breadcrumb mb-3">
        <li class="breadcrumb-item active">Cuenta con la que se envían los códigos de verificación y de recuperación de contraseña</li>
    </ol>

    {{-- Cuenta en uso: la llena correo-controller.js --}}
    <div class="mail-current mb-4" id="mailCurrent">
        <div class="mail-current-icon"><i class="fas fa-paper-plane"></i></div>
        <div>
            <div class="mail-current-label">Los clientes reciben los correos desde</div>
            <div class="mail-current-value" id="mailCurrentValue">Cargando...</div>
            <div class="mail-current-hint" id="mailCurrentHint"></div>
        </div>
        <button type="button" class="btn btn-outline-secondary btn-sm ms-auto d-none" id="btnUsarServidor">
            <i class="fas fa-rotate-left me-1"></i> Volver al correo del servidor
        </button>
    </div>

    <div class="row">
        <div class="col-xl-8 col-md-12">
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-primary d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span><i class="fas fa-envelope me-1"></i> Cuentas de correo</span>
                    <button type="button" class="btn btn-success btn-sm" id="btnNuevaCuenta">
                        <i class="fas fa-plus me-1"></i> Agregar cuenta
                    </button>
                </div>
                <div class="card-body p-0">
                    <div id="mailSendersList" class="mail-list">
                        <p class="text-muted text-center py-4 mb-0">Cargando...</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-12">
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-primary">
                    <i class="fas fa-circle-question me-1"></i> ¿Cómo agrego una cuenta de Gmail?
                </div>
                <div class="card-body">
                    <ol class="mail-steps">
                        <li>Entra a la cuenta de Google que quieres usar y activa la <strong>verificación en 2 pasos</strong>.</li>
                        <li>Abre <a href="https://myaccount.google.com/apppasswords" target="_blank" rel="noopener">Contraseñas de aplicaciones</a>, escribe un nombre (ej. "Tienda") y copia la clave de <strong>16 letras</strong>. Google la muestra en grupos de 4 (<code>abcd efgh ijkl mnop</code>).</li>
                        <li>Aquí, toca <strong>Agregar cuenta</strong> y pega esa clave tal cual: los espacios se quitan solos. No uses la contraseña normal del correo.</li>
                        <li>Envía un <strong>correo de prueba</strong>. Si llega, toca <strong>Usar esta cuenta</strong>.</li>
                    </ol>
                    <p class="mail-note mb-0">
                        <i class="fas fa-shield-halved me-1"></i>
                        Si la cuenta en uso deja de funcionar, los códigos se envían con el correo del servidor para que ningún cliente se quede sin el suyo.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal agregar / editar cuenta -->
    <div class="modal fade" id="mailSenderModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mailSenderModalTitle">Agregar cuenta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form id="mailSenderForm" autocomplete="off">
                    <div class="modal-body">
                        <input type="hidden" id="senderId">
                        <div class="mb-3">
                            <label for="senderFromName" class="form-label">Nombre que verán los clientes</label>
                            <input type="text" class="form-control" id="senderFromName" maxlength="100" placeholder="Ej: Amerishop Tecpán" required>
                        </div>
                        <div class="mb-3">
                            <label for="senderEmail" class="form-label">Correo</label>
                            <input type="email" class="form-control" id="senderEmail" maxlength="150" placeholder="tienda@gmail.com" required>
                        </div>
                        <div class="mb-3">
                            <label for="senderPassword" class="form-label">Contraseña de aplicación</label>
                            <input type="password" class="form-control" id="senderPassword" maxlength="255" placeholder="abcd efgh ijkl mnop" autocomplete="new-password">
                            <div class="form-text" id="senderPasswordHint">La clave de 16 letras que genera Google (los espacios se quitan solos), no la contraseña normal del correo.</div>
                        </div>

                        <details class="mail-advanced">
                            <summary>Opciones avanzadas (solo si no es Gmail)</summary>
                            <div class="row g-2 mt-1">
                                <div class="col-8">
                                    <label for="senderHost" class="form-label">Servidor SMTP</label>
                                    <input type="text" class="form-control" id="senderHost" placeholder="smtp.gmail.com">
                                </div>
                                <div class="col-4">
                                    <label for="senderPort" class="form-label">Puerto</label>
                                    <input type="number" class="form-control" id="senderPort" min="1" max="65535" placeholder="587">
                                </div>
                            </div>
                        </details>

                        <div class="alert alert-danger d-none mt-3 mb-0" id="mailSenderError"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success" id="mailSenderSubmit">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal correo de prueba -->
    <div class="modal fade" id="mailTestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Enviar correo de prueba</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form id="mailTestForm">
                    <div class="modal-body">
                        <input type="hidden" id="testSenderId">
                        <p class="text-muted small mb-3">Se enviará desde <strong id="testSenderEmail"></strong>. Revisa también la carpeta de spam.</p>
                        <label for="testTo" class="form-label">Enviar la prueba a</label>
                        <input type="email" class="form-control" id="testTo" maxlength="150" required>
                        <div class="alert alert-danger d-none mt-3 mb-0" id="mailTestError"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success" id="mailTestSubmit"><i class="fas fa-paper-plane me-1"></i> Enviar prueba</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="/admin/js/correo-controller.js"></script>
@endpush
