# PowerOn System - AuthorizationService

Servicio de autorización de usuarios, inicio, cierre, pausa y resumen de sesión.

## Instalación vía Composer

Podés instalar AuthorizationService vía
[Composer](https://getcomposer.org)  a través de la consola:

``` bash
$ composer require poweronsystem/authorizationservice
```
## Requisitos

* PHP >= 5.4
* poweronsystem/utility: "^0.1.3"

## Uso

### Instancia y configuración
Creación de la clase y configuración básica de la misma

``` php
//Autoload composer
require '/vendor/autoload.php';

//Configuración del autenticador (Ver archivo src/Authorization.php)
$config =  [
  'login_session_time' => 7200,
  'login_session_inactive_time' => 3600
];

//Establecemos los permisos para los sectores
$permissions = [];

//Creamos una instancia del autorizador
$auth = new PowerOn\Authorization\Authorization($config, $permissions);

```

### Ejemplo de autenticación de usuarios

#### Adaptador de sesiones
Adaptador de ejemplo
``` php

namespace App;

use PowerOn\Database\Model;
use PowerOn\Authorization\AuthorizationAdapterInterface;
use PowerOn\Authorization\UserCredentials;

public class MyAdapter implements AuthorizationAdapterInterface {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }

    public function login(UserCredentials $credentials) {
        /* Lógica de comprobación de usuario ...
         * Lógica de Password Hasher personalizada ...
         * $user = $db->getUser($credentials->username, $credentials->password);
         * ...
         */
        
        //Si es inválido retorna FALSE
        
        //Si es correcto devuelve las credenciales con los datos del usuario que se deseen cargar
        $credentials->setUserData($user);
        
        //Se establece el nivel de acceso del usuario
        $credentials->setUserAccessLevel($user['access_level']);
        
        //Otra lógica adicional antes de finalizar...
                
        return $credentials;
    }

    public function logout() {
        return TRUE;
    }

    public function pauseSession() {
        return TRUE;
    }

    public function resumeSession() {
        return TRUE;
    }

}
```
Registrar adaptador a la clase

``` php
//Base de datos requerida por el adaptador de ejemplo
$database = new PDO();

//Instancia del adaptador
$adapter = new App\MyAdapter($database);

//Registro del adaptador
$auth->registerAdapter($adapter);

```

#### Inicio de sesión

``` php
//Credenciales obtenidas de un formulario
$credentals = new PowerOn\Authorization\UserCredentials($_POST['username'], $_POST['password']);
try {
  $auth->login($login_credentials);
  //Login success
  
  echo $auth->getStatus(); //ok
  
} catch (PowerOn\Authorization\AuthorizationException $e) {
  //Lógica de excepciones
  
  echo $auth->getStatus(); //user_not_found | user_error
}

```
#### Cierre de sesión

``` php
$auth->logout();

echo $auth->getStatus(); //user_not_found

```

#### Pausa

``` php
$auth->pause();

echo $auth->getStatus(); //paused
var_dump($auth->isValid()); //bool(false)

```

#### Resumen

``` php
$auth->resume();

echo $auth->getStatus(); //ok
echo $auth->getUserCredentials->getSessionInactiveTime(); //120
var_dump($auth->isValid()); //bool(true)

```

### Ejemplo de authorización de sectores
Ejemplo básico para la validación de sectores

#### Configuración de permisos
``` php
//Establecemos los permisos para los sectores de la siguiente manera
$permissions = [
  //La url "/home" require un nivel de acceso básico de 1
  '/home' => ['access_level' => 1],
  
  //Cualquier sector referente a "/admin" requerirá un nivel de acceso de 10 o superior
  '/admin/*' => ['access_level' => 10],
  
  //En este ejemplo solo permitimos el acceso a "/account/new-password" para que el usuario cambie su contraseña
  //solo en caso que haya transcurrido más de un dia desde la última modificación.
  '/account/new-password => ['allowed' => function(\PowerOn\Authorization\UserCredentials $userCredentials) {
      return (time() - $userCredentials['last_password_request_time']) > 86400;
  }]
];


//Creamos una instancia del autorizador
$auth = new PowerOn\Authorization\Authorization($config, $permissions);

```

#### Ejecución de validación de permisos de sectores

``` php
//Clase Request genérica de ejemplo
$request = new \App\MyRequestClass();

//Url obtenida de una clase request
$url = $request->getUrl();

//Otros archivos adicionales a pasar en la función callable del array de permisos con la clave "allowed"
$otherFile = new \App\SomeClass();

$result = $auth->sector($url, $request, $otherFile);

var_dump($result); //bool(false) | bool(true)
echo $auth->getStatus(); //sector_low_access_level | sector_not_allowed

```
