<?php
/**
 * VisitStats 仪表盘（大屏可视化版 + 筛选功能）
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class VisitStats_Action extends Typecho_Widget implements Widget_Interface_Do
{
    public function action() {}

    public function dashboard()
    {
        /* 🔒 权限校验：仅管理员可访问 */
        $user = Typecho_Widget::widget('Widget_User');
        if (!$user->hasLogin() || !$user->pass('administrator')) {
            $loginUrl = Helper::options()->adminUrl . 'login.php';
            header("Location: " . $loginUrl);
            exit;
        }

        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();

        /* ======== 筛选条件 ======== */
        $start = isset($_GET['start']) ? intval($_GET['start']) : intval(date('Ymd', time() - 6*86400));
        $end   = isset($_GET['end'])   ? intval($_GET['end'])   : intval(date('Ymd'));
        $deviceFilter = isset($_GET['device']) ? trim($_GET['device']) : '';
        $geoFilter    = isset($_GET['geo'])    ? trim($_GET['geo'])    : '';

        /* ======== PV/UV 数据 ======== */
        $select = $db->select()->from($prefix.'visit_stats')
            ->where('ymd >= ?', $start)
            ->where('ymd <= ?', $end)
            ->order('ymd', Typecho_Db::SORT_ASC);
        $rows = $db->fetchAll($select);

        $labels = [];
        $pvData = [];
        $uvData = [];
        foreach ($rows as $r) {
            $labels[] = date('m-d', strtotime($r['ymd']));
            $pvData[] = intval($r['pv']);
            $uvData[] = intval($r['uv']);
        }

        /* ======== 设备分布 ======== */
        $select = $db->select('device', 'COUNT(*) AS cnt')
            ->from($prefix.'visit_stats_log')
            ->where('ymd >= ?', $start)
            ->where('ymd <= ?', $end);
        if ($deviceFilter) {
            $select->where('device = ?', $deviceFilter);
        }
        if ($geoFilter) {
            $select->where('geo LIKE ?', '%'.$geoFilter.'%');
        }
        $select->group('device');
        $deviceRows = $db->fetchAll($select);
        $deviceData = [];
        foreach ($deviceRows as $dr) {
            $deviceData[] = ['name' => $dr['device'], 'value' => intval($dr['cnt'])];
        }

        /* ======== 地域分布 ======== */
        $select = $db->select('geo', 'COUNT(*) AS cnt')
            ->from($prefix.'visit_stats_log')
            ->where('ymd >= ?', $start)
            ->where('ymd <= ?', $end);
        if ($deviceFilter) {
            $select->where('device = ?', $deviceFilter);
        }
        if ($geoFilter) {
            $select->where('geo LIKE ?', '%'.$geoFilter.'%');
        }
        $select->group('geo')->order('cnt', Typecho_Db::SORT_DESC)->limit(10);
        $geoRows = $db->fetchAll($select);
        $geoLabels = [];
        $geoValues = [];
        foreach ($geoRows as $gr) {
            $geoLabels[] = $gr['geo'] ?: '未知';
            $geoValues[] = intval($gr['cnt']);
        }

        /* ======== 最近日志 ======== */
        $select = $db->select()->from($prefix.'visit_stats_log')
            ->where('ymd >= ?', $start)
            ->where('ymd <= ?', $end)
            ->order('id', Typecho_Db::SORT_DESC)
            ->limit(20);
        if ($deviceFilter) {
            $select->where('device = ?', $deviceFilter);
        }
        if ($geoFilter) {
            $select->where('geo LIKE ?', '%'.$geoFilter.'%');
        }
        $logs = $db->fetchAll($select);

        /* 数据转 JSON */
        $assetsUrl = Helper::options()->pluginUrl . '/VisitStats/assets';
        $labelsJson = json_encode($labels, JSON_UNESCAPED_UNICODE);
        $pvJson = json_encode($pvData);
        $uvJson = json_encode($uvData);
        $deviceJson = json_encode($deviceData, JSON_UNESCAPED_UNICODE);
        $geoLabelsJson = json_encode($geoLabels, JSON_UNESCAPED_UNICODE);
        $geoValuesJson = json_encode($geoValues);

        /* 页面输出 */
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>VisitStats 仪表盘</title>';
        echo '<style>
            body{margin:0;padding:0;background:#1b1b1b;color:#eee;font-family:"Segoe UI",sans-serif;}
            h1{color:#fff;margin:10px 0;}
            .topbar{padding:10px;background:#111;display:flex;justify-content:space-between;align-items:center;}
            .btn-back{color:#fff;background:#467b96;padding:6px 12px;border-radius:4px;text-decoration:none;}
            .filter{color:#ddd;}
            .filter input,.filter select{padding:4px;margin:0 5px;background:#333;color:#fff;border:1px solid #555;}
            .container{display:grid;grid-template-columns:1fr 1fr;grid-gap:20px;padding:20px;}
            .chart{background:#222;padding:10px;border-radius:6px;}
            table{border-collapse:collapse;width:100%;margin-top:10px;color:#eee;}
            th,td{border:1px solid #444;padding:6px;text-align:center;}
            th{background:#333;}
        </style>';
        echo '</head><body>';

        /* 顶部工具条 */
        $adminUrl = Helper::options()->adminUrl;
        echo '<div class="topbar">';
        echo '<a href="'.$adminUrl.'" class="btn-back">← 返回后台首页</a>';
        echo '<form class="filter" method="get" action="">';
        echo '开始:<input type="text" name="start" value="'.$start.'" placeholder="YYYYMMDD">';
        echo '结束:<input type="text" name="end" value="'.$end.'" placeholder="YYYYMMDD">';
        echo '设备:<select name="device">
                <option value="">全部</option>
                <option value="pc" '.($deviceFilter=='pc'?'selected':'').'>PC</option>
                <option value="phone" '.($deviceFilter=='phone'?'selected':'').'>Phone</option>
              </select>';
        echo '地域:<input type="text" name="geo" value="'.$geoFilter.'" placeholder="关键词">';
        echo '<input type="submit" value="应用筛选">';
        echo '</form>';
        echo '</div>';

        /* 大屏图表区域 */
        echo '<div class="container">';
        echo '<div id="chart" class="chart" style="height:400px;"></div>';
        echo '<div id="deviceChart" class="chart" style="height:400px;"></div>';
        echo '<div id="geoChart" class="chart" style="grid-column:1/3;height:400px;"></div>';
        echo '</div>';

        /* 最近日志 */
        echo '<h1 style="padding:20px;">最近 20 条访客日志</h1>';
        echo '<table><tr><th>ID</th><th>日期</th><th>设备</th><th>IP</th><th>地区</th><th>时间</th></tr>';
        foreach ($logs as $log) {
            echo '<tr>';
            echo '<td>'.$log['id'].'</td>';
            echo '<td>'.$log['ymd'].'</td>';
            echo '<td>'.$log['device'].'</td>';
            echo '<td>'.$log['ip'].'</td>';
            echo '<td>'.htmlspecialchars($log['geo']).'</td>';
            echo '<td>'.date('Y-m-d H:i:s', $log['created']).'</td>';
            echo '</tr>';
        }
        echo '</table>';

        /* ECharts 脚本 */
        echo '<script src="'.$assetsUrl.'/echarts.min.js"></script>';
        echo "<script>
        var chart = echarts.init(document.getElementById('chart'),'dark');
        chart.setOption({
            title:{text:'PV/UV 趋势',textStyle:{color:'#fff'}},
            tooltip:{trigger:'axis'},
            legend:{data:['PV','UV'],textStyle:{color:'#fff'}},
            xAxis:{type:'category',data:$labelsJson},
            yAxis:{type:'value'},
            series:[
                {name:'PV',type:'line',smooth:true,data:$pvJson},
                {name:'UV',type:'line',smooth:true,data:$uvJson}
            ]
        });

        var deviceChart = echarts.init(document.getElementById('deviceChart'),'dark');
        deviceChart.setOption({
            title:{text:'设备分布',textStyle:{color:'#fff'}},
            tooltip:{trigger:'item'},
            legend:{top:'5%',textStyle:{color:'#fff'}},
            series:[{
                name:'设备类型',
                type:'pie',
                radius:['40%','70%'],
                data:$deviceJson
            }]
        });

        var geoChart = echarts.init(document.getElementById('geoChart'),'dark');
        geoChart.setOption({
            title:{text:'地域分布 TOP10',textStyle:{color:'#fff'}},
            tooltip:{trigger:'axis'},
            xAxis:{type:'category',data:$geoLabelsJson,axisLabel:{color:'#fff'}},
            yAxis:{type:'value',axisLabel:{color:'#fff'}},
            series:[{type:'bar',data:$geoValuesJson}]
        });
        </script>";

        echo '</body></html>';
        exit;
    }
}
