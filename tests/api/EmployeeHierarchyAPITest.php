<?php

namespace api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;

class EmployeeHierarchyAPITest extends TestCase
{
    private $httpClient;
    private $baseUri;

    protected function setUp(): void
    {
        // Set up the HTTP client with the base URI from the ENDPOINT environment variable
        $this->httpClient = new Client(['base_uri' => $_ENV['ENDPOINT']]);
        $this->baseUri = $_ENV['ENDPOINT'];
    }

    /**
     * @throws GuzzleException
     */
    public function testHandleRequest_PostRequest_ValidJSON_ReturnsSuccessResponse()
    {
        // Arrange
        $json = '{
            "Pete": "Nick",
            "Barbara": "Nick",
            "Nick": "Sophie",
            "Sophie": "Jonas"
        }';

        $validToken = "your_secret_token_here";
        $headers = ['Authorization' => "Bearer $validToken", 'Content-Type' => 'application/json'];

        // Act
        $response = $this->httpClient->post('/path/to/endpoint', [
            'headers' => $headers,
            'body' => $json,
        ]);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Inserted 4 employee hierarchy data.', (string)$response->getBody());
    }

    /**
     * @throws GuzzleException
     */
    public function testHandleRequest_PostRequest_InvalidJSON_ReturnsErrorResponse()
    {
        // Arrange
        $invalidJson = 'This is not a valid JSON';
        $validToken = "your_secret_token_here";
        $headers = ['Authorization' => "Bearer $validToken", 'Content-Type' => 'application/json'];

        // Act
        $response = $this->httpClient->post('/path/to/endpoint', [
            'headers' => $headers,
            'body' => $invalidJson,
        ]);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Invalid JSON format.', (string)$response->getBody());
    }

    public function testHandleRequest_GetRequest_InvalidQueryParameter_ReturnsErrorResponse()
    {
        // Arrange
        $validToken = "your_secret_token_here";
        $headers = ['Authorization' => "Bearer $validToken"];

        // Act
        $response = $this->httpClient->get('/path/to/endpoint', [
            'headers' => $headers,
            'query' => ['query' => 'invalid_query'],
        ]);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Invalid query parameter.', (string)$response->getBody());
    }

    public function testHandleRequest_InvalidRequestMethod_ReturnsErrorResponse()
    {
        // Arrange
        $validToken = "your_secret_token_here";
        $headers = ['Authorization' => "Bearer $validToken"];

        // Act
        $response = $this->httpClient->put('/path/to/endpoint', [
            'headers' => $headers,
        ]);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Invalid request.', (string)$response->getBody());
    }
}
