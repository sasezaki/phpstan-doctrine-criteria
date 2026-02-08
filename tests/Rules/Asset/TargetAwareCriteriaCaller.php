<?php

namespace Otobank\PHPStan\Doctrine\Rules\Asset;

class TargetAwareCriteriaCaller
{
    public function accessFiledShouldNotRaiseError() : void
    {
        $criteria = new AcmeTargetAwareCriteria();
        $criteria->orderBy(['foo' => 'asc']);
    }

    public function nonAccessFiledShouldNotRaiseError() : void
    {
        $criteria = new AcmeTargetAwareCriteria();
        $criteria->orderBy(['bar' => 'asc']);
    }

    public function methodCallTargetAwareCriteria() : void
    {
        $criteria = new AcmeTargetAwareCriteria();
        $criteria->orderBy(['baz' => 'asc']);
    }

    /** @param 'bar'|'baz' $filed */
    public function methodCallTargetAwareCriteria2(string $filed) : void
    {
        $criteria = new AcmeTargetAwareCriteria();
        $criteria->orderBy([$filed => 'asc']);
    }
}
