<?php

namespace SocialiteProviders\Nest;

use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\InvalidStateException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use GuzzleHttp\ClientInterface;

class Provider extends AbstractProvider implements ProviderInterface
{
  /**
   * Unique Provider Identifier.
   */
  const IDENTIFIER = 'NEST';

  /**
   * The separating character for the requested scopes.
   *
   * @var string
   */
  protected $scopeSeparator = ' ';

  /**
   * {@inheritdoc}
   */
  protected function getAuthUrl($state)
  {
    return $this->buildAuthUrlFromBase(
      'https://home.nest.com/login/oauth2', $state
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getTokenUrl()
  {
    return 'https://api.home.nest.com/oauth2/access_token';
  }

  /**
   * {@inheritdoc}
   */
  protected function getUserByToken($token)
  {
    $response = $this->getHttpClient()->get(
      'https://developer-api.nest.com', [
      'headers' => [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer ' . $token,
      ],
    ]);

    return json_decode($response->getBody()->getContents(), true);
  }

  /**
   * {@inheritdoc}
   */
  protected function mapUserToObject(array $user)
  {
    return (new User())->setRaw($user)->map([
      'devices' => $user['devices'],
      'structures' => $user['structures']
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getTokenFields($code)
  {
    return array_merge(parent::getTokenFields($code), [
      'grant_type' => 'authorization_code',
    ]);
  }

  /**
   * Get the access token for the given code.
   *
   * @param  string $code
   * @return string
   */
  public function getAccessToken($code)
  {
    $postKey = (version_compare(ClientInterface::VERSION, '6') === 1) ? 'form_params' : 'body';
    $response = $this->getHttpClient()->post($this->getTokenUrl(), [
      'headers' => ['Accept' => 'application/json'],
      $postKey => $this->getTokenFields($code),
    ]);
    return json_decode($response->getBody()->getContents(), true);
  }

  /**
   * @return \SocialiteProviders\Manager\OAuth2\User
   */
  public function user()
  {
    if ($this->hasInvalidState()) {
      throw new InvalidStateException();
    }

    $response = $this->getAccessToken($this->getCode());
    $user = $this->mapUserToObject($this->getUserByToken(
      $token = $this->parseAccessToken($response)
    ));

    $this->credentialsResponseBody = $response;

    if ($user instanceof User) {
      $user->setAccessTokenResponseBody($this->credentialsResponseBody);
    }

    return $user->setToken($token)
      ->setRefreshToken($this->parseRefreshToken($response))
      ->setExpiresIn($this->parseExpiresIn($response));
  }
}
