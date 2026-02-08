<?php

namespace Otobank\PHPStan\Doctrine\Rules;

use Doctrine\Common\Collections\Expr\Comparison;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ObjectType;

/**
 * ```
 *   TargetAwareCriteria::expr()->eq($field, $value); // Validate $field
 * ```
 *
 * @template-implements \PHPStan\Rules\Rule<MethodCall>
 */
class ValidateFieldComparisonCallRule implements \PHPStan\Rules\Rule
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
     *
     * @throws ShouldNotHappenException
     */
    public function processNode(Node $node, Scope $scope) : array
    {
        $type = $scope->getType($node);

        if (! $type->isObject()->yes()) {
            return [];
        }

        if (! (new ObjectType(Comparison::class))->isSuperTypeOf($type)->yes()) {
            return [];
        }

        $methodNameIdentifier = $node->name;
        if (! $methodNameIdentifier instanceof Node\Identifier) {
            return [];
        }

        $args = $node->getArgs();

        if (! isset($args[0])) {
            return [];
        }

        $argType = $scope->getType($args[0]->value);

        $constantStrings = $argType->getConstantStrings();

        if (! count($constantStrings) > 0) {
            return [];
        }

        $errors = [];
        foreach ($constantStrings as $constantString) {
            $field = $constantString->getValue();

            $criteriaClassNames = [];
            if (isset($node->var->class)) {
                $className = $scope->resolveName($node->var->class);
                assert(class_exists($className));
                $criteriaClassNames[] = $className;
            } elseif (isset($node->var->var)) {
                $varType = $scope->getType($node->var->var);
                foreach ($varType->getObjectClassNames() as $className) {
                    assert(class_exists($className));
                    $criteriaClassNames[] = $className;
                }
            } else {
                continue;
            }

            foreach ($criteriaClassNames as $criteriaClassName) {
                $errors = array_merge($errors, $this->validateFields($criteriaClassName, [$field]));
            }
        }

        return $errors;
    }
}
