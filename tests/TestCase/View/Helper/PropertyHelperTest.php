<?php
/**
 * BEdita, API-first content management framework
 * Copyright 2020 ChannelWeb Srl, Chialab Srl
 *
 * This file is part of BEdita: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * See LICENSE.LGPL or <http://gnu.org/licenses/lgpl-3.0.html> for more details.
 */

namespace App\Test\TestCase\View\Helper;

use App\View\Helper\PropertyHelper;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Cake\View\View;

/**
 * {@see \App\View\Helper\PropertyHelper} Test Case
 *
 * @coversDefaultClass \App\View\Helper\PropertyHelper
 */
class PropertyHelperTest extends TestCase
{
    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        Configure::write(
            'Control.handlers',
            array_merge(
                (array)\Cake\Core\Configure::read('Control.handlers'),
                [
                    'dummies' => [ // an object type
                        'descr' => [ // a field
                            'class' => 'App\Test\TestCase\View\Helper\PropertyHelperTest',
                            'method' => 'dummy',
                            'type' => 'string', // property type
                        ],
                    ],
                ]
            )
        );
    }

    /**
     * Data provider for `testControl` test case.
     *
     * @return array
     */
    public function controlProvider(): array
    {
        return [
            'text' => [
                'title',
                'something',
                [],
                [
                    'oneOf' => [
                        ['type' => null],
                        ['type' => 'string', 'contentMediaType' => 'text/html'],
                    ],
                    '$id' => '/properties/title',
                    'title' => 'Title',
                    'description' => null,
                ],
                '<div class="input title text"><label for="title">Title</label><input type="text" name="title" class="title" id="title" value="something"/></div>',
            ],
            'date' => [
                'created',
                '2020-06-15 12:35:00',
                [],
                [
                    'type' => 'string',
                    'format' => 'date-time',
                    '$id' => '/properties/created',
                    'title' => 'Created',
                    'description' => 'creation date',
                    'readonly' => true,
                ],
                '<div class="input datepicker text"><label for="created">Created</label><input type="text" name="created" v-datepicker="true" date="true" time="true" id="created" value="2020-06-15 12:35:00"/></div>',
            ],
            'status' => [
                'status',
                'en',
                ['class' => 'test'],
                [
                    'type' => 'string',
                    'enum' => [
                        'on',
                        'off',
                        'draft',
                    ],
                    '$id' => '/properties/status',
                    'title' => 'Status',
                    'description' => 'object status: on, draft, off',
                    'default' => 'draft',
                ],
                '<div class="input radio"><label>Status</label><input type="hidden" name="status" value=""/><label for="status-on"><input type="radio" name="status" value="on" id="status-on" class="test">On</label><label for="status-draft"><input type="radio" name="status" value="draft" id="status-draft" class="test">Draft</label><label for="status-off"><input type="radio" name="status" value="off" id="status-off" class="test">Off</label></div>',
            ],
            'categories' => [
                'categories',
                [['name' => 'cat-1'], ['name' => 'cat-3']],
                ['class' => 'test'],
                [
                    ['name' => 'cat-1', 'label' => 'category 1'],
                    ['name' => 'cat-2', 'label' => 'category 2'],
                    ['name' => 'cat-3', 'label' => 'category 3'],
                    ['name' => 'cat-4', 'label' => 'category 4'],
                    ['name' => 'cat-5', 'label' => 'category 5'],
                ],
                '<div class="input select"><label for="categories">Categories</label><input type="hidden" name="categories" value=""/><div class="checkbox"><label for="categories-cat-1" class="selected"><input type="checkbox" name="categories[]" value="cat-1" checked="checked" id="categories-cat-1" class="test">category 1</label></div><div class="checkbox"><label for="categories-cat-2"><input type="checkbox" name="categories[]" value="cat-2" id="categories-cat-2" class="test">category 2</label></div><div class="checkbox"><label for="categories-cat-3" class="selected"><input type="checkbox" name="categories[]" value="cat-3" checked="checked" id="categories-cat-3" class="test">category 3</label></div><div class="checkbox"><label for="categories-cat-4"><input type="checkbox" name="categories[]" value="cat-4" id="categories-cat-4" class="test">category 4</label></div><div class="checkbox"><label for="categories-cat-5"><input type="checkbox" name="categories[]" value="cat-5" id="categories-cat-5" class="test">category 5</label></div></div>',
            ],
            'object' => [
                'an object',
                ['an' => 'object'],
                [],
                [
                    'type' => 'object',
                    '$id' => '/properties/object',
                ],
                '<div class="input textarea"><label for="an-object">An Object</label><textarea name="an object" v-jsoneditor="true" class="json" id="an-object" rows="5">{&quot;an&quot;:&quot;object&quot;}</textarea></div>',
            ],
            'html' => [
                'descr',
                'something',
                [],
                [
                    'type' => 'custom',
                    'name' => 'descr',
                ],
                '<dummy>something</dummy>',
            ],
        ];
    }

    /**
     * Test `control`
     *
     * @param string $key The key
     * @param mixed|null $value The value
     * @param array $options The options
     * @param array $schema The schema
     * @param string $expected The expected result
     * @return void
     *
     * @dataProvider controlProvider()
     * @covers ::control()
     * @covers ::schema()
     */
    public function testControl(string $key, $value, array $options = [], array $schema = [], string $expected = ''): void
    {
        $view = new View(null, null, null, []);
        if ($key === 'categories') {
            $schema = ['categories' => $schema];
        } else {
            $schema = ['properties' => [$key => $schema]];
        }
        $view->set('schema', $schema);
        $view->set('objectType', 'dummies');
        $property = new PropertyHelper($view);
        $actual = $property->control($key, $value, $options);
        static::assertEquals($expected, $actual);
    }

    /**
     * Dummy function to test custom control.
     *
     * @param mixed|null $value The value
     * @return array
     */
    public static function dummy($value): array
    {
        return ['html' => sprintf('<dummy>%s</dummy>', $value)];
    }
}
