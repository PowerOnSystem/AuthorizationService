<?php
session_start();
define('ROOT', dirname(dirname(__FILE__)));
define('DS', DIRECTORY_SEPARATOR);

require ROOT . DS . 'vendor' . DS . 'autoload.php';

$permissions = [
    '/login' => TRUE,
    '/works' => ['access_level' => 1],
    '/works/view/[0-9]+' => ['access_level' => 4],
    '/extruders/*' => ['access_level' => 5],
    '/extruders/delete/[0-9]+' => ['access_level' => 10],
    '/home' => ['access_level' => 5, 'allowed' => function(PowerOn\Authorization\UserCredentials $credentials) {
        return $credentials['first_name'] === 'Lucas';
    }]
];

$auth = new PowerOn\Authorization\Authorization([
    'strict_mode' => TRUE
], $permissions);
$service = new PDO(sprintf('mysql:host=%s;dbname=%s;port=%s', 'localhost', 'absol', 3306), 'root', '');
$database = new PowerOn\Database\Model($service);
$adapter = new App\MainAdapter($database);
$auth->registerAdapter($adapter);

try {
    if ( isset($_POST['action']) ) {
        $action = $_POST['action'];
        if ($action == 'join'){
            //Datos Obtenidos de algun formulario
            $login_credentials = new PowerOn\Authorization\UserCredentials('test', '4321');

            $auth->login($login_credentials);

            echo '<fieldset><legend style="color:green">User login successful</legend>';
            echo '<p style="color:blue">Welcome <strong>' . $auth->getUserCredentials()->username . '</strong></p></fieldset>';
        }
        
        if ($action == 'exit'){
            $auth->logout();
            
            echo '<fieldset><legend style="color:green">User logout successful</legend>';
            echo '<p style="color:blue">Bye bye <strong>' . $auth->getUserCredentials()->username . '</strong>! =(</p></fieldset>';
        }
        
        if ($action == 'pause'){
            $auth->pause();
            
            echo '<fieldset><legend style="color:green">Session paused successful</legend>';
            echo '<p style="color:red">You dont have access anything for now!</p></fieldset>';
        }
        
        if ($action == 'resume'){
            $auth->resume();
            
            echo '<fieldset><legend style="color:green">Session resumed successful</legend>';
            echo '<p style="color:blue">Welcome back <strong>' . $auth->getUserCredentials()->username . '</strong>! =)</p></fieldset>';
        }
    }
    
    echo $auth->isValid() 
        ? '<p style="color:green">User logged in as <strong> ' . $auth->getUserCredentials()->username . '</strong></p>'
        : ($auth->isPaused()
            ? '<p style="color:orange">User logged in as <strong> ' . $auth->getUserCredentials()->username . '</strong> with paused session</p>'
            : '<p style="color:red">User logged out</p>'
        );
    
    // Simulamos un request para la autorización del sector
    echo $auth->sector(isset($_POST['url']) ? $_POST['url'] : '/', NULL)
        ? '<p style="color:green">Sector authorized successful</p>'
        : '<p style="color:red">Sector unauthorized</p>';
    
    echo '<p style="">Auth status: <strong>' . $auth->getStatus() . '</strong></p>';
    if ( $auth->isValid() || $auth->isPaused() ) {
        echo '<p style="">Inactive session time: <strong>' . $auth->getUserCredentials()->getSessionInactiveTime() . '</strong> s.</p>';
        echo '<p style="">Total session time: <strong>' . $auth->getUserCredentials()->getSessionTime() . '</strong> s.</p>';
    }
    
    
    echo '<form method="post">';
        echo '<input type="text" name="url" value="' . (isset($_POST['url']) ? $_POST['url'] : '/') . '">';
        echo '<input type="submit" value="comprobar url" />';
    echo '</form>';
    
    

    echo '<form method="post">';
        echo '<input type="hidden" name="action" value="join">';
        echo '<input type="submit" value="login" />';
    echo '</form>';
    
    
    if ( $auth->getUserCredentials() ) {
        echo '<form method="post">';
            echo '<input type="hidden" name="action" value="exit">';
            echo '<input type="submit" value="logout" />';
        echo '</form>';
    }
    
    if ( $auth->isValid() ) {        
        echo '<form method="post">';
            echo '<input type="hidden" name="action" value="pause">';
            echo '<input type="submit" value="pause" />';
        echo '</form>';
        echo '<form method="post">';
            echo '<input type="hidden" name="action" value="join">';
            echo '<input type="submit" value="re-login" />';
        echo '</form>';
    }
    
    if ( $auth->isPaused() ) {
        echo '<form method="post">';
            echo '<input type="hidden" name="action" value="resume">';
            echo '<input type="submit" value="resume" />';
        echo '</form>';
    }
    
    d($_SESSION);
} catch (PowerOn\Authorization\AuthorizationException $e) {
    echo '<h1>' . $e->getMessage() . '</h1>';
    echo '<h2>' . $e->getFile() . ':' . $e->getLine() . '</h2>';
    !d($e->getContext());
}