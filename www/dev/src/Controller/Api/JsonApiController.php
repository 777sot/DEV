<?php

namespace App\Controller\Api;


use ErrorException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;


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
     * JsonApiController constructor.
     * @param HttpClientInterface $httpClient
     */
    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @param $numVal
     * @param int $afterPoint
     * @param int $minAfterPoint
     * @param string $thousandSep
     * @param string $decPoint
     * @return bool|string
     */
    public function num_format($numVal, $afterPoint = 2, $minAfterPoint = 0, $thousandSep = ",", $decPoint = ".")
    {

        $ret = number_format($numVal, $afterPoint, $decPoint, $thousandSep);
        if ($afterPoint != $minAfterPoint) {
            while (($afterPoint > $minAfterPoint) && (substr($ret, -1) === '0')) {
                $ret = substr($ret, 0, -1);
                --$afterPoint;
            }
        }
        if (substr($ret, -1) === $decPoint) {
            $ret = substr($ret, 0, -1);
        }
        return $ret;
    }


    /**
     * @Route("/api/v1", name="app_json_api")
     * @return Response
     * @throws TransportExceptionInterface
     * @throws ErrorException
     */
    public function index(): Response
    {
        if ($_REQUEST['method'] && $_REQUEST['method'] === 'rates') {

            return $this->rates();
        }
        if ($_REQUEST['method'] && $_REQUEST['method'] === 'convert') {

            return $this->convert();
        }
    }

    /**
     * @param array $ratesArray
     * @return array
     */
    public function sortArrayASC(array $ratesArray)
    {
        $volume = array_column($ratesArray, '15m');
        array_multisort($volume, SORT_ASC, $ratesArray);

        return $ratesArray;
    }

    /**
     * @param array $rates
     * @param string $parameter
     * @return array
     */
    public function sortRatesWithParameter(array $rates, string $parameter): array
    {
        $parameter = explode(',', $parameter);

        foreach ($parameter as $key => $value) {
            foreach ($rates as $i => $v) {
                if ($value === $i) {
                    $result[$i] = $v;
                }
            }
        }
        return $result;
    }

    /**
     * @return ResponseInterface
     * @throws TransportExceptionInterface
     */
    public function getResponse()
    {
        $response = $this->httpClient->request(
            'POST',
            'https://blockchain.info/ticker'
        );
        return $response;
    }

    public function getRates()
    {
        $response = $this->getResponse();
        $ratesJson = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        foreach ($ratesJson as $key => $value) {
            foreach ($value as $i => $iValue) {
                if (is_numeric($iValue)) {
                    $iValue *= 1.02; // rates + 2%
                }
                $ratesArray[$key][$i] = $iValue;
            }
        }
        return $ratesArray;
    }

    /**
     * @return Response
     * @throws TransportExceptionInterface
     */
    public function rates(): Response
    {
        $ratesArray = $this->getRates();
        $response = $this->getResponse();

        if (isset($_REQUEST['parameter'])) {

            $parameter = $_REQUEST['parameter'];
            $ratesArray = $this->sortRatesWithParameter((array)$ratesArray, $parameter);
        }

        if ($response->getStatusCode() !== 200) {

            return $this->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Invalid token',
            ]);
        }

        return $this->json([
            'status' => 'success',
            'code' => $response->getStatusCode(),
            'data' => $this->sortArrayASC($ratesArray),
        ]);
    }


    public function convert(): Response
    {
        $ratesArray = $this->getRates();
        $response = $this->getResponse();


        if ($_REQUEST['currency_from'] && $_REQUEST['currency_to'] && $_REQUEST['value']) {

            $parameter = $_REQUEST['currency_from'] . ',' . $_REQUEST['currency_to'];
            $ratesArray = $this->sortRatesWithParameter((array)$ratesArray, $parameter);

            if ($_REQUEST['value'] < 0.01) {

                throw new ErrorException('error minimum value 0.01');
            }
            if ($_REQUEST['currency_from'] !== 'BTC') {
                $rate = $ratesArray[$_REQUEST['currency_from']]['buy'];
                $convertedValue = $this->num_format($_REQUEST['value']/$rate, 10);
            } elseif ($_REQUEST['currency_from'] === 'BTC') {
                $rate = $ratesArray[$_REQUEST['currency_to']]['buy'];
                $convertedValue = $this->num_format($_REQUEST['value']*$rate, 2);
            }


            if ($response->getStatusCode() === 200) {

                return $this->json([
                    'status' => 'success',
                    'code' => $response->getStatusCode(),
                    'data' => [
                        'currency_from' => $_REQUEST['currency_from'],
                        'currency_to' => $_REQUEST['currency_to'],
                        'value' => $_REQUEST['value'],
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
