<?php

namespace Scheb\TwoFactorBundle\Model\SMS;

interface TwoFactorInterface
{
    /**
     * Return the user name.
     *
     * @return string
     */
    public function getUsername();
    
    /**
     * Return the SMS number of the user
     * When an empty string or null is returned, the SMS authentication is disabled.
     *
     * @return string|null
     */
    public function getSMSNumber();

    /**
     * Set the SMS Number of the user
     *
     * @param int $SMSNumber
     */
    public function setSMSNumber($SMSNumber);
}
