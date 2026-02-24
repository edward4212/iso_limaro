<?php

class SolicitudController extends Controller
{
    /** @var PDO */
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

   public function crear(): void
{
    if (!Auth::hasPermission('solicitud/crear', 'crear')) {
        header('Location: ' . APP_URL . 'dashboard/index');
        exit;
    }

    $modelo      = new Solicitud();
    $tipos       = $modelo->obtenerTipos();
    $prioridades = $modelo->obtenerPrioridades();

    // Catálogo de tipos de documento activos
    $stmt = $this->db->query("
        SELECT id_tipo_documento, tipo_documento
        FROM tipo_documento
        WHERE id_estado = 1
        ORDER BY tipo_documento
    ");
    $tiposDocumento = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $mensaje = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // Obtener id_empleado desde la sesión/usuario
        $idEmpleado = 0;
        if (!empty($_SESSION['user']['idusuario'])) {
            $idUsuario = (int)$_SESSION['user']['idusuario'];
            $sqlEmp = "SELECT id_empleado FROM usuario WHERE id_usuario = :id";
            $stEmp  = $this->db->prepare($sqlEmp);
            $stEmp->execute([':id' => $idUsuario]);
            $rowEmp = $stEmp->fetch(PDO::FETCH_ASSOC);
            if ($rowEmp && !empty($rowEmp['id_empleado'])) {
                $idEmpleado = (int)$rowEmp['id_empleado'];
            }
        }

        if ($idEmpleado <= 0) {
            $_SESSION['flash'] = [
                'type' => 'error',
                'msg'  => 'No se pudo determinar el empleado asociado al usuario actual.',
            ];
            header('Location: ' . APP_URL . 'dashboard/index');
            exit;
        }

        // Manejo de archivo anexo
        $nombreArchivo = null;
        $rutaArchivo   = null;

        if (!empty($_FILES['documento']['name']) && is_uploaded_file($_FILES['documento']['tmp_name'])) {

            // Ruta base física: public/storage/solicitudes/
            // Asumiendo que este controlador está en app/controllers/
            $publicPath  = realpath(__DIR__ . '/../../public'); // ajusta niveles según tu estructura real
            $baseStorage = $publicPath . '/storage/solicitudes/';

            if (!is_dir($baseStorage)) {
                mkdir($baseStorage, 0777, true);
            }

            // Subcarpeta por fecha: AAAA/MM/DD/
            $subCarpeta = date('Y') . '/' . date('m') . '/' . date('d') . '/';
            $rutaFisica = $baseStorage . $subCarpeta;

            if (!is_dir($rutaFisica)) {
                mkdir($rutaFisica, 0777, true);
            }

            // Nombre de archivo único (aquí puedes luego incluir el ID de solicitud si lo necesitas)
            $ext = pathinfo($_FILES['documento']['name'], PATHINFO_EXTENSION);
            $ext = $ext ? '.' . strtolower($ext) : '';
            $nombreArchivo = 'anexo_' . date('Ymd_His') . '_' . mt_rand(1000, 9999) . $ext;

            $destino = $rutaFisica . $nombreArchivo;

            if (move_uploaded_file($_FILES['documento']['tmp_name'], $destino)) {
                // Ruta relativa desde public/ para servir el archivo
                // Ejemplo: storage/solicitudes/2025/12/17/
                $rutaArchivo = 'storage/solicitudes/' . $subCarpeta;
            } else {
                $nombreArchivo = null;
                $rutaArchivo   = null;
            }
        }

        $data = [
            'id_empleado'       => $idEmpleado,
            'id_prioridad'      => (int)($_POST['id_prioridad'] ?? 1),
            'id_tipo_documento' => (int)($_POST['id_tipo_documento'] ?? 0),
            'id_tipo_solicitud' => (int)($_POST['id_tipo_solicitud'] ?? 0),
            'codigo_documento'  => trim($_POST['codigo_documento'] ?? ''),
            'solicitud'         => trim($_POST['solicitud'] ?? ''),
            'ruta'              => $rutaArchivo,    // p.ej. storage/solicitudes/2025/12/17/
            'documento'         => $nombreArchivo,  // p.ej. anexo_20251217_120000_1234.pdf
        ];

        $idUsuarioComentario = (int)($_SESSION['user']['idusuario'] ?? 0); // usuario que crea la solicitud

        $idNueva = $modelo->crear($data, $idUsuarioComentario);

        if ($idNueva) {
            $_SESSION['flash'] = [
                'type' => 'success',
                'msg'  => 'Solicitud registrada correctamente.',
            ];
            header('Location: ' . APP_URL . 'solicitud/mis');
            exit;
        } else {
            $mensaje = 'No se pudo registrar la solicitud.';
        }
    }

    $titulo = 'Radicar solicitud de documento';
    $this->render('solicitud/crear', [
        'titulo'         => $titulo,
        'tipos'          => $tipos,
        'prioridades'    => $prioridades,
        'mensaje'        => $mensaje,
        'tiposDocumento' => $tiposDocumento,
    ]);
}


    public function mis(): void
    {
        if (!Auth::check()) {
            header('Location: ' . APP_URL . 'auth/login');
            exit;
        }

        // Obtener id_empleado desde la sesión/usuario
        $idEmpleado = 0;
        if (!empty($_SESSION['user']['idusuario'])) {
            $idUsuario = (int)$_SESSION['user']['idusuario'];
            $sqlEmp = "SELECT id_empleado FROM usuario WHERE id_usuario = :id";
            $stEmp  = $this->db->prepare($sqlEmp);
            $stEmp->execute([':id' => $idUsuario]);
            $rowEmp = $stEmp->fetch(PDO::FETCH_ASSOC);
            if ($rowEmp && !empty($rowEmp['id_empleado'])) {
                $idEmpleado = (int)$rowEmp['id_empleado'];
            }
        }

        if ($idEmpleado <= 0) {
            $_SESSION['flash'] = [
                'type' => 'error',
                'msg'  => 'No se pudo determinar el empleado asociado al usuario actual.',
            ];
            header('Location: ' . APP_URL . 'dashboard/index');
            exit;
        }

        $modelo = new Solicitud();
        $lista  = $modelo->obtenerMisSolicitudes($idEmpleado);

        $titulo = 'Mis solicitudes';
        $this->render('solicitud/mis', compact('titulo', 'lista'));
    }

    public function index(): void
    {
        if (!Auth::hasPermission('solicitud/index', 'ver')) {
            header('Location: ' . APP_URL . 'dashboard/index');
            exit;
        }

        $modelo = new Solicitud();

        $filtros = [
            'id_estado_solicitud' => (int)($_GET['id_estado_solicitud'] ?? 0),
            'id_tipo_solicitud'   => (int)($_GET['id_tipo_solicitud'] ?? 0),
            'texto'               => trim($_GET['texto'] ?? ''),
        ];

        $lista   = $modelo->obtenerTodas($filtros);
        $estados = $modelo->obtenerEstados();
        $tipos   = $modelo->obtenerTipos();

        $titulo = 'Gestión de solicitudes';
        $this->render('solicitud/index', compact('titulo', 'lista', 'estados', 'tipos', 'filtros'));
    }

public function asignar(): void
{
    header('Content-Type: application/json; charset=utf-8');

    if (!Auth::hasPermission('solicitud/asignar', 'editar')) {
        echo json_encode(['success' => false, 'msg' => 'Sin permisos']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'msg' => 'Método no permitido']);
        exit;
    }

    $idSolicitud = (int)($_POST['id_solicitud'] ?? 0);
    $idUsuario   = (int)($_POST['id_usuario_asignado'] ?? 0); // nuevo responsable

    if ($idSolicitud <= 0 || $idUsuario <= 0) {
        echo json_encode(['success' => false, 'msg' => 'Datos incompletos']);
        exit;
    }

    $modelo         = new Solicitud();
    $idUsuarioTarea = (int)($_SESSION['user']['idusuario'] ?? 0); // quien ejecuta

    // 1) ¿Ya hay tarea ligada a esta solicitud?
    $idTareaExistente = $modelo->existeTareaPorSolicitud($idSolicitud);

    if ($idTareaExistente) {
        // 2A) Solo crear nuevo estado con el nuevo responsable
        // AJUSTA este id al que corresponda en tu tabla tarea_estado_tipo
        $idTipoEstadoAsignada = 1; 

        $ok  = $modelo->crearEstadoTarea($idTareaExistente, $idUsuario, $idTipoEstadoAsignada);
        $msg = 'Responsable de la tarea actualizado correctamente.';
    } else {
        // 2B) No hay tarea: usar SP que crea tarea + estado inicial
        $ok  = $modelo->asignarConTarea($idSolicitud, $idUsuario, $idUsuarioTarea);
        $msg = 'Solicitud asignada y tarea creada correctamente.';
    }

    echo json_encode([
        'success' => $ok,
        'msg'     => $ok ? $msg : 'No se pudo asignar la solicitud.',
    ]);
    exit;
}

    public function usuarios_asignables(): void
    {
        if (!Auth::check()) {
            http_response_code(401);
            exit;
        }

        // Ajusta los roles que pueden gestionar tareas
        $sql = "SELECT u.id_usuario, e.nombre_completo
                FROM usuario u
                JOIN empleado e ON e.id_empleado = u.id_empleado
                WHERE u.id_estado = 1
                 --  AND u.id_rol IN (2)
                ORDER BY e.nombre_completo";
        $st   = $this->db->query($sql);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($rows);
        exit;
    }

   public function buscarPorCodigo(): void
{
    if (!Auth::check()) {
        http_response_code(401);
        exit;
    }

    $q = trim($_GET['q'] ?? '');
    if ($q === '') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([]);
        exit;
    }

    // Versión SIN parámetros para descartar el HY093
    $qEsc = str_replace("'", "''", $q); // escape simple
    $sql = "
        SELECT
            id_documento,
            codigo,
            nombre_documento
        FROM documento
        WHERE codigo LIKE '%{$qEsc}%'
           OR nombre_documento LIKE '%{$qEsc}%'
        ORDER BY codigo
        LIMIT 20
    ";

    $st   = $this->db->query($sql);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($rows);
    exit;
}

}
