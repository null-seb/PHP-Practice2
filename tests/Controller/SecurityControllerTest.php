<?php

/**
 * PHP version 7.4
 *
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://www.etsisi.upm.es/ E.T.S. de Ingeniería de Sistemas Informáticos
 */

namespace App\Tests\Controller;

use Generator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SecurityControllerTest
 *
 * @package App\Tests\Controller
 * @group   controllers
 *
 * @coversDefaultClass \App\Controller\SecurityController
 */
class SecurityControllerTest extends BaseTestCase
{

    /**
     * POST /api/v1/login_check 200 Ok
     *
     * @param string $testEmail
     * @param string $testPasswd
     * @dataProvider userProvider()
     * @return void
     */
    public function testLogincheckAction200Ok(string $testEmail, string $testPasswd): void
    {
        $data = [
            'email' => $testEmail,
            'password' => $testPasswd
        ];

        // Request body
        self::$client->request(
            Request::METHOD_POST,
            '/api/v1/login_check',
            [],
            [],
            [ 'CONTENT_TYPE' => 'application/json' ],
            json_encode($data)
        );
        $response = self::$client->getResponse();
        $json_resp = json_decode($response->getContent(), true);
        self::assertArrayHasKey('token', $json_resp);
        self::assertNotNull($response->headers->get('X-Token'));

        // Form
        self::$client->request(
            Request::METHOD_POST,
            '/api/v1/login_check',
            $data,
            [],
            [ 'CONTENT_TYPE' => 'application/x-www-form-urlencoded' ]
        );
        $response = self::$client->getResponse();
        self::assertTrue($response->isSuccessful());
        $json_resp = json_decode($response->getContent(), true);
        self::assertArrayHasKey('token', $json_resp);
        self::assertNotNull($response->headers->get('X-Token'));

        // Urlencoded request body
        $data = 'email=' . urlencode($testEmail);
        $data .= '&password=' . urlencode($testPasswd);
        self::$client->request(
            Request::METHOD_POST,
            '/api/v1/login_check',
            [],
            [],
            [ 'CONTENT_TYPE' => 'text/plain' ],
            $data
        );
        $response = self::$client->getResponse();
        self::assertTrue($response->isSuccessful());
        $json_resp = json_decode($response->getContent(), true);
        self::assertArrayHasKey('token', $json_resp);
        self::assertNotNull($response->headers->get('X-Token'));
    }

    /**
     * POST /api/v1/login_check 401 UNAUTHORIZED
     *
     * @return void
     */
    public function testLogincheckAction401Unauthorized(): void
    {
        $data = [
            'email' => self::$faker->email,
            'password' => self::$faker->password
        ];

        self::$client->request(
            Request::METHOD_POST,
            '/api/v1/login_check',
            [],
            [],
            [ 'CONTENT_TYPE' => 'application/json' ],
            json_encode($data)
        );
        $response = self::$client->getResponse();
        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $json_resp = json_decode($response->getContent(), true);
        self::assertArrayNotHasKey('token', $json_resp);
        self::assertNull($response->headers->get('X-Token'));
    }

    /**
     * User provider
     *
     * @return Generator [ username, password]
     */
    public function userProvider(): Generator
    {
        yield 'role_user'  => [ $_ENV['ROLE_USER_EMAIL'], $_ENV['ROLE_USER_PASSWD'] ];
        yield 'role_admin' => [ $_ENV['ADMIN_USER_EMAIL'], $_ENV['ADMIN_USER_PASSWD'] ];
    }
}
