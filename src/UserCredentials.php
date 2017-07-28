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

use PowerOn\Validation\Validator;

/**
 * Credentials Credenciales a autorizar
 * @author Lucas Sosa
 * @version 0.1
 */
class UserCredentials {
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
     * ID del usuario
     * @var integer
     */
    public $id;
    /**
     * Sectores que tiene habilitados el usuario
     * @var array
     */
    public $allowed_sectors;
    
    /**
     * Crea una credencial de usuario
     * @param string $username Usuario o Email del usuarioa loguear
     * @param string $password Contaseña de usuario a loguear
     * @param itenger $access_level Nivel de acceso de usuario logueado
     * @param integer $id ID de usuario logueado
     */
    public function __construct($username = NULL, $password = NULL, $access_level = NULL, $id = NULL, $sectors_allowed = []) {
        $this->username = $username;
        $this->password = $password;
        $this->access_level = $access_level;
        $this->id = $id;
        $this->allowed_sectors = $sectors_allowed;
    }
    
    /**
     * Valida los datos recibidos para crear las 
     * credenciales del usuario
     * @param array $config Datos de configuración del autorizador
     * @return array Devuelve un array con errores encontrados o TRUE si no se encontró ningún error
     */
    public function validate(array $config) {
        $validator = new Validator();
        
        $validator->add('username', 
                $config['login_email_mode'] ? 
                    [['email', TRUE], ['required', TRUE]] : [
                        ['min_length', $config['username_min_length']],
                        ['max_length', $config['username_max_length']],
                        ['required', TRUE]
                    ]
        );
        
        $validator->add('password', [
                ['min_length', $config['password_min_length']],
                ['max_length', $config['password_max_length']],
                ['required', TRUE]
            ]
        );
        
        $validator->add('access_level', [
                ['min_val', $config['access_level_min_val']],
                ['max_val', $config['access_level_max_val']]
            ]
        );
        
        $validator->add('id', [
                ['min_val', 0],
                ['string_allow', ['numbers']]
            ]
        );
        
        $validator->validate([
            'username' => $this->username,
            'password' => $this->password,
            'access_level' => $this->access_level
        ]);
        
        return $validator->getErrors() ? $validator->getErrors() : TRUE;
    }
}