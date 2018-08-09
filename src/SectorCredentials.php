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
class SectorCredentials {
    /**
     * Nombre del sector o URL
     * @var string
     */
    public $name;
    /**
     * Nivel de acceso requerido
     * @var string
     */
    public $access_level;
    /**
     * Función callable que retorna verificación
     * @var \Closure
     */
    public $allowed;
    /**
     * Extension solicitada
     * @var string
     */
    public $extension;
    
    /**
     * Crea una credencial de usuario
     * @param string $name Nombre del sector
     * @param integer $require_level Nivel de acceso requerido
     * @param itenger $ajax_access_mode Modo de acceso ajax al sector
     */
    public function __construct($name = NULL, $require_level = NULL, callable $allowed = NULL, $extension = NULL) {
        $this->name = $name;
        $this->access_level = $require_level;
        $this->allowed = $allowed;
        $this->extension = $extension;
    }
}