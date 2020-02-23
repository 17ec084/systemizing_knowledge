<?php

require 'XmlSheetTree.php';

new XmlSheet("class_diagram.xml");

/**
 * 各知識体系のクラス間の関係を表すxmlシートについてのクラス
 * @author <a href=http://github.com/17ec084>Tomotaka Hirata(17ec084)</a>
 */
class XmlSheet
{
    private $exception_raiser;
    private $xml_file_path;
    private $assoc_array;

    function __construct($xml_file_path)
    {
        $this->xml_file_path = $xml_file_path;

        $xml = new XMLReader();
        $xml->open($xml_file_path);

        $xml->setSchema("class_diagram.xsd");
        //以降、read時にxsdと照らし合わせ、違反があればwarningを発行する。

        $this->exception_raiser = new XmlExceptionRaiser($xml);

        $this->exception_raiser->raise_if_illegal();
        //readを1度のみ実行し、warningが出ればスキーマ違反として例外を投げる。

        $xml_tree = new XMLReaderSheetTree($xml);
        $this->assoc_array = $xml_tree->get_tree_same_addr()->get_as_assoc_array();
        //ここで、各クラスに1-1-2のようなidを振って連想配列へ
var_dump($this->assoc_array);
    }

    function get_xml_file_path(){return $this->xml_file_path;}
}

    /**
     * 本来はXmlSheetの内部privateクラスとして造りたかったクラス。<br>
     * 外部からの呼び出しは想定していない。<br>
     * warningなどで生じるエラーを例外に変換する処理が案外面倒なので、クラスにまとめた
     * @author <a href=http://github.com/17ec084>Tomotaka Hirata(17ec084)</a>
     *
     */
    class XmlExceptionRaiser
    {
        private $xml;
        function __construct(XMLReader $xml)
        {
            $this->xml = $xml;
        }

        function raise_if_illegal()
        {
            @$this->xml->read();//「@」にてwarningの表示を抑制

            $lastError = error_get_last();
            if ( !empty( $lastError ) )
                if(preg_match("/^XMLReader::read\(\).*$/", $lastError['message']))
                    throw new Exception("クラス関係記録シートがスキーマに違反しています。".$lastError['message'],  $lastError['type']);

        }


    }

    /**
     * 本来はXmlSheetの内部privateクラスとして造りたかったクラス。<br>
     * 外部からの呼び出しは想定していない。<br>
     * XMLReaderを操作してシートを連想配列にまとめる<br>
     * @author <a href=http://github.com/17ec084>Tomotaka Hirata(17ec084)</a>
     */
    class XMLReaderSheetTree
    {
        private $xml;
        private $sheet_tree;
        function __construct(XMLReader $xml)
        {
            $this->xml = $xml;
            $this->sheet_tree = new XmlSheetTree(null);

            while($xml->read())
            {
                $end_element_cnt = $this->run_to_next_class_element();
                //$xmlを次のclass要素まで進める。途中でend elementに出会った回数を返却
                if($end_element_cnt === null) break;//0==nullなので要注意。型を含めた比較が必要

                $next_generation = $end_element_cnt-1;
                //次のクラスは、現在のクラスの$next_generation世代だけ上である。

                $class_name = $this->get_attr_value_of_current_element("name");
                $this->sheet_tree->add($next_generation, $class_name);
                //ツリーの「最後」に追加する(XmlSheetTreeクラスのphpdoc参照のこと)

            }
        }


        function get_tree_deep_copy():XmlSheetTree{return $this->sheet_tree->get_deep_copy();}
        function get_tree_same_addr():XmlSheetTree{return $this->sheet_tree;}

        /*
         * 以下、privateメソッド。
         * 「汎用的なデータ構造を持つクラスを継承せずに利用する」場合に相当するとみなした
         */


        private function run_to_next_class_element() : ?int
        {
            $xml = $this->xml;
            $end_element_cnt = 0;
            $current_element_name = "";
            while(!(strcmp($current_element_name, "class")==0))
            {
                $is_exhausted = !($xml->read());
                if($xml->nodeType == XMLReader::END_ELEMENT)
                    $end_element_cnt++;
                if($xml->nodeType == XMLReader::ELEMENT)
                {
                    $current_element_name = $this->get_current_element_name();
                }
                if($is_exhausted)
                    return null;
            }
            return $end_element_cnt;
        }

        private function get_current_element_name()
        {
            $xml = $this->xml;
            if($xml->nodeType == XMLReader::ELEMENT)
                return (new SimpleXMLElement($xml->readOuterXML()))->getName();
            else
               throw new Exception("要素でないノードに対してget_current_element_name()を呼ぶことはできません");
        }

        private function get_attr_value_of_current_element($attr_name)
        {
            $xml = $this->xml;
            if($xml->nodeType == XMLReader::ELEMENT)
                return $xml->getAttribute($attr_name);
            else
               throw new Exception("要素でないノードに対してget_attr_value_of_current_element(\$str)を呼ぶことはできません");
        }

    }


/*以下はテストクラス*/

class XmlReaderSheetTreeTester
{
    function __construct()
    {
        $xml = new XMLReader();
        $xml->open("class_diagram.xml");

        $xml->setSchema("class_diagram.xsd");
        var_dump((new XMLReaderSheetTree($xml))->get_tree_same_addr()->get_as_assoc_array());
    }
}

class XmlSheetTester
{

}

new XmlSheetTester();

?>