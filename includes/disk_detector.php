<?php
/**
 * 网盘链接检测模块
 * 
 * 用于检测URL是否为网盘链接，并识别具体的网盘类型
 * 
 * @copyright Copyright (c) 2023 GooShare (https://gooshare.xyz/)
 * @author GooShare <contact@gooshare.xyz>
 * @version 1.0.0
 */

/**
 * 检测URL是否为网盘链接
 * 
 * @param string $url 要检测的URL
 * @return array 包含是否为网盘链接及网盘类型的信息
 */
function detectDiskLink($url) {
    // 初始化返回数组
    $result = [
        'is_disk_link' => false,
        'disk_type' => '',
        'disk_name' => ''
    ];
    
    // 解析URL以获取域名部分
    $parsedUrl = parse_url($url);
    if (!isset($parsedUrl['host'])) {
        return $result;
    }
    
    $host = strtolower($parsedUrl['host']);
    $path = isset($parsedUrl['path']) ? strtolower($parsedUrl['path']) : '';
    
    // 百度网盘检测
    if (strpos($host, 'pan.baidu.com') !== false || 
        strpos($host, 'yun.baidu.com') !== false) {
        $result['is_disk_link'] = true;
        $result['disk_type'] = 'baidu';
        $result['disk_name'] = '百度网盘';
        return $result;
    }
    
    // 阿里云盘检测
    if (strpos($host, 'aliyundrive.com') !== false ||
        strpos($host, 'alipan.com') !== false) {
        $result['is_disk_link'] = true;
        $result['disk_type'] = 'aliyun';
        $result['disk_name'] = '阿里云盘';
        return $result;
    }
    
    // 腾讯微云检测
    if (strpos($host, 'weiyun.com') !== false) {
        $result['is_disk_link'] = true;
        $result['disk_type'] = 'weiyun';
        $result['disk_name'] = '腾讯微云';
        return $result;
    }
    
    // 天翼云盘检测
    if (strpos($host, 'cloud.189.cn') !== false) {
        $result['is_disk_link'] = true;
        $result['disk_type'] = 'tianyiyun';
        $result['disk_name'] = '天翼云盘';
        return $result;
    }
    
    // 和彩云检测
    if (strpos($host, 'caiyun.139.com') !== false) {
        $result['is_disk_link'] = true;
        $result['disk_type'] = 'caiyun';
        $result['disk_name'] = '和彩云';
        return $result;
    }
    
    // 115网盘检测
    if (strpos($host, '115.com') !== false && 
        (strpos($path, '/file/') !== false || strpos($path, '/share/') !== false)) {
        $result['is_disk_link'] = true;
        $result['disk_type'] = '115';
        $result['disk_name'] = '115网盘';
        return $result;
    }
    
    // 蓝奏云检测
    if (strpos($host, 'lanzou') !== false || 
        strpos($host, 'lanzoui.com') !== false || 
        strpos($host, 'lanzoux.com') !== false) {
        $result['is_disk_link'] = true;
        $result['disk_type'] = 'lanzou';
        $result['disk_name'] = '蓝奏云';
        return $result;
    }
    
    // 夸克网盘检测
    if (strpos($host, 'pan.quark.cn') !== false) {
        $result['is_disk_link'] = true;
        $result['disk_type'] = 'quark';
        $result['disk_name'] = '夸克网盘';
        return $result;
    }
    
    // 123云盘检测
    if (strpos($host, '123pan.com') !== false) {
        $result['is_disk_link'] = true;
        $result['disk_type'] = '123pan';
        $result['disk_name'] = '123云盘';
        return $result;
    }
    
    // 迅雷云盘检测
    if (strpos($host, 'pan.xunlei.com') !== false) {
        $result['is_disk_link'] = true;
        $result['disk_type'] = 'xunlei';
        $result['disk_name'] = '迅雷云盘';
        return $result;
    }
    
    // 坚果云检测
    if (strpos($host, 'jianguoyun.com') !== false || 
        strpos($host, 'nutstore.net') !== false) {
        $result['is_disk_link'] = true;
        $result['disk_type'] = 'jianguo';
        $result['disk_name'] = '坚果云';
        return $result;
    }
    
    // OneDrive检测
    if (strpos($host, 'onedrive.live.com') !== false || 
        strpos($host, '1drv.ms') !== false) {
        $result['is_disk_link'] = true;
        $result['disk_type'] = 'onedrive';
        $result['disk_name'] = 'OneDrive';
        return $result;
    }
    
    // Google Drive检测
    if (strpos($host, 'drive.google.com') !== false || 
        strpos($host, 'docs.google.com') !== false) {
        $result['is_disk_link'] = true;
        $result['disk_type'] = 'googledrive';
        $result['disk_name'] = 'Google Drive';
        return $result;
    }
    
    // Dropbox检测
    if (strpos($host, 'dropbox.com') !== false) {
        $result['is_disk_link'] = true;
        $result['disk_type'] = 'dropbox';
        $result['disk_name'] = 'Dropbox';
        return $result;
    }
    
    // 未检测到已知网盘
    return $result;
}