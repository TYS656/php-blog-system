<?php
// 强制清空所有之前的输出，解决 header 发送失败的核心问题
ob_clean();

session_start();

// 声明输出为 PNG 图片，必须在任何实际输出之前执行
header("Content-Type: image/png");

// 创建画布
$width = 120;
$height = 40;
$image = imagecreatetruecolor($width, $height);

// 分配颜色
$bgColor   = imagecolorallocate($image, 245, 247, 250);  // 浅灰背景
$textColor = imagecolorallocate($image, 37, 99, 235);    // 蓝色文字
$lineColor = imagecolorallocate($image, 200, 200, 200);  // 灰色干扰线

// 填充背景
imagefill($image, 0, 0, $bgColor);

// 生成4位验证码（去除易混淆字符 0/O、1/I）
$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$code = '';
for ($i = 0; $i < 4; $i++) {
    $code .= $chars[mt_rand(0, strlen($chars) - 1)];
}
// 存入 session，统一转小写
$_SESSION['captcha_code'] = strtolower($code);

// 写入验证码文字（PHP内置字体，无需额外字体文件）
imagestring($image, 5, 32, 12, $code, $textColor);

// 添加3条干扰线
for ($i = 0; $i < 3; $i++) {
    imageline($image, 0, mt_rand(0, $height), $width, mt_rand(0, $height), $lineColor);
}

// 输出图片并释放内存
imagepng($image);
imagedestroy($image);
// 直接终止，后面不允许有任何内容（包括空格、换行）
exit;