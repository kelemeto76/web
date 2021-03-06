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

namespace App\Test\TestCase\Controller;

use App\Controller\TranslationsController;
use BEdita\SDK\BEditaClient;
use BEdita\WebTools\ApiClientProvider;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

/**
 * {@see \App\Controller\TranslationsController} Test Case
 *
 * @coversDefaultClass \App\Controller\TranslationsController
 */
class TranslationsControllerTest extends TestCase
{
    /**
     * Test Translations controller
     *
     * @var App\Test\TestCase\Controller\TranslationsController
     */
    public $controller;

    /**
     * Test api client
     *
     * @var BEdita\SDK\BEditaClient
     */
    public $client;

    /**
     * Uname for test object
     *
     * @var string
     */
    protected $uname = 'translations-controller-test-document';

    /**
     * Test request config
     *
     * @var array
     */
    public $defaultRequestConfig = [
        'environment' => [
            'REQUEST_METHOD' => 'GET',
        ],
        'get' => [],
        'params' => [
            'object_type' => 'documents',
        ],
    ];

    /**
     * Setup api client and auth
     *
     * @return void
     */
    private function setupApi() : void
    {
        $this->client = ApiClientProvider::getApiClient();
        $adminUser = getenv('BEDITA_ADMIN_USR');
        $adminPassword = getenv('BEDITA_ADMIN_PWD');
        $response = $this->client->authenticate($adminUser, $adminPassword);
        $this->client->setupTokens($response['meta']);
    }

    /**
     * Setup controller to test with request config
     *
     * @param array|null $requestConfig
     * @return void
     */
    protected function setupController(?array $requestConfig = []): void
    {
        $config = array_merge($this->defaultRequestConfig, $requestConfig);
        $request = new ServerRequest($config);
        $this->controller = new TranslationsController($request);
        $this->setupApi();
        $this->createTestObject();
    }

    /**
     * Test `add` method
     *
     * @covers ::add()
     *
     * @return void
     */
    public function testAdd() : void
    {
        // Setup controller for test
        $this->setupController();

        // get object ID for test
        $id = $this->getTestId();

        // do controller call
        $result = $this->controller->add($id);

        // verify response status code and type
        static::assertNull($result);
        static::assertEquals(200, $this->controller->response->statusCode());
        static::assertEquals('text/html', $this->controller->response->type());

        // verify expected vars in view
        $this->assertExpectedViewVars(['schema', 'object', 'translation']);
    }

    /**
     * Test `edit` method
     *
     * @covers ::edit()
     *
     * @return void
     */
    public function testEdit() : void
    {
        // Setup controller for test
        $this->setupController();

        // get object ID for test
        $id = $this->getTestId();
        $lang = 'it';

        // do controller call
        $result = $this->controller->edit($id, $lang);

        // verify response status code and type
        static::assertNull($result);
        static::assertEquals(200, $this->controller->response->statusCode());
        static::assertEquals('text/html', $this->controller->response->type());

        // verify expected vars in view
        $this->assertExpectedViewVars(['schema', 'object', 'translation']);
    }

    /**
     * Test `save` method
     *
     * @covers ::save()
     *
     * @return void
     */
    public function testSave() : void
    {
        // Setup controller for test
        $this->setupController();

        // get object for test
        $lang = 'it';
        $o = $this->getTestObject();
        $config = [
            'environment' => [
                'REQUEST_METHOD' => 'POST',
            ],
            'post' => [
                'lang' => $lang,
                'object_id' => $o['id'],
                'translated_fields' => [
                    'title' => sprintf('Una traduzione di esempio per "%s"', $o['attributes']['title']),
                ],
            ],
            'params' => [
                'object_type' => 'documents',
            ],
        ];
        $request = new ServerRequest($config);
        $this->controller = new TranslationsController($request);

        // do controller call
        $result = $this->controller->save();

        // verify response status code and type
        static::assertEquals(302, $result->statusCode());
        static::assertEquals('text/html', $result->type());
    }

    /**
     * Test `delete` method
     *
     * @covers ::delete()
     *
     * @return void
     */
    public function testDelete() : void
    {
        // Setup controller for test
        $this->setupController();

        // get object for test
        $objectId = $this->getTestId();
        $config = [
            'environment' => [
                'REQUEST_METHOD' => 'POST',
            ],
            'post' => [
                [
                    'id' => $this->getTestTranslationId($objectId, 'documents', 'it'),
                    'object_id' => $objectId,
                ]
            ],
            'params' => [
                'object_type' => 'documents',
            ],
        ];
        $request = new ServerRequest($config);
        $this->controller = new TranslationsController($request);

        // do controller call
        $result = $this->controller->delete();

        // verify response status code and type
        static::assertEquals(302, $result->statusCode());
        static::assertEquals('text/html', $result->type());

        // restore test object
        $this->restoreTestObject($objectId, 'documents');
    }

    /**
     * Get test object id
     *
     * @return void
     */
    private function getTestId()
    {
        // call index and get first available object, for test view
        $o = $this->getTestObject();

        return $o['id'];
    }

    /**
     * Get an object for test purposes
     *
     * @return array
     */
    private function getTestObject()
    {
        $response = $this->client->getObjects('documents', ['filter' => ['uname' => $this->uname]]);

        if (!empty($response['data'][0])) {
            return $response['data'][0];
        }

        return null;
    }

    /**
     * Get the translation for test object
     *
     * @param string|int $id The object ID
     * @param string $objectType The object type
     * @param string $lang The lang code
     * @return string|int|null The translation ID
     */
    private function getTestTranslationId($id, $objectType, $lang)
    {
        $response = $this->client->getObject($id, $objectType, compact('lang'));

        if (empty($response['included'])) { // if not found, create dummy translation
            $response = $this->client->save('translations', ['object_id' => $id, 'status' => 'draft', 'lang' => 'it', 'translated_fields' => [ 'title' => 'Titolo di test' ]]);

            return $response['data']['id'];
        } else {
            foreach ($response['included'] as $included) {
                if ($included['type'] === 'translations' && $included['attributes']['object_id'] == $id && $included['attributes']['lang'] === $lang) {
                    return $included['id'];
                }
            }
        }

        return null;
    }

    /**
     * Create a object for test purposes (if not available already)
     *
     * @return array
     */
    private function createTestObject()
    {
        $o = $this->getTestObject();
        if ($o == null) {
            // create document
            $response = $this->client->save('documents', ['title' => 'translations controller test document', 'uname' => $this->uname]);
            $o = $response['data'];

            // create translation
            $this->client->save('translations', ['object_id' => $o['id'], 'status' => 'draft', 'lang' => 'it', 'translated_fields' => [ 'title' => 'Titolo di test' ]]);
        }

        return $o;
    }

    /**
     * Restore object by id
     *
     * @param string|int $id The object ID
     * @param string $type The object type
     * @return void
     */
    private function restoreTestObject($id, $type)
    {
        $o = $this->getTestObject();
        if ($o == null) {
            $response = $this->client->restoreObject($id, $type);
        }
    }

    /**
     * Verify existence of vars in controller view
     *
     * @param array $expected The expected vars in view
     * @return void
     */
    private function assertExpectedViewVars($expected)
    {
        foreach ($expected as $varName) {
            static::assertArrayHasKey($varName, $this->controller->viewVars);
        }
    }
}
