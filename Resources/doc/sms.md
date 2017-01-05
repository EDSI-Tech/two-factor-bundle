SMS Authentication
====================

## How it works ##

The user entity has to be linked with a phone number.


## Basic Configuration ##

To enable this authentication method add this to your config.yml:

```yaml
scheb_two_factor:
    sms:
        enabled: true
```

Your user entity has to implement `Scheb\TwoFactorBundle\Model\SMS\TwoFactorInterface`. The phone number must be persisted, so make sure that it is stored in a persisted field.

```php
namespace Acme\DemoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\SMS\TwoFactorInterface;

class User implements TwoFactorInterface
{
    /**
     * @ORM\Column(name="SMSNumber", type="string", nullable=true)
     */
    private $SMSNumber;

    // [...]

    public function getSMSNumber()
    {
        return $this->SMSNumber;
    }

    public function setSMSNumber($SMSNumber)
    {
        $this->SMSNumber = $SMSNumber;
    }
}
```


## Custom Template ##

The bundle uses `Resources/views/Authentication/form.html.twig` to render the authentication form. If you want to use a different template you can simply register it in configuration:

```yaml
scheb_two_factor:
    sms:
        template: AcmeDemoBundle:Authentication:my_custom_template.html.twig
```


