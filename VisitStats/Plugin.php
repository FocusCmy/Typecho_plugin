<?php
/**
 * VisitStats 插件主体（支持 ip2region 地域解析）
 * @package VisitStats
 * @author lrsigs
 * @version 1.0
 * @link https://github.com/FocusCmy
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

require_once __DIR__ . '/Action.php';

class VisitStats_Plugin implements Typecho_Plugin_Interface
{
    public static function activate()
    {
        // 页面底部挂统计
        Typecho_Plugin::factory('Widget_Archive')->footer = [__CLASS__, 'track'];

        // 注册路由
        Helper::addRoute('visit_stats_dashboard', '/visit-stats', 'VisitStats_Action', 'dashboard');

        // 创建数据表
        self::createTables();

        return _t('VisitStats 插件已启用，访问 <a href="/index.php/visit-stats" target="_blank">/index.php/visit-stats</a> 查看统计。');
    }

    public static function deactivate()
    {
        Helper::removeRoute('visit_stats_dashboard');
    }

    /**
     * 插件配置界面
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        // SPA 支持
        $spa = new Typecho_Widget_Helper_Form_Element_Radio(
            'spa',
            ['0' => '关闭', '1' => '开启'],
            '0',
            _t('单页应用(SPA)支持'),
            _t('开启后可在前端路由切换时统计访问')
        );
        $form->addInput($spa);

        // 爬虫 UA 过滤
        $bots = new Typecho_Widget_Helper_Form_Element_Textarea(
            'exclude_bots',
            null,
            "bot\nspider\ncrawler\nslurp\ncurl\nwget\npython\nhttpclient",
            _t('排除爬虫 UA'),
            _t('每行一个关键字，命中则不计入统计')
        );
        $form->addInput($bots);

        // 是否记录设备
        $device = new Typecho_Widget_Helper_Form_Element_Radio(
            'record_device',
            ['0' => '否', '1' => '是'],
            '1',
            _t('记录设备类型'),
            _t('根据 UA 判断 PC 或 Phone')
        );
        $form->addInput($device);

        // 是否记录地域
        $geo = new Typecho_Widget_Helper_Form_Element_Radio(
            'record_geo',
            ['0' => '否', '1' => '是'],
            '0',
            _t('记录访客地域'),
            _t('需要配合 ip2region.xdb 实现地理位置解析')
        );
        $form->addInput($geo);

        // 仪表盘跳转
        $url = Helper::options()->siteUrl . 'visit-stats';
        $goto = new Typecho_Widget_Helper_Form_Element_Text(
            'dashboard_link',
            null,
            $url,
            _t('统计仪表盘地址'),
            _t('点击访问：<a href="'.$url.'" target="_blank">'.$url.'</a>')
        );
        $form->addInput($goto);
    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form) {}

    /**
     * 统计逻辑（PV/UV + UA + IP + 地域）
     */
    public static function track()
    {
        $opts = Helper::options()->plugin('VisitStats');
        $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');

        // 过滤爬虫 UA
        if ($ua && !empty($opts->exclude_bots)) {
            $lines = preg_split('/\r\n|\r|\n/', strtolower($opts->exclude_bots));
            foreach ($lines as $needle) {
                $needle = trim($needle);
                if ($needle && strpos($ua, $needle) !== false) {
                    return; // 爬虫直接跳过
                }
            }
        }

        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();
        $today = intval(date('Ymd'));

        // UV 基于 cookie 去重
        $uvKey = 'vs_uv_' . $today;
        $isUV = false;
        if (empty($_COOKIE[$uvKey])) {
            setcookie($uvKey, '1', strtotime(date('Y-m-d 23:59:59')), '/');
            $_COOKIE[$uvKey] = '1';
            $isUV = true;
        }

        // 设备类型
        $device = 'unknown';
        if (!empty($opts->record_device) && $opts->record_device === '1') {
            if (preg_match('/mobile|android|iphone|ipad|phone/i', $ua)) {
                $device = 'phone';
            } else {
                $device = 'pc';
            }
        }

        // IP + 地域
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $geo = '';
        if (!empty($opts->record_geo) && $opts->record_geo === '1' && $ip) {
            $xdbFile = __DIR__ . '/assets/ip2region.xdb';
            $searcherFile = __DIR__ . '/assets/XdbSearcher.php';
            if (file_exists($xdbFile) && file_exists($searcherFile)) {
                require_once $searcherFile;
                try {
                    static $searcher = null;
                    if ($searcher === null) {
                        $searcher = XdbSearcher::newWithFileOnly($xdbFile);
                    }
                    $region = $searcher->search($ip);
                    // 返回格式 "中国|0|上海|上海市|电信"
                    $geo = str_replace('|0', '', $region);
                } catch (Exception $e) {
                    $geo = $ip; // 回退存 IP
                }
            } else {
                $geo = $ip;
            }
        }

        // 更新总表
        $row = $db->fetchRow($db->select()->from($prefix.'visit_stats')->where('ymd = ?', $today));
        if ($row) {
            $pv = intval($row['pv']) + 1;
            $uv = intval($row['uv']) + ($isUV ? 1 : 0);
            $db->query($db->update($prefix.'visit_stats')->rows(['pv'=>$pv,'uv'=>$uv])->where('ymd=?',$today));
        } else {
            $db->query($db->insert($prefix.'visit_stats')->rows(['ymd'=>$today,'pv'=>1,'uv'=>$isUV?1:0]));
        }

        // 写入日志表
        $db->query($db->insert($prefix.'visit_stats_log')->rows([
            'ymd'     => $today,
            'ua'      => $ua,
            'device'  => $device,
            'ip'      => $ip,
            'geo'     => $geo,
            'created' => time()
        ]));
    }

    /**
     * 建表
     */
    private static function createTables()
    {
        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();

        $sql1 = <<<SQL
CREATE TABLE IF NOT EXISTS `{$prefix}visit_stats` (
  `ymd` INT NOT NULL,
  `pv` INT NOT NULL DEFAULT 0,
  `uv` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`ymd`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
SQL;

        $sql2 = <<<SQL
CREATE TABLE IF NOT EXISTS `{$prefix}visit_stats_log` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ymd` INT NOT NULL,
  `ua` TEXT,
  `device` VARCHAR(20),
  `ip` VARCHAR(64),
  `geo` VARCHAR(255),
  `created` INT
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
SQL;

        $db->query($sql1, Typecho_Db::WRITE);
        $db->query($sql2, Typecho_Db::WRITE);
    }
}
