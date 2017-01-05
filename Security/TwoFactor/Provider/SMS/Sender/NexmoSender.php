<?php

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider\SMS\Sender;

use \NexmoMessage;

class NexmoSender implements ChallengeSenderInterface
{
    /**
     * @var NexmoMessage $nexmo
     */
    private $nexmo;

    /**
     * @var string $fromNumber
     */
    private $fromNumber;

    /**
     * NexmoSender constructor.
     * @param $nexmoUsername
     * @param $nexmoPassword
     * @param $fromNumber
     */
    public function __construct($nexmoUsername, $nexmoPassword, $fromNumber)
    {

        $this->fromNumber = $fromNumber;

        $this->nexmo = new NexmoMessage($nexmoUsername,$nexmoPassword);
    }


    public function send($number, $text)
    {
        $result = $this->nexmo->sendText(
            $number,
            $this->from,
            $text
        );

        $result = $result->messages[0];

        if($result->status == 0) {
            //message has been sent
            return true;
        } else {

            throw new SenderException($result->status);

        }

    }
}