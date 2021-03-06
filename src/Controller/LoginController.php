<?php
/**
 * BEdita, API-first content management framework
 * Copyright 2018 ChannelWeb Srl, Chialab Srl
 *
 * This file is part of BEdita: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * See LICENSE.LGPL or <http://gnu.org/licenses/lgpl-3.0.html> for more details.
 */
namespace App\Controller;

use BEdita\SDK\BEditaClientException;
use Cake\Http\Response;
use Psr\Log\LogLevel;

/**
 * Perform basic login and logout operations.
 */
class LoginController extends AppController
{

    /**
     * Display login page or perform login via API.
     *
     * @return \Cake\Http\Response|null
     */
    public function login() : ?Response
    {
        $this->request->allowMethod(['get', 'post']);

        if (!$this->request->is('post')) {
            // Display login form.
            return null;
        }

        // Attempted login.
        $user = null;
        $reason = 'Username or password is incorrect';
        try {
            $user = $this->Auth->identify();
        } catch (BEditaClientException $e) {
            $this->log('Login failed - ' . $e->getMessage(), LogLevel::INFO);
            $attributes = $e->getAttributes();
            if (!empty($attributes['reason'])) {
                $reason = $attributes['reason'];
            }
        }
        if (!empty($user) && is_array($user)) {
            // setup timezone from request
            $user['timezone'] = $this->userTimezone();
            // Successful login. Redirect.
            $this->Auth->setUser($user);

            return $this->redirect($this->Auth->redirectUrl());
        }

        // Failed login.
        $this->Flash->error(__($reason));

        return null;
    }

    /**
     * Retrieve user timezone from request data.
     *
     * @return string User timezone
     */
    protected function userTimezone() : string
    {
        // 'timezone_offset' must contain UTC offset in seconds
        // plus Daylight Saving Time DST 0 or 1 like: '3600 1' or '7200 0'
        $offset = $this->request->getData('timezone_offset');
        if (empty($offset)) {
            return 'UTC';
        }
        $data = explode(' ', (string)$offset);
        $dst = empty($data[1]) ? 0 : 1;

        return timezone_name_from_abbr('', intval($data[0]), $dst);
    }

    /**
     * Logout and redirect to login page.
     *
     * @return \Cake\Http\Response
     */
    public function logout() : Response
    {
        $this->request->allowMethod(['get']);

        return $this->redirect($this->Auth->logout());
    }
}
