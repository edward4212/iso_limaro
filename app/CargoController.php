<?php

class CargoController extends Controller
{
    private string $uploadDir;

    public function __construct()
    {
        // Carpeta base para los manuales de cargos
        $this->uploadDir = BASE_PATH . '/public/storage/cargos/';
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0775, true);
        }
    }

    public function index(): void
    {
        if (!Auth::hasPermission('cargo/index', 'ver')) {
            header('Location: ' . APP_URL . 'dashboard/index');
            exit;
        }

        $model = new Cargo();
        $lista = $model->obtenerTodos();

        $titulo = 'Cargos';
        $this->render('cargo/index', compact('titulo', 'lista'));
    }

    public function crear(): void
    {
        if (!Auth::hasPermission('cargo/crear', 'crear')) {
            header('Location: ' . APP_URL . 'cargo/index');
            exit;
        }

        $model    = new Cargo();
        $verModel = new CargoManualVersion();
        $mensaje  = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombreCargo  = trim($_POST['cargo'] ?? '');
            $idEstado     = (int)($_POST['id_estado'] ?? 1);
            $fechaEmision = trim($_POST['fecha_emision'] ?? date('Y-m-d'));

            $slug     = preg_replace('~[^a-z0-9]+~i', '_', strtolower($nombreCargo));
            $cargoDir = $this->uploadDir . $slug . '/';

            if (!is_dir($cargoDir)) {
                mkdir($cargoDir, 0775, true);
            }

            $manual  = null;
            $version = 0;

            if (!empty($_FILES['manual_funciones']['name']) &&
                $_FILES['manual_funciones']['error'] === UPLOAD_ERR_OK) {

                // Nuevo cargo → versión 1
                $version    = 1;
                $versionDir = $cargoDir . 'v' . $version . '/';
                if (!is_dir($versionDir)) {
                    mkdir($versionDir, 0775, true);
                }

                $tmp  = $_FILES['manual_funciones']['tmp_name'];
                $name = basename($_FILES['manual_funciones']['name']);
                $ext  = pathinfo($name, PATHINFO_EXTENSION);

                $baseFile = 'manual_' . $slug . '_v' . $version;
                $nuevo    = $baseFile . '.' . $ext;
                $dest     = $versionDir . $nuevo;

                if (move_uploaded_file($tmp, $dest)) {
                    $manual = $slug . '/v' . $version . '/' . $nuevo;
                }
            }

            $data = [
                'cargo'            => $nombreCargo,
                'manual_funciones' => $manual,
                'id_estado'        => $idEstado,
            ];

            $idCargo = $model->crear($data);

                if ($idCargo > 0) {
                    if ($manual && $version === 1) {
                        $verModel->crearVersion($idCargo, $version, $fechaEmision, $manual);
                    }
                
                    $_SESSION['flash'] = [
                        'type' => 'success',
                        'msg'  => 'Cargo creado correctamente.',
                    ];
                    header('Location: ' . APP_URL . 'cargo/index');
                    exit;
                }
                
                // aquí sabemos que falló (posible duplicado)
                $_SESSION['flash'] = [
                    'type' => 'error',
                    'msg'  => 'Ya existe un cargo con ese nombre.',
                ];
                header('Location: ' . APP_URL . 'cargo/crear');
                exit;
        }

        $titulo = 'Crear cargo';
        $this->render('cargo/crear', compact('titulo', 'mensaje'));
    }

    public function editar($id): void
    {
        if (!Auth::hasPermission('cargo/editar', 'editar')) {
            header('Location: ' . APP_URL . 'cargo/index');
            exit;
        }

        $id       = (int)$id;
        $model    = new Cargo();
        $verModel = new CargoManualVersion();

        $cargo = $model->obtenerPorId($id);
        if (!$cargo) {
            header('Location: ' . APP_URL . 'cargo/index');
            exit;
        }

        $mensaje = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombreCargo  = trim($_POST['cargo'] ?? '');
            $idEstado     = (int)($_POST['id_estado'] ?? 1);
            $fechaEmision = trim($_POST['fecha_emision'] ?? date('Y-m-d'));
            $accionManual = $_POST['accion_manual'] ?? 'nueva_version';

            $slug     = preg_replace('~[^a-z0-9]+~i', '_', strtolower($nombreCargo));
            $cargoDir = $this->uploadDir . $slug . '/';

            if (!is_dir($cargoDir)) {
                mkdir($cargoDir, 0775, true);
            }

            // Por defecto se mantiene el manual actual
            $manual = $cargo['manual_funciones'] ?? null;

            if (!empty($_FILES['manual_funciones']['name']) &&
                $_FILES['manual_funciones']['error'] === UPLOAD_ERR_OK) {

                $tmp  = $_FILES['manual_funciones']['tmp_name'];
                $name = basename($_FILES['manual_funciones']['name']);
                $ext  = pathinfo($name, PATHINFO_EXTENSION);

                if ($accionManual === 'nueva_version') {
                    // CAMINO 1: crear nueva versión
                    $ultima  = $verModel->obtenerUltimaVersion($id);
                    $version = $ultima + 1;

                    $versionDir = $cargoDir . 'v' . $version . '/';
                    if (!is_dir($versionDir)) {
                        mkdir($versionDir, 0775, true);
                    }

                    $baseFile = 'manual_' . $slug . '_v' . $version;
                    $nuevo    = $baseFile . '.' . $ext;
                    $dest     = $versionDir . $nuevo;

                    if (move_uploaded_file($tmp, $dest)) {
                        $manual = $slug . '/v' . $version . '/' . $nuevo;
                        $verModel->crearVersion($id, $version, $fechaEmision, $manual);
                    }

                } else {
                    // CAMINO 2: reemplazar archivo de la versión actual (no cambia número de versión)
                   $ultima = $verModel->obtenerUltimaPorCargo($id);
if ($ultima) {
    $version = (int)$ultima['version'];

    $relPathActual = $ultima['archivo'];
    $destActual    = $this->uploadDir . $relPathActual;

    if (file_exists($destActual)) {
        unlink($destActual);
    }

    $versionDir = $this->uploadDir . dirname($relPathActual) . '/';
    if (!is_dir($versionDir)) {
        mkdir($versionDir, 0775, true);
    }

    $baseFile = 'manual_' . $slug . '_v' . $version;
    $nuevo    = $baseFile . '.' . $ext;
    $dest     = $versionDir . $nuevo;

    if (move_uploaded_file($tmp, $dest)) {
        $manual = $slug . '/v' . $version . '/' . $nuevo;

        // ACTUALIZAR la fila existente de esa versión
        $verModel->actualizarArchivoVersion($id, $version, $fechaEmision, $manual);
    }
} else {
                        // Si no había versiones, lo tratamos como nueva versión 1
                        $version    = 1;
                        $versionDir = $cargoDir . 'v' . $version . '/';
                        if (!is_dir($versionDir)) {
                            mkdir($versionDir, 0775, true);
                        }

                        $baseFile = 'manual_' . $slug . '_v' . $version;
                        $nuevo    = $baseFile . '.' . $ext;
                        $dest     = $versionDir . $nuevo;

                        if (move_uploaded_file($tmp, $dest)) {
                            $manual = $slug . '/v' . $version . '/' . $nuevo;
                            $verModel->crearVersion($id, $version, $fechaEmision, $manual);
                        }
                    }
                }
            }

            $data = [
                'cargo'            => $nombreCargo,
                'manual_funciones' => $manual,
                'id_estado'        => $idEstado,
            ];

            if ($model->actualizar($id, $data)) {
                $_SESSION['flash'] = [
                    'type' => 'success',
                    'msg'  => 'Cargo actualizado correctamente.',
                ];
                header('Location: ' . APP_URL . 'cargo/index');
                exit;
            }

            $mensaje = 'No se pudo actualizar el cargo.';
            $cargo   = $model->obtenerPorId($id);
        }

        $titulo = 'Editar cargo';
        $this->render('cargo/editar', compact('titulo', 'cargo', 'mensaje'));
    }
    
    
}
