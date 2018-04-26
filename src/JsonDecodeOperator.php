<?php

namespace Neox\Reddit;

use Rx\DisposableInterface;
use Rx\ObservableInterface as ObservableI;
use Rx\Observer\CallbackObserver;
use Rx\ObserverInterface as ObserverI;
use Rx\Operator\OperatorInterface;
use Rx\SchedulerInterface as SchedulerI;

class JsonDecodeOperator implements OperatorInterface
{
    public function __invoke(ObservableI $observable, ObserverI $observer, SchedulerI $scheduler = null): DisposableInterface
    {
        $callbackObserver = new CallbackObserver(
            function ($value) use ($observer) {
                $decoded = json_decode($value, true);
                if (json_last_error() == JSON_ERROR_NONE) {
                    $observer->onNext($decoded);
                } else {
                    $msg = json_last_error_msg();
                    $e = new \InvalidArgumentException($msg);
                    $observer->onError($e);
                }
            },
            [$observer, 'onError'],
            [$observer, 'onCompleted']
        );

        return $observable->subscribe($callbackObserver, $scheduler);
    }
}