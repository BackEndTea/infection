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

namespace Infection\Visitor;

use Infection\Mutation;
use Infection\Mutator\Util\Mutator;
use Infection\TestFramework\Coverage\CodeCoverageData;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * @internal
 */
final class MutationsCollectorVisitor extends NodeVisitorAbstract
{
    /**
     * @var Mutator[]
     */
    private $mutators;

    /**
     * @var Mutation[]
     */
    private $mutations = [];

    /**
     * @var Node[]
     */
    private $fileAst;

    /**
     * @var CodeCoverageData
     */
    private $codeCoverageData;
    /**
     * @var bool
     */
    private $onlyCovered;

    public function __construct(
        array $mutators,
        array $fileAst,
        CodeCoverageData $codeCoverageData,
        bool $onlyCovered
    ) {
        $this->mutators = $mutators;
        $this->fileAst = $fileAst;
        $this->codeCoverageData = $codeCoverageData;
        $this->onlyCovered = $onlyCovered;
    }

    public function leaveNode(Node $node): ?Node
    {
        foreach ($this->mutators as $mutator) {
            if (!$mutator->shouldMutate($node)) {
                continue;
            }

            $isOnFunctionSignature = $node->getAttribute(ReflectionVisitor::IS_ON_FUNCTION_SIGNATURE, false);

            if (!$isOnFunctionSignature
                && !$node->getAttribute(ReflectionVisitor::IS_INSIDE_FUNCTION_KEY)
            ) {
                continue;
            }

            $path = $node->getAttribute(FilePathVisitor::FILE_PATH);
            if ($isOnFunctionSignature) {
                // hasExecutedMethodOnLine checks for all lines of a given method,
                // therefore it isn't making any sense to check any other line but first
                $isCoveredByTest = $this->codeCoverageData->hasExecutedMethodOnLine($path, $node->getLine());
                $linerange = $this->getNodeRange($node);
            } else {
                $outerMostArrayNode = $this->getOuterMostArrayNode($node);
                $isCoveredByTest = false;
                $linerange = $this->getNodeRange($outerMostArrayNode);

                foreach ($linerange as $line) {
                    if ($this->codeCoverageData->hasTestsOnLine($path, $line)) {
                        $isCoveredByTest = true;

                        break;
                    }
                }
            }

            if ($this->onlyCovered && !$isCoveredByTest) {
                continue;
            }

            $mutatedResult = $mutator->mutate($node);

            $mutatedNodes = $mutatedResult instanceof \Generator ? $mutatedResult : [$mutatedResult];

            foreach ($mutatedNodes as $mutationByMutatorIndex => $mutatedNode) {
                $this->mutations[] = new Mutation(
                    $node->getAttribute(FilePathVisitor::FILE_PATH),
                    $this->fileAst,
                    $mutator,
                    $node->getAttributes(),
                    \get_class($node),
                    $isOnFunctionSignature,
                    $isCoveredByTest,
                    $mutatedNode,
                    $mutationByMutatorIndex,
                    $linerange
                );
            }
        }

        return null;
    }

    /**
     * @return Mutation[]
     */
    public function getMutations(): array
    {
        return $this->mutations;
    }

    /**
     * If the node is part of an array, this will find the outermost array.
     * Otherwise this will return the node itself
     */
    private function getOuterMostArrayNode(Node $node): Node
    {
        $outerMostArrayParent = $node;

        do {
            if ($node instanceof Node\Expr\Array_) {
                $outerMostArrayParent = $node;
            }
        } while ($node = $node->getAttribute(ParentConnectorVisitor::PARENT_KEY));

        return $outerMostArrayParent;
    }

    /**
     * @return array|int[]
     */
    private function getNodeRange(Node $node): array
    {
        return range($node->getStartLine(), $node->getEndLine());
    }
}
