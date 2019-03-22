<?php
declare(strict_types=1);

namespace Infection\Tests\Fixtures;

use Infection\Mutator\Util\BaseMutator;
use PhpParser\Node;

class StubMutator extends BaseMutator
{
    public function mutate(Node $node)
    {
    }

    public function mutatesNode(Node $node): bool
    {
        return false;
    }
}
