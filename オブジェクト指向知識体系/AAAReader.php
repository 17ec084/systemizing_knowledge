<?php
/**
 * https://qiita.com/17ec084/items/559080ef25daed7730f1に寄せられたコメントをみよ。<br>
 * AAAとはArgAssociativeArrayの略だ。
 *
 */
class AAAReader
{
    private $assoc;
    function __construct(array $AAA)
    {
        $this->assoc = $AAA;
    }

    function get():array{return $this->assoc;}
    function g(string $param){return $this->assoc[$param];}
    function has(string $param):bool{return isset($this->assoc[$param]);}
        function g_has(string $param):bool{return $this->has($param);}
}

?>