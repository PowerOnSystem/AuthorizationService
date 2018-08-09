<?php

/*
 * Copyright (C) PowerOn Sistemas - Lucas Sosa
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

namespace App;
use PowerOn\Database\Model;
use PowerOn\Authorization\AuthorizationAdapterInterface;
use PowerOn\Authorization\UserCredentials;

/**
 * AuthorizationAdapter
 * @author Lucas Sosa
 * @version 0.1
 * @copyright (c) 2016, Lucas Sosa
 */
class MainAdapter implements AuthorizationAdapterInterface {
    /**
     * Base de datos
     * @var Model
     */
    private $db;
    
    public function __construct(Model $database) {
        $this->db = $database;
    }

    public function login(UserCredentials $credentials) {
        $credentials->setUserData([
            'first_name' => 'Lucas',
            'last_name' => 'Sosa',
            'access_level' => 5,
            'username' => 'maker'
        ]);
        
        $credentials->setUserAccessLevel(5);
        
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
