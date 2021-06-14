<?php
namespace App\Test\TestCase\Controller\Component;

use App\Controller\Component\QueryComponent;
use Cake\Controller\Controller;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

/**
 * {@see \App\Controller\Component\QueryComponent} Test Case
 *
 * @coversDefaultClass \App\Controller\Component\QueryComponent
 */
class QueryComponentTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Controller\Component\QueryComponent
     */
    public $Query;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $controller = new Controller();
        $registry = $controller->components();
        $this->Query = $registry->load(QueryComponent::class);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Query);

        parent::tearDown();
    }

    /**
     * Provider for `testIndex`.
     *
     * @return array
     */
    public function indexProvider(): array
    {
        return [
            'query filter' => [
                ['filter' => ['gustavo' => true]],
                ['filter' => ['gustavo' => true]],
            ],
            'no query' => [
                ['some' => ['thing' => 'else']],
                ['some' => ['thing' => 'else'], 'sort' => '-id'],
            ],
        ];
    }

    /**
     * Test `index` method
     *
     * @return void
     * @covers ::index()
     * @dataProvider indexProvider()
     */
    public function testIndex(array $queryParams, array $expected): void
    {
        $controller = new Controller(
            new ServerRequest(
                [
                    'query' => $queryParams,
                    'environment' => [
                        'REQUEST_METHOD' => 'GET',
                    ],
                ]
            )
        );
        $registry = $controller->components();
        $this->Query = $registry->load(QueryComponent::class);
        $actual = $this->Query->index();
        static::assertEquals($expected, $actual);
    }

    /**
     * Data provider for `testPrepare`
     *
     * @return void
     */
    public function prepareProvider()
    {
        return [
            'simple' => [
                [
                    'page_size' => 7,
                    'q' => 'gustavo',
                ],
                [
                    'page_items' => 32,
                    'page_size' => 7,
                    'count' => 123,
                    'q' => 'gustavo',
                    'filter' => [],
                ],
            ],

            'filter 1' => [
                [
                    'filter' => [
                        'type' => 'documents',
                    ],
                ],
                [
                    'filter' => [
                        'type' => 'documents',
                        'b' => null,
                    ],
                ],
            ],
            'filter 2' => [
                [],
                [
                    'filter' => [
                        'type' => null,
                        'a' => '',
                    ],
                ],
            ],
        ];
    }

    /**
     * Test `prepare` method.
     *
     * @return void
     *
     * @dataProvider prepareProvider
     * @covers ::prepare()
     */
    public function testPrepare(array $expected, array $query): void
    {
        $actual = $this->Query->prepare($query);
        static::assertEquals($expected, $actual);
    }
}
