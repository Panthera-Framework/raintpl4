<?php
use PhpParser\Node;
class NodeVisitor_pseudoSandboxing extends PhpParser\NodeVisitorAbstract
{
    public $calledFunctions = array();

    /**
     * Iterate through nodes and collect all called function names
     *
     * @param Node $node
     * @author Damian Kęska <damian@pantheraframework.org>
     * @return string
     */
    public function leaveNode(Node $node)
    {
        $recognizedToken = null;

        if ($node instanceof PhpParser\Node\Expr\FuncCall)
            $recognizedToken = $node->name->parts[0];
        elseif ($node instanceof PhpParser\Node\Expr\Closure)
            $recognizedToken = '@closure';
        elseif ($node instanceof PhpParser\Node\Expr\Eval_)
            $recognizedToken = '@eval';
        elseif ($node instanceof PhpParser\Node\Expr\Include_)
            $recognizedToken = '@include';
        elseif ($node instanceof PhpParser\Node\Expr\ShellExec)
            $recognizedToken = 'shell_exec';

        if ($recognizedToken)
        {
            if (!isset($this->calledFunctions[$recognizedToken]))
                $this->calledFunctions[$recognizedToken] = 0;

            $this->calledFunctions[$recognizedToken]++;
        }

        return $recognizedToken;
    }
}