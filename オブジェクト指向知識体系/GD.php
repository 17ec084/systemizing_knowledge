<?php

class GD
{
    private $image;
    private $contents_monitor;
    //イメージ内のオブジェクトを監視。
    //重なりの検知などに役立てる(まだ未実装)

    function __construct(int $width, int $height)
    {
        $image = imagecreatetruecolor($width, $height);//真っ黒なイメージの生成
        imagealphablending($image, false);//自動ブレンドを停止
        imagesavealpha($image, true);//αチャンネル情報を残して保存できるよう設定

        $back = imagecolorallocatealpha($image, 255, 255, 255, 127);//背景色設定
        //αチャンネルは0(不透明)～127(透明)

        imagefilledrectangle($image, 0, 0, $width - 1, $height - 1, $back);
        //背景色で背景を塗りつぶす


        $this->image = $image;
    }

    /**
     *
     * @param int $start_x 左端x座標
     * @param int $start_y 上端y座標
     * @param int $adjust_angle 重ならないように座標を修正する際の方向
     * @param string $class_name クラス名
     * @param array $propaty_names プロパティ名一覧
     * @param array $method_names メソッド名一覧
     * @return GD
     */
    function create_rectangle(int $start_x, int $start_y, int $adjust_angle, string $class_name, array $propaty_names, array $method_names):GD
    {

/*
        $image = $this->image;
        $back = imagecolorallocatealpha($image, 255, 255, 206, 0);
        $border = imagecolorallocatealpha($image, 33, 115, 70, 0);

        //クラス図の幅を決める
            //クラス名の長さ(全角→1, 半角0.5)を取得
            $class_name_len = 0;
            for($i = 0; $i < mb_strlen($class_name); $i++)
                $class_name_len += mb_strwidth(mb_substr($class_name, $i, 1));
            $class_name_len /= 2;

            //プロパティの最大長を取得
            $propaty_max_len = 0;
            foreach($propatis as $propaty)
            {
                $propaty_len = 0;
                for($i = 0; $i < mb_strlen($propaty); $i++)
                    $propaty_len += mb_strwidth(mb_substr($propaty, $i, 1));
                if($propaty_max_len < $propaty_len) $propaty_max_len = $propaty_len;
            }
            $propaty_max_len /= 2;

            //メソッドの最大長を取得
            $method_max_len = 0;
            foreach($methods as $method)
            {
                $method_len = 0;
                for($i = 0; $i < mb_strlen($method); $i++)
                    $method_len += mb_strwidth(mb_substr($method, $i, 1));
                    if($method_max_len < $method_len) $method_max_len = $method_len;
            }
            $method_max_len /= 2;

            $width = max([$class_name_len, $propaty_max_len, $method_max_len])*10 + 10;

        //クラス図のサイズ
        $end_x = $start_x + $width;
        $end_y = $start_y + 20;

        //クラス図の外枠
        imagefilledrectangle($image, $start_x, $start_y, $end_x, $end_y, $back);
        imagerectangle($image, $start_x, $start_y, $end_x, $end_y, $border);


        $font = realpath('meiryo.ttc');
        $text = $class_name;
        $textcolor = imagecolorallocate($image, 0, 0, 0);
        imagettftext
        ($image, /*font size as point = * /10*0.75, /*angle = * /0,
        $start_x+1+5, $start_y+(10-1)+5, $textcolor, $font, $text);
        //pt=px*0.75
        //xやyは「1行目のベースライン(≒左下)」
    */

        $this->image = (new Diagram_rectangle($this->image, $start_x, $start_y, $adjust_angle, $class_name, $propaty_names, $method_names))
        ->get();
        return $this;
    }


    /**
     * 呼び出し側のphpファイルをpng画像化します。
     * @deprecated 呼び出し側のphpファイルが、以前に何も出力していないことを確認してから呼び出してください。
     */
    function printpng():void
    {
        header("Content-type: image/png");
        imagepng($this->image);
    }



}

abstract class Diagram_content
{
    protected $coords;
    protected $histories_this = [];
    protected $image;

    /**
     * 平行移動
     * @param int $x
     * @param int $y
     */
    function move(int $x, int $y):self
    {
        for($i = 0; $i < count($this->coords); $i++)
        {
            $this->coords[$i][0] += $x;
            $this->coords[$i][1] += $y;
        }
        return $this;
    }

    /**
     * 座標情報を名前を付けて保存
     * @param string $save_as 名前
     */
    function save(string $save_as):self
    {
        $this->histories_this += array($save_as => [$this->coords, $this->image]);
        return $this;
    }

    /**
     * 保存した座標情報を復元。既存の座標情報は消える。
     * @param string $subject 名前
     */
    function load(string $subject):self
    {
        $this->coords = $this->histories_this[$subject][0];
        $this->image  = $this->histories_this[$subject][1];
        return $this;
    }

    /**
     * 保存した座標情報を削除
     * @param string $subject 名前
     */
    function clear(string $subject):self
    {
        unset($this->histories_coords[$subject]);
        return $this;
    }

    /**
     * resourceを取得
     * @return resource
     */
    function get()
    {
        return $this->image;
    }
}

class Diagram_arrow extends Diagram_content{}


class Diagram_rectangle extends Diagram_content
{
    private $class;
    private $propaties;
    private $methods;

    /**
     *
     * @param resource $image
     * @param int $start_x 左端x座標
     * @param int $start_y 上端y座標
     * @param int $adjust_angle 重ならないように座標を修正する際の方向
     * @param string $class_name クラス名
     * @param array $propaty_names プロパティ名一覧
     * @param array $method_names メソッド名一覧
     */
    function __construct($image ,int $start_x, int $start_y, int $adjust_angle, string $class_name, array $propaty_names, array $method_names)
    {
        $back = imagecolorallocatealpha($image, 255, 255, 206, 0);
        $border = imagecolorallocatealpha($image, 33, 115, 70, 0);

        $this->class = new Diagram_rectangle_class($class_name);
        $this->propaties = new Diagram_rectangle_propaties($propaty_names);
        $this->methods = new Diagram_rectangle_methods($method_names);

        //矩形の大きさを決める
        $arrlx2 = [$this->class->get_len_x2(), $this->propaties->get_len_x2(), $this->methods->get_len_x2()];
        $arrc   = [$this->class->get_cnt(), $this->propaties->get_cnt(), $this->methods->get_cnt()];
        $width =         max($arrlx2) *(10/2) + 10;
        $height = (array_sum($arrc)+2)*10     + 10;
        $end_x = $start_x + $width;
        $class_end_y = $start_y + ($this->class->get_cnt()+1)*10;
        $propaty_end_y = $class_end_y + (1 + $this->propaties->get_cnt())*10;
        $end_y = $start_y + $height;

        //矩形の外枠
        imagefilledrectangle($image, $start_x, $start_y, $end_x, $end_y, $back);
        imagerectangle($image, $start_x, $start_y, $end_x, $end_y, $border);

        //3つの矩形(クラス、プロパティ、メソッド)
        imagerectangle($image, $start_x, $start_y, $end_x, $class_end_y, $border);
        imagerectangle($image, $start_x, $class_end_y, $end_x, $propaty_end_y, $border);
        imagerectangle($image, $start_x, $propaty_end_y, $end_x, $end_y, $border);

        $font = realpath('NasuM-Regular-20200227.ttf');//http://itouhiro.hatenablog.com/entry/20140917/fontからダウンロード可。(Apache License 2.0)
        //$text = $class_name;
        $textcolor = imagecolorallocate($image, 0, 0, 0);
        $texts = array_merge([$this->class->get_text()], [""], $this->propaties->get_texts(), [""], $this->methods->get_texts());
        $i = 1;
        foreach($texts as $text)
        {
            imagettftext
            ($image, /*font size as point = */10*0.75, /*angle = */0,
            $start_x+1+5, $start_y+(10*$i-1)+5, $textcolor, $font, $text);
            //pt=px*0.75
            //xやyは「1行目のベースライン(≒左下)」
            $i += 1;
        }

        $this->image = $image;
        $this->save("default");
    }
}

interface DRComposer{function get_len_x2():int; function get_cnt():int; function get_texts():array;}

abstract class Diagram_rectangle_members implements DRComposer
{
    protected $names;

    function get_len_x2():int
    {
        $propaty_max_len = 0;
        foreach($this->names as $name)
        {
            $propaty_len = 0;
            for($i = 0; $i < mb_strlen($name); $i++)
                $propaty_len += mb_strwidth(mb_substr($name, $i, 1));
            if($propaty_max_len < $propaty_len) $propaty_max_len = $propaty_len;
        }
        return $propaty_max_len;
    }

    function get_cnt():int{return count($this->names);}

    function get_texts():array{return $this->names;}
}

class Diagram_rectangle_class extends Diagram_rectangle_members
{function __construct(string $name){ $this->names = array($name);}
 function get_text():string{return $this->names[0];}}
class Diagram_rectangle_propaties extends Diagram_rectangle_members{ function __construct(array $names){ $this->names = $names;}}
class Diagram_rectangle_methods extends Diagram_rectangle_members{ function __construct(array $names){ $this->names = $names;}}

/*
(new GD(300,300))
->create_rectangle(100, 100, 0, "テスト", ["あ","ああああA"], ["い","う","え"])
->printpng();
*/

//以下、GET送信(?member=class|propaty1,propaty2,...|method1,method2,...)でクラス図を作れるようにしたもの

$matches = []; class M extends Exception{}
try
{
    if(!preg_match("/^(.*)\|(.*)\|(.*)$/", $_GET['member'], $matches))
        throw new M();

    $class_name    = $matches[1];
    $propaty_names = explode(",", $matches[2]);
    $method_names  = explode(",", $matches[3]);

    (new GD(1000, 1000))
    ->create_rectangle(0, 0, 0, $class_name, $propaty_names, $method_names)
    ->printpng();
}catch(M $m)
{
    print "エラー: urlパラメータを「?member=class|propaty1,propaty2,...|method1,method2,...」の形式で与えてください。";
}
?>



