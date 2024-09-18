<?php

namespace App\HttpClients;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class PostHttpClient
{
    private string $token = '';
    private ?int $timeOfLifeToken = null;
    private const BASE_URL = 'http://127.0.0.1:8000/api';
    private const ENDPOINTS = [
        'index' => '/posts',
        'login' => '/login',
        'create' => '/posts',
        'update' => '/posts/{id}',
        'delete' => '/posts/{id}'
    ];

    /**
     * @throws ConnectionException
     * @throws \Exception
     */
    public function index(): Collection
    {
        return $this->makeRequest('post', self::ENDPOINTS['index'])->collect();
    }

    /**
     * @throws ConnectionException
     * @throws \Exception
     */

    public function store(array $data): array
    {
        return $this->makeRequest('post', self::ENDPOINTS['create'], $data)->json();
    }

    /**
     * @throws ConnectionException
     * @throws \Exception
     */
    public function update(int $id, array $data): array
    {
        $endpoint = str_replace('{id}', $id, self::ENDPOINTS['update']);
        return $this->makeRequest('patch', $endpoint, $data)->json();
    }

    /**
     * @param  int  $id
     * @throws ConnectionException
     * @throws \Exception
     */
    public function delete(int $id): bool
    {
        $endpoint = str_replace('{id}', $id, self::ENDPOINTS['delete']);
        $this->makeRequest('delete', $endpoint)->json();

        return true;
    }

    /**
     * @throws \Exception
     */
    public function login(): PostHttpClient
    {
        $response = Http::post(self::BASE_URL.self::ENDPOINTS['login'], [
            'email' => config('forest.email'),
            "password" => config('forest.password'),
        ]);

        $this->handleResponse($response, 'login');
        $data = $response->collect();
        $this->token = $data['access_token'];
        $this->timeOfLifeToken = now()->addSeconds($data['expires_in'])->timestamp;

        return $this;
    }

    public static function make(): PostHttpClient
    {
        return new self();
    }

    /**
     * @param $response
     * @param  string  $action
     * @throws \Exception
     */
    private function handleResponse($response, string $action): void
    {
        if (!$response->successful()) {
            throw new \Exception("Failed to {$action}. Status: ".$response->status());
        }
    }

    /**
     * Make a request with token validation.
     *
     * @param  string  $method
     * @param  string  $endpoint
     * @param  array|null  $data
     * @return \Illuminate\Http\Client\Response
     * @throws \Exception
     */
    private function makeRequest(string $method, string $endpoint, array $data = null)
    {
        $this->checkAndRefreshToken();

        $response = Http::withToken($this->token)
            ->{$method}(self::BASE_URL.$endpoint, $data);

        $this->handleResponse($response, $method.' request');

        return $response;
    }

    /**
     * Check if the token exists and is still valid. If not, refresh the token.
     *
     * @throws \Exception
     */
    private function checkAndRefreshToken(): void
    {
        if (!$this->token || $this->isTokenExpired()) {
            $this->login();
        }
    }

    private function isTokenExpired(): bool
    {
        return $this->timeOfLifeToken === null || now()->timestamp >= $this->timeOfLifeToken;
    }
}
