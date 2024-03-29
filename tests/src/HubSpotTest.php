<?php
namespace HelpScout\OAuth2\Client\Test;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use HelpScout\OAuth2\Client\Provider\HubSpot;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class HubSpotTest extends TestCase
{
    /**
     * @var HubSpot
     */
    protected $provider;

    protected function setUp(): void
    {
        $this->provider = new HubSpot([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'mock_redirect_uri'
        ]);
    }

    protected function getJsonFile($file, $encode = false)
    {
        $json = file_get_contents(__DIR__ . '/../' . $file);
        $data = json_decode($json, true);

        if ($encode && json_last_error() == JSON_ERROR_NONE) {
            return $data;
        }

        return $json;
    }

    public function testAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertArrayHasKey('scope', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertArrayHasKey('approval_prompt', $query);
        $this->assertNotNull($this->provider->getState());
    }

    public function testScopes()
    {
        $options = [
            'scope' => [uniqid(), uniqid()]
        ];

        $url = $this->provider->getAuthorizationUrl($options);

        $this->assertStringContainsString(implode('%20', $options['scope']), $url);
    }

    public function testGetAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);

        $this->assertEquals('app.hubspot.com', $uri['host']);
        $this->assertEquals('/oauth/authorize', $uri['path']);
    }

    public function testGetBaseAccessTokenUrl()
    {
        $params = [];

        $url = $this->provider->getBaseAccessTokenUrl($params);
        $uri = parse_url($url);

        $this->assertEquals('api.hubapi.com', $uri['host']);
        $this->assertEquals('/oauth/v1/token', $uri['path']);
    }

    public function testGetResourceOwnerDetailsUrl()
    {
        $token = 'mock_token';
        $tokenMock = $this->getMockBuilder(AccessToken::class)
            ->disableOriginalConstructor()
            ->getMock();

        $tokenMock->method('getToken')->willReturn($token);

        $url = $this->provider->getResourceOwnerDetailsUrl($tokenMock);
        $uri = parse_url($url);

        $this->assertEquals('api.hubapi.com', $uri['host']);
        $this->assertEquals('/oauth/v1/access-tokens/' . $token, $uri['path']);
    }

    public function testGetAccessToken()
    {
        $accessToken = $this->getJsonFile('access_token_response.json');
        $response = new Response(200, ['content-type' => 'json'], $accessToken);

        $clientMock = $this->getMockBuilder(ClientInterface::class)->getMock();
        $clientMock->expects($this->once())->method('send')->willReturn($response);
        $this->provider->setHttpClient($clientMock);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertEquals(time() + 21600, $token->getExpires());
        $this->assertEquals('mock_refresh_token', $token->getRefreshToken());
        $this->assertNull($token->getResourceOwnerId());
    }

    public function testUserData()
    {
        $accessToken = $this->getJsonFile('access_token_response.json');
        $accessTokenInfoJson = $this->getJsonFile('access_token_info.json');
        $accessTokenInfo = json_decode($accessTokenInfoJson, true);

        $postResponse = new Response(200, ['content-type' => 'json'], $accessToken);
        $accessTokenInfoResponse = new Response(200, ['content-type' => 'json'], $accessTokenInfoJson);

        $clientMock = $this->getMockBuilder(ClientInterface::class)->getMock();
        $clientMock->expects($this->exactly(2))
            ->method('send')
            ->willReturnOnConsecutiveCalls($postResponse, $accessTokenInfoResponse);

        $this->provider->setHttpClient($clientMock);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $user = $this->provider->getResourceOwner($token);

        $this->assertEquals($accessTokenInfo, $user->toArray());
        $this->assertEquals($accessTokenInfo['user'], $user->getEmail());
        $this->assertEquals($accessTokenInfo['user_id'], $user->getId());
        $this->assertEquals($accessTokenInfo['hub_domain'], $user->getHubSpotDomain());
    }

    public function testExceptionThrownWhenErrorReceived()
    {
        $this->expectException(IdentityProviderException::class);

        $status = rand(401,599);
        $postResponse = new Response($status, ['content-type' => 'json'], '{"error": "error_code","error_description": "A human readable error message"}');

        $clientMock = $this->getMockBuilder(ClientInterface::class)->getMock();
        $clientMock->expects($this->once())->method('send')->willReturn($postResponse);

        $this->provider->setHttpClient($clientMock);

        $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }
}
