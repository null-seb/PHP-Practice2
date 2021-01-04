<?php


namespace App\Controller;

use App\Entity\Message;
use App\Entity\Result;
use App\Utility\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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