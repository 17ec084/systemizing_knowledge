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

    function set_title(string $title):Canvas
    {
        $this->code =
        preg_replace
        (
            "/<!-- 17ec084 safe_title -->(?:.|[ \t\r\n])*<!-- 17ec084 end safe_title -->/U" ,
            "<!-- 17ec084 safe_title --><title>$title</title><!-- 17ec084 end safe_title -->" ,
            $this->code
        );
        //修飾子Uにより貪欲さ反転
        return $this;
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
     * @param {(border_color(枠線の色。RGB値。000000～FFFFFF))? border_width? (back_color)?} (任意)
     * @param {text(長方形に含める文字列を指定する) text_size text_color?}(任意)
     */
    function create_rectangle(array $AAA):Canvas
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
        $this
        ->add_script('ctx = document.getElementById("cv").getContext("2d");')
        ->add_script('ctx.beginPath();')
        ->add_script('ctx.moveTo('.$coords[0][$x].', '.$coords[0][$y].');')
        ->add_script('ctx.lineTo('.$coords[1][$x].', '.$coords[1][$y].');')
        ->add_script('ctx.lineTo('.$coords[2][$x].', '.$coords[2][$y].');')
        ->add_script('ctx.lineTo('.$coords[3][$x].', '.$coords[3][$y].');')
        ->add_script('ctx.closePath();');
        if($ar->g_has("border_color"))
            $this->add_script('ctx.strokeStyle = "#'.$ar->g("border_color").'";');
        if($ar->g_has("border_width"))
            $this->add_script('ctx.lineWidth = '.$ar->g("border_width").';');
        $this->add_script('ctx.stroke();');


        if($ar->g_has("back_color"))
            $this
            ->add_script('ctx.fillStyle = "#'.$ar->g("back_color").'";')
            ->add_script('ctx.fill();');
        if($ar->g_has("text"))
        {
            $this
            ->add_script('ctx = document.getElementById("cv").getContext("2d");')
            ->add_script('ctx.font = "'.$ar->g("text_size").'px \'ＭＳ ゴシック\'";')
            ->add_script('ctx.textAlign = "center";')
            ->add_script('ctx.textBaseline = "middle";');
            if($ar->g_has("text_color"))
                $this->add_script('ctx.fillStyle = "#'.$ar->g("text_color").'";');
            $this->add_script('ctx.fillText("'.$ar->g("text").'", '.(int)(($coords[0][$x]+$coords[2][$x])/2).', '.(int)(($coords[0][$y]+$coords[2][$y])/2).');');
        }

        return $this->add_script('ctx.restore();'."\n".'ctx.save()');//初期化忘れずに.また一度restoreするとsaveの内容が消えるので、すぐsaveも実行。
    }

    /**
     * 円の追加。
     * @param {center([x, y]) radius border_color? back_color? {text text_size text_color?}?}
     *
     */
    function create_circle(array $AAA):Canvas
    {
        $ar = new AAAReader($AAA);
        $this
        ->add_script('ctx = document.getElementById("cv").getContext("2d");')
        ->add_script('ctx.beginPath();')
        ->add_script('ctx.arc('.$ar->g("center")[0].', '.$ar->g("center")[1].', '.$ar->g("radius").', 0, 2*Math.PI, false);');
        if($ar->g_has("border_color"))
            $this->add_script('ctx.strokeStyle = "#'.$ar->g("border_color").'";');
        if($ar->g_has("border_width"))
            $this->add_script('ctx.lineWidth = '.$ar->g("border_width").';');
        $this->add_script('ctx.stroke();');
        if($ar->g_has("back_color"))
            $this
            ->add_script('ctx.fillStyle = "#'.$ar->g("back_color").'";')
            ->add_script('ctx.fill();');
        if($ar->g_has("text"))
        {
            $this
            ->add_script('ctx = document.getElementById("cv").getContext("2d");')
            ->add_script('ctx.font = "'.$ar->g("text_size").'px \'ＭＳ ゴシック\'";')
            ->add_script('ctx.textAlign = "center";')
            ->add_script('ctx.textBaseline = "middle";');
            if($ar->g_has("text_color"))
                $this->add_script('ctx.fillStyle = "#'.$ar->g("text_color").'";');
            $this->add_script('ctx.fillText("'.$ar->g("text").'", '.$ar->g("center")[0].', '.$ar->g("center")[1].');');
        }
        return $this->add_script('ctx.restore();'."\n".'ctx.save()');
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
            "<!-- 17ec084 safe_script --><script>
ctx = document.getElementById(\"cv\").getContext(\"2d\");
ctx.strokeStyle = \"#000000\";
ctx.save();
$this->script</script><!-- 17ec084 end safe_script -->" ,
            $this->code
        );
    }

}

//以下テストクラス

class CanvasTester
{
    function __construct()
    {
        $cv = new Canvas(5000, 5000);
        print $cv
        ->set_title("テスト")
        ->create_rectangle
        ([
            'center'=>[2500, 3000],
            'height'=>1000,
            'width'=>2000,
            'angle'=>1,
            'back_color'=>'ffffcc',
            'border_width'=> 100
        ])
        ->create_rectangle
        ([
            'center'=>[2500, 3000],
            'height'=>1000,
            'width'=>2000,
            'angle'=>45,
            'back_color'=>'ccffff',
            'border_color'=>'ff0000',
            'text'=>'17ec084',
            'text_size'=>100,
            'text_color'=>'ffccff'
        ])
        ->create_circle
        ([
            'center'=>[2000, 3500],
            'radius'=>500,
            'back_color'=>'aa0000',
            'text'=>'sample',
            'text_color'=>'ffffff',
            'text_size'=>150
        ])
        ->get_code();
        file_put_contents("test.html", $cv->get_code());
    }
}

new CanvasTester();


?>