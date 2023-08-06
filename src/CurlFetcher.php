<?php

namespace Ayesh\CurlFetcher;

use CurlHandle;
use RuntimeException;

use function curl_error;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt;
use function is_array;
use function is_object;
use function json_decode;
use function sprintf;

use const CURL_SSLVERSION_TLSv1_2;
use const CURLINFO_RESPONSE_CODE;
use const CURLINFO_SIZE_DOWNLOAD;
use const CURLOPT_ENCODING;
use const CURLOPT_FOLLOWLOCATION;
use const CURLOPT_HEADER;
use const CURLOPT_MAXREDIRS;
use const CURLOPT_PROTOCOLS;
use const CURLOPT_REDIR_PROTOCOLS;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_SSL_VERIFYHOST;
use const CURLOPT_SSL_VERIFYPEER;
use const CURLOPT_SSLVERSION;
use const CURLOPT_TCP_KEEPALIVE;
use const CURLOPT_TIMEOUT;
use const CURLOPT_URL;
use const CURLPROTO_HTTP;
use const CURLPROTO_HTTPS;
use const JSON_THROW_ON_ERROR;

class CurlFetcher {
    private CurlHandle $curlHandle;
    private int $total_download_size = 0;

    public function __construct() {
        $this->curlHandle = $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS | CURLPROTO_HTTP);
        curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTPS);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TCP_KEEPALIVE, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'ayesh/curl-fetcher');
    }

    public function getTransferSize(): int {
        return $this->total_download_size;
    }

    public function get(string $url, array $headers = []): string {
        curl_setopt($this->curlHandle, CURLOPT_URL, $url);
        curl_setopt($this->curlHandle, CURLOPT_HTTPHEADER, $headers);

        $content = curl_exec($this->curlHandle);

        $this->total_download_size += curl_getinfo($this->curlHandle, CURLINFO_SIZE_DOWNLOAD);

        $this->reportErrors($url, $content);
        return $content;
    }

    /**
     * @throws \JsonException
     */
    public function getJson(string $url, array $headers = []): object|array {
        $data = $this->get($url, $headers);
        $data = json_decode($data, false, 16, JSON_THROW_ON_ERROR);
        if (!is_array($data) && !is_object($data)) {
            throw new RuntimeException('Returned content is not a JSON response containing array|object');
        }

        return $data;
    }

    private function reportErrors(string $url, string|false $content): void {
        if ($content === false) {
            $error = curl_error($this->curlHandle);
            throw new RuntimeException(sprintf('Unable to fetch content from: %s ; Error: %s', $url, $error));
        }

        if ($content === '') {
            throw new RuntimeException(sprintf('Returned empty content from: %s', $url));
        }

        $status_code = curl_getinfo($this->curlHandle, CURLINFO_RESPONSE_CODE);
        if ($status_code !== 200) {
            throw new RuntimeException(sprintf("HTTP Error %d in %s\r\n%s", $status_code, $url, $content));
        }
    }
}
