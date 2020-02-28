<?php
require 'AAAReader.php';
require 'Shape.php';
/**
 * html5のCanvasのクラス
 * @author <a href=http://github.com/17ec084>Tomotaka Hirata(17ec084)</a>
 *
 */
class Canvas
{
    private $width;
    private $height;
    private $code;
    private $script;

    function __construct(int $width, int $height)
    {
        $this->width = $width;
        $this->height = $height;
        $this->code =
        "<!DOCTYPE HTML>
        <html lang=\"ja\">
        <head>
        <!-- 17ec084 safe_title --><title></title><!-- 17ec084 end safe_title -->
        <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
        </head>
        <body>
        <canvas id=\"cv\" width=\"$width\" height=\"$height\" style=\"border: 1px solid #333333;\"></canvas>
        <!-- 17ec084 safe_script --><script></script><!-- 17ec084 end safe_script -->
        </body>
        </html>";
        $this->script = "";

    }

    function set_title(string $title):void
    {
        $this->code =
        preg_replace
        (
            "/<!-- 17ec084 safe_title -->(?:.|[ \t\r\n])*<!-- 17ec084 end safe_title -->/U" ,
            "<!-- 17ec084 safe_title --><title>$title</title><!-- 17ec084 end safe_title -->" ,
            $this->code
        );
        //修飾子Uにより貪欲さ反転
    }

    /**
     * javascriptによるcanvas操作を追加
     * @param string $str
     */
    function add_script(string $str):Canvas{$this->script = $this->script.$str."\n"; return $this;}

    /**
     * 長方形の追加。引数は連想配列として。<br>
     * (https://qiita.com/17ec084/items/559080ef25daed7730f1に寄せられたコメント)
     * @param {center(int配列。キー0がx座標、キー1がy座標) height(int) width(int) (angle=0.0)?}|{fourCoords} (必須) 座標を決定する
     * @param {border_color(枠線の色。RGBの順で配列に。0～FF) back_color} (任意) 未完成
     */
    function create_rectangle($AAA):Canvas
    {
        $ar = new AAAReader($AAA);
        $x = 0; $y = 1;

        if(!$ar->g_has("center") && !$ar->g_has("fourCoords"))
            new Exception();
        if($ar->g_has("center"))
        {
            $center = $ar->g("center");
            $center_x = $center[0];
            $center_y = $center[1];
            $height = $ar->g("height");
            $width = $ar->g("width");
            $angle = $ar->g_has("angle")?$ar->g("angle"):0.0;
            $rectangle = new Rectangle();
            $rectangle->set_CHWA($center_x, $center_y, $height, $width, $angle);
            $coords = $rectangle->get_coords();
        }
        else
       {
            $coords = $ar->g('coords');
        }
        return $this
        ->add_script('ctx = document.getElementById("cv").getContext("2d");')
        ->add_script('ctx.beginPath();')
        ->add_script('ctx.moveTo('.$coords[0][$x].', '.$coords[0][$y].');')
        ->add_script('ctx.lineTo('.$coords[1][$x].', '.$coords[1][$y].');')
        ->add_script('ctx.lineTo('.$coords[2][$x].', '.$coords[2][$y].');')
        ->add_script('ctx.lineTo('.$coords[3][$x].', '.$coords[3][$y].');')
        ->add_script('ctx.closePath();')
        ->add_script('ctx.stroke();');

    }

    /**
     * Canvasを描画するhtml5コードを文字列として返却する
     */
    function get_code():string
    {
        return
       preg_replace
        (
            "/<!-- 17ec084 safe_script -->(?:.|[ \t\r\n])*<!-- 17ec084 end safe_script -->/U" ,
            "<!-- 17ec084 safe_script --><script>$this->script</script><!-- 17ec084 end safe_script -->" ,
            $this->code
        );
    }

}

//以下テストクラス

class CanvasTester
{
    function __construct()
    {
        $cv = new Canvas(500, 500);
        $cv->set_title("テスト");
        $cv->create_rectangle(['center'=>[250, 300], 'height'=>'100', 'width'=>200]);
        //center(int配列。キー0がx座標、キー1がy座標) height(int) width(int) (angle=0.0)?}|{
        print $cv->get_code();
        file_put_contents("test.html", $cv->get_code());
    }
}

new CanvasTester();


?>