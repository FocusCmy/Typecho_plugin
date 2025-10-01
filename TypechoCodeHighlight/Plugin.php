<?php
/**
 * TypechoCodeHighlight：本地 highlight.js + 自定义样式/脚本
 * @package TypechoCodeHighlight
 * @author lrsigs 
 * @version 1.0.0
 * @link https://dovic.cn
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class TypechoCodeHighlight_Plugin implements Typecho_Plugin_Interface
{
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Archive')->header = [__CLASS__, 'header'];
        Typecho_Plugin::factory('Widget_Archive')->footer = [__CLASS__, 'footer'];
        return _t('TypechoCodeHighlight 已启用');
    }

    public static function deactivate() {}

    public static function config(Typecho_Widget_Helper_Form $form)
    {
        // 读取本地主题列表
        $styleFiles = glob(dirname(__FILE__) . '/assets/styles/*.min.css') ?: [];
        $names = array_map('basename', $styleFiles);
        $names = array_map(fn($v) => substr($v, 0, strpos($v, '.min.css')), $names);
        $map   = array_combine($names, $names) ?: ['atom-one-light' => 'atom-one-light'];

        $style = new Typecho_Widget_Helper_Form_Element_Select(
            'theme', $map, 'atom-one-light', _t('高亮主题（来自 assets/styles）')
        );
        $form->addInput($style);

        $showln = new Typecho_Widget_Helper_Form_Element_Checkbox(
            'showln', ['showln' => _t('显示行号')], [], _t('需要本地 highlightjs-line-numbers.min.js 与样式')
        );
        $form->addInput($showln);

        $macbar = new Typecho_Widget_Helper_Form_Element_Checkbox(
            'macbar', ['macbar' => _t('启用 macOS 外框')], ['macbar'], _t('为代码块包裹红黄绿按钮标题栏')
        );
        $form->addInput($macbar);
    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form) {}

    /** 在 <head> 注入 CSS/JS（本地） */
    public static function header()
    {
        $opt = Helper::options()->plugin('TypechoCodeHighlight');
        $base = Helper::options()->pluginUrl . '/TypechoCodeHighlight/assets';
        $theme = htmlspecialchars($opt->theme ?: 'atom-one-light', ENT_QUOTES);

        // 主题 CSS
        echo '<link rel="stylesheet" href="' . $base . '/styles/' . $theme . '.min.css">' . "\n";
        // 自定义 CSS（macOS 外框/修饰）
        echo '<link rel="stylesheet" href="' . $base . '/codehighlight.css">' . "\n";

        // highlight.js
        echo '<script src="' . $base . '/highlight.min.js"></script>' . "\n";

        // 行号插件（可选）
        $showln = (bool)$opt->showln;
        if ($showln) {
            echo '<script src="' . $base . '/highlightjs-line-numbers.min.js"></script>' . "\n";
        }
    }

    /** 在 </body> 注入初始化脚本（你的代码） */
    public static function footer()
    {
        $opt   = Helper::options()->plugin('TypechoCodeHighlight');
        $base  = Helper::options()->pluginUrl . '/TypechoCodeHighlight/assets';
        $showln = (bool)$opt->showln ? 'true' : 'false';
        $macbar = (bool)$opt->macbar ? 'true' : 'false';

        // 将开关透传给前端 init
        echo '<script>window.__TCHL__ = { showLineNumber: ' . $showln . ', macBar: ' . $macbar . ' };</script>' . "\n";
        echo '<script src="' . $base . '/codehighlight.js"></script>' . "\n";
    }
}
