# DCaptcha

Usage：


$key: random code of this action
$code: the code in image
$keytype: your page, like "reg", "login","order"

Captcha_Api::setCaptchaCode($key, $code, $keytype)


Captcha_Api::getCaptchaImg("login")

Captcha_Api::checkCaptchaCode($key, $code, $keytype = "login")

//you can limit IP or cookie
$keyType: your page, like "reg", "login","order"
$codeType: the style of captcha image
$IP: your clients' IP
Captcha_Api::($keyType, $codeType, $ip )
