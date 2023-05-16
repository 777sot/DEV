<?php

namespace App\Security;

use App\Repository\UserRepository;
use DateTimeImmutable;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use stdClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Http\Util\TargetPathTrait;


class ApiKeyAuthenticator extends AbstractFormLoginAuthenticator
{
    use TargetPathTrait;

    protected const HEADER_AUTH_TOKEN = 'AUTH-TOKEN';

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    private $userRepository;
    /**
     * @var Response
     */
    private $response;


    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        UserRepository $userRepository
    )
    {
        $this->urlGenerator = $urlGenerator;
        $this->userRepository = $userRepository;
        $this->response = new Response();
    }

    public function getToken(): string
    {
        $secretKey = 'T608PM1gnT'; //pass user

        $issuedAt = new DateTimeImmutable();

        $nbf = $issuedAt->getTimestamp();

        $expire = $issuedAt->modify('+30240 minutes')->getTimestamp();

        $serverName = 'https://www.google.ru/';

        $payload = [
            'iat' => $nbf,         // Issued at: time when the token was generated
            'iss' => $serverName,       // Issuer
            'nbf' => $nbf,         // Not before
            'exp' => $expire,           // Expire
            'key' => $secretKey     // secretKey
        ];


        return JWT::encode(
            $payload,
            $secretKey,
            'HS256'
        );
    }

    /**
     * @param $token
     * @return stdClass
     */
    public function decodedToken($token, $key = null)
    {
        if ($key !== null) {
            $secretKey = $key;
        } else {
            $secretKey = 'T608PM1gnT'; //pass user
        }

        return JWT::decode($token, new Key($secretKey, 'HS256'));

    }


    protected function getLoginUrl()
    {
        return $this->urlGenerator->generate('app_json_api');
    }


    public function supports(Request $request)
    {
        return $request->attributes->get('_route') === 'app_json_api';
    }


    public function getCredentials(Request $request)
    {
        if ($request->headers->has(self::HEADER_AUTH_TOKEN)) {
            $credentials = [
                'token' => $request->headers->get(self::HEADER_AUTH_TOKEN)
            ];
            $request->getSession()->set(
                Security::LAST_USERNAME,
                $credentials['token']
            );
            return $credentials;

        } else {

            return $this->response->setStatusCode(403);
        }

    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        if ($this->response->getStatusCode() !== 403) {

            $token = $this->decodedToken($credentials['token']);

            $user = $this->userRepository->findOneBy(['password' => $token->key]);
        } else {
            $this->response->setContent(json_encode([
                'status' => 'error',
                'code' => 403,
                'message' => 'Invalid token',
            ], JSON_THROW_ON_ERROR));
            $this->response->headers->set('Content-Type', 'application/json');
            $this->response->send();
            exit();
        }

        return $user;

    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return true;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey)
    {
        $path = $this->getTargetPath($request->getSession(), $providerKey);
        //return new RedirectResponse($path ?: $this -> urlGenerator -> generate('app_json_api'));
    }
}
