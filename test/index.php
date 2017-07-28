<?php
session_start();
define('ROOT', dirname(dirname(__FILE__)));
define('DS', DIRECTORY_SEPARATOR);

require ROOT . DS . 'vendor' . DS . 'autoload.php';

$auth = new PowerOn\Authorization\Authorization([
    'db_pdo' => new PDO(sprintf('mysql:host=%s;dbname=%s', 'localhost', 'data'), 'root', ''),
    'password_min_length' => 3
]);

try {
    if ( !key_exists('user_logged', $_SESSION) ) {
        //Datos Obtenidos de algun formulario
        $login_credentials = new PowerOn\Authorization\UserCredentials('test', '4321');
        //Guardamos el token generado en una sesion
        $_SESSION['user_logged'] = $auth->login($login_credentials);
        
        echo 'login successful';
    } else {
        $user_credentials = $auth->setUserLogged($_SESSION['user_logged']);        
        echo $auth->isLogged() ? 'usuario logueado correctamente' : 'error';
        
        //Datos del sector a autorizar, se pueden obtener por base de datos
        $sector_credentials = new \PowerOn\Authorization\SectorCredentials('local_admin', 1,
                \PowerOn\Authorization\SectorCredentials::AJAX_ACCESS_DENIED);
        
        if ( $auth->sector($sector_credentials, $user_credentials) )  {
            echo '<p>Sector authorized successful</p>';
        }
    }
} catch (PowerOn\Authorization\AuthorizationException $e) {
    echo '<h1>' . $e->getMessage() . '</h1>';
    echo '<h2>' . $e->getFile() . ':' . $e->getLine() . '</h2>';
    !d($e->getContext());
}