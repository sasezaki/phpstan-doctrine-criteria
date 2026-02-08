<?php

namespace Otobank\PHPStan\Doctrine\Rules;

use Doctrine\Common\Collections\Criteria;
use Otobank\Doctrine\Collections\TargetAwareCriteriaInterface;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;

/**
 * ```
 *   // Validate fieldA
 *   TargetAwareCriteria::orderBy([
 *       'fieldA' => ...
 *   ]);
 * ```
 *
 * @template-implements \PHPStan\Rules\Rule<MethodCall>
 */
class ValidateFieldCriteriaCallRule implements \PHPStan\Rules\Rule
{
    use ValidateTrait;

    public function getNodeType() : string
    {
        return MethodCall::class;
    }

    /**
     * @param MethodCall $node
     *
     * @return (string|\PHPStan\Rules\RuleError)[]
     */
    public function processNode(Node $node, Scope $scope) : array
    {
        $type = $scope->getType($node->var);

        if (! $type->isObject()->yes()) {
            return [];
        }

        if (! (new ObjectType(Criteria::class))->isSuperTypeOf($type)->yes()) {
            return [];
        }

        if (! (new ObjectType(TargetAwareCriteriaInterface::class))->isSuperTypeOf($type)->yes()) {
            return [];
        }

        $methodNameIdentifier = $node->name;
        if (! $methodNameIdentifier instanceof Node\Identifier) {
            return [];
        }

        $methodName = $methodNameIdentifier->toLowerString();
        if (! in_array($methodName, ['orderby'], true)) {
            return [];
        }

        $args = $node->getArgs();

        if (! isset($args[0])) {
            return [];
        }

        $argType = $scope->getType($args[0]->value);

        if ($argType->isConstantArray()->no()) {
            return [];
        }

        $argTypes = $argType->getConstantArrays();

        if (count($argTypes) === 0) {
            return [];
        }

        $fields = [];
        foreach ($argTypes as $argType) {
            foreach ($argType->getKeyTypes() as $keyType) {
                $keys = $keyType->getConstantStrings();
                if (count($keys) === 0) {
                    continue;
                }

                foreach ($keys as $key) {
                    $fields[] = $key->getValue();
                }
            }
        }

        $errors = [];
        foreach ($type->getObjectClassNames() as $criteriaClassName) {
            assert(class_exists($criteriaClassName));
            $errors = array_merge($errors, $this->validateFields($criteriaClassName, $fields));
        }

        return $errors;
    }
}
