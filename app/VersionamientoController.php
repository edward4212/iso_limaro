<?php

class VersionamientoController extends Controller
{
    public function index(): void
    {
        if (!Auth::hasPermission('versionamiento/index', 'ver')) {
            header('Location: ' . APP_URL . 'dashboard/index');
            exit;
        }

        $model = new Versionamiento();
        $lista = $model->obtenerTodos();

        $titulo = 'Versionamiento de Documentos';

        $this->render('versionamiento/index', compact('titulo', 'lista'));
    }

    public function crear(): void
    {
        if (!Auth::hasPermission('versionamiento/crear', 'crear')) {
            header('Location: ' . APP_URL . 'dashboard/index');
            exit;
        }

        $model        = new Versionamiento();
        $documentos   = $model->obtenerDocumentos();
        $funcionarios = $model->obtenerFuncionarios();
        $titulo       = 'Versionamiento de Documentos';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $idDocumento = (int)($_POST['id_documento'] ?? 0);
            $descripcion = trim($_POST['descripcion_version'] ?? '');
            $idElabora   = (int)($_POST['id_usuario_creacion'] ?? 0);
            $idRevisa    = (int)($_POST['id_usuario_revision'] ?? 0);
            $idAprueba   = (int)($_POST['id_usuario_aprobacion'] ?? 0);

            $fElabora = $_POST['fecha_creacion'] ?? null;
            $fRevisa  = $_POST['fecha_revision'] ?? null;
            $fAprueba = $_POST['fecha_aprobacion'] ?? null;

            // Validación básica
            if ($idDocumento === 0 || $descripcion === '' || $idElabora === 0) {
                $_SESSION['flash'] = [
                    'type' => 'error',
                    'msg'  => 'Seleccione documento, descripción y elaborado por.',
                ];
                header('Location: ' . APP_URL . 'versionamiento/crear');
                exit;
            }

            // Archivo obligatorio
            $archivo = $_FILES['documento'] ?? null;
            if (!$archivo || $archivo['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['flash'] = [
                    'type' => 'error',
                    'msg'  => 'Debe seleccionar un archivo válido.',
                ];
                header('Location: ' . APP_URL . 'versionamiento/crear');
                exit;
            }

            $data = [
                'id_documento'          => $idDocumento,
                'descripcion_version'   => $descripcion,
                'id_usuario_creacion'   => $idElabora,
                'id_usuario_revision'   => $idRevisa ?: null,
                'id_usuario_aprobacion' => $idAprueba ?: null,
                'fecha_creacion'        => $fElabora ?: null,
                'fecha_revision'        => $fRevisa ?: null,
                'fecha_aprobacion'      => $fAprueba ?: null,
                'archivo'               => $archivo,
            ];

            $idVersion = $model->crearNuevaVersion($data);

            if ($idVersion === 0) {
                $_SESSION['flash'] = [
                    'type' => 'error',
                    'msg'  => 'No se pudo crear la nueva versión.',
                ];
                header('Location: ' . APP_URL . 'versionamiento/crear');
                exit;
            }

            $_SESSION['flash'] = [
                'type' => 'success',
                'msg'  => 'Versión creada correctamente y versión anterior marcada como obsoleta.',
            ];
            header('Location: ' . APP_URL . 'versionamiento/index');
            exit;
        }

        $this->render('versionamiento/crear', compact('titulo', 'documentos', 'funcionarios'));
    }

    public function editar($id): void
    {
        if (!Auth::hasPermission('versionamiento/editar', 'editar')) {
            header('Location: ' . APP_URL . 'dashboard/index');
            exit;
        }

        $id    = (int)$id;
        $model = new Versionamiento();
        $item  = $model->obtenerPorId($id);

        if (!$item) {
            $_SESSION['flash'] = [
                'type' => 'error',
                'msg'  => 'Versión no encontrada.',
            ];
            header('Location: ' . APP_URL . 'versionamiento/index');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $archivo = $_FILES['documento'] ?? null;
            if (!$archivo || $archivo['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['flash'] = [
                    'type' => 'error',
                    'msg'  => 'Debe seleccionar un archivo válido.',
                ];
                header('Location: ' . APP_URL . 'versionamiento/editar/' . $id);
                exit;
            }

            $ok = $model->actualizarArchivo($id, $archivo);

            $_SESSION['flash'] = [
                'type' => $ok ? 'success' : 'error',
                'msg'  => $ok
                    ? 'Archivo actualizado sin cambiar la versión.'
                    : 'No se pudo actualizar el archivo.',
            ];

            header('Location: ' . APP_URL . 'versionamiento/index');
            exit;
        }

        $titulo = 'Actualizar archivo de versión';
        $this->render('versionamiento/editar', compact('titulo', 'item'));
    }
    
    public function exportarExcel(): void
{
      
    if (!Auth::hasPermission('versionamiento/exportarExcel', 'ver')) {
    header('Location: ' . APP_URL . 'versionamiento/index');
    exit;
}
    
    $model = new Versionamiento();
    $datos = $model->obtenerTodos();

    if (empty($datos)) {
        $_SESSION['flash'] = [
            'type' => 'warning',
            'msg'  => 'No hay datos para exportar.',
        ];
        header('Location: ' . APP_URL . 'versionamiento/index');
        exit;
    }

    // Headers para Excel
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="Versionamientos_' . date('Ymd_His') . '.xls"');
    header('Pragma: public');
    header('Expires: 0');

    // HTML table que Excel interpreta como XLS
    echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    
    // Encabezados
    echo "<tr style='background-color: #2E7D32; color: white; font-weight: bold;'>";
    echo "<td>ID</td>";
    echo "<td>Versión</td>";
    echo "<td>Documento</td>";
    echo "<td>Código</td>";
    echo "<td>Estado</td>";
    echo "<td>Fecha Creación</td>";
    echo "</tr>";

    // Datos
    foreach ($datos as $v) {
        echo "<tr>";
        echo "<td>" . (int)$v['id_versionamiento'] . "</td>";
        echo "<td>v" . (int)$v['numero_version'] . "</td>";
        echo "<td>" . htmlspecialchars($v['nombre_documento']) . "</td>";
        echo "<td>" . htmlspecialchars($v['codigo']) . "</td>";
        echo "<td>" . htmlspecialchars($v['estado_version']) . "</td>";
        echo "<td>" . $v['fecha_creacion'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    exit;
}

public function buscarDocumentos() {
    $term = $_GET['term'] ?? '';
    if (strlen($term) < 2) exit(json_encode([]));
    
    $versionamiento = new Versionamiento();
    $resultados = $versionamiento->buscarDocumentosLike($term);
    
    $lista = [];
    foreach ($resultados as $doc) {
        $lista[] = [
            'id' => $doc['id_documento'],
            'label' => $doc['codigo'] . ' - ' . $doc['nombre_documento'],
            'value' => $doc['codigo'] . ' - ' . $doc['nombre_documento']
        ];
    }
    header('Content-Type: application/json');
    echo json_encode($lista);
    exit;
}

public function buscarFuncionarios() {
    $term = $_GET['term'] ?? '';
    if (strlen($term) < 2) {
        header('Content-Type: application/json');
        echo json_encode([]);
        exit;
    }
    
    $versionamiento = new Versionamiento();
    $resultados = $versionamiento->buscarFuncionariosLike($term);
    
    $lista = [];
    foreach ($resultados as $user) {
        $lista[] = [
            'id' => $user['id_usuario'],
            'label' => $user['nombre_completo'] . ' - ' . $user['cargo'],
            'value' => $user['nombre_completo']
        ];
    }
    header('Content-Type: application/json');
    echo json_encode($lista);
    exit;
}

}
