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

/**
 * Credentials Credenciales a autorizar
 * @author Lucas Sosa
 * @version 0.1
 */
class UserCredentials implements \ArrayAccess {
    /**
     * Nombre de usuario
     * @var string
     */
    public $username;
    /**
     * Contraseña a verificar
     * @var string
     */
    public $password;
    /**
     * Nivel de acceso
     * @var integer
     */
    public $access_level;
    /**
     * Usuario baneado
     * @var array
     */
    public $banned;
    /**
     * Datos completos del usuario
     * @var array
     */
    private $data;
    /**
     * Establece si el usuario esta verificado
     * @var bool
     */
    private $verified;
    /**
     * Tiempo de inicio de sesión
     * @var int
     */
    private $session_start_time;
    /**
     * Tiempo desde última actividad
     * @var int
     */
    private $session_last_activity_time;
    /**
     * Sesion en pausa
     * @var bool
     */
    private $session_paused;
    
    /**
     * Crea una credencial de usuario
     * @param string $username Usuario o Email del usuarioa loguear
     * @param string $password Contaseña de usuario a loguear
     * @param itenger $access_level Nivel de acceso de usuario logueado
     * @param integer $banned Si el usuario esta baneado
     */
    public function __construct($username = NULL, $password = NULL, $access_level = NULL, $banned = FALSE) {
        $this->username = $username;
        $this->password = $password;
        $this->access_level = $access_level;
        $this->banned = $banned;
    }
    
    /**
     * Establece los datos de un usuario y su nivel de acceso
     * @param array $data
     * @param int $access_level [Opcional] Establece el nivel de acceso del usuario
     */
    public function setUserData(array $data = NULL) {
        $this->data = $data;
        
        $this->verified = $data ? TRUE : FALSE;
    }
    
    public function setCredentialProperties(array $data = NULL) {
        if (!$data) {
            return;
        }
        $this->username = key_exists('username', $data) ? $data['username'] : NULL;
        $this->access_level = key_exists('access_level', $data) ? $data['access_level'] : NULL;
        $this->session_start_time = key_exists('session_start_time', $data) ? $data['session_start_time'] : NULL;
        $this->session_last_activity_time = key_exists('session_last_activity_time', $data) ? $data['session_last_activity_time'] : NULL;
        $this->session_paused = key_exists('session_paused', $data) ? $data['session_paused'] : FALSE;
    }
    
    /**
     * Establece el nivel de acceso del usuario logueado
     * @param int $access_level
     */
    public function setUserAccessLevel($access_level) {
        $this->access_level = intval($access_level);
    }
    
    /**
     * Establece el tiempo de inicio de sesión
     * @param int $time
     */
    public function setSessionStartTime($time) {
        $this->session_start_time = $time;
        $this->data['_session_start_time'] = $time;
    }
        
    /**
     * Obtiene el tiempo de inicio de sesión
     * @return int
     */
    public function getSessionTime() {
        return time() - $this->session_start_time;
    }
    
    /**
     * Obtiene el tiempo de inicio de sesión
     * @return int
     */
    public function getSessionInactiveTime() {
        if (!$this->session_last_activity_time) {
            return 0;
        }
        return time() - $this->session_last_activity_time;
    }
    
    /**
     * Devuelve los datos de un usuario
     * @return array
     */
    public function getUserData() {
        return $this->data;
    }
    
    /**
     * Comprueba que el usuario esté verificado
     * @return bool
     */
    public function isVerified() {
        return $this->verified;
    }
    
    /**
     * Comprueba si la sesion de las credenciales se encuentra pausada
     * @return bool
     */
    public function isSessionPaused() {
        return $this->session_paused;
    }
    
    public function offsetSet($offset, $valor) {
        if ( is_null($offset) ) {
            $this->data[] = $valor;
        } else {
            $this->data[$offset] = $valor;
        }
    }

    public function offsetExists($offset) {
        return isset($this->data[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->data[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->data[$offset]) ? $this->data[$offset] : NULL;
    }

}