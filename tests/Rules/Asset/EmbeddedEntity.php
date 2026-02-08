<?php

namespace Otobank\PHPStan\Doctrine\Rules\Asset;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable()
 */
class EmbeddedEntity
{
    /**
     * @ORM\Column(name="baz", type="string")
     */
    private $baz;

    /**
     * @ORM\Column(name="baz2", type="string")
     */
    private $baz2;

    public function getBaz() : string
    {
        return $this->baz;
    }

    public function getBaz2() : string
    {
        return $this->baz;
    }
}
