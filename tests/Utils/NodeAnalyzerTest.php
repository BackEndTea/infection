<?php
/**
 * This code is licensed under the BSD 3-Clause License.
 *
 * Copyright (c) 2017-2019, Maks Rafalko
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

namespace Infection\Tests\Utils;

use Infection\Utils\NodeAnalyzer;
use PhpParser\Node;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class NodeAnalyzerTest extends TestCase
{
    /**
     * @dataProvider providesNodesAndNames
     */
    public function test_get_lower_cased_name(Node $node, string $name): void
    {
        $this->assertSame($name, NodeAnalyzer::getLowerCasedName($node));
    }

    public function providesNodesAndNames(): \Generator
    {
        yield 'It returns the correct name' => [
            new Node\Expr\FuncCall(new Node\Name('foo')),
            'foo',
        ];

        yield 'It returns the lowercased version of the name' => [
            new Node\Expr\FuncCall(new Node\Name('foo_BAR')),
            'foo_bar',
        ];

        yield 'It doesn\'t crash if the name isn\'t set' => [
            new Node\Expr\FuncCall(new Node\Scalar\String_('foo_bar')),
            '',
        ];

        yield 'It does not crash when the node does not have a name' => [
            new Node\Expr\BinaryOp\Plus(new Node\Scalar\LNumber(1), new Node\Scalar\LNumber(5)),
            '',
        ];
    }
}
