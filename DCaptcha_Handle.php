<?php

/**
 * 用户操作触发验证码的检查类.
 *
 * @package captcha
 * @author zhenhao
 */
class DCaptcha_Handle
{
    /**
     * 存储自身的单例对象.
     *
     * @var DCaptcha_Handle
     */
    private static $_instance;

    /**
     * 记录检查结果，防止一个进程中多次调用check导致验证码不通过的情况.
     *
     * @var boolean
     */
    private static $_result = false;

    //成员变量
    private $_style;            // 验证码页面的类型 "form" "ajax" "dialog"
    private $_keytype;          // 验证码的类型 "login" "reg" ...
    private $_rcode;            // 验证码在cache中的key值
    private $_code;             // 用户输入的值

    /**
     * 私有构造函数.
     * @param string $style
     * @param string $keytype
     * @param string $rcode
     * @param string $code
     */
    private function __construct($style='form', $keytype='login', $rcode=null, $code=null)
    {
        $this->_style = $style;
        $this->_keytype = $keytype;
        $this->_rcode = $rcode;
        $this->_code = $code;
    }

    /**
     * 初始化自身单例对象.
     * @param string $style         // 验证码页面的类型 "form" "ajax" "dialog"
     * @param string $keytype       // 验证码的类型 "login" "reg" ...
     * @param string $rcode         // 验证码在cache中的key值
     * @param string $code          // 用户输入的值
     */
    public static function init($style='form', $keytype='login', $rcode=null, $code=null)
    {
        if (DCaptcha_WhiteList::inWhiteList())
        {
            return false;
        }
        
        if(!self::$_instance)
        {
            self::$_instance = new DCaptcha_Handle($style, $keytype, $rcode, $code);
        }
    }

    /**
     * 检查验证码是否已经输入及是否正确.
     * 如果没有调用过DCaptcha_Handle::init(),则此函数不会做任何事情
     * 本函数如果被多次调用，而且第一次调用验证码正确的情况下，其后的调用则不再做验证
     *
     * @throws DCaptcha_Empty_Exception
     * @throws DCaptcha_Wrong_Exception
     */
    public static function check()
    {
        if(self::$_instance)
        {
            if(self::$_instance->_rcode && self::$_instance->_code)
            {
                if(!self::$_result)
                {
                    $res = DCaptcha_Api::checkMemcacheCode(self::$_instance->_rcode, self::$_instance->_code, self::$_instance->_keytype);
                    self::$_result = $res;
                }
                else
                { // 如果一个进程中已经检查通过了，则不再进行检查
                    $res = true;
                }
            }
            if(!$res)
            {
                $data = array(
                    'style'   => self::$_instance->_style,
                    'keytype' => self::$_instance->_keytype,
                );
                if(self::$_instance->_code)
                {   // 验证码错误
                    throw new DCaptcha_Wrong_Exception($data);
                }
                else
                {   // 验证码为空
                    throw new DCaptcha_Empty_Exception($data);
                }
            }
        }
    }

}