<?php

namespace App\Doctrine\MySQL;

class TimeToSec extends \Doctrine\ORM\Query\AST\Functions\FunctionNode
{
    public $timeExpression = null;

    /**
     * @param \Doctrine\ORM\Query\SqlWalker $sqlWalker
     *
     * @return string
     */
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return 'TIME_TO_SEC(' . $sqlWalker->walkArithmeticPrimary($this->timeExpression) . ')';
    }

    /**
     * @param \Doctrine\ORM\Query\Parser $parser
     *
     * @return void
     */
    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(\Doctrine\ORM\Query\Lexer::T_IDENTIFIER);
        $parser->match(\Doctrine\ORM\Query\Lexer::T_OPEN_PARENTHESIS);

        $this->timeExpression = $parser->ArithmeticPrimary();

        $parser->match(\Doctrine\ORM\Query\Lexer::T_CLOSE_PARENTHESIS);
    }

}