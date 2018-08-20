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
use PowerOn\Utility\Session;

/**
 * Authorizer
 * Maneja la autorizacion de acceso al sistema
 * @author Lucas Sosa <sosalucas87@gmail.com>
 * @version 0.1.2
 */
class Authorization {
    /**
     * Configuración del autorizador
     * @var array
     */
    private $config = [];    
    /**
     * Credenciales del usuario logueado
     * @var UserCredentials
     */
    private $userCredentials = NULL;
    /**
     * Credenciales del sector verificado
     * @var SectorCredentials
     */
    private $sectorCredentials = NULL;
    /**
     * Adaptador de inicio de sesión
     * @var AuthorizationAdapterInterface
     */
    private $adapter;
    /**
     * Permisos de sectores
     * @var array
     */
    private $permissions;
    /**
     * Estado de autorización
     * @var string
     */
    private $status = 'user_not_found';
    
    const AUTH_STATUS_OK = 'ok';
    const AUTH_STATUS_PAUSED = 'paused';
    const AUTH_STATUS_USER_ERROR = 'user_error';
    const AUTH_STATUS_USER_NOT_FOUND = 'user_not_found';
    const AUTH_STATUS_USER_SESSION_END = 'user_session_end';
    const AUTH_STATUS_USER_SESSION_INACTIVE = 'user_session_inactive';
    
    const AUTH_STATUS_SECTOR_LOW_ACCESS_LEVEL = 'sector_low_access_level';
    const AUTH_STATUS_SECTOR_NOT_ALLOWED = 'sector_not_allowed';    
    
    /**
     * Crea una nueva autorización
     * @param array $config Configuración del autorizador
     * <table width=100% border=1>
     * <tr><td>login_email_mode</td><td>(bool) FALSE</td> <td>Especifica si se usa email para el inicio de sesión</td></tr>
     * <tr><td>login_error_max_chances</td><td>(int) 5</td> <td>Cantidad de veces que se puede equivocar en el inicio de sesión</td></tr>
     * <tr><td>login_error_wait_time</td><td>(int) 60</td> <td>Segundos que debe esperar antes de volver a intentar iniciar sesión 
     * luego de exceder el límite de errores</td></tr>
     * <tr><td>login_check_ban</td><td>(bool) FALSE</td> <td>Habilita la verificación de usuario baneado</td></tr>
     * <tr><td>login_session_time</td><td>(int) 28800</td> <td>Cantidad de segundos que dura una sesión</td></tr>
     * <tr><td>login_session_inactive_time</td><td>(int) 1800</td> <td>Cantidad de segundos que dura una sesión</td></tr>
     * <tr><td>strict_mode</td><td>(bool) FALSE</td> <td>En modo estricto las url que no se encuentren en la lista de permisos </td></tr>
     * <tr><td>login_session_inactive_time</td><td>(int) 1800</td> <td>Cantidad de segundos que dura una sesión</td></tr>
     * </table>
     * 
     */
    public function __construct( array $config = [], array $permissions = []) {
        $this->config = $config + [            
            'login_email_mode' => FALSE,
            'login_error_max_chances' => 5,
            'login_error_wait_time' => 60,
            'login_check_ban' => FALSE,
            'login_session_time' => 28800,
            'login_session_inactive_time' => 3600,
            'strict_mode' => FALSE
        ];

        $this->permissions = $permissions;
        
        if ( Session::exist('AuthUser') ) {
            $this->userCredentials = new UserCredentials();
            $this->userCredentials->setUserData(Session::read('AuthUser.data'));
            $this->userCredentials->setCredentialProperties(Session::read('AuthUser.credentials'));
            
            if ( !$this->userCredentials->isVerified() ) {
                $this->status = self::AUTH_STATUS_USER_ERROR;
            } else if ( $this->config['login_session_time'] && $this->config['login_session_time'] < $this->userCredentials->getSessionTime() ) {
                $this->status = self::AUTH_STATUS_USER_SESSION_END;
            } else if ( $this->config['login_session_inactive_time'] 
                    && $this->config['login_session_inactive_time'] < $this->userCredentials->getSessionInactiveTime() ) {
                $this->status = self::AUTH_STATUS_USER_SESSION_INACTIVE;
            } else if ( $this->userCredentials->isVerified() ) {
                if ( $this->userCredentials->isSessionPaused() ) {
                    $this->status = self::AUTH_STATUS_PAUSED;
                } else {
                    $this->status = self::AUTH_STATUS_OK;
                    Session::merge('AuthUser.credentials.session_last_activity_time', time());
                }
            }
        }
    }
    
    /**
     * Registra un adaptador para el manejo de inicio y cierre de sesion
     * @param \PowerOn\Authorization\AuthorizationAdapterInterface $adapter
     */
    public function registerAdapter(AuthorizationAdapterInterface $adapter) {
        $this->adapter = $adapter;
    }
    
    /**
     * Devuelve el estado de autorización
     * @return int
     */
    public function getStatus() {
        return $this->status;
    }
    
    /**
     * Devuelve las credenciales del usuario logueado
     * @return UserCredentials
     */
    public function getUserCredentials() {
        return $this->userCredentials;
    }
    
    /**
     * Devuelve las credenciales del sector verificado
     * @return SectorCredentials
     */
    public function getSectorCredentials() {
        return $this->sectorCredentials;
    }

    /**
     * Bloquea la sesión del usuario sin eliminarla
     * @return bool
     */
    public function pause() {
        $this->status = self::AUTH_STATUS_PAUSED;
        Session::merge('AuthUser.credentials.session_paused', TRUE);
        return $this->adapter->pauseSession();
    }
    
    /**
     * Vuelve con la última sesión activa
     * @return bool
     */
    public function resume() {
        if ( $this->status = self::AUTH_STATUS_PAUSED && $this->userCredentials->isVerified() ) {
            $this->status = self::AUTH_STATUS_OK;
            Session::merge('AuthUser.credentials.session_paused', FALSE);
            Session::merge('AuthUser.credentials.session_last_activity_time', time());
            return $this->adapter->resumeSession();
        }
        
        return FALSE;
    }
    
    /**
     * Inicia sesión a un usuario especificando sus credenciales utilizando un adaptador específico
     * El adaptador debe utilizar la interface provista y su método login debe retornar una credencial de usuario
     * o FALSE en caso de error
     * @param \PowerOn\Authorization\Credentials $credentials
     * @return integer Devuelve el token generado del usuario encontrado
     * @throws AuthorizationException
     * @throws \LogicException
     */
    public function login(UserCredentials $credentials) {
        $loginCount = (Session::exist('login_count') ? Session::read('login_count') : $this->config['login_error_max_chances']);
        $loginCountTime = Session::exist('login_count_time') ? Session::read('login_count_time') : NULL;

        if ( $loginCount <= 0 ) {
            if ( $loginCountTime === NULL ) {
                $time = time();
                $time += $this->config['login_error_wait_time'];
                Session::write('login_count_time', $time);
                $loginCountTime = $_SESSION['login_count_time'];
            }
            
            if ( $loginCountTime > time() ) {
                throw new AuthorizationException(
                    sprintf(
                        'Superó el máximo de (%d) intentos de inicio de sesión, deberá esperar (%d segundos) para volver a intentarlo.',
                        $this->config['login_error_max_chances'],
                        $loginCountTime - time()
                    ), 3, [$loginCountTime - time()]
                );
            } else {
                Session::remove('login_count_time');
                Session::remove('login_count');
            }
        }
        
        if ( !$this->adapter ){
            throw new \LogicException('Debe establecer un adaptador que implemente la interface '
                    . '\PowerOn\Authorization\AuthorizationAdapterInterface');
        }
        
        $userCredentials = $this->adapter->login($credentials);
        
        if ( !$userCredentials instanceof UserCredentials || !$userCredentials->isVerified() ) {
            Session::write('login_count', Session::exist('login_count')
                ? Session::read('login_count') - 1 
                : $this->config['login_error_max_chances']
            );
            $this->status = self::AUTH_STATUS_USER_NOT_FOUND;
            throw new AuthorizationException(
                'El usuario o la contraseña son inválidos.', 
                2, 
                ['chances' => Session::read('login_count')]
            );
        }
        
        if ( $this->config['login_check_ban'] ) {
            if ( $userCredentials->banned ) {
                $this->status = self::AUTH_STATUS_USER_ERROR;
                throw new AuthorizationException(
                    'Su cuenta se encuentra bloqueada, comuníquese con un administrador.',
                    10
                );
            }
        }
        $userCredentials->setSessionStartTime(time());
        $this->userCredentials = $userCredentials;
        
        $AuthUser = [
            'data' => $userCredentials->getUserData(),
            'credentials' => [
                'username' => $userCredentials->username,
                'access_level' => $userCredentials->access_level,
                'banned' => $userCredentials->banned,
                'session_last_activity_time' => time(),
                'session_start_time' => time(),
                'session_paused' => FALSE
            ]
        ];
        Session::write('AuthUser', $AuthUser);
        $this->status = self::AUTH_STATUS_OK;
    }
    
    public function logout() {
        if ( !$this->adapter ){
            throw new \LogicException('Debe establecer un adaptador que implemente la interface '
                    . '\PowerOn\Authorization\AuthorizationAdapterInterface');
        }
        Session::destroy();
        $this->status = self::AUTH_STATUS_USER_NOT_FOUND;
        return $this->adapter->logout();
    }
    
    /**
     * Verifica si un usuario inició sesión correctamente
     * @return boolean
     */
    public function isValid() {
        return $this->status === self::AUTH_STATUS_OK;
    }
    
    public function isPaused(){
        return $this->status === self::AUTH_STATUS_PAUSED;
    }
    
    /**
     * Autoriza el acceso a un modulo indicado
     * @param string $url Url a verificar
     * @param mixed $s_ [optional]
     * @return boolean True en caso de exito
     * @throws AuthorizerException
     */
    public function sector($url) {
        $params = func_get_args();
        $params[0] = $this->userCredentials;
        
        $sectorCredentials = $this->findSectorCredentials($url);
        $this->sectorCredentials = $sectorCredentials;
        if ( !$sectorCredentials && $this->config['strict_mode'] ) {
            $this->status = self::AUTH_STATUS_SECTOR_NOT_ALLOWED;
            return FALSE;
        }
        
        if ( $sectorCredentials && !$this->isValid() && !$this->config['strict_mode']) {
            return FALSE;
        }
        
        if ( $sectorCredentials && $this->isValid() ) {
            if ($sectorCredentials->access_level && $this->userCredentials->access_level < $sectorCredentials->access_level) {
                $this->status = self::AUTH_STATUS_SECTOR_LOW_ACCESS_LEVEL;
                return FALSE;
            }
            
            if ($sectorCredentials->allowed && !call_user_func_array($sectorCredentials->allowed, $params)) {
                $this->status = self::AUTH_STATUS_SECTOR_NOT_ALLOWED;
                return FALSE;
            }            
        }
        
        return TRUE;
    }
    
    /**
     * Busca un permiso establecido
     * @param string $url  URL solicitada
     * @return \PowerOn\Authorization\SectorCredentials
     */
    private function findSectorCredentials($url) {
        $matches = array_filter(array_keys($this->permissions), function($pattern) use ($url) { 
            $filtered = '/^' . str_replace(['/', '/*'], ['\/', '/?[a-zA-Z0-9\/]*'], $pattern) . '$/';
            return preg_match($filtered, $url);
        });

        $name = $matches ? end($matches) : FALSE;
        $permission = $name ? $this->permissions[$name] : FALSE;
        
        return $permission 
            ? new SectorCredentials(
                $name,
                is_array($permission) && key_exists('access_level', $permission) ? $permission['access_level'] : NULL,
                is_array($permission) && key_exists('allowed', $permission) ? $permission['allowed'] : NULL
            )
            : FALSE
        ;
    }
}