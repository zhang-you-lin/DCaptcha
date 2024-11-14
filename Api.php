<?php
/**
 * 验证码相关接口的类
 *
 * @package captcha
 * @author youlin
 *
 */
define("FONT_FILE_HZ_CODE", APP_PATH."/data/font/codeword.txt");
define("CAPTCHA_BACKGROUND_PATH", APP_PATH."/data/font/captchaimg/");
define("FONT_PATH", APP_PATH."/data/");
class Captcha_Api {
    CONST HANZI_CODE = 1; //汉字
    //CONST ENGLISH_CODE = 2; //英文 太简单了，不用
    CONST WAVE_ENGLISH_CODE = 3; //扭曲英文  太简单了，不用
    CONST EXPRESSION = 4; //运算验证码
    CONST QQSTYLE_ENGLISH_CODE = 5; //新英文
    CONST GOOGLE_CODE = 7; //google风格验证码
    CONST WAVE_CODE = 8; //高扭曲验证码
    CONST IMG_CODE = 9; //自造图片数字验证码, 特殊验证码，不能改参数
    CONST ANSWER_CODE = 10; //答题验证码, 特殊验证码，不能改参数
    CONST SHADOW_CODE = 11; //阴影验证码
    CONST SMS_CODE = 12; //短信验证码


    //小尺寸的验证码只能用8
    private static $keytype_config = array(
        "login" => array('codetype'=>3, 'length'=>4, 'width'=>72, 'height'=>34, 'level' => 2),
        "reg" => array('codetype'=>3, 'length'=>4, 'width'=>72, 'height'=>34),
        "mobilereg" => array('codetype'=>12, 'length'=>4),
        "zhonghua" => array('codetype'=>8, 'length'=>4, 'width'=>70, 'height'=>25),
        
    );

    public static $fonts = array(
        //'Antykwa'  => array('spacing' => -3, 'minSize' => 26, 'maxSize' => 28, 'font' => 'AntykwaBold.ttf'),
        'CALIBRI'  => array('minSize' => 21, 'maxSize' => 23, 'font' => 'CALIBRI.TTF'),
        'georgiai' => array( 'minSize' =>19, 'maxSize' => 22, 'font' => 'georgiai.ttf'),
        'ANTQUAI' => array( 'minSize' => 20, 'maxSize' => 23, 'font' => 'ANTQUAI.TTF'),
        'BKANT' => array( 'minSize' => 18, 'maxSize' => 21, 'font' => 'BKANT.TTF'),
        'times' => array('minSize' => 21, 'maxSize' => 23, 'font' => 'times.ttf'),
        'arial' => array('minSize' => 21, 'maxSize' => 23, 'font' => 'arial.ttf'),
    );

    public static $hanzi_fonts = array(
        array('font' => '华文彩云.TTF'),
        array('font' => '华文琥珀.TTF'),
        array('font' => '华文隶书.TTF'),
        array('font' => '方正姚体.TTF'),
        array('font' => '华文行楷.TTF'),
    );


    public static $colors = array(
        array(27,78,181), // blue
        array(22,163,35), // green
        array(214,36,7),  // red
        array(65,10,225), //品蓝
        array(3,168,158), //锰蓝
        //array(255,99,71), //番茄红
    );
    public static $backcolors = array(
        array(255,245,238),//海贝壳色
        array(255,235,205),//亚麻色
        array(189,252,201),//白杏仁
        array(240,255,255),//天蓝色
        array(192,192,192),//灰色
        array(240,255,255)
    );
    public static $fontpath = FONT_PATH;


    //设置某个IP的验证码类型
    public static function setCodeTypeWithIp($keyType, $codeType, $IP = '')
    {
        $codeType = (int)$codeType;
        $IP = self::getPartyOfIP($IP, $keyType);
        $cache = Cache_Cache::getInstance(CACHE_TYPE);
        $cacheTime = 7200;
        if ($keyType == 'login')
        {
            $cacheTime = 600;
        }
        $codeType = $cache->set("rcode_".$keyType."_".$IP, $codeType, $cacheTime);

        ////global $multilog;
        ////$multilog->addSysDebugLog("captcha_debug:setcodetype:rcode_".$keytype."_".$ip . " | " . $codetype);
    }

    public static function setCodeTypeWithCookie($keytype, $codetype)
    {
        $rand = mt_rand(1, 9999999).mt_rand(1, 9999999);
        $cache = Cache_Cache::getInstance(CACHE_TYPE);
        $cacheTime = 600;
        $codetype = $cache->set("rcode_".$keytype."_".$rand, $codetype, $cacheTime);
        setcookie("cpt", $rand, time() + $cacheTime, "/", COMMON_HOST);
    }

    public static function setCodeLengthWithIp($keytype, $length, $ip = '')
    {
        $length = (int)$length;
        $ip = self::getPartyOfIP($ip, $keytype);
        $cache = Cache_Cache::getInstance(CACHE_TYPE);
        $cacheTime = 7200;
        if ($keytype == 'login')
        {
            $cacheTime = 600;
        }
        $codetype = $cache->set("rcode_".$keytype."_".$ip."_length", $length, $cacheTime);
    }

    public static function setCodeLimitWithUID($keytype, $uid = '')
    {
    	return;
        if (empty($uid))
        {
            $uid = BaseModel::getInstance('Page_PublicModel')->getLogin();
        }
        $uid = (int)$uid;
        if (empty($uid) or empty($keytype))
        {
            return false;
        }
        $cache = Cache_Cache::getInstance(CACHE_TYPE);
        $cacheTime = 600;
        $key = "rcode_".$keytype."_er_".$uid."_".date('Ymd');
        $count = $cache->incrementEx($key, 1, $cacheTime);
    }

    public static function checkCodeLimitWithUID($keytype, $uid = '')
    {
        if (empty($uid))
        {
            $uid = BaseModel::getInstance('Page_PublicModel')->getLogin();
        }
        $uid = (int)$uid;
        if (empty($uid) or empty($keytype))
        {
            return 0;
        }
        $cache = Cache_Cache::getInstance(CACHE_TYPE);
        $count = $cache->get("rcode_".$keytype."_er_".$uid."_".date('Ymd'));
        return $count;
    }

    public static function setCaptchaCode($key, $code, $keytype = "common", $flag = 1, $ip = null)
    {
        $cache = Cache_Cache::getInstance(CACHE_TYPE);
        if(!$ip)
        {
            $ip = get_client_ip();
        }
        //记录用户请求验证码的IP
        $cache->set($keytype."_".$key."_ip", $ip, 600);
        $count = $cache->incrementEx($keytype."_cpt_".$ip, 1, 3600);

        if ($flag == 1 and $keytype != 'wapauto') //这可能是自造验证码
        {
            //global $multilog;
            $refer = $_SERVER['HTTP_REFERER'];
            //$multilog->addSysDebugLog("captcha_debug_zizao2:".$keytype."_".$key."_".$code."_" .$ip."_" .$refer."_". $_SERVER['REQUEST_URI']);
        }

        if ($count > 500 and ($keytype == 'wapreg' or $keytype == 'waplogin'))//每小时只允许请求500次
        {
            $code = mt_rand(1, 9999);
            //global $multilog;
            $refer = $_SERVER['HTTP_REFERER'];
            ////$multilog->addSysDebugLog("captcha_debug_iplimit:".$keytype."_".$key."_".$code."_" .$ip."_" .$refer);
        }
        $key = strtoupper($key);
        $ret = $cache->set($keytype."_".$key, strtoupper($code), 600);
        if ($ret != 1)
        {
            
            ////global $multilog;
            $ip = get_client_ip();
            ////$multilog->addSysDebugLog("captcha_debug_error:".$keytype."_".$key."_".$code."_".$ip."_" .$key);
        }
    }

    public static function setSCode($key, $code, $keytype = "common")
    {
        $cache = Cache_Cache::getInstance(CACHE_TYPE);
        $cache->set($keytype."_scode_".$key, strtoupper($code), 600);
    }

    public static function getSCode($key, $keytype = "common")
    {
        $cache = Cache_Cache::getInstance(CACHE_TYPE);
        return $cache->get($keytype."_scode_".$key);
    }

    private static function updateCaptchaScore($flag, $uid = '')
    {
    	return ;
        if (empty($uid))
        {
            $uid = BaseModel::getInstance('Page_PublicModel')->getLogin();
        }
        if (empty($uid))
        {
            return;
        }
        $ip = get_client_ip();
        $ident = DUser_Identity::getIdentity($uid, $ip);
        $payload = array('success' => $flag);
        $flag = DCore_StreamFilter::CheckPoint("common.checkCaptcha", $ident, $payload);
    }

    /**
     * 验证码验证后的回调动作，应用场景：登录时，输入验证码正确给用户加分，但此时还没登录完成，取不到用户UID。需要在登录动作完成后调用此方法通知某用户验证成功，目前只登录用
     *
     * @param keytype string 验证码业务类型
     * @param uid int 当前用户UID
     * @param bool 是否登录成功
     *
     */

    public static function checkMemcacheCodeCallBack($keytype, $uid, $rightflag)
    {
        if ($keytype == 'login')
        {
            self::updateCaptchaScore($rightflag, $uid);
        }
    }
    
    /**
     * 验证码检查完之后执行，用于统计等
     * 
     * @param unknown $keytype
     * @param unknown $key
     * @param unknown $rightflag
     */
    public static function aftercheckMemcacheCode($keytype, $key, $rightflag, $exinfo = '',$ip = '')
    {
    	return ;
        self::updateCaptchaScore($rightflag);
        
        if (empty($ip))
        {
            $ip = get_client_ip();
        }
        $_uid = BaseModel::getInstance('Page_PublicModel')->getLogin();
        if (empty($_uid))
        {
            $_uid = $_COOKIE['_uid'];
        }
        
        $cache = Cache_Cache::getInstance(CACHE_TYPE);
        /*
        $monitorArr = array(
            'codetype' => $codetype,
            'ip' => $ip,
            'gettime' => time(),
        );
        */
        $monitorArr = unserialize($cache->get($keytype."_codemonitor_".$key));
        $spantime = time() - $monitorArr['gettime'];
        $codetype = $monitorArr['codetype'];
         
    }

    public static function checkCaptchaCode($key, $code, $keytype = "common", $del = true, $exinfo ='', $ip = '', $uid = '')
    {
        if (empty($key) or empty($code))
        {
            self::aftercheckMemcacheCode($keytype, $key, 0, 'emptycode_'.$uid,$ip);
            return false;
        }


        //用于判断10分钟内错误超过多少次，就不让通过,防止大量刷垃圾
        if (empty($uid))
        {
            $uid = BaseModel::getInstance('Page_PublicModel')->getLogin();
        }
        if ($uid)
        {
            $errorcount = self::checkCodeLimitWithUID($keytype, $uid);
            if ($errorcount > 20)
            {
                self::aftercheckMemcacheCode($keytype, $key, 0, $errorcount.'_errorcount_'.$uid,$ip);
                return false;
            }
        }


        

        $cache = Cache_Cache::getInstance(CACHE_TYPE);
        $key = strtoupper($key);
        $memcode = $cache->get($keytype."_".$key);
         
        if (is_numeric($code))
        {
            $code = abs($code);
        }
        if (is_numeric($memcode))
        {
            $memcode = abs($memcode);
        }

        //验证后删除验证码
        if ($del)
        {
            $cache->rm($keytype."_".$key);
             
        }
        
      //var_dump($memcode);
      //var_dump($code);
      //exit;

        if (strlen($memcode) and strtoupper($memcode) == strtoupper($code))
        {
            //self::aftercheckMemcacheCode($keytype, $key, 1, 'ok_'.$uid,$ip);
            return true;
        }

        //self::setCodeLimitWithUID($keytype, $uid);

       // self::aftercheckMemcacheCode($keytype, $key, 0, $memcode.'_'.$code.'_wrong_'.$uid,$ip);
        return false;
    }

    public static function writeTextImg($text, $ip = null)
    {
        $height = 30;

        $textarr = CStr::str2arr($text);
        $fontsize = 14;
        $space = 4;
        $codelength = count($textarr);
        $width = $codelength * ($fontsize + $space*2);
        $x = intval($width / $codelength);

        $fontfile = self::$fontpath.'/font/times.ttf';
        $hzfontfile = self::$fontpath.'/font/华文行楷.TTF';

        $im = imagecreatetruecolor($width, $height);
        for ($i=0; $i<10; $i++)
        {
            imageString($im, 1,  mt_rand(1, $width), mt_rand(1, $height), ".",imageColorAllocate($im, mt_rand(200,255), mt_rand(200,255), mt_rand(200,255)));
        }

        $bkcolor = array(255,255,255);
        $backcolor = imagecolorallocate($im, $bkcolor[0],$bkcolor[1],$bkcolor[2]);
        $color = array(255,102,0);
        $forecolor = imagecolorallocate($im, $color[0], $color[1], $color[2]);
        imagefilledrectangle($im, 0, 0, $width, $height, $backcolor);
        $i = 0;
        foreach ($textarr as $char)
        {
            if (strlen($char) > 1)
            {
                $char = iconv(DB_CHARSET, SYS_CHARSET, $char);
                $tempfont = $hzfontfile;
                $tmpspace = $space * 2;
            }
            else
            {
                $tempfont = $fontfile;
                $tmpspace = $space;
            }
            	
            $bkcolor = Captcha_Api::$backcolors[mt_rand(0,sizeof(Captcha_Api::$backcolors)-1)];
            $backcolor = imagecolorallocate($im, $bkcolor[0],$bkcolor[1],$bkcolor[2]);
            $color = Captcha_Api::$colors[mt_rand(0, sizeof(Captcha_Api::$colors)-1)];
            $tmpforecolor = imagecolorallocate($im, $color[0], $color[1], $color[2]);

            imagettftext($im, $fontsize, mt_rand(-25,25), $tmpspace + $i * $x + mt_rand(0, 5), mt_rand($height / 2, $height - 2),$tmpforecolor, $tempfont, $char);
            $i++;
        }


        header("Content-type: image/gif");
        imagegif($im, null, 80);
        imagedestroy($im);
    }

    public static function getCaptchaImg($keytype, $img_width = 140, $img_height = 50, $key = '')
    {
        if (empty($keytype))
        {
            throw new Exception('no keytype');
        }

        

        $img_width = self::$keytype_config[$keytype] && self::$keytype_config[$keytype]['width'] ? self::$keytype_config[$keytype]['width'] : 140;
        $img_height = self::$keytype_config[$keytype] && self::$keytype_config[$keytype]['height'] ? self::$keytype_config[$keytype]['height'] : 50;
        $codetype = self::getCodeType($keytype);
        $codelength = self::getCodeLength($keytype);
        $authcode_array = self::getString($codelength, $codetype);
        $fontfile = self::getFontFile($codetype);

        $ip = get_client_ip();
        $_uid =  BaseModel::getInstance('Page_PublicModel')->getLogin();
        if (empty($_uid))
        {
            $_uid = $_COOKIE['_uid'];
        }
        
         
        $cache = Cache_Cache::getInstance(CACHE_TYPE);
       
          if ($codetype == self::WAVE_ENGLISH_CODE)
        {
            return self::createWaveEnglishImg($keytype, $codelength, $img_width, $img_height);
        }
        else if ($codetype == self::QQSTYLE_ENGLISH_CODE)
        {
            return self::createQQEnglishImg($keytype, $codelength, $img_width, $img_height);
        }
        else if($codetype == self::EXPRESSION)
        {
            return self::getEqualImg($keytype, $img_width, $img_height);
        } else if($codetype == self::GOOGLE_CODE)
        {
        	include_once('DCaptcha_GoogleCode.php');
            $c = new DCaptcha_GoogleCode();
            return $c->getGoogleCodeImg($keytype, $codelength, $img_width, $img_height);
        }
        else if($codetype == self::WAVE_CODE)
        {
        	include_once('DCaptcha_WaveCode.php');
            $c = new DCaptcha_WaveCode();
            return $c->getCodeImg($keytype, $codelength, $img_width, $img_height);
        } 
        
        else if($codetype == self::ANSWER_CODE)
        {
        	include_once('DCaptcha_Answer.php');
            $c = new DCaptcha_Answer();
            return $c->getCodeImg($keytype, $codelength, $img_width, $img_height);
        }
        else if($codetype == self::IMG_CODE)
        {
        	include_once('DCaptcha_Img.php');
            $c = new DCaptcha_Img();
            return $c->getCodeImg($keytype, $codelength, $img_width, $img_height);
        }
        else if($codetype == self::SHADOW_CODE)
        {
        	include_once('DCaptcha_Shadow.php');
            $c = new DCaptcha_Shadow();
            return $c->getCodeImg($keytype, $codelength, $img_width, $img_height);
        }
    }

    private static function createWaveEnglishImg($keytype, $codelength, $width, $height)
    {
        $maxRotation = 16;
        $maxWordLength = 8;
        $scale = 2;
        $im = imagecreatetruecolor($width * $scale,$height * $scale);
        /** Wave configuracion in X and Y axes */
        $Yperiod= 12;
        $Yamplitude = 9;
        $Xperiod= 11;
        $Xamplitude = 5;


        $bkcolor = Captcha_Api::$backcolors[mt_rand(0,sizeof(Captcha_Api::$backcolors)-1)];
        $backcolor = imagecolorallocate($im, $bkcolor[0],$bkcolor[1],$bkcolor[2]);
        $color = Captcha_Api::$colors[mt_rand(0, sizeof(Captcha_Api::$colors)-1)];
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
        for ($i=0;$i< $len/5; $i++)
        {
            $x1 = $x + 8;
            $y1 = $y + rand(-12,12) + sin(rand(-1,1));

            if($y1 > 90)
            {
                $y1 -= 30;
            }
            if($y1 < 20)
            {
                $y1 += 30;
            }

            //imagelinethick($im, $x, $y, $x1, $y1, $forecolor,rand(2,4));
            $x = $x1;
            $y = $y1;

        }

        $x = $width * $scale * 0.65;
        $y = $height * $scale/2 + rand (-10,10);
        $len = rand($width * $scale * 0.2, $width * $scale * 0.3 );
        if ( $width < 80)
        {
            $len = 0;
        }
        for ($i = 0;$i < $len/16; $i++)
        {
            $x1 = $x + 16;
            $y1 = $y + rand(-8,8) + sin(rand(-1,1));

            imagelinethick($im, $x, $y, $x1, $y1,$forecolor, rand(2,4));
            $x = $x1;
            $y = $y1;
        }


        $text = GetRandomCaptchaText($codelength);

        $fontkey = array_rand(Captcha_Api::$fonts);
        $fontcfg  = Captcha_Api::$fonts[$fontkey];
        WriteEnhanceText($im , $text, $forecolor, $width, $height);
        //WaveImage($im, $width, $height);

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
        imagecopymerge( $im, $source, 0, 0, 0, 0, $width,50, 50 );

        header("Content-type: image/gif");
        imagegif($im, null, 80);
        imagedestroy($im);
        return $text;
    }


    private static function createQQEnglishImg($keytype, $codelength, $width, $height)
    {
        $maxRotation = 16;
        $maxWordLength = 8;
        $scale = 2;
        $im = imagecreatetruecolor($width * $scale,$height * $scale);
        /** Wave configuracion in X and Y axes */
        $Yperiod= 12;
        $Yamplitude = 9;
        $Xperiod= 11;
        $Xamplitude = 5;

        $bkcolor = array(255,255,255);
        $backcolor = imagecolorallocate($im, $bkcolor[0],$bkcolor[1],$bkcolor[2]);
        $color = array(0,0,0);
        $forecolor = imagecolorallocate($im, $color[0], $color[1], $color[2]);
        
        //统一风格颜色
        $color = Captcha_Api::$colors[mt_rand(0, sizeof(Captcha_Api::$colors)-1)];
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
        for ($i=0;$i< $len/5; $i++)
        {
            $x1 = $x + 8;
            $y1 = $y + rand(-12,12) + sin(rand(-1,1));

            if($y1 > 90)
            {
                $y1 -= 30;
            }
            if($y1 < 20)
            {
                $y1 += 30;
            }

            imagelinethick($im, $x, $y, $x1, $y1, $forecolor,rand(2,4));
            $x = $x1;
            $y = $y1;

        }

        $x = $width * $scale * 0.1;
        $y = $height * $scale/2 + rand (-10,10);
        $len = rand($width * $scale * 0.2, $width * $scale * 0.3 );
        if ( $width < 80)
        {
            $len = 0;
        }
        for ($i = 0;$i < 55; $i++)
        {
            $x1 = $x + 50;
            $y1 = $y + rand(-18,18) + sin(rand(-3,3));

            imagelinethick($im, $x, $y, $x1, $y1,$forecolor, rand(1,3));
            imagelinethick($im, $x, $y, $x1+6, $y1,$forecolor, rand(1,2));
            $x = $x1;
            $y = $y1;
        }



        $text = GetQQRandomCaptchaText($codelength);
        WriteQQText($im , $text, $forecolor, $width, $height);
        WaveImage($im, $width, $height);





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
        imagecopymerge( $im, $source, 0, 0, 0, 0, 150,50, 50 );


        header("Content-type: image/gif");
        imagegif($im, null, 80);
        imagedestroy($im);
        return $text;
    }

    private static function getPartyOfIP($ip, $keytype)
    {
        $ip = $ip ? $ip : get_client_ip();
        $ipparty = self::$keytype_config[$keytype]['ipparty'];
        $ipparty = (int)$ipparty;
        if ($ipparty > 0 and $ipparty < 4)
        {
            $ipArr=  explode('.', $ip);
            for($i = 0; $i < $ipparty; $i++)
            {
                $newip .= $ipArr[$i].".";
            }
            $ip =  trim($newip, ".");
        }
        return $ip;
    }

    private static function getFontsize($keytype)
    {
        if (self::$keytype_config[$keytype])
        {
            $len = self::$keytype_config[$keytype]['length'];
            $w = self::$keytype_config[$keytype]['width'];
            $h = self::$keytype_config[$keytype]['height'];
            if ($len && $w && $h)
            {
                // px/fontsize = 4/3
                $maxw = intval($w * 3 / 5 / $len);
                $maxh = intval($h * 3 / 5);
                return min($maxw, $maxh);
            }
        }

        return mt_rand(12, 16);
    }

    public static function getCodeType($keytype)
    {
        $ip = get_client_ip();
        $ip = self::getPartyOfIP($ip, $keytype);
        $cache = Cache_Cache::getInstance(CACHE_TYPE);
        $codetype = $cache->get("rcode_".$keytype."_".$ip);
        if ($codetype)
        {
            return $codetype;//
        }
        
        if (empty($codetype))
        {
            self::$keytype_config[$keytype] && ($codetype = self::$keytype_config[$keytype]['codetype']);
        }

        return $codetype;
    }

    static function getCodeLength($keytype)
    {
        $ip = get_client_ip();
        $ip = self::getPartyOfIP($ip, $keytype);
        $cache = Cache_Cache::getInstance(CACHE_TYPE);
        $codelength = $cache->get("rcode_".$keytype."_".$ip."_length");
        if (empty($codelength))
        {
            self::$keytype_config[$keytype] && ($codelength = self::$keytype_config[$keytype]['length']);
        }
        if (empty($codelength))
        {
            $codelength = 4;
        }
        ////global $multilog;
        ////$multilog->addSysDebugLog("captcha_debug:getcodelength:rcode_".$keytype."_".$ip."_length | " . $codelength);
        return $codelength;
    }

    private static function getCodeLevel($keytype)
    {
        $level = self::$keytype_config[$keytype]['level'];
        if (empty($level))
        {
            $level = 1;
        }
        return $level;
    }


    private static function getFontFile($codetype)
    {
        if (self::HANZI_CODE == $codetype)
        {
            //return DATA_PATH."/font/simsun.ttc";
            $rand = mt_rand(0, 4);
            return APP_PATH."/data/font/".self::$hanzi_fonts[$rand]['font'];
        }
        else
        {
            return APP_PATH."/data/font/georgiai.ttf";
        }
    }

    private static function getString($codelength, $codetype)
    {
        if (self::HANZI_CODE == $codetype)
        {
            $hz_array = file(FONT_FILE_HZ_CODE);
            $len = count($hz_array);
            for ($i=0;$i< $codelength; $i++)
            {
                $word = $hz_array[mt_rand() % $len];
                $word = trim($word);
                if (strlen($word) == 4)
                {
                    $authcode_array[$i] = substr($word, 0, 2);
                    $i++;
                    $authcode_array[$i] = substr($word, 2, 2);
                }
                else if (strlen($word) == 8)
                {
                    $authcode_array[$i] = substr($word, 0, 2);
                    $i++;
                    $authcode_array[$i] = substr($word, 2, 2);
                    $i++;
                    $authcode_array[$i] = substr($word, 4, 2);
                    $i++;
                    $authcode_array[$i] = substr($word, 6, 2);
                }
                else
                {
                    $i--;
                }
            }
        }
        else
        {
            $origin_string = "2345678ABCDEFGHJKLMNPQRSTUVWXYZabcdefhjkmnpqrstuvwxyz";
            $len = strlen($origin_string);
            for ($i=0;$i< $codelength; $i++)
            {
                $authcode_array[$i] = $origin_string[mt_rand() % $len];
            }
        }
        $authcode_array = array_slice($authcode_array, 0, $codelength);
        return $authcode_array;
    }


    public static function getEqualImg($keytype, $width, $height)
    {
        $scale = 2;
        $level = self::getCodeLevel($keytype);
        if ($level <= 1)
        {
            $op = array('+', '-', '*');
            $length = 2;
        }
        else
        {
            $op = array('+', '-', '*');
            $length = mt_rand(2,3);
            if ($length == 3)
            {
                $op = array('+', '-', '+');
            }
        }

        $a = mt_rand(1,9);
        $b = mt_rand(1,9);
        $c = mt_rand(1,9);

        $op1 = mt_rand(0,2);
        if ($op[$op1] == '-')
        {
            $a = mt_rand(10,30);
        }
        $length = 2; //暂时只用2个数运算
        if($length == 2)
        {
            	
            $expr = $a.$op[$op1].$b;
            $exprArr = array($a, $op[$op1], $b);
        }
        else if($length == 3)
        {
            $op2 = mt_rand(0,2);
            if ($op[$op1] == '-' and $op[$op2] == '-')
            {
                $op[$op2] = '+';
            }
            $expr = $a.$op[$op1].$b.$op[$op2].$c;
            $exprArr = array($a, $op[$op1], $b, $op[$op2], $c);
        }
        eval('$result='.$expr.';');
        $img = imagecreate($width * $scale, $height*$scale);
        ImageColorAllocate($img,255,255,255);
        WriteEnhanceEqual($img , $exprArr, $width, $height);
        WaveImage($img, $width, $height);
        $imResampled = imagecreatetruecolor($width, $height);
        imagecopyresampled($imResampled, $img,
        0, 0, 0, 0,
        $width, $height,
        $width * $scale, $height * $scale
        );

        imagedestroy($img);
        $img = $imResampled;

        $backfile = CAPTCHA_BACKGROUND_PATH . "bg" . mt_rand(1,6).".gif";
        $source = imagecreatefromgif( $backfile );
        imagecopymerge( $img, $source, 0, 0, 0, 0, $width,$height, 50 );


        Header("Content-Type: image/gif");
        imagegif($img);
        imagedestroy($img);
        return $result;
    }

}




function imagelinethick($image, $x1, $y1, $x2, $y2, $color, $thick = 1)
{
    /* this way it works well only for orthogonal lines
     imagesetthickness($image, $thick);
    return imageline($image, $x1, $y1, $x2, $y2, $color);
    */
    if ($thick == 1) {
        return imageline($image, $x1, $y1, $x2, $y2, $color);
    }
    $t = $thick / 2 - 0.5;
    if ($x1 == $x2 || $y1 == $y2) {
        return imagefilledrectangle($image, round(min($x1, $x2) - $t), round(min($y1, $y2) - $t), round(max($x1, $x2) + $t), round(max($y1, $y2) + $t), $color);
    }
    $k = ($y2 - $y1) / ($x2 - $x1); //y = kx + q
    $a = $t / sqrt(1 + pow($k, 2));
    $points = array(
        round($x1 - (1+$k)*$a), round($y1 + (1-$k)*$a),
        round($x1 - (1-$k)*$a), round($y1 - (1+$k)*$a),
        round($x2 + (1+$k)*$a), round($y2 - (1-$k)*$a),
        round($x2 + (1-$k)*$a), round($y2 + (1+$k)*$a),
    );
    imagefilledpolygon($image, $points, 4, $color);
    return imagepolygon($image, $points, 4, $color);
}
/**
 * Wave filter
 */
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

function GetRandomCaptchaText($length = null)
{
    if (empty($length)) {
        $length = rand($minWordLength, $maxWordLength);
    }

    $words  = "abcdefghkmnpqrstuvwxyz3456789";

    $wolen = strlen($words);


    $text  = "";
    $chars = array();

    for ($i=0; $i<$length; $i++) {

        $now = substr($words, mt_rand(0, $wolen-1), 1);
        while(isset($chars[$now]))
        {
            $now = substr($words, mt_rand(0, $wolen-1), 1);
        }
        $text .= $now;
        $chars[$now] = 1;

    }
    return $text;
}

function GetQQRandomCaptchaText($length = null)
{
    if (empty($length)) {
        $length = rand($minWordLength, $maxWordLength);
    }

    $words  = "abcdefghijkmnpqrstuvwxyz";

    $wolen = strlen($words);


    $text  = "";
    $chars = array();

    for ($i=0; $i<$length; $i++) {

        $now = substr($words, mt_rand(0, $wolen-1), 1);
        while(isset($chars[$now]))
        {
            $now = substr($words, mt_rand(0, $wolen-1), 1);
        }
        $text .= $now;
        $chars[$now] = 1;

    }
    return $text;
}


function getSpacing($rotate1, $rotate2)
{

    $spacing = rand(-3, -5);
    return $spacing;
}
function WriteEnhanceText($image , $text, $forecolor,$width, $height)
{

    /** Increase font-size for shortest words: 9% for each glyp missing */
    $maxRotation = 16;
    $maxWordLength = 8;
    $scale = 2;
    $lettersMissing = $maxWordLength-strlen($text);
    $fontSizefactor = 1+($lettersMissing*0.2);

    // Text generation (char by char)
    $x  = 5*$scale;
    $y  = round(($height*27/38)*$scale);
    $length = strlen($text);
    $rotates = array();
    $maxRotation = 1.5 * $maxRotation;
    for($i=0; $i<$length; $i++)
    {
        $degree   = rand($maxRotation*(-1), $maxRotation);
        $rotates[$i] = $degree;
    }
    $fontcfg  = Captcha_Api::$fonts[array_rand(Captcha_Api::$fonts)];
    $fontfile = Captcha_Api::$fontpath.'/font/'.$fontcfg['font'];

    for ($i=0; $i<$length; $i++)
    {


        $fontsize = rand($fontcfg['minSize'], $fontcfg['maxSize'])*$scale*$fontSizefactor;
        $fontsize = $fontsize*$width/140;
        $fontsize = (int)$fontsize;
        $letter   = substr($text, $i, 1);
        $degree = $rotates[$i];
        $spacing = 0;
        if($i> 0)
        {
            $spacing = getSpacing($rotates[$i-1], $rotates[$i]);
        }

        // Full path of font file
        $coords = imagettftext($image, $fontsize, $degree, $x, $y,$forecolor, $fontfile, $letter);

        $x += ($coords[2]-$x) + ($spacing*$scale) + $fontsize/3 - $length;

    }
}


function WriteQQText($image , $text, $forecolor,$width, $height)
{

    /** Increase font-size for shortest words: 9% for each glyp missing */
    $maxRotation = 20;
    $maxWordLength = 6;
    $scale = 2;
    $lettersMissing = $maxWordLength-strlen($text);
    $fontSizefactor = $width/150 +($lettersMissing*0.2);

    //$text = 'YaVDSS';
    // Text generation (char by char)
    $x  = 10*$scale;
    $y  = round(($height*27/38)*$scale);
    $length = strlen($text);
    $rotates = array();
    $maxRotation = 1.5 * $maxRotation;
    for($i=0; $i<$length; $i++)
    {
        $degree   = rand($maxRotation*(-1), $maxRotation);
        $rotates[$i] = $degree;
    }

    $fontcfg  = Captcha_Api::$fonts['arial'];
    $fontfile = Captcha_Api::$fontpath.'/font/'.$fontcfg['font'];
    $fontsize = rand($fontcfg['minSize'], $fontcfg['maxSize'])*$scale*$fontSizefactor;

    for ($i=0; $i<$length; $i++)
    {

        $letter   = substr($text, $i, 1);
        if (mt_rand(0,1))
        {
            $letter = strtoupper($letter);
            $fontsize = rand(16, 22)*$scale*$fontSizefactor;
        }
        else
        {
            $fontsize = rand(18, 26)*$scale*$fontSizefactor;
        }
        $degree = $rotates[$i];
        if($i> 0)
        {
            $spacing = getSpacing($rotates[$i-1], $rotates[$i]);
        }

        $y += mt_rand(0,3);

        // Full path of font file
        $coords = imagettftext($image, $fontsize, $degree,$x, $y,$forecolor, $fontfile, $letter);
        $t = mt_rand(1,3);
        if ($t)
        {
            imagettftext($image, $fontsize, $degree, ($x+$t), $y,$forecolor, $fontfile, $letter);
        }


        $x += ($coords[2]-$x) + mt_rand(7,12);

    }
}

function WriteEnhanceEqual($image , $exprArr, $width, $height)
{
    $cnums = array('零','一','二','三','四','五','六','七','八','九');
    /** Increase font-size for shortest words: 9% for each glyp missing */
    $maxRotation = 16;
    $maxWordLength = 8;
    $scale = 2;
    $lettersMissing = $maxWordLength-strlen($text);
    $fontSizefactor = 1+($lettersMissing*0.09);

    // Text generation (char by char)
    $x  = 15*$scale;
    $y  = round(($height*27/38)*$scale);
    $length = strlen($text);
    $rotates = array();
    $maxRotation = 1.5 * $maxRotation;
    foreach ($exprArr as $i => $letter)
    {
        if($text[$i] == '+' || $text[$i] == '-' || $text[$i] == '*')
        {
            $rotates[$i] = 0;
        }
        else
        {
            $degree   = rand($maxRotation*(-1), $maxRotation);
            $rotates[$i] = $degree;
        }
    }

    $bkcolor = Captcha_Api::$backcolors[mt_rand(0,sizeof(Captcha_Api::$backcolors)-1)];
    $backcolor = imagecolorallocate($image, $bkcolor[0],$bkcolor[1],$bkcolor[2]);

    imagefilledrectangle($image, 0, 0, $width*$scale, $height*$scale, $backcolor);

    $color = Captcha_Api::$colors[mt_rand(0, sizeof(Captcha_Api::$colors)-1)];
    $forecolor = imagecolorallocate($image, $color[0], $color[1], $color[2]);
    foreach ($exprArr as $i => $letter)
    {
        $fontcfg  = Captcha_Api::$fonts[array_rand(Captcha_Api::$fonts)];
        $fontfile = APP_PATH."/data/font/simsun.ttc";
        $fontsize = rand($fontcfg['minSize'], $fontcfg['maxSize'])*$scale*$fontSizefactor;
        $fontsize = $fontsize*$width/150;
        $fontsize = (int)$fontsize;
        $degree = $rotates[$i];
        $spacing = 0;
        if($i> 0)
        {
            $spacing = getSpacing($rotates[$i-1], $rotates[$i]);
        }


        if (in_array($letter, array("+", "-", "*")))
        {
            //$forecolor = imagecolorallocate($image, 0, 0, 0);
            $degree = 0;
        }
        if($letter == '+')
        {
            if(mt_rand(0,1) == 0)
            {
                $letter = '加';
                $fontsize = $fontsize / 2;
            }
        }
        else if($letter == '-')
        {
            if(mt_rand(0,1) == 0)
            {
                $letter = '减';
                $fontsize = $fontsize / 2;
            }
        }
        else if($letter == '*')
        {
            if(mt_rand(0,1) == 0)
            {
                $letter = '乘';
            }
            else
            {
                $letter = '乘'; //X都用汉字了
                //$letter = '×';
            }
            $fontsize = $fontsize / 2;
        }
        else if(is_numeric($letter) and $letter < 10)
        {
            $rand = mt_rand(0,1);
            if ($rand)
            {
                $letter = $cnums[$letter];
                $fontsize = $fontsize / 1.5;
            }
        }
        else if (strlen($letter) == 2)
        {
            $fontsize = $fontsize / 2;
        }

        // Full path of font file
        //$letter = iconv(DB_CHARSET, SYS_CHARSET, $letter);
        $coords = imagettftext($image, $fontsize, $degree,$x, $y,$forecolor, $fontfile, $letter);
        $x += ($coords[2]-$x) + ($spacing*$scale);
    }

    $forecolor = imagecolorallocate($image, 0, 0, 0);
    $degree = 0;
    $letter = "=?";
    $coords = imagettftext($image, $fontsize, $degree,$x, $y,$forecolor, $fontfile, $letter);
    $x += ($coords[2]-$x) + ($spacing*$scale);
}
?>
