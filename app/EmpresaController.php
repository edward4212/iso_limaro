<?php

class EmpresaController extends Controller
{
    
    
    public function index(): void
    {
        if (!Auth::check()) {
            header('Location: ' . APP_URL . 'auth/login');
            exit;
        }

        $empresaModel = new Empresa();
        $empresa      = $empresaModel->obtenerUnica();

        $titulo = 'Empresa';
        $this->render('empresa/index', compact('titulo', 'empresa'));
    }

    public function editarIdentidad(): void
    {
        if (!Auth::hasPermission('empresa/editarIdentidad', 'editar')) {
            header('Location: ' . APP_URL . 'empresa/index');
            exit;
        }

        $empresaModel = new Empresa();
        $empresa      = $empresaModel->obtenerUnica();
        $mensaje      = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($empresa['id_empresa'] ?? 1);

            $data = [
                'nombre_empresa'    => trim($_POST['nombre_empresa'] ?? ''),
                'mision'            => trim($_POST['mision'] ?? ''),
                'vision'            => trim($_POST['vision'] ?? ''),
                'politica_calidad'  => trim($_POST['politica_calidad'] ?? ''),
                'objetivos_calidad' => trim($_POST['objetivos_calidad'] ?? ''),
            ];

            if ($empresaModel->actualizarIdentidad($id, $data)) {
                // SweetAlert2 (flash)
                $_SESSION['flash'] = [
                    'type' => 'success',
                    'msg'  => 'Identidad actualizada correctamente.',
                ];

                header('Location: ' . APP_URL . 'empresa/index');
                exit;
            } else {
                // Mensaje de error (también vía SweetAlert si quieres)
                $_SESSION['flash'] = [
                    'type' => 'error',
                    'msg'  => 'No se pudo actualizar la identidad.',
                ];
                $mensaje = 'No se pudo actualizar.'; // opcional si sigues mostrando alertas en la vista
            }

            $empresa = $empresaModel->obtenerUnica();
        }

        $titulo = 'Empresa - Identidad';
        $this->render('empresa/editarIdentidad', compact('titulo', 'empresa', 'mensaje'));
    }

    public function editarEstructura(): void
    {
        if (!Auth::hasPermission('empresa/editarEstructura', 'editar')) {
            header('Location: ' . APP_URL . 'empresa/index');
            exit;
        }

        $empresaModel = new Empresa();
        $empresa      = $empresaModel->obtenerUnica();
        $mensaje      = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($empresa['id_empresa'] ?? 1);

            $uploadDir = BASE_PATH . '/public/storage/empresa/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            $logo         = $empresa['logo'] ?? null;
            $organigrama  = $empresa['organigrama'] ?? null;
            $mapaProcesos = $empresa['mapa_procesos'] ?? null;

            $subir = function (string $campo) use ($uploadDir) {
                if (empty($_FILES[$campo]['name'])) {
                    return null;
                }
                $tmpName = $_FILES[$campo]['tmp_name'];
                $name    = basename($_FILES[$campo]['name']);

                $ext   = pathinfo($name, PATHINFO_EXTENSION);
                $nuevo = $campo . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
                $dest  = $uploadDir . $nuevo;

                if (move_uploaded_file($tmpName, $dest)) {
                    return $nuevo;
                }
                return null;
            };

            if ($nuevo = $subir('logo')) {
                $logo = $nuevo;
            }
            if ($nuevo = $subir('organigrama')) {
                $organigrama = $nuevo;
            }
            if ($nuevo = $subir('mapa_procesos')) {
                $mapaProcesos = $nuevo;
            }

            $data = [
                'logo'          => $logo,
                'organigrama'   => $organigrama,
                'mapa_procesos' => $mapaProcesos,
            ];

            if ($empresaModel->actualizarEstructura($id, $data)) {
                $_SESSION['flash'] = [
                    'type' => 'success',
                    'msg'  => 'Estructura actualizada correctamente.',
                ];

                header('Location: ' . APP_URL . 'empresa/index');
                exit;
            } else {
                $_SESSION['flash'] = [
                    'type' => 'error',
                    'msg'  => 'No se pudo actualizar la estructura.',
                ];
                $mensaje = 'No se pudo actualizar.';
            }

            $empresa = $empresaModel->obtenerUnica();
        }

        $titulo = 'Empresa - Estructura';
        $this->render('empresa/editarEstructura', compact('titulo', 'empresa', 'mensaje'));
    }
}
