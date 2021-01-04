<?php


namespace App\Controller;

use App\Entity\Message;
use App\Entity\Result;
use App\Utility\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ApiResultsController
 *
 * @package App\Controller
 *
 * @Route(
 *     path=ApiResultsController::RUTA_API,
 *     name="api_results_"
 * )
 */
class ApiResultsController extends AbstractController
{
    public const RUTA_API = '/api/v1/results';

    private const HEADER_CACHE_CONTROL='Cache-Control';
    private const HEADER_ETAG = 'ETag';
    private const HEADER_ALLOW='Allow';
    private const ROLE_ADMIN = 'ROLE_ADMIN';

    private  EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager){
        $this->entityManager = $entityManager;
    }

    /**
     * CGET Action
     * Summary: Retrieves the collection of Result resources.
     * Notes: Returns all results from the system that the result has access to.
     *
     * @param   Request $request
     * @return  Response
     * @Route(
     *     path=".{_format}/{sort?id}",
     *     defaults={ "_format": "json", "sort": "id" },
     *     requirements={
     *         "sort": "id|result|user|time",
     *         "_format": "json|xml"
     *     },
     *     methods={ Request::METHOD_GET },
     *     name="cget"
     * )
     *
     * @Security(
     *     expression="is_granted('IS_AUTHENTICATED_FULLY')",
     *     statusCode=401,
     *     message="`Unauthorized`: Invalid credentials."
     * )
     */
    public function cgetAction(Request $request): Response{
        $order = $request->get('sort');
        $results = $this->entityManager
            ->getRepository(Result::class)
            ->findBy([],[$order=>'ASC']);

        $format=Utils::getFormat($request);

        if(empty($results)){
            return $this->error404($format);
        }

        return Utils::apiResponse(
            Response::HTTP_OK,
            ['results'=>array_map(fn($r)=>['result'=>$r],$results)],$format,
            [
                self::HEADER_CACHE_CONTROL=>'must-revalidate',
                self::HEADER_ETAG=>md5(json_encode($results))
            ]
        );
    }

    /**
     * GET Action
     * Summary: Retrieves a Result resource based on a single ID.
     * Notes: Returns the user identified by &#x60;resultId&#x60;.
     *
     * @param Request $request
     * @param int $resultId Result id
     * @return Response
     * @Route(
     *     path="/{userId}.{_format}",
     *     defaults={ "_format": null },
     *     requirements={
     *          "userId": "\d+",
     *          "_format": "json|xml"
     *     },
     *     methods={ Request::METHOD_GET },
     *     name="get"
     * )
     *
     * @Security(
     *     expression="is_granted('IS_AUTHENTICATED_FULLY')",
     *     statusCode=401,
     *     message="`Unauthorized`: Invalid credentials."
     * )
     */
    public function getAction(Request $request, int $resultId): Response
    {
        $result = $this->entityManager
            ->getRepository(Result::class)
            ->find($resultId);
        $format=Utils::getFormat($request);

        if (empty($result)){
            return $this->error404($format);
        }

        return Utils::apiResponse(
          Response::HTTP_OK,
            [Result::Result_ATTR=>$result],
            $format,
            [
                self::HEADER_CACHE_CONTROL=>'must-revalidate',
                self::HEADER_ETAG=>md5(json_encode($result))
            ]
        );
    }

    /**
     * Summary: Provides the list of HTTP supported methods
     * Notes: Return a &#x60;Allow&#x60; header with a list of HTTP supported methods.
     *
     * @param  int $resultId Result id
     * @return Response
     * @Route(
     *     path="/{resultId}.{_format}",
     *     defaults={ "resultId" = 0, "_format": "json" },
     *     requirements={
     *          "resultId": "\d+",
     *         "_format": "json|xml"
     *     },
     *     methods={ Request::METHOD_OPTIONS },
     *     name="options"
     * )
     */
    public function optionsAction(int $resultId): Response{
        $methods =$resultId
            ?[Request::METHOD_GET,Request::METHOD_PUT,Request::METHOD_DELETE]
            :[Request::METHOD_GET,Request::METHOD_POST];
        $methods[]= Request::METHOD_OPTIONS;

        return new JsonResponse(
          null,
          Response::HTTP_NO_CONTENT,
          [
              self::HEADER_ALLOW=>implode(',',$methods),
              self::HEADER_CACHE_CONTROL=>'public, unchangeable'
          ]
        );

    }

    /**
     * DELETE Action
     * Summary: Removes the Result resource.
     * Notes: Deletes the result identified by &#x60;resultId&#x60;.
     *
     * @param   Request $request
     * @param   int $resultId Result id
     * @return  Response
     * @Route(
     *     path="/{resultId}.{_format}",
     *     defaults={ "_format": null },
     *     requirements={
     *          "resultId": "\d+",
     *         "_format": "json|xml"
     *     },
     *     methods={ Request::METHOD_DELETE },
     *     name="delete"
     * )
     *
     * @Security(
     *     expression="is_granted('IS_AUTHENTICATED_FULLY')",
     *     statusCode=401,
     *     message="`Unauthorized`: Invalid credentials."
     * )
     */
    public function deleteAction(Request $request,int $resultId):Response{

        if(!$this->isGranted(self::ROLE_ADMIN)){
            throw new HttpException(
                Response::HTTP_FORBIDDEN,
                'Forbidden: you don\'t have permission to access'
            );
        }
        $format= Utils::getFormat($request);

        $result=$this->entityManager
            ->getRepository(Result::class)
            ->findOneBy(['id'=>$resultId]);

        if(null=== $result){
            return $this->error404($format);
        }

        $this->entityManager->remove($result);
        $this->entityManager->flush();

        return Utils::apiResponse(Response::HTTP_NO_CONTENT);

    }

    /**
     * Response 404 Not Found
     * @param string $format
     *
     * @return Response
     */
    private function error404(string $format):Response{
        $message=new Message(Response::HTTP_NOT_FOUND,Response::$statusTexts[404]);
        return Utils::apiResponse(
            $message->getCode(),
            $message,
            $format
        );
    }

}