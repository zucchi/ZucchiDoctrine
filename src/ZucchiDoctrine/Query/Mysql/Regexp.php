<?php

namespace ZucchiDoctrine\Query\Mysql;

use Doctrine\ORM\Query\AST\Functions\FunctionNode,
    Doctrine\ORM\Query\Lexer;

class Regexp extends FunctionNode
{

    public $field;
    
    public $regex;

    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return $this->field->dispatch($sqlWalker) . ' REGEXP ' . $this->regex->dispatch($sqlWalker) ;
    }

    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER); // (2)
        $parser->match(Lexer::T_OPEN_PARENTHESIS); // (3)
        $this->field = $parser->StringPrimary(); // (4)
        $parser->match(Lexer::T_COMMA); // (5)
        $this->regex = $parser->StringPrimary(); // (6)
        $parser->match(Lexer::T_CLOSE_PARENTHESIS); // (3)
    }
}