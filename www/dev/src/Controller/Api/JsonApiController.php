<?php

namespace App\Controller\Api;


use App\Entity\JsonApi;
use ErrorException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
     * JsonApiController constructor.
     * @param HttpClientInterface $httpClient
     */
    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->jsonApi = new JsonApi();
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
        if ($_REQUEST['method']) {

            if ($_REQUEST['method'] === 'rates') {

                return $this->rates();
            } elseif ($_REQUEST['method'] === 'convert') {

                return $this->convert();
            } else {
                throw new ErrorException('No such method was found');
            }

        }

    }

    /**
     * @return Response
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws ErrorException
     */
    protected function rates(): Response
    {
        $parameter = $_REQUEST['parameter'];

        $ratesArray = $this->jsonApi
            ->getRates($this->httpClient)
        ;

        $parameter = $this->jsonApi->checkingValuesRates($ratesArray, $parameter);

        if ($parameter !== null) {

            $ratesArray = $this->jsonApi
                ->sortRatesWithParameter($ratesArray, $parameter)
            ;
        }else {

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
                ->sortArrayASC($ratesArray)
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
        if ($_REQUEST['currency_from'] && $_REQUEST['currency_to']) {

            $currencyFrom = $this->jsonApi
                ->checkingValuesConvert(
                    $this->jsonApi->
                    getRates($this->httpClient)
                    ,
                    $_REQUEST['currency_from']
                )
            ;

            $currencyTo = $this->jsonApi
                ->checkingValuesConvert(
                    $this->jsonApi->
                    getRates($this->httpClient)
                    ,
                    $_REQUEST['currency_to']
                )
            ;

        }

        if (is_numeric($_REQUEST['value'])) {

            $value = htmlspecialchars($_REQUEST['value'], ENT_QUOTES);
        }


        if (empty($currencyFrom) || empty($currencyTo)) {

            throw new ErrorException('the currency format does not match the set one');

        }

        $response = $this->jsonApi->getResponse($this->httpClient);

        if ($currencyFrom && $currencyTo && $value) {

            $parameter = $currencyFrom . ',' . $currencyTo;

            $ratesArray = $this->jsonApi
                ->sortRatesWithParameter(
                    (array)$this->jsonApi
                        ->getRates($this->httpClient)
                    ,
                    $parameter
                )
            ;

            if ($value < 0.01) {

                throw new ErrorException('error minimum value 0.01');
            }

            if ($currencyFrom !== 'BTC') {

                $rate = $ratesArray[$currencyFrom]['buy'];

                $convertedValue = $this->jsonApi
                    ->numFormat(
                        $value/$rate,
                        10
                    )
                ;

            } elseif ($currencyFrom === 'BTC') {

                $rate = $ratesArray[$currencyTo]['buy'];

                $convertedValue = $this->jsonApi
                    ->numFormat(
                        $value*$rate,
                        2
                    )
                ;
            }

            if ($response->getStatusCode() === 200) {

                return $this->json([
                    'status' => 'success',
                    'code' => $response->getStatusCode(),
                    'data' => [
                        'currency_from' => $currencyFrom,
                        'currency_to' => $currencyTo,
                        'value' => $value,
                        'converted_value' => $convertedValue,
                        'rate' => $rate,
                    ],
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
