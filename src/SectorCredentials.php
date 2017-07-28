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
class SectorCredentials {
    /**
     * Nombre del sector
     * @var string
     */
    public $name;
    /**
     * Nivel de acceso requerido
     * @var string
     */
    public $require_level;
    /**
     * Modo de acceso ajax al sector
     * @var integer
     */
    public $ajax_access_mode;
    
    /**
     * Prohibe el acceso mediante peticion ajax
     */
    const AJAX_ACCESS_DENIED = 0;
    /**
     * El sector solo puede ser accedido mediante una petición ajax
     */
    const AJAX_ACCESS_REQUIRED = 1;
    /**
     * El sector solo recibe datos POST mediante ajax
     */
    const AJAX_ACCESS_ALLOW_POST = 2;
    /**
     * Permite el acceso mediante una petición ajax de cualquier tipo
     */
    const AJAX_ACCESS_ALLOW_ALL = 2;
    
    /**
     * Crea una credencial de usuario
     * @param string $name Nombre del sector
     * @param integer $require_level Nivel de acceso requerido
     * @param itenger $ajax_access_mode Modo de acceso ajax al sector
     */
    public function __construct($name = NULL, $require_level = NULL, $ajax_access_mode = NULL) {
        $this->name = $name;
        $this->require_level = $require_level;
        $this->ajax_access_mode = $ajax_access_mode;
    }
    
    /**
     * Valida los datos recibidos para crear las 
     * credenciales del usuario
     * @param array $config Datos de configuración del autorizador
     * @return array Devuelve un array con errores encontrados o TRUE si no se encontró ningún error
     */
    public function validate(array $config) {
        $validator = new Validator();
        
        $validator->add('name', [
                ['string_allow', ['alpha', 'low_strips']],
                ['required', TRUE]
            ]
        );
        
        $validator->add('require_level', [
                ['min_val', $config['access_level_min_val']],
                ['max_val', $config['access_level_max_val']],
                ['required', TRUE]
            ]
        );
        
        $validator->add('ajax_access_mode', [
                ['min_val', 0],
                ['max_val', 2],
                ['string_allow', ['numbers']]
            ]
        );
        
        $validator->validate([
            'name' => $this->name,
            'require_level' => $this->require_level,
            'ajax_access_mode' => $this->ajax_access_mode
        ]);
        
        return $validator->getErrors() ? $validator->getErrors() : TRUE;
    }
}