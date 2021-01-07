<?php

namespace App\Tests\Controller;

use App\Controller\ApiResultsController;
use App\Entity\Result;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ApiResultsControllerTest
 *
 * @package App\Tests\Controller
 * @group   controllers
 *
 * @coversDefaultClass ApiResultsController
 */
class ApiResultsControllerTest extends BaseTestCase
{
    private const RUTA_API = '/api/v1/results';
    private const RUTA_API_USER = '/api/v1/users';

    /**
     * Test OPTIONS /results[/resultId] 204 No Content
     *
     * @covers ::__construct
     * @covers ::optionsAction
     * @return void
     */
    public function testOptionsResultAction204NoContent(): void{
        self::$client->request(
            Request::METHOD_OPTIONS,
            self::RUTA_API
        );
        $response = self::$client->getResponse();

        self::assertSame(
            Response::HTTP_NO_CONTENT,
            $response->getStatusCode()
        );
        self::assertNotEmpty($response->headers->get('Allow'));

        // OPTIONS /api/v1/users/{id}
        self::$client->request(
            Request::METHOD_OPTIONS,
            self::RUTA_API . '/' . self::$faker->numberBetween(1, 100)
        );

        self::assertSame(
            Response::HTTP_NO_CONTENT,
            $response->getStatusCode()
        );
        self::assertNotEmpty($response->headers->get('Allow'));
    }

    /**
     * Test POST /results 201 Created
     *
     * @return array result data
     */
    public function testPostResultAction201Created(): array
    {
        $p_data = [
            Result::RESULT_ATTR=>self::$faker->numberBetween(1,100),
            Result::USER_ATTR=>$this->createUser(),
            Result::TIME_ATTR=>"2021-01-07 18:17:23",
        ];

        //201
        $headers=$this->getTokenHeaders();
        self::$client->request(
            Request::METHOD_POST,
            self::RUTA_API,
            [],
            [],
            $headers,
            json_encode($p_data),
        );
        $response = self::$client->getResponse();
//      print_r($response);die();
        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertTrue($response->isSuccessful());
        self::assertNotNull($response->headers->get('Location'));
        self::assertJson($response->getContent());
        $result = json_decode($response->getContent(), true);
        self::assertNotEmpty($result[Result::RESULT_ATTR][Result::USER_ATTR]);

        return $result['result'];
    }

    /**
     * Route userCreated
     *
     * @return  int
     */
    public function createUser(): int
    {
        $role = self::$faker->word;
        $p_data = [
            User::EMAIL_ATTR => self::$faker->email,
            User::PASSWD_ATTR => self::$faker->password,
            User::ROLES_ATTR => [ $role ],
        ];

        // 201
        $headers = $this->getTokenHeaders();
        self::$client->request(
            Request::METHOD_POST,
            self::RUTA_API_USER,
            [],
            [],
            $headers,
            json_encode($p_data)
        );
        $response = self::$client->getResponse();
        $user = json_decode($response->getContent(), true);
        return $user['user']['id'];
    }

}
