<?php


namespace App\Vendors\GuzzleHttp;


class GoProxyClient extends \GuzzleHttp\Client
{
    /**
     * Url проксирующего сервера
     *
     * @var string|null
     */
    protected $proxyUrl = null;

    /**
     * Установка url проксирующего сервера
     *
     * @param string $url
     */
    public function setProxyUrl(string $url): void
    {
        $this->proxyUrl = $url;
    }

    /**
     * Удаление url проксирующего сервера
     */
    public function deleteProxyUrl(): void
    {
        $this->proxyUrl = null;
    }

    /**
     * @inheritDoc
     */
    public function request($method, $uri = '', array $options = [])
    {
        if (!is_null($this->proxyUrl)) {

            $options['headers'] = $options['headers'] ?? [];
            $options['headers']['Go'] = $uri;

            if (isset($options['query']) && is_array($options['query']) && (strtoupper($method) == 'GET' || strtoupper($method) == 'POST')) {

                $query = [];
                foreach ($options['query'] as $parameterName => $parameterValue) {
                    $query[] = "{$parameterName}={$parameterValue}";
                }

                if (!empty($query)) {
                    $options['headers']['Go'] .= "?".implode("&", $query);
                }

                unset($options['query']);
            }

            $uri = $this->proxyUrl;
        }

        return parent::request($method, $uri, $options);
    }
}
