<?php

namespace Botonomous;

use Botonomous\client\ApiClient;
use Botonomous\utility\LoggerUtility;
use /* @noinspection PhpUndefinedClassInspection */
    GuzzleHttp\Client;
use /* @noinspection PhpUndefinedClassInspection */
    GuzzleHttp\Psr7\Request;

class Sender
{
    private $slackbot;
    private $loggerUtility;
    private $config;
    private $apiClient;
    private $client;

    /**
     * Sender constructor.
     *
     * @param AbstractBot $slackbot
     */
    public function __construct(AbstractBot $slackbot)
    {
        $this->setSlackbot($slackbot);
    }

    /**
     * @return Slackbot
     */
    public function getSlackbot()
    {
        return $this->slackbot;
    }

    /**
     * @param AbstractBot $slackbot
     */
    public function setSlackbot(AbstractBot $slackbot)
    {
        $this->slackbot = $slackbot;
    }

    /**
     * Final endpoint for the response.
     *
     * @param $channel
     * @param $response
     * @param $attachments
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function send($channel, $response, $attachments = null)
    {
        // @codeCoverageIgnoreStart
        if ($this->getSlackbot()->getListener()->isThisBot() !== false) {
            return false;
        }
        // @codeCoverageIgnoreEnd

        $responseType = $this->getConfig()->get('response');
        $debug = (bool) $this->getSlackbot()->getRequest('debug');

        if (empty($channel)) {
            $channel = $this->getConfig()->get('channel');
        }

        $data = [
            'text'    => $response,
            'channel' => $channel,
        ];

        if ($attachments !== null) {
            $data['attachments'] = json_encode($attachments);
        }

        $this->getLoggerUtility()->logChat(__METHOD__, $response, $channel);

        if ($responseType === 'slack') {
            $this->getApiClient()->chatPostMessage($data);
        } elseif ($responseType === 'slashCommand') {
            /** @noinspection PhpUndefinedClassInspection */
            $request = new Request(
                'POST',
                $this->getSlackbot()->getRequest('response_url'),
                ['Content-Type' => 'application/json'],
                json_encode([
                    'text'          => $response,
                    'response_type' => 'in_channel',
                ])
            );

            /* @noinspection PhpUndefinedClassInspection */
            $this->getClient()->send($request);
        } elseif ($responseType === 'json' || $debug === true) {
            // headers_sent is used to avoid issue in the test
            if (!headers_sent()) {
                header('Content-type:application/json;charset=utf-8');
            }

            echo json_encode($data);
        }

        return true;
    }

    /**
     * @return LoggerUtility
     */
    public function getLoggerUtility()
    {
        if (!isset($this->loggerUtility)) {
            $this->setLoggerUtility(new LoggerUtility());
        }

        return $this->loggerUtility;
    }

    /**
     * @param LoggerUtility $loggerUtility
     */
    public function setLoggerUtility(LoggerUtility $loggerUtility)
    {
        $this->loggerUtility = $loggerUtility;
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        if (!isset($this->config)) {
            $this->config = (new Config());
        }

        return $this->config;
    }

    /**
     * @param Config $config
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @return ApiClient
     */
    public function getApiClient()
    {
        if (!isset($this->apiClient)) {
            $this->setApiClient(new ApiClient());
        }

        return $this->apiClient;
    }

    /**
     * @param ApiClient $apiClient
     */
    public function setApiClient(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        if (!isset($this->client)) {
            $this->setClient(new Client());
        }

        return $this->client;
    }

    /**
     * @param Client $client
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
    }
}
