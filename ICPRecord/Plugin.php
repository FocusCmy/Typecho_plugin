<?php
/*
 * @Author: lrsigs
 * @Date: 2025-10-01 12:07:07
 * @LastEditTime: 2025-10-01 12:07:26
 * @ProjectName: MyProjectA
 * @License: MIT
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class ICPRecord_Plugin implements Typecho_Plugin_Interface
{
    // 插件激活方法
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Archive')->footer = array('ICPRecord_Plugin', 'addICP');
    }

    // 插件禁用方法
    public static function deactivate()
    {
        // 可以在这里清理插件相关的资源
    }

    // 插件配置方法
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $ICP = new Typecho_Widget_Helper_Form_Element_Text('ICP', null, '', _t('ICP备案号'), _t('请输入您的ICP备案号'));
        $form->addInput($ICP);

        // 配置上传图标
        $icon = new Typecho_Widget_Helper_Form_Element_Text('ICPIcon', null, '', _t('备案图标URL'), _t('请输入备案图标的URL地址（可选）'));
        $form->addInput($icon);
    }

    // 插件默认设置方法
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
        // 个人设置不需要实现
    }

    // 在页面底部输出 ICP 备案信息和图标
    public static function addICP()
    {
        $options = Typecho_Widget::widget('Widget_Options');
        $ICP = $options->plugin('ICPRecord')->ICP;
        $ICPIcon = $options->plugin('ICPRecord')->ICPIcon;

        if (!empty($ICP)) {
            echo '<div style="text-align:center; padding:10px; background-color:#f5f5f5;">';
            if (!empty($ICPIcon)) {
                echo '<img src="' . htmlspecialchars($ICPIcon) . '" alt="ICP备案图标" style="vertical-align:middle; margin-right:5px;" />';
            }
            echo '备案号：' . htmlspecialchars($ICP) . '</div>';
        }
    }
}
?>
