<?php
class TareaController extends Controller {
    
    public function mis(): void {
        if (!Auth::check()) {
            header('Location: ' . APP_URL . 'auth/login');
            exit;
        }

        $idUsuario = (int)($_SESSION['user']['idusuario'] ?? 0);  // ← TU estructura
        
        $filtros = [
            'estado_tarea' => $_GET['estado_tarea'] ?? null,
            'texto' => trim($_GET['texto'] ?? ''),
            'id_prioridad' => $_GET['id_prioridad'] ?? null,
            'id_tipo_solicitud' => $_GET['id_tipo_solicitud'] ?? null,
            'id_tipo_documento' => $_GET['id_tipo_documento'] ?? null,
        ];

        $pagina = max(1, (int)($_GET['pagina'] ?? 1));
        $porPagina = 20;
        $offset = ($pagina - 1) * $porPagina;

        $tareaModel = new Tarea();
        $tareas = $tareaModel->getByUsuario($idUsuario, $filtros, $porPagina, $offset);
        $total = $tareaModel->countByUsuario($idUsuario, $filtros);
        $totalPaginas = ceil($total / $porPagina);

        $this->render('tarea/mis', [
            'titulo' => 'Mis tareas',
            'tareas' => $tareas,
            'totalPaginas' => $totalPaginas,
            'pagina' => $pagina,
            'filtros' => $filtros,
            'tiposEstado' => $tareaModel->getTiposEstado(),
            'prioridades' => $tareaModel->getPrioridades(),
            'tiposSolicitud' => $tareaModel->getTiposSolicitud(),
            'tiposDocumento' => $tareaModel->getTiposDocumento(),
        ]);
    }

    public function index(): void {
        if (!Auth::hasPermission('tarea/index', 'ver')) {
            header('Location: ' . APP_URL . 'dashboard/index');
            exit;
        }

        $filtros = [
            'estado_tarea' => $_GET['estado_tarea'] ?? null,
            'texto' => trim($_GET['texto'] ?? ''),
            'id_prioridad' => $_GET['id_prioridad'] ?? null,
            'id_tipo_solicitud' => $_GET['id_tipo_solicitud'] ?? null,
            'id_tipo_documento' => $_GET['id_tipo_documento'] ?? null,
        ];

        $pagina = max(1, (int)($_GET['pagina'] ?? 1));
        $porPagina = 20;
        $offset = ($pagina - 1) * $porPagina;

        $tareaModel = new Tarea();
        $tareas = $tareaModel->getTodas($filtros, $porPagina, $offset);
        $total = $tareaModel->countTodas($filtros);
        $totalPaginas = ceil($total / $porPagina);

        $this->render('tarea/index', [
            'titulo' => 'Gestión de tareas',
            'tareas' => $tareas,
            'totalPaginas' => $totalPaginas,
            'pagina' => $pagina,
            'filtros' => $filtros,
            'total' => $total,
            'tiposEstado' => $tareaModel->getTiposEstado(),
            'prioridades' => $tareaModel->getPrioridades(),
            'tiposSolicitud' => $tareaModel->getTiposSolicitud(),
            'tiposDocumento' => $tareaModel->getTiposDocumento(),
        ]);
    }

    public function ver($id): void {
    if (!Auth::check()) {
        header('Location: ' . APP_URL . 'auth/login');
        exit;
    }

    $idTarea = (int)$id;
    if ($idTarea <= 0) {
        header('Location: ' . APP_URL . 'tarea/mis');
        exit;
    }

    // ✅ DECLARAR UNA SOLA VEZ AL INICIO
    $tareaModel = new Tarea();
    $usuarioModel = new Usuario();
    
    // ✅ OBTENER TAREA PRIMERO
    $tarea = $tareaModel->getById($idTarea);
    if (!$tarea) {
        header('Location: ' . APP_URL . 'tarea/mis');
        exit;
    }

    // ✅ ÚLTIMO ESTADO
    $ultimoEstado = $tareaModel->getUltimoEstado($idTarea);
    if ($ultimoEstado) {
        $tarea['nombre_estado_tarea'] = $ultimoEstado['nombre'];
        $tarea['fecha_tarea_estado'] = $ultimoEstado['fecha_tarea_estado'];
    }

    // ✅ TODOS LOS DATOS
    $historialEstados = $tareaModel->getHistorialEstados($idTarea);
    $comentarios = $tareaModel->getComentariosSolicitud((int)$tarea['id_solicitud']);
    $tiposEstado = $tareaModel->getTiposEstado();
    $usuarios = $usuarioModel->listarActivos();

    $this->render('tarea/ver', [
        'titulo' => 'Detalle de tarea',
        'tarea' => $tarea,
        'historialEstados' => $historialEstados,
        'comentarios' => $comentarios,
        'tiposEstado' => $tiposEstado,
        'usuarios' => $usuarios,
    ]);
}


    public function cambiar_estado(): void {
    if (!Auth::check() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Acceso denegado'];
        header('Location: ' . APP_URL . 'tarea/mis');
        exit;
    }

    // ✅ EXTRAER DATOS CON VALIDACIÓN
    $idUsuario = (int)($_SESSION['user']['idusuario'] ?? 0);
    $idTarea = (int)($_POST['id_tarea'] ?? 0);
    $idTareaEstadoTipo = (int)($_POST['id_tarea_estado_tipo'] ?? 0);
    $idUsuarioAsignado = !empty($_POST['id_usuario_asignado']) ? (int)$_POST['id_usuario_asignado'] : null;
    $comentario = trim($_POST['comentario'] ?? '');

    // ✅ VALIDAR ANTES DE CONTINUAR
    if ($idUsuario <= 0) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Sesión inválida'];
        header('Location: ' . APP_URL . 'tarea/mis');
        exit;
    }
    
    if ($idTarea <= 0 || $idTareaEstadoTipo <= 0) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => "Tarea inválida: ID=$idTarea, Estado=$idTareaEstadoTipo"];
        header('Location: ' . APP_URL . 'tarea/ver/' . $idTarea);
        exit;
    }

    // ✅ SUBIR DOCUMENTO - MEJORADO
    $ruta = '';
    $documento = null;
    
    if (!empty($_FILES['documento_tarea']['name']) && $_FILES['documento_tarea']['error'] === UPLOAD_ERR_OK) {
        // Verificar tamaño máximo (5MB)
        if ($_FILES['documento_tarea']['size'] > 5 * 1024 * 1024) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Archivo muy grande (máx 5MB)'];
            header('Location: ' . APP_URL . 'tarea/ver/' . $idTarea);
            exit;
        }

        $publicPath = realpath(__DIR__ . '/../../public');
        if (!$publicPath) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Error de ruta pública'];
            header('Location: ' . APP_URL . 'tarea/ver/' . $idTarea);
            exit;
        }

        $baseStorage = $publicPath . '/storage/tareas/';
        if (!is_dir($baseStorage)) {
            mkdir($baseStorage, 0755, true);
        }
        
        $subCarpeta = date('Y/m/d/');
        $rutaFisica = $baseStorage . $subCarpeta;
        if (!is_dir($rutaFisica)) {
            mkdir($rutaFisica, 0755, true);
        }

        $ext = pathinfo($_FILES['documento_tarea']['name'], PATHINFO_EXTENSION);
        $ext = $ext ? '.' . strtolower($ext) : '';
        $nombreArchivo = 'tarea_' . date('Ymd_His') . '_' . mt_rand(1000, 9999) . $ext;
        $destino = $rutaFisica . $nombreArchivo;

        if (move_uploaded_file($_FILES['documento_tarea']['tmp_name'], $destino)) {
            $ruta = 'storage/tareas/' . $subCarpeta;
            $documento = $nombreArchivo;
        } else {
            // No falla el proceso si no sube archivo
            $ruta = '';
            $documento = null;
        }
    }

    // ✅ CAMBIAR ESTADO
    $tareaModel = new Tarea();
    $ok = $tareaModel->cambiarEstado($idTarea, $idTareaEstadoTipo, $idUsuario, $ruta, $documento);

    // ✅ OPERACIONES SECUNDARIAS SOLO si éxito
    if ($ok) {
        if ($idUsuarioAsignado) {
            $tareaModel->reasignarTarea($idTarea, $idUsuarioAsignado, $idUsuario);
        }

        if ($comentario !== '') {
            $tareaModel->agregarComentarioTarea($idTarea, $comentario, $idUsuario);
        }
    }

    // ✅ FLASH MESSAGE
    $_SESSION['flash'] = [
        'type' => $ok ? 'success' : 'error',
        'msg' => $ok ? 
            'Tarea actualizada correctamente' . 
            (!empty($documento) ? ' (Documento: ' . $documento . ')' : '') :
            'Error al actualizar tarea'
    ];
    
    header('Location: ' . APP_URL . 'tarea/ver/' . $idTarea);
    exit;
}


    public function agregar_comentario(): void {
        if (!Auth::check() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . APP_URL . 'tarea/mis');
            exit;
        }

        // ✅ CORREGIDO: idusuario (sin guión)
        $idUsuario = (int)($_SESSION['user']['idusuario'] ?? 0);
        $idTarea = (int)($_POST['id_tarea'] ?? 0);
        $comentario = trim($_POST['comentario'] ?? '');

        if ($idUsuario <= 0 || $idTarea <= 0 || $comentario === '') {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Datos inválidos'];
            header('Location: ' . APP_URL . 'tarea/mis');
            exit;
        }

        $tareaModel = new Tarea();
        $tareaModel->agregarComentarioTarea($idTarea, $comentario, $idUsuario);

        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Comentario agregado'];
        header('Location: ' . APP_URL . 'tarea/ver/' . $idTarea);
        exit;
    }

   
}
