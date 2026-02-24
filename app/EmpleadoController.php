<?php

require_once __DIR__ . '/../models/Empleado.php';

class EmpleadoController extends Controller
{
    public function index(): void
    {
        if (!Auth::hasPermission('empleado/index', 'ver')) {
            header('Location: ' . APP_URL . 'dashboard/index');
            exit;
        }

        $model = new Empleado();
        $lista = $model->listar();

        $titulo = 'Empleados';

        $this->render('empleado/index', compact('titulo', 'lista'));
    }

    public function crear(): void
{
    if (!Auth::hasPermission('empleado/crear', 'crear')) {
        header('Location: ' . APP_URL . 'empleado/index');
        exit;
    }

    $empleadoModel = new Empleado();

    // listas para los selects
    $cargos   = $empleadoModel->obtenerCargosActivos();
    $empresas = $empleadoModel->obtenerEmpresasActivas();

    $mensaje = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
       $data = [
                'nombre_completo'    => trim($_POST['nombre_completo'] ?? ''),
                'img_empleado'       => $nombreArchivoSubido ?? null,
                'correo_empleado'    => trim($_POST['correo_empleado'] ?? ''),
                'id_cargo'           => (int)($_POST['id_cargo'] ?? 0),
                'id_empresa'         => (int)($_POST['id_empresa'] ?? 0),
                'id_estado_empleado' => 1,
            ];


        if ($empleadoModel->crear($data)) {
            $_SESSION['flash'] = [
                'type' => 'success',
                'msg'  => 'Empleado creado correctamente.',
            ];
            header('Location: ' . APP_URL . 'empleado/index');
            exit;
        } else {
            $mensaje = 'No se pudo crear el empleado.';
        }
    }

    $titulo = 'Crear empleado';
    $this->render('empleado/crear', compact('titulo', 'cargos', 'empresas', 'mensaje'));
}


   public function editar($id): void
{
    if (!Auth::hasPermission('empleado/editar', 'editar')) {
        header('Location: ' . APP_URL . 'empleado/index');
        exit;
    }

    $id    = (int)$id;
    $model = new Empleado();

    $empleado = $model->obtenerPorId($id);
    if (!$empleado) {
        header('Location: ' . APP_URL . 'empleado/index');
        exit;
    }

    // listas para los <select>
    $cargos   = $model->obtenerCargosActivos();
    $empresas = $model->obtenerEmpresasActivas();

    $mensaje = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = [
            'nombre_completo'    => trim($_POST['nombre_completo'] ?? ''),
            'img_empleado'       => $empleado['img_empleado'] ?? 'usuario.png',
            'correo_empleado'    => trim($_POST['correo_empleado'] ?? ''),
            'id_cargo'           => (int)($_POST['id_cargo'] ?? 0),
            'id_empresa'         => (int)($_POST['id_empresa'] ?? 1),
            'id_estado_empleado' => (int)($_POST['id_estado_empleado'] ?? 1),
        ];

        try {
            if ($model->actualizar($id, $data)) {
                $_SESSION['flash'] = [
                    'type' => 'success',
                    'msg'  => 'Empleado actualizado correctamente.',
                ];
                header('Location: ' . APP_URL . 'empleado/index');
                exit;
            }

            $_SESSION['flash'] = [
                'type' => 'error',
                'msg'  => 'No se pudo actualizar el empleado.',
            ];
        } catch (PDOException $e) {
            $_SESSION['flash'] = [
                'type' => 'error',
                'msg'  => 'Error al actualizar el empleado.',
            ];
        }

        $empleado = $model->obtenerPorId($id);
    }

    $titulo = 'Editar empleado';

    $this->render('empleado/editar', [
        'titulo'   => $titulo,
        'empleado' => $empleado,
        'cargos'   => $cargos,
        'empresas' => $empresas,
        'mensaje'  => $mensaje,
    ]);
}

public function obtenerActivos(): array
    {
        $sql = "SELECT id_empleado, nombre_completo
                FROM empleado
                WHERE id_estado_empleado = 1
                ORDER BY nombre_completo";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function obtenerConEmpresa(int $idEmpleado): ?array
    {
        $sql = "SELECT 
                    e.id_empleado,
                    e.nombre_completo,
                    e.correo_empleado,
                    emp.nombre_empresa
                FROM empleado e
                INNER JOIN empresa emp ON emp.id_empresa = e.id_empresa
                WHERE e.id_empleado = :id";
        $st = $this->db->prepare($sql);
        $st->execute([':id' => $idEmpleado]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

}
