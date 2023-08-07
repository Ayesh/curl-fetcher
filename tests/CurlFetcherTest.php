<?php


namespace Ayesh\CurlFetcher\Tests;

use Ayesh\CurlFetcher\CurlFetcher;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class CurlFetcherTest extends TestCase {
    /**
     * @covers \Ayesh\CurlFetcher\CurlFetcher
     */
    public function testCurlFetcher(): void {
        $fetcher = new CurlFetcher();
        $content = $fetcher->get('https://en.wikipedia.org/wiki/Visa_requirements_for_Iraqi_citizens');
        self::assertStringContainsString('Sri Lanka', $content);
        $size = $fetcher->getTransferSize();

        $fetcher->get('https://en.wikipedia.org/wiki/Visa_requirements_for_Iraqi_citizens');
        self::assertGreaterThan($size, $fetcher->getTransferSize());
    }

    /**
     * @covers \Ayesh\CurlFetcher\CurlFetcher
     */
    public function testCurlFetcherOn404(): void {
        $fetcher = new CurlFetcher();
        $this->expectException(RuntimeException::class);
        $fetcher->get('https://en.wikipedia.org/wiki/dsaewqeqwoewqewoq');
    }
}
