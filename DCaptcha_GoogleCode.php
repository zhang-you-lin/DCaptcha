<?php
/**
 * 谷歌风格验证码
 *
 * @package captcha
 * @author youlin
 *
 */


class DCaptcha_GoogleCode
{

	function getGoogleCodeImg($keytype, $codelength, $width, $height)
	{
		$scale = 2;
		$im = imagecreatetruecolor($width * $scale,$height * $scale);

		$bkcolor = array(255,255,255);
		$backcolor = imagecolorallocate($im, $bkcolor[0],$bkcolor[1],$bkcolor[2]);
		$rand = mt_rand(0,5);
		$color = Captcha_Api::$colors[$rand];
		$forecolor = imagecolorallocate($im, $color[0], $color[1], $color[2]);

		imagefilledrectangle($im, 0, 0, $width*$scale, $height*$scale, $backcolor);
		
		
		// Foreground color
		$x = 0;
		$y = $height*$scale/2 + rand (-10,10);
		$r = 5;
		$len = rand($width *$scale* 0.6, $width*$scale * 0.8 );
		if ( $width < 80)
		{
			$len = 0;
		}


		$x = $width * $scale * 0.1;
		$y = $height * $scale/2 + rand (-10,10);
		$len = rand($width * $scale * 0.2, $width * $scale * 0.3 );
		if ( $width < 80)
		{
			$len = 0;
		}

		

		$codelength = Captcha_Api::getCodeLength($keytype);
		$text = '';
		
		$origin_string = "2345678ABCDEFGHJKLMNPQRSTUVWXYZabcdefhjkmnprstuvwxy";
		$len = strlen($origin_string);
		for ($i = 0; $i < $codelength; $i++)
		{
		    $text .= $origin_string[mt_rand() % $len];
		}
		/*
		for ($i = 0; $i< $codelength; $i++)
		{
			$text .= mt_rand(0,9);
		}
		*/

		
		$this->writeGoogleCode($im , $text, $forecolor, $width, $height);
		$this->WaveImage($im, $width, $height);

		
		$imResampled = imagecreatetruecolor($width, $height);
		imagecopyresampled($imResampled, $im,
		0, 0, 0, 0,
		$width, $height,
		$width * $scale, $height * $scale
		   );
		   

		imagedestroy($im);
		$im = $imResampled;
		
		$backfile = CAPTCHA_BACKGROUND_PATH . "bg" . mt_rand(1,6).".gif";
		$source = imagecreatefromgif( $backfile );
		imagecopymerge( $im, $source, 0, 0, 0, 0, $width,$height, 50 );

		header("Content-type: image/gif");
		imagegif($im, null, 80);
		imagedestroy($im);
		return $text;
	}
	
	
 	function WaveImage($image, $width, $height) 
	{
		$Yperiod    = 12;
		$Yamplitude = 9;
		$Xperiod    = 11;
		$Xamplitude = 5;
		$level = 3;
		$scale = 2;
        // X-axis wave generation
        $xp = $scale*$Xperiod;
        $k = rand(0, 100);
        for ($i = 0; $i < ($width*$scale); $i++) 
		{
            imagecopy($image, $image,
                $i-1, sin($k+$i/$xp) * ($scale*$Xamplitude),
                $i, 0, 1, $height*$scale);
        }

        // Y-axis wave generation
        $k = rand(0, 100);
        $yp = $scale*$Yperiod;
        for ($i = 0; $i < ($height*$scale); $i++) {
            imagecopy($image, $image,
                sin($k+$i/$yp) * ($scale*$Yamplitude), $i-1,
                0, $i, $width*$scale, 1);
        }
    }

	function writeGoogleCode($image , $text, $forecolor,$width, $height)
	{
		/** Increase font-size for shortest words: 9% for each glyp missing */
		
		$maxWordLength = 6;
		$scale = 2;
		$lettersMissing = $maxWordLength-strlen($text);
		$fontSizefactor = 1+($lettersMissing*0.2);
	
		//$text = 'YaVDSS';
		// Text generation (char by char)
		$x  = 10*$scale+mt_rand(3,10);
		$y  = round(($height*27/38)*$scale);
		$length = strlen($text);
		$rotates = array();
		
		$maxRotation = mt_rand(10, 50);
		if (mt_rand(0, 1))
		{
			$maxRotation = -$maxRotation;
		}
		
		
		for($i=0; $i<$length; $i++)
		{
			$degree   = rand(0, $maxRotation);
			$rotates[$i] = $degree;
		}
	
		$fontkey = array_rand(Captcha_Api::$fonts);
		$fontcfg  = Captcha_Api::$fonts[$fontkey];
		$fontfile = Captcha_Api::$fontpath.'/font/'.$fontcfg['font'];
		$fontsize = rand($fontcfg['minSize'], $fontcfg['maxSize'])*$scale*$fontSizefactor;
		$fontsize = $fontsize*$width/140;
		for ($i=0; $i<$length; $i++)
		{

			$letter   = substr($text, $i, 1);

			$degree = $rotates[$i];
			$ty = $y + mt_rand(3,7);
	
			// Full path of font file
			$coords = imagettftext($image, $fontsize, $degree,$x, $ty,$forecolor, $fontfile, $letter);
			$t = mt_rand(5,11);
			for ($it = 1; $it<= $t; $it++)
			{
				imagettftext($image, $fontsize, $degree, ($x+$it), ($ty+1),$forecolor, $fontfile, $letter);
			}
			
	
			$x += ($coords[2]-$x) + mt_rand(2,3);
	
		}
	
	}
}


?>
