<?php

namespace App\Controller\Api;

use App\Entity\JsonApi;
use ErrorException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;


/**
 * Class JsonApiController
 * @package App\Controller\Api
 */
class JsonApiController extends AbstractController
{
    /**
     * @var HttpClientInterface
     */
    private HttpClientInterface $httpClient;
    /**
     * @var JsonApi
     */
    private JsonApi $jsonApi;
    /**
     * @var Request
     */
    private $request;

    /**
     * JsonApiController constructor.
     * @param HttpClientInterface $httpClient
     */
    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->jsonApi = new JsonApi();
        $this->request = new Request(
            $_GET,
            $_POST,
            [],
            $_COOKIE,
            $_FILES,
            $_SERVER
        );

    }

    /**
     * @Route("/api/v1", name="app_json_api")
     * @return Response
     * @throws ClientExceptionInterface
     * @throws ErrorException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function index(): Response
    {
        $method = $this->request->get('method');
        //$method = htmlspecialchars(strip_tags($_REQUEST['method']));


        if ($method) {

            if ($method === 'rates') {

                return $this->rates();
            } elseif ($method === 'convert') {

                return $this->convert();
            } else {
                throw new ErrorException('No such method was found');
            }
        }

    }

    /**
     * @return Response
     * @throws ClientExceptionInterface
     * @throws ErrorException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    protected function rates(): Response
    {
        $parameter = $this->request->get('parameter');
        //$parameter = $_REQUEST['parameter'];

        $rates_array = $this->jsonApi
            ->getRates($this->httpClient);

        $parameter = $this->jsonApi->checkingValuesRates($rates_array, $parameter);

        if ($parameter !== null) {

            $rates_array = $this->jsonApi
                ->sortRatesWithParameter($rates_array, $parameter);
        } else {

            throw new ErrorException('the currency format does not match the set one');
        }

        if ($this->jsonApi
                ->getResponse($this->httpClient)
                ->getStatusCode() !== 200
        ) {

            return $this->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Invalid token',
            ]);
        }

        return $this->json([
            'status' => 'success',
            'code' => $this->jsonApi
                ->getResponse($this->httpClient)
                ->getStatusCode()
            ,
            'data' => $this->jsonApi
                ->sortArrayASC($rates_array)
            ,
        ]);
    }


    /**
     * @return Response
     * @throws ClientExceptionInterface
     * @throws ErrorException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    protected function convert(): Response
    {
        $currency_to = $this->request->get('currency_to');
        $currency_from = $this->request->get('currency_from');
        $value = $this->request->get('value');

        if ($currency_from && $currency_to) {

            $currency_from = $this->jsonApi
                ->checkingValuesConvert(
                    $this->jsonApi->
                    getRates($this->httpClient)
                    ,
                    $currency_from
                );

            $currency_to = $this->jsonApi
                ->checkingValuesConvert(
                    $this->jsonApi->
                    getRates($this->httpClient)
                    ,
                    $currency_to
                );

        }

        if (!is_numeric($value)) {
            throw new ErrorException('Invalid value format');
        }


        if (empty($currency_from) || empty($currency_to)) {

            throw new ErrorException('The currency format does not match the set one');

        }

        $response = $this->jsonApi->getResponse($this->httpClient);

        if ($currency_from && $currency_to && $value) {

            $parameter = $currency_from . ',' . $currency_to;

            $rates_array = $this->jsonApi
                ->sortRatesWithParameter(
                    (array)$this->jsonApi
                        ->getRates($this->httpClient)
                    ,
                    $parameter
                );

            if ($value < 0.01) {

                throw new ErrorException('error minimum value 0.01');
            }

            if ($currency_from !== 'BTC') {

                $rate = $rates_array[$currency_from]['buy'];

                $converted_value = $this->jsonApi
                    ->numFormat(
                        $value/$rate,
                        10
                    );

            } elseif ($currency_from === 'BTC') {

                $rate = $rates_array[$currency_to]['buy'];

                $converted_value = $this->jsonApi
                    ->numFormat(
                        $value*$rate,
                        2
                    );
            }

            if ($response->getStatusCode() === 200) {

                return $this->json([
                    'status' => 'success',
                    'code' => $response->getStatusCode(),
                    'data' => compact('currency_from', 'currency_to', 'value', 'converted_value', 'rate'),
                ]);
            }

            return $this->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Invalid token',
            ]);
        }

    }
}
