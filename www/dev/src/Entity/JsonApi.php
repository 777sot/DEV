<?php

namespace App\Entity;

use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use function count;


class JsonApi
{

    /**
     * @param HttpClientInterface $httpClient
     * @return ResponseInterface
     * @throws TransportExceptionInterface
     */
    public function getResponse($httpClient): ResponseInterface
    {
        $response = $httpClient->request(
            'POST',
            'https://blockchain.info/ticker'
        );

        return $response;
    }

    /**
     * @param $httpClient
     * @return mixed
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function getRates($httpClient): array
    {
        $response = $this->getResponse($httpClient);

        $ratesJson = json_decode(
            $response->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

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
     * @param array $rates
     * @param string $parameter
     * @return mixed
     */
    public function sortRatesWithParameter(array $rates, string $parameter)
    {
        $parameter = explode(',',
            htmlspecialchars(trim($parameter),
                 ENT_QUOTES
            )
        );

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
     * @param array $ratesArray
     * @return array
     */
    public function sortArrayASC(array $ratesArray): array
    {
        $volume = array_column($ratesArray, '15m');

        array_multisort($volume, SORT_ASC, $ratesArray);

        return $ratesArray;
    }

    /**
     * @param $numVal
     * @param int $afterPoint
     * @param int $minAfterPoint
     * @param string $thousandSep
     * @param string $decPoint
     * @return bool|string
     */
    public function numFormat($numVal, $afterPoint = 2, $minAfterPoint = 0, $thousandSep = ",", $decPoint = ".")
    {

        $ret = number_format($numVal, $afterPoint, $decPoint, $thousandSep);

        if ($afterPoint !== $minAfterPoint) {

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
     * @param array $data
     * @param string $value
     * @return bool|string
     */
    public function checkingValuesConvert(array $data, string $value)
    {
        $value =  htmlspecialchars(trim($value), ENT_QUOTES);

        foreach ($data as $key => $item) {

            if ($key === $value || $value === 'BTC') {
                return $value;
            }

        }

    }

    /**
     * @param array $data
     * @param string $value
     * @return string
     */
    public function checkingValuesRates(array $data, string $value)
    {
        $parameter = explode(',', htmlspecialchars(trim($value), ENT_QUOTES));
        $countValue = count($parameter);

        foreach ($data as $key => $item) {

            for ($i = 0; $i < $countValue; $i++) {
                if ($key === $parameter[$i]) {
                    $resultChecking[$i] = $parameter[$i];
                }
            }
        }
        if ($countValue === count($resultChecking) ) {
            return $value;
        }

    }
}
