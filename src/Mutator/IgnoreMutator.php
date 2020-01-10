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

namespace Infection\Mutator;

use Generator;
use Infection\Mutator\Util\MutatorConfig;
use Infection\Visitor\ReflectionVisitor;
use PhpParser\Node;
use ReflectionClass;

/**
 * The mutators implement the ignore + canMutator pattern. The downside of this pattern is that
 * it makes its public API more complex and easier to mess up since the caller needs to be careful
 * of checking if he should mutate before attempting to mutate.
 *
 * A better alternative would be to allow to blindly mutate and do this "ignore + should mutate"
 * check internally. We however do not do so because before actually mutating, there is a few
 * expansive steps (e.g. retrieving the tests methods). Hence the currently chosen pattern allows
 * better performance optimization in our case.
 *
 * @internal
 */
final class IgnoreMutator
{
    private $config;
    private $mutator;

    public function __construct(MutatorConfig $config, Mutator $mutator)
    {
        $this->config = $config;
        $this->mutator = $mutator;
    }

    public function shouldMutate(Node $node): bool
    {
        if (!$this->mutator->canMutate($node)) {
            return false;
        }

        /** @var ReflectionClass|false $reflectionClass */
        $reflectionClass = $node->getAttribute(ReflectionVisitor::REFLECTION_CLASS_KEY, false);

        if ($reflectionClass === false) {
            return true;
        }

        return !$this->config->isIgnored(
            $reflectionClass->getName(),
            $node->getAttribute(ReflectionVisitor::FUNCTION_NAME, ''),
            $node->getLine()
        );
    }

    /**
     * @return Generator<Node|Node[]>
     */
    public function mutate(Node $node): Generator
    {
        return $this->mutator->mutate($node);
    }

    // TODO: this function is necessary for now mostly because Mutations requires Mutator which is
    //  expected to be the underlying mutator for now.
    //  It might be possible to remove this getter if it turns out that Mutation may not require
    //  the full Mutator object but just some elements of it.
    public function getMutator(): Mutator
    {
        return $this->mutator;
    }
}
