<?php

namespace Neox\Reddit;

use Rx\Disposable\CompositeDisposable;

/**
 * Class CurlObservable
 * @package Neox\Reddit
 */
class CurlObservable extends \Rx\Observable
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var array
     */
    private $observers;

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    private function startDownload()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, [$this, 'progress']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_NOPROGRESS, false);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.98 Safari/537.36');
        // Disable gzip compression
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip;q=0,deflate,sdch');
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    /**
     * @param \Rx\ObserverInterface $observer
     * @return \Rx\DisposableInterface
     */
    public function _subscribe(\Rx\ObserverInterface $observer): \Rx\DisposableInterface
    {
        $scheduler = new \Rx\Scheduler\ImmediateScheduler();

        $this->observers[] = $observer;

        $scheduledDisposable = $scheduler->schedule(function () use ($observer) {
            $response = $this->startDownload();

            if ($response) {
                $observer->onNext($response);
                $observer->onCompleted();
            } else {
                $observer->onError(new \Exception('Unable to download ' . $this->url));
            }
        });

        return new CompositeDisposable([$scheduledDisposable]);
    }

    /**
     * @param $resource
     * @param $downloadTotal
     * @param $downloadNow
     * @param $uploadTotal
     * @param $uploadNow
     */
    private function progress($resource, $downloadTotal, $downloadNow, $uploadTotal, $uploadNow)
    {
        if ($downloadTotal > 0) {
            $percentage = sprintf("%.2f", $downloadNow / $downloadTotal * 100);
            foreach ($this->observers as $observer) {
                /** @var \Rx\ObserverInterface $observer */
                $observer->onNext(floatval($percentage));
            }
        }
    }
}