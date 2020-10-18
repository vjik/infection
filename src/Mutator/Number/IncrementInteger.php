<?php
/**
 * This code is licensed under the BSD 3-Clause License.
 *
 * Copyright (c) 2017, Maks Rafalko
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * * Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 * * Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 * * Neither the name of the copyright holder nor the names of its
 *   contributors may be used to endorse or promote products derived from
 *   this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

declare(strict_types=1);

namespace Infection\Mutator\Number;

use Infection\Mutator\Definition;
use Infection\Mutator\GetMutatorName;
use Infection\Mutator\MutatorCategory;
use Infection\PhpParser\Visitor\ParentConnector;
use PhpParser\Node;

/**
 * @internal
 */
final class IncrementInteger extends AbstractNumberMutator
{
    use GetMutatorName;

    public static function getDefinition(): ?Definition
    {
        return new Definition(
            'Increments an integer value with 1.',
            MutatorCategory::ORTHOGONAL_REPLACEMENT,
            null
        );
    }

    /**
     * @param Node\Scalar\LNumber $node
     *
     * @return iterable<Node\Scalar\LNumber>
     */
    public function mutate(Node $node): iterable
    {
        yield new Node\Scalar\LNumber($node->value + 1);
    }

    public function canMutate(Node $node): bool
    {
        if (!$node instanceof Node\Scalar\LNumber || $node->value === 0) {
            return false;
        }

        if ($this->isPartOfSizeComparison($node)) {
            return false;
        }

        if ($this->isPregSplitArgument($node)) {
            return false;
        }

        return true;
    }

    private function isPregSplitArgument(Node $node): bool
    {
        $parentNode = ParentConnector::getParent($node);

        if (!$parentNode instanceof Node\Expr\UnaryMinus) {
            return false;
        }

        $parentNode = ParentConnector::getParent($parentNode);

        if (!$parentNode instanceof Node\Arg) {
            return false;
        }

        $parentNode = ParentConnector::getParent($parentNode);

        /** @var Node\Scalar\LNumber $node */
        if (
            $node->value === 1 &&
            $parentNode instanceof Node\Expr\FuncCall &&
            $parentNode->name instanceof Node\Name &&
            $parentNode->name->toLowerString() === 'preg_split'
        ) {
            return true;
        }

        return false;
    }
}
