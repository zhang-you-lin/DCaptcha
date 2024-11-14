<?php
/**
 * 验证码,
 *
 * @package captcha
 * @author youlin
 *
 */

define("DCaptcha_IMG_PATH", APP_PATH."/data/font/captchaimg/imgcode4/");

class DCaptcha_Img
{
    
    const IMG_PATH = DCaptcha_IMG_PATH;

    /**
     * 获取图片
     * 
     * 
     * @param string $keytype
     * @param int $codelength
     * @param int $width
     * @param int $height
     * @return string
     */
    public function getCodeImg($keytype, $codelength, $width, $height)
    {
        $scale = 1;
        $im = imagecreatetruecolor($width * $scale,$height * $scale);

        $bkcolor = array(255,255,255);
        $backcolor = imagecolorallocate($im, $bkcolor[0], $bkcolor[1],$bkcolor[2]);
        $rand = mt_rand(0,5);
        $color = Captcha_Api::$colors[$rand];
        $forecolor = imagecolorallocate($im, $color[0], $color[1], $color[2]);

        imagefilledrectangle($im, 0, 0, $width * $scale, $height * $scale, $backcolor);




        $codelength = Captcha_Api::getCodeLength($keytype);
        $text = '';

        $origin_string = "2345678ABCDEFGHJKLMNPQRSTUVWXYZabcdefhjkmnprstuvwxy";
        $len = strlen($origin_string);
        for ($i = 0; $i < 4; $i++)
        {
            $text .= $origin_string[mt_rand() % $len];
        }


        $this->writeCode($im, $text, $forecolor, $width, $height);
        //$this->WaveImage($im, $width, $height);


        $imResampled = imagecreatetruecolor($width, $height);
        imagecopyresampled($imResampled, $im,
        0, 0, 0, 0,
        $width, $height,
        $width * $scale, $height * $scale
        );
         

        imagedestroy($im);
        $im = $imResampled;

        /*
        $backfile = CAPTCHA_BACKGROUND_PATH . "bg" . mt_rand(1,6).".gif";
        $source = imagecreatefromgif( $backfile );
        imagecopymerge( $im, $source, 0, 0, 0, 0, $width,$height, 50 );
        */

        header("Content-type: image/gif");
        imagegif($im, null, 80);
        imagedestroy($im);
        return $text;
    }


    private function WaveImage($image, $width, $height)
    {
        $Yperiod    = 12;
        $Yamplitude = 9;
        $Xperiod    = 11;
        $Xamplitude = 5;

        $scale = 1;
        // X-axis wave generation
        $xp = $scale * $Xperiod;
        $k = rand(0, 80);
        for ($i = 0; $i < ($width * $scale); $i++)
        {
            imagecopy($image, $image,
            $i-1, sin($k+$i/$xp) * ($scale * $Xamplitude),
            $i, 0, 1, $height * $scale);
        }

        // Y-axis wave generation
        $k = rand(0, 80);
        $yp = $scale * $Yperiod;
        for ($i = 0; $i < ($height * $scale); $i++) {
            imagecopy($image, $image,
            sin($k + $i / $yp) * ($scale * $Yamplitude), $i - 1,
            0, $i, $width * $scale, 1);
        }
    }

    private function writeCode($image , $text, $forecolor,$width, $height)
    {
        /*
        $length = strlen($text);
        $length = 4;
        for ($i=0; $i<$length; $i++)
        {
            $letter = substr($text, $i, 1);
            $img1 = self::IMG_PATH.$letter."_c.png";
            $source = imagecreatefrompng( $img1 );
            imagecopymerge( $image, $source, 10, 40, 0, 0, 30, 30, 90 );
            
            break;
        }
        */
        
        //左右移动像素
        $yy = mt_rand(-5, 0);
        
        //上下移动像素
        $xx = mt_rand(-2, 2);
        
        
        
        $letter = substr($text, 1, 1);
        $source = self::getImgSource($letter, 'a');
        imagecopymerge( $image, $source, 8 + $xx, 3 + $yy, 0, 0, 65, 45, 90 );
        
        //第一个字放到第2个字的上面
        $letter = substr($text, 0, 1);
        $source = self::getImgSource($letter, 'c');
        imagecopymerge( $image, $source, 4, 25, 0, 0, 30, 30, 90 );
        
        $letter = substr($text, 2, 1);
        $source = self::getImgSource($letter, 'b');
        imagecopymerge( $image, $source, 63 + $xx, 5+$yy, 0, 0, 65, 45, 90 );
        
        $letter = substr($text, 3, 1);
        $source = self::getImgSource($letter, 'c');
        imagecopymerge( $image, $source, 110, 20, 0, 0, 30, 30, 90 );

    }
    
    static private function getImgSource($letter, $type)
    {
        $img1 = self::IMG_PATH.$letter."_".$type.".png";
        $source = imagecreatefrompng( $img1 );
        return $source;
    }
}


?>
