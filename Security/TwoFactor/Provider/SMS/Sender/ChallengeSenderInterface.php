<?php

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider\SMS\Sender;

interface ChallengeSenderInterface
{
    
    public function send($number,$text);

}