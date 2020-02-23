<?php

require 'XmlSheet.php';

$_GET = array('update_diagrams' => true, 'myclass' => '決定性オートマトン');

if(isset($_GET['update_diagrams']))
    update_all_diagram($_GET['dir']);//未完成 マップデータを更新する。
if(isset($_GET['myclass']))
    Diagram($_GET['dir'], $_GET['myclass'])->make_jsfile();//未完成 クラス名_ancestorsmembers.jsを作る

/**
 * クラス図を表す。<br>
 * 各メソッドで、read/writeは定義ファイルへの読み書きを、realizeは実際の関係から読み取ることを意味する接頭辞である。
 * @author <a href=http://github.com/17ec084>Tomotaka Hirata(17ec084)</a>
 *
 */
class Diagram
{
    private $sheet;//クラス間の関係をメモしておくxmlシート
    private $class_str;
    private $dir;
    private $parents;

    function __construct($dir, $class_str)
    {
        $this->sheet = new XmlSheet("class_diagram.xml");//未完成
        $this->dir = $dir;
        $this->class_str = $class_str;
        $this->parents = read_parents();//未完成
    }

    function update_ancestors()//自分の先祖の情報を更新
    {
        $this->write_delete();//未完成
        update_ancestors($without_delete);
    }

    function update_ancestors($without_delete)
    {
        $this->write_parents($ancestors=$this->realize_parents());//未完成 親クラスをすべて取得
        foreach($ancestors as $ancestor)
            if(is_circular())//未完成。
                throw new Exception("循環継承が見つかりました。あるクラスの先祖が、同じクラスの子孫でもあります。");
            else
               (new Diagram($this->dir, $ancestor))->update_ancestors();
    }

    function realize_parents()
    {
        $path = $dir + "/" + $class_str + ".html";
        $parent_strs = document_getElementById_value("is-a", $path);//未完成
        $parent_strs = preg_replace('/(( |\\\t|\\\r|\\\n/)*,( |\\\t|\\\r|\\\n/)*)/', ',', $parent_strs);
        $parent_strs = explode(",", $parent_strs);

        $parents = [];
        foreach($parent_strs as $parent_str)
            array_push($parents, new Diagram($dir, $parent_str));
        return $parents;
    }

    function get_class_str(){return $this->class_str;}
    function get_parents(){return $this->parents;}
}


//子クラスを持たないすべてのクラスについて、先祖をたどることで、すべてのクラスを参照できる。
?>

<!--
クラス名_ancestorsmembers.jsは
getメソッドにて"fieldcsv"と"methodlink"をキーとして持つ連想配列を返却する。
値は、どちらも、document.getElementsByNameの配列でよい
-->