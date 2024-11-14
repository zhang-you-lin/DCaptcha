<?php
/**
 * 验证码,不能区分字块的
 *
 * @package captcha
 * @author youlin
 *
 */


class DCaptcha_WaveCode
{

	function getCodeImg($keytype, $codelength, $width, $height)
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
		
		$origin_string = "345678ABCDEFGHJKLMNPQRSTUVWXYZabcdefhjkmnpqrstuvwxy";
		$len = strlen($origin_string);
		for ($i = 0; $i< $codelength; $i++)
		{
			$text .= $origin_string[mt_rand() % $len];
		}

		$x = $width * $scale * 0.1;
		$y = $height * $scale/2 + rand (-10,10);
		$len = rand($width * $scale * 0.2, $width * $scale * 0.3 );
		if ( $width < 80)
		{
		    $len = 0;
		}
		for ($i = 0;$i < 155; $i++)
		{
		    $x1 = $x + 80;
		    $y1 = $y + rand(-18,18) + sin(rand(-3,3));
		
		    imagelinethick($im, $x, $y, $x1, $y1,$forecolor, rand(1,3));
		    imagelinethick($im, $x, $y, $x1+6, $y1,$forecolor, rand(1,2));
		    $x = $x1;
		    $y = $y1;
		}
		
		
		$this->writeCode($im, $text, $forecolor, $width, $height);
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

	function writeCode($image , $text, $forecolor,$width, $height)
	{
		/** Increase font-size for shortest words: 9% for each glyp missing */
		
		$maxWordLength = 6;
		$scale = 2;
		$lettersMissing = $maxWordLength-strlen($text);
		$fontSizefactor = 1+($lettersMissing*0.2);
	
		//$text = 'YaVDSS';
		// Text generation (char by char)
		$x  = 10*$scale+mt_rand(2,8);
		$y  = round(($height*27/38)*$scale);
		$length = strlen($text);
		$rotates = array();
		
		/*
		//往同一个方向倾斜时
		$maxRotation = mt_rand(10, 50);
		if (mt_rand(0, 1))
		{
			$maxRotation = -$maxRotation;
		}
		*/
		
		//往不同方向倾斜时
		$maxRotation = mt_rand(-40, 50);
		
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
		
		$randflag= rand(0, 1);
		for ($i=0; $i<$length; $i++)
		{
			if ($i%2 == $randflag)
			{
				$curfontsize = $fontsize * 1.1;
				$yfloat = mt_rand(-2,5);
			}
			else
			{
				$curfontsize = $fontsize * 0.85;
				$yfloat = mt_rand(8,12);
			}

			$letter   = substr($text, $i, 1);

			$degree = $rotates[$i];
			$ty = $y + $yfloat;
	
			// Full path of font file
			$coords = imagettftext($image, $curfontsize, $degree,$x, $ty,$forecolor, $fontfile, $letter);
			$t = mt_rand(0,5);
			for ($it = 1; $it<= $t; $it++)
			{
				imagettftext($image, $curfontsize, $degree, ($x+$it), $ty,$forecolor, $fontfile, $letter);
			}
			
	
			$x += ($coords[2]-$x) + mt_rand(2,3);
	
		}
	
	}
}


?>
