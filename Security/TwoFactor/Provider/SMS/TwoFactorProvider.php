<?php

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider\SMS;

use Scheb\TwoFactorBundle\Model\SMS\TwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\SMS\Sender\SenderException;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Renderer;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;


class TwoFactorProvider implements TwoFactorProviderInterface
{
    const SESSION_KEY_CHALLENGE = '2fa_sms_challenge';

    /**
     * @var BackupCodeValidator
     */
    private $backupCodeValidator;

    /**
     * @var string
     */
    private $authCodeParameter;

    /**
     * @var ChallengeSenderInterface
     */
    private $challengeSender;

    /**
     * @var EngineInterface $templating
     */
    private $templating;

    /**
     * @var int
     */
    private $digits;

    /**
     * @var bool
     */
    private $failOpen;

    /**
     * @var string formTemplate
     */
    private $formTemplate;

    /**
     * @var string errorTemplate
     */
    private $errorTemplate;

    public function __construct(BackupCodeValidator $backupCodeValidator, ChallengeSenderInterface $challengeSender, EngineInterface $templating, $authCodeParameter, $digits, $failOpen, $formTemplate, $errorTemplate)
    {
        $this->backupCodeValidator = $backupCodeValidator;
        $this->authCodeParameter = $authCodeParameter;
        $this->challengeSender = $challengeSender;
        $this->digits = $digits;
        $this->failOpen = $failOpen;
        $this->templating = $templating;
        $this->formTemplate = $formTemplate;
        $this->errorTemplate = $errorTemplate;
    }

    /**
     * Generate authentication code.
     *
     * @param int $digits
     *
     * @return int
     */
    public static function generateCode($digits)
    {
        $min = pow(10, $digits - 1);
        $max = pow(10, $digits) - 1;

        return mt_rand($min,$max);
    }

    /**
     * @param AuthenticationContext $context
     * @return boolean
     */
    public function beginAuthentication(AuthenticationContextInterface $context)
    {
        /** @var User $user */
        $user = $context->getUser();

        //no phone registered, skip auth.
        if (!($user instanceof TwoFactorInterface && $user->getSMSNumber())) {
            return false;
        }
        //otherwise, start 2f auth by SMS    
        
        // Add the challenge in the session
        $challenge = self::generateCode($this->digits);
        $context->getSession()->set(self::SESSION_KEY_CHALLENGE, $challenge);

        //get the translated message
        $message = $context->get('translator')->trans(
            'scheb_two_factor.challenge_message',
            array(
                '%challenge%' => $challenge,
                '%username%' => $user->getUsername(),
            )
        );

        //send the message
        try {

            $this->challengeSender->send($user->getSMSNumber(), $message);

        } catch (SenderException $e) {

            // What to do in case of failure of the Sender:
            if($this->failOpen) {
                //in case we want to fail open, disable the SMS provider now
                return false;
            } else {
                
                //otherwise, display the error message now
                return $this->templating->renderResponse($this->errorTemplate);
            }
        }

    }

    /**
     * Ask for SMS authentication code.
     *
     * @param AuthenticationContextInterface $context
     *
     * @return Response|null
     */
    public function requestAuthenticationCode(AuthenticationContextInterface $context)
    {
        $user = $context->getUser();
        $request = $context->getRequest();
        $session = $context->getSession();

        // Display and process form
        $authCode = $request->get($this->authCodeParameter);
        if ($authCode !== null) {

            //if the challenge is validated with the session
            if ($authCode == $session->get(self::SESSION_KEY_CHALLENGE)) {

                $context->setAuthenticated(true);
                return new RedirectResponse($request->getUri());

                //if not the SMS challenge, is it a backup code ?
            } else if ($user instanceof BackupCodeInterface && $user->isBackupCode($authCode)) {
                //backup code has been validated

                if($this->backupCodeValidator->checkCode($user, $authCode)) {

                    $context->setAuthenticated(true);
                    return new RedirectResponse($request->getUri());

                }
            }

            $session->getFlashBag()->set('two_factor', 'scheb_two_factor.code_invalid');
        }

        // Force authentication code dialog
        return $this->templating->renderResponse($this->formTemplate, array(
            'useTrustedOption' => $context->useTrustedOption(),
            'auth_type' => 'sms',
        ));
    }

}
