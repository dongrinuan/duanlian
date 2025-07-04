<?php
/**
 * SEO优化组件
 * 
 * 提供网站SEO优化的功能
 */

class SEO {
    private $title;
    private $description;
    private $keywords;
    private $canonicalURL;
    private $ogTags = [];
    private $twitterTags = [];
    
    /**
     * 设置页面标题
     * 
     * @param string $title 页面标题
     * @return SEO 返回实例便于链式调用
     */
    public function setTitle($title) {
        $this->title = $title;
        return $this;
    }
    
    /**
     * 设置页面描述
     * 
     * @param string $description 页面描述
     * @return SEO 返回实例便于链式调用
     */
    public function setDescription($description) {
        $this->description = $description;
        return $this;
    }
    
    /**
     * 设置页面关键词
     * 
     * @param array|string $keywords 关键词数组或逗号分隔的字符串
     * @return SEO 返回实例便于链式调用
     */
    public function setKeywords($keywords) {
        if (is_array($keywords)) {
            $keywords = implode(', ', $keywords);
        }
        $this->keywords = $keywords;
        return $this;
    }
    
    /**
     * 设置规范URL
     * 
     * @param string $url 规范URL
     * @return SEO 返回实例便于链式调用
     */
    public function setCanonical($url) {
        $this->canonicalURL = $url;
        return $this;
    }
    
    /**
     * 添加OpenGraph标签
     * 
     * @param string $property 属性名
     * @param string $content 内容
     * @return SEO 返回实例便于链式调用
     */
    public function addOgTag($property, $content) {
        $this->ogTags[$property] = $content;
        return $this;
    }
    
    /**
     * 添加Twitter卡片标签
     * 
     * @param string $name 属性名
     * @param string $content 内容
     * @return SEO 返回实例便于链式调用
     */
    public function addTwitterTag($name, $content) {
        $this->twitterTags[$name] = $content;
        return $this;
    }
    
    /**
     * 生成SEO元标签
     * 
     * @return string HTML标签
     */
    public function generateTags() {
        $tags = [];
        
        // 基础元标签
        if (!empty($this->description)) {
            $tags[] = sprintf('<meta name="description" content="%s">', htmlspecialchars($this->description));
        }
        
        if (!empty($this->keywords)) {
            $tags[] = sprintf('<meta name="keywords" content="%s">', htmlspecialchars($this->keywords));
        }
        
        if (!empty($this->canonicalURL)) {
            $tags[] = sprintf('<link rel="canonical" href="%s">', htmlspecialchars($this->canonicalURL));
        }
        
        // OpenGraph标签
        if (!empty($this->title) && !isset($this->ogTags['og:title'])) {
            $this->addOgTag('og:title', $this->title);
        }
        
        if (!empty($this->description) && !isset($this->ogTags['og:description'])) {
            $this->addOgTag('og:description', $this->description);
        }
        
        foreach ($this->ogTags as $property => $content) {
            $tags[] = sprintf('<meta property="%s" content="%s">', htmlspecialchars($property), htmlspecialchars($content));
        }
        
        // Twitter卡片标签
        foreach ($this->twitterTags as $name => $content) {
            $tags[] = sprintf('<meta name="%s" content="%s">', htmlspecialchars($name), htmlspecialchars($content));
        }
        
        return implode("\n    ", $tags);
    }
}
