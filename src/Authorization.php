<?php
/*
 * Copyright (C) PowerOn Sistemas
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace PowerOn\Authorization;
use Moment\Moment;

/**
 * Authorizer
 * Maneja la autorizacion de acceso al sistema
 * @author Lucas Sosa
 * @version 0.1
 */
class Authorization {
    /**
     * Configuración del autorizador
     * @var array
     */
    public $_config = [];
        
    /**
     * Credenciales del usuario logueado
     * @var UserCredentials
     */
    private $_user_logged = NULL;
    /**
     * Crea una nueva autorización
     * @param array $config Configuración del autorizador
     * <table width=100% border=1>
     * <tr><td>login_email_mode</td><td>(bool) FALSE</td> <td>Especifica si se usa email para el inicio de sesión</td></tr>
     * <tr><td>login_error_max_chances</td><td>(int) 5</td> <td>Cantidad de veces que se puede equivocar en el inicio de sesión</td></tr>
     * <tr><td>login_error_wait_time</td><td>(int) 60</td> <td>Segundos que debe esperar antes de volver a intentar iniciar sesión 
     * luego de exceder el límite de errores</td></tr>
     * <tr><td>login_check_ban</td><td>(bool) FALSE</td> <td>Habilita la verificación de usuario baneado, se deben especificar los parámetros
     * <i>db_field_banned</i> y <i>db_field_banned_date</i></td></tr>
     * <tr><td>login_session_time</td><td>(int) 3600</td> <td>Cantidad de segundos que dura una sesóin</td></tr>
     * <tr><td colspan=3 align=center>Validaciones de campos</td></tr>
     * <tr><td>password_min_length</td><td>(int) 5</td> <td>Cantidad de caracteres mínimos del campo password</td></tr>
     * <tr><td>password_max_length</td><td>(int) 150</td> <td>Cantidad de caracteres máximos del campo password</td></tr>
     * <tr><td>username_min_length</td><td>(int) 4</td> <td>Cantidad de caracteres mínimos del campo username</td></tr>
     * <tr><td>username_max_length</td><td>(int) 40</td> <td>Cantidad de caracteres máximos del campo username</td></tr>
     * <tr><td>access_level_min_val</td><td>(int) 1</td> <td>Rango de valores mínimo del nivel de acceso</td></tr>
     * <tr><td>access_level_max_val</td><td>(int) 1</td> <td>Rango de valores máximo del nivel de acceso</td></tr>
     * <tr><td colspan=3 align=center>Base de datos</td></tr>
     * <tr><td>db_pdo</td><td>(\PDO instance) NULL</td> <td>Instancia del servicio PDO para la conexión de la base de datos</td></tr>
     * <tr><td>db_table_users</td><td>(string) users</td> <td>Nombre de la tabla que contiene los usuarios</td></tr>
     * <tr><td>db_field_id</td><td>(string) id</td> <td>Nombre de la clave principal de la tabla users</td></tr>
     * <tr><td>db_user_mail</td><td>(string) username</td> <td>Nombre del campo username de la tabla users</td></tr>
     * <tr><td>db_user_password</td><td>(string) password</td> <td>Nombre del campo password de la tabla users</td></tr>
     * <tr><td>db_user_access_level</td><td>(string) access_level</td> <td>Nombre del campo access_level de la tabla users</td></tr>
     * <tr><td>db_user_banned</td><td>(string) banned</td> <td>Nombre del campo banned de la tabla users</td></tr>
     * <tr><td>db_user_banned_date</td><td>(string) banned_date</td> <td>Nombre del campo banned_date de la tabla users</td></tr>
     * <tr><td>db_user_token</td><td>(string) token</td> <td>Nombre del campo token de la tabla users</td></tr>
     * <tr><td>db_user_token_time</td><td>(string) token_time</td> <td>Nombre del campo token_time de la tabla users</td></tr>
     * <tr><td>crypt_password</td><td>(string)</td> <td>Nombre de la clave principal de la tabla users</td></tr>
     * </table>
     * 
     */
    public function __construct( array $config = [] ) {
        $this->_config = $config + [            
            'login_email_mode' => FALSE,
            'login_error_max_chances' => 5,
            'login_error_wait_time' => 60,
            'login_check_ban' => FALSE,
            'login_session_time' => 3600,

            'password_min_length' => 5,
            'password_max_length' => 150,
            'username_min_length' => 4,
            'username_max_length' => 40,
            
            'access_level_min_val' => 1,
            'access_level_max_val' => 10,
            
            'db_pdo' => NULL,
            'db_table_users' => 'users',            
            'db_field_id' => 'id',
            'db_field_user_mail' => 'username',
            'db_field_password' => 'password',
            'db_field_access_level' => 'access_level',
            'db_field_banned' => 'banned',
            'db_field_banned_date' => 'banned_date',
            'db_field_token' => 'token',
            'db_field_token_time' => 'token_time',
            
            'crypt_password' => '189OP02bbf23gt780b25bf252dj8901h9'
        ];
    }
    
    /**
     * Devuelve las credenciales del usuario logueado
     * @return UserCredentials
     */
    public function getUserCredentials() {
        return $this->_user_logged;
    }
    
    /**
     * Autentifica y devuelve las credencials del usuario en base al token recibido
     * @param string $token Token único de la sesión activa del usuario
     */
    public function setUserLogged( $token ) {
        /* @var $pdo \PDO */
        $pdo = $this->_config['db_pdo'];
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        
        $find_user = $pdo->prepare(sprintf('SELECT * FROM `%s` '
                . 'WHERE `%s` = :token', $this->_config['db_table_users'], $this->_config['db_field_token']));
        $find_user->execute([
            'token' => $token,
        ]);
        
        $user = $find_user->fetchObject();
        
        if ( !$user || (new Moment())->isAfter($user->{ $this->_config['db_field_token_time'] }) ) {
            return FALSE;
        }
        
        if ( !property_exists($user, $this->_config['db_field_access_level']) ) {
            throw new \InvalidArgumentException(sprintf('No existe el campo (%s) en la tabla (%s),'
                    . ' debe configurar correctamente el valor (db_field_access_level) del autorizador.', 
                    $this->_config['db_field_access_level'], $this->_config['db_table_users']));
        }
        
        $this->_user_logged = new UserCredentials($user->{ $this->_config['db_field_user_mail'] }, NULL, 
                $user->{ $this->_config['db_field_access_level'] }, $user->{ $this->_config['db_field_id'] });
    }
    
    /**
     * Inicia sesión a un usuario especificando sus credenciales
     * @param \PowerOn\Authorization\Credentials $credentials
     * @return integer Devuelve el token generado del usuario encontrado
     * @throws AuthorizationException
     * @throws \LogicException
     */
    public function login(UserCredentials $credentials) {
        $login_count = (key_exists('login_count', $_SESSION) ? $_SESSION['login_count'] : $this->_config['login_error_max_chances']);
        $login_count_time = key_exists('login_count_time', $_SESSION) ? $_SESSION['login_count_time'] : NULL;

        if ( $login_count <= 0 ) {
            if ( $login_count_time === NULL ) {
                $moment = new Moment('now');
                $moment->addSeconds($this->_config['login_error_wait_time']);
                $_SESSION['login_count_time'] = $moment->format();
                $login_count_time = $_SESSION['login_count_time'];
            }
            
            $login_time = new Moment($login_count_time);

            if ( $login_time->isAfter('now') ) {
                throw new AuthorizationException(sprintf('login_error_max_chances', $this->_config['login_error_max_chances']), 3,
                        ['seconds' => $login_time->fromNow()->getSeconds() * -1]);
            } else {
                unset($_SESSION['login_count_time']);
                unset($_SESSION['login_count']);
            }
        }
        
        $valid_credentials = $credentials->validate( $this->_config );
        if ( $valid_credentials !== TRUE )  {
            throw new AuthorizationException('invalid_credentials', 1, ['errors' => $valid_credentials]);
        }
        
        if ( !$this->_config['db_pdo'] instanceof \PDO ){
            throw new \LogicException('Debe configurar la base de datos a utilizar con el par&aacute;metro (db_pdo)');
        }
        
        /* @var $pdo \PDO */
        $pdo = $this->_config['db_pdo'];
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        
        $find_user = $pdo->prepare(sprintf('SELECT * FROM `%s` '
                . 'WHERE `%s` = :value_user_mail', $this->_config['db_table_users'], $this->_config['db_field_user_mail']));
        $find_user->execute([
            'value_user_mail' => $credentials->username,
        ]);
        $user = $find_user->fetchObject();

        if ( !$user || ($user && !$this->test($credentials->password, $user->password)) ) {
            $_SESSION['login_count'] = key_exists('login_count', $_SESSION) ? $_SESSION['login_count'] - 1 : 
                $this->_config['login_error_max_chances'];
            
            throw new AuthorizationException('user_not_found', 2, ['chances' => $_SESSION['login_count']]);
        }
        
        if ( $this->_config['login_check_ban'] ) {
            if ( !property_exists($user, $this->_config['db_field_banned']) ) {
                throw new \InvalidArgumentException(sprintf('La tabla (%s) no tiene el campo (%s) requerido',
                        $this->_config['db_table_users'], $this->_config['db_field_banned']));
            }
            
            if ( $this->_config['db_field_banned_date'] && !property_exists($user, $this->_config['db_field_banned_date']) ) {
                throw new \InvalidArgumentException(sprintf('La tabla (%s) no tiene el campo (%s) requerido, si no necesita realizar esta'
                        . 'verificaci&oacute;n, puede establecer el valor del campo en NULL dentro de la configuraci&oacute;n del autorizador.',
                        $this->_config['db_table_users'], $this->_config['db_field_banned_date']));
            }
            
            if ( $this->_config['db_field_banned_date'] && $user->{ $this->_config['db_field_banned_date'] }) {
                $banned_date = new Moment($this->user->{ $this->_config['db_field_banned_date'] });
            } else {
                $banned_date = NULL;
            }
            
            if ( $this->_config['db_field_banned'] && $user->{ $this->_config['db_field_banned'] } 
                    && (!$banned_date || $banned_date->isAfter()) ) {
                throw new AuthorizationException('banned_account', 10, ['banned_date' => $banned_date ? $banned_date->format() : NULL]);
            }
        }

        $secret_token = $this->crypt(uniqid('scrttkn'));
            
        $update_token = $pdo->prepare(sprintf('UPDATE `%s` SET `%s` = :token, `%s` = :token_expire '
                . ' WHERE `id` = :user_id', $this->_config['db_table_users'], $this->_config['db_field_token'],
                $this->_config['db_field_token_time']));

        $update_token->execute([
            'token' => $secret_token,
            'token_expire' => (new Moment())->addSeconds( $this->_config['login_session_time'] )->format(\DateTime::ISO8601),
            'user_id' => $user->id
        ]);
        
        $this->_logged = TRUE;
        
        return $secret_token;
    }
    
    
    
    /**
     * Verifica si un usuario inició sesión correctamente
     * @return boolean
     */
    public function isLogged() {
        return $this->_user_logged !== NULL;
    }
    
    /**
     * Autoriza el acceso a un modulo indicado
     * @param SectorCredentials $sector Credenciales del sector a autorizar
     * @return boolean True en caso de exito
     * @throws AuthorizerException
     */
    public function sector(SectorCredentials $sector) {
        //LOGIN REQUIRED
        if ( $sector->require_level > 0 && !$this->isLogged() ) {
            throw new AuthorizationException('sector_require_login', 4);
        }
        
        if ( $this->isLogged() ) {
            //AUTHORIZED SECTOR
            if ( $this->_user_logged->allowed_sectors && !in_array($sector->name, $this->_user_logged->allowed_sectors) ) {
                throw new AuthorizationException('sector_require_access', 2, ['user_allowed_sectors' => $this->_user_logged->allowed_sectors]);
            }

            //AUTHORIZED LEVEL SECTOR
            if ( $sector->require_level && $this->_user_logged->access_level < $sector->require_level ) {
                throw new AuthorizationException('sector_require_access', 5, ['user_access_level' => $this->_user_logged->access_level]);
            }
        }
        //AJAX_MODE
        $http_request = filter_input(INPUT_SERVER, 'HTTP_X_REQUESTED_WITH', FILTER_SANITIZE_STRING);
        $ajax_request = ( !empty($http_request) && strtolower($http_request) == 'xmlhttprequest' ) ? TRUE : FALSE;
        $post_request = filter_input(INPUT_SERVER, 'METHOD', FILTER_SANITIZE_STRING) == 'POST' ? TRUE : FALSE ;
        
        if ( $sector->ajax_access_mode == SectorCredentials::AJAX_ACCESS_DENIED && $ajax_request ) {
            throw new AuthorizationException('sector_ajax_error', 6, array('module' => $sector));
        } elseif ( $sector->ajax_access_mode == SectorCredentials::AJAX_ACCESS_REQUIRED && !$ajax_request ) {
            throw new AuthorizationException('sector_ajax_required', 7);
        } elseif ( $sector->ajax_access_mode == SectorCredentials::AJAX_ACCESS_ALLOW_POST && $post_request && !$ajax_request ) {
            throw new AuthorizationException('sector_ajax_post', 8);
        }
        
        return TRUE;
    }
    
    /**
     * Encripta una cadena
     * @param string $words La cadena a encriptar
     * @return string La cadena encriptada
    */
    private function crypt($words) {
       $encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, 
               md5($this->_config['crypt_password']), $words, MCRYPT_MODE_CBC, md5(md5($this->_config['crypt_password']))));
       return $encrypted;

    }
    /**
     * Desencripta una cadena
     * @param string $words La cadena a desencriptar
     * @return string la cadena desencriptada
    */
    private function decrypt($words){
        $decrypted = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($this->_config['crypt_password']),
                base64_decode($words), MCRYPT_MODE_CBC, md5(md5($this->_config['crypt_password']))), "\0");
       return $decrypted;
    }

    /**
     * Encripta un password utilizando las funciones de php
     * @param String $password el password a encriptar
     * @param Integer $digit el numero de digitos
     * @return String Devuelve el password encriptado
    */
    private function blowfish($password, $digit = 7) {
       $set_salt = './1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
       $salt = sprintf('$2a$%02d$', $digit);
       for($i = 0; $i < 22; $i++) {
           $salt .= $set_salt[mt_rand(0, 63)];
       }

       return crypt($password, $salt);
    }

    /**
     * Comprueba que el password sea correcto utilizando el metodo crypt
     * @param String $input El password enviado por el usuario
     * @param String $saved El password guardado en db
     * @return Boolean Devuelve True en caso de coincidir o False en caso contrario
    */
    private function test($input, $saved) {
       return crypt($input, $saved) == $saved;
    }
}